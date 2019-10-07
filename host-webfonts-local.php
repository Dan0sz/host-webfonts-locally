<?php
/**
 * @formatter:off
 * Plugin Name: OMGF
 * Plugin URI: https://daan.dev/wordpress-plugins/host-google-fonts-locally
 * Description: Minimize DNS requests and leverage browser cache by easily saving Google Fonts to your server and removing the external Google Fonts.
 * Version: 1.9.10
 * Author: Daan van den Bergh
 * Author URI: https://daan.dev
 * License: GPL2v2 or later
 * Text Domain: host-webfonts-local
 * @formatter:on
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

/**
 * Define constants.
 */
define('CAOS_WEBFONTS_DB_VERSION', '1.8.3');
define('CAOS_WEBFONTS_STATIC_VERSION', '1.7.6');
define('CAOS_WEBFONTS_SITE_URL', 'https://daan.dev');
define('CAOS_WEBFONTS_DB_TABLENAME', $wpdb->prefix . 'caos_webfonts');
define('CAOS_WEBFONTS_DB_CHARSET', $wpdb->get_charset_collate());
define('CAOS_WEBFONTS_HELPER_URL', 'https://google-webfonts-helper.herokuapp.com/api/fonts/');
define('CAOS_WEBFONTS_FILENAME', 'fonts.css');
define('CAOS_WEBFONTS_CACHE_DIR', esc_attr(get_option('caos_webfonts_cache_dir')) ?: '/cache/omgf-webfonts');
define('CAOS_WEBFONTS_CDN_URL', esc_attr(get_option('caos_webfonts_cdn_url')));
define('CAOS_WEBFONTS_REMOVE_VERSION', esc_attr(get_option('caos_webfonts_remove_version')));
define('CAOS_WEBFONTS_CURRENT_BLOG_ID', get_current_blog_id());
define('CAOS_WEBFONTS_UPLOAD_DIR', WP_CONTENT_DIR . CAOS_WEBFONTS_CACHE_DIR);
define('CAOS_WEBFONTS_UPLOAD_URL', hwlGetUploadUrl());
define('CAOS_WEBFONTS_DISPLAY_OPTION', esc_attr(get_option('caos_webfonts_display_option')) ?: 'auto');
define('CAOS_WEBFONTS_REMOVE_GFONTS', esc_attr(get_option('caos_webfonts_remove_gfonts')));
define('CAOS_WEBFONTS_PRELOAD', esc_attr(get_option('caos_webfonts_preload')));

/**
 * Register settings
 */
function hwlRegisterSettings()
{
    register_setting('caos-webfonts-basic-settings',
        'caos_webfonts_cache_dir'
    );
    register_setting('caos-webfonts-basic-settings',
        'caos_webfonts_cdn_url'
    );
    register_setting('caos-webfonts-basic-settings',
        'caos_webfonts_remove_version'
    );
    register_setting('caos-webfonts-basic-settings',
        'caos_webfonts_display_option'
    );
    register_setting('caos-webfonts-basic-settings',
        'caos_webfonts_remove_gfonts'
    );
    register_setting('caos-webfonts-basic-settings',
        'caos_webfonts_preload'
    );
}

/**
 * Create the Admin menu-item
 */
function hwlCreateMenu()
{
    add_options_page(
        'OMGF',
        'Optimize Webfonts',
        'manage_options',
        'optimize-webfonts',
        'hwlSettingsPage'
    );
    add_action('admin_init',
        'hwlRegisterSettings'
    );
}
add_action('admin_menu', 'hwlCreateMenu');

/**
 * Returns the configured name of WordPress' content directory.
 *
 * @return mixed
 */
function hwlGetContentDirName()
{
    preg_match('/[^\/]+$/u', WP_CONTENT_DIR, $match);

    return $match[0];
}

/**
 * @return string
 */
function hwlGetUploadUrl()
{
    if (CAOS_WEBFONTS_CDN_URL) {
        $uploadUrl = '//' . CAOS_WEBFONTS_CDN_URL . '/' . hwlGetContentDirName() . CAOS_WEBFONTS_CACHE_DIR;
    } else {
        $uploadUrl = get_site_url(CAOS_WEBFONTS_CURRENT_BLOG_ID, hwlGetContentDirName() . CAOS_WEBFONTS_CACHE_DIR);
    }

    return $uploadUrl;
}

/**
 * Create table to store downloaded fonts in version 1.6.1.
 */
function hwlCreateWebfontsTable()
{
    global $wpdb;
    $sql = "CREATE TABLE IF NOT EXISTS " . CAOS_WEBFONTS_DB_TABLENAME . " (
            font_id varchar(191) NOT NULL,
            font_family varchar(191) NOT NULL,
            font_weight mediumint(5) NOT NULL,
            font_style varchar(191) NOT NULL,
            downloaded tinyint(1) DEFAULT 0,
            url_ttf varchar(191) NULL,
            url_woff varchar(191) NULL,
            url_woff2 varchar(191) NULL,
            url_eot varchar(191) NULL,
            UNIQUE KEY (font_id)
            ) " . CAOS_WEBFONTS_DB_CHARSET . ";";
    $wpdb->query($sql);

    add_option('caos_webfonts_db_version', '1.6.1');
}

/**
 * Create table to store selected subsets in version 1.7.0.
 */
function hwlCreateSubsetsTable()
{
    global $wpdb;
    $sql = "CREATE TABLE IF NOT EXISTS " . CAOS_WEBFONTS_DB_TABLENAME . '_subsets' . " (
            subset_font varchar(32) NOT NULL,
            subset_family varchar(191) NOT NULL,
            available_subsets varchar(191) NOT NULL,
            selected_subsets varchar(191) NOT NULL,
            UNIQUE KEY (subset_font)
            ) " . CAOS_WEBFONTS_DB_CHARSET . ";";
    $wpdb->query($sql);

    update_option('caos_webfonts_db_version', '1.7.0');
}

/**
 * Adds the 'local' column in version 1.8.3
 */
function hwlAddLocalColumn()
{
    global $wpdb;

    $sql = "ALTER TABLE " . CAOS_WEBFONTS_DB_TABLENAME . " " .
           "ADD COLUMN local varchar(128) AFTER font_style;";
    $wpdb->query($sql);

    update_option('caos_webfonts_db_version', '1.8.3');
}

/**
 * Check current version and execute required db updates.
 */
function hwlRunDbUpdates()
{
    $currentVersion = get_option('caos_webfonts_db_version') ?: '1.0.0';
    if (version_compare($currentVersion, '1.6.1') < 0) {
        hwlCreateWebfontsTable();
    }
    if (version_compare($currentVersion, '1.7.0') < 0) {
        hwlCreateSubsetsTable();
    }
    if (version_compare($currentVersion, CAOS_WEBFONTS_DB_VERSION) < 0) {
        hwlAddLocalColumn();
    }
}
add_action('plugins_loaded', 'hwlRunDbUpdates');

/**
 * @param $links
 *
 * @return mixed
 */
function hwlSettingsLink($links)
{
    $adminUrl     = admin_url() . 'options-general.php?page=optimize-webfonts';
    $settingsLink = "<a href='$adminUrl'>" . __('Settings') . "</a>";
    array_push($links, $settingsLink);

    return $links;
}
$caosLink = plugin_basename(__FILE__);

add_filter("plugin_action_links_$caosLink", 'hwlSettingsLink');

/**
 * Render the settings page.
 */
function hwlSettingsPage()
{
    if (!current_user_can('manage_options')) {
        wp_die(__("You're not cool enough to access this page."));
    }
    ?>
    <div class="wrap">
        <h1><?php _e('OMGF | Optimize My Google Fonts', 'host-webfonts-local'); ?></h1>
        <p>
            <?php _e('Developed by: ', 'host-webfonts-local'); ?>
            <a title="Buy me a beer!" href="<?php echo CAOS_WEBFONTS_SITE_URL; ?>/donate/">
                Daan van den Bergh</a>.
        </p>

        <div id="hwl-admin-notices"></div>

        <?php require_once(plugin_dir_path(__FILE__) . 'includes/welcome-panel.php'); ?>

        <form id="hwl-options-form" name="hwl-options-form" method="post" style="float: left; width: 60%;">
            <div class="">
                <?php

                include(plugin_dir_path(__FILE__) . 'includes/caos-webfonts-style-generation.php');

                ?>
            </div>
        </form>

        <form id="hwl-settings-form" name="hwl-settings-form" method="post" action="options.php" style="float: left; width: 39%;">
            <?php
            settings_fields('caos-webfonts-basic-settings');
            do_settings_sections('caos-webfonts-basic-settings');

            include(plugin_dir_path(__FILE__) . 'includes/caos-webfonts-basic-settings.php');

            do_action('hwl_after_settings_form_settings');

            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * @return array|\Exception
 */
function hwlGetTotalFonts()
{
    global $wpdb;

    try {
        return $wpdb->get_results("SELECT * FROM " . CAOS_WEBFONTS_DB_TABLENAME);
    } catch (\Exception $e) {
        return $e;
    }
}

/**
 * @return array|\Exception
 */
function hwlGetDownloadedFonts()
{
    global $wpdb;

    try {
        return $wpdb->get_results("SELECT * FROM " . CAOS_WEBFONTS_DB_TABLENAME . " WHERE downloaded = 1");
    } catch (\Exception $e) {
        return $e;
    }
}

/**
 * @return array
 */
function hwlGetDownloadStatus()
{
    return array(
        "downloaded" => count(hwlGetDownloadedFonts()),
        "total"      => count(hwlGetTotalFonts())
    );
}

/**
 * @return array|\Exception|null|object
 */
function hwlGetSubsets()
{
    global $wpdb;

    try {
        return $wpdb->get_results("SELECT * FROM " . CAOS_WEBFONTS_DB_TABLENAME . "_subsets");
    } catch (\Exception $e) {
        return $e;
    }
}

function hwlGetFontsByFamily($family)
{
    global $wpdb;

    try {
        return $wpdb->get_results("SELECT * FROM " . CAOS_WEBFONTS_DB_TABLENAME . " WHERE font_family = '$family'");
    } catch (\Exception $e) {
        return $e;
    }
}

/**
 * @return \Exception|false|int
 */
function hwlCleanQueue()
{
    global $wpdb;

    try {
        $wpdb->query("TRUNCATE TABLE " . CAOS_WEBFONTS_DB_TABLENAME);
        $wpdb->query("TRUNCATE TABLE " . CAOS_WEBFONTS_DB_TABLENAME . "_subsets");
    } catch (\Exception $e) {
        return $e;
    }
}

/**
 * AJAX-wrapper for hwlGetDownloadStatus()
 */
function hwlAjaxGetDownloadStatus()
{
    return wp_die(
        json_encode(
            hwlGetDownloadStatus()
        )
    );
}
add_action('wp_ajax_hwlAjaxGetDownloadStatus', 'hwlAjaxGetDownloadStatus');

/**
 * AJAX-wrapper for hwlCleanQueue()
 */
function hwlAjaxCleanQueue()
{
    return wp_die(hwlCleanQueue());
}
add_action('wp_ajax_hwlAjaxCleanQueue', 'hwlAjaxCleanQueue');

/**
 * AJAX-wrapper for hwlEmptyDir()
 *
 * @return array
 */
function hwlAjaxEmptyDir()
{
    return array_map('unlink', array_filter((array) glob(CAOS_WEBFONTS_UPLOAD_DIR . '/*')));
}
add_action('wp_ajax_hwlAjaxEmptyDir', 'hwlAjaxEmptyDir');

/**
 * Search Subsets in Google Webfonts Helper
 */
function hwlAjaxSearchFontSubsets()
{
    try {
        $searchQueries = explode(',', sanitize_text_field($_POST['search_query']));

        foreach ($searchQueries as $searchQuery) {
            $request = curl_init();
            curl_setopt($request, CURLOPT_URL, CAOS_WEBFONTS_HELPER_URL . $searchQuery);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($request);
            curl_close($request);

            $result     = json_decode($result);
            $response[] = array(
                'family'  => $result->family,
                'id'      => $result->id,
                'subsets' => $result->subsets
            );
        }
        wp_die(json_encode($response));
    } catch (\Exception $e) {
        wp_die($e);
    }
}
add_action('wp_ajax_hwlAjaxSearchFontSubsets', 'hwlAjaxSearchFontSubsets');

/**
 * Search Fonts in Google Webfonts Helper
 */
function hwlAjaxSearchGoogleFonts()
{
    try {
        $request     = curl_init();
        $searchQuery = sanitize_text_field($_POST['search_query']);
        $subsets     = implode($_POST['search_subsets'], ',');

        curl_setopt($request, CURLOPT_URL, CAOS_WEBFONTS_HELPER_URL . $searchQuery . '?subsets=' . $subsets);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($request);

        curl_close($request);
        wp_die($result);
    } catch (\Exception $e) {
        wp_die($e);
    }
}
add_action('wp_ajax_hwlAjaxSearchGoogleFonts', 'hwlAjaxSearchGoogleFonts');

/**
 * Create cache dir upon plugin (re-)activation.
 */
function hwlCreateCacheDir()
{
    $uploadDir = CAOS_WEBFONTS_UPLOAD_DIR;
    if (!is_dir($uploadDir)) {
        wp_mkdir_p($uploadDir);
    }
}
register_activation_hook(__FILE__, 'hwlCreateCacheDir');

/**
 * @return array
 */
function hwlFontDisplayOptions()
{
    $fontDisplay = array(
        __('Auto (default)', 'host-webfonts-local') => 'auto',
        __('Block', 'host-webfonts-local')          => 'block',
        __('Swap', 'host-webfonts-local')           => 'swap',
        __('Fallback', 'host-webfonts-local')       => 'fallback',
        __('Optional', 'host-webfonts-local')       => 'optional'
    );

    return $fontDisplay;
}

/**
 * The function for generating the stylesheet and saving it to the upload-dir.
 */
function hwlAjaxGenerateStyles()
{
    require_once(plugin_dir_path(__FILE__) . 'includes/ajax/generate-stylesheet.php');
}
add_action('wp_ajax_hwlAjaxGenerateStyles', 'hwlAjaxGenerateStyles');

/**
 * Saves the chosen webfonts to the database for further processing.
 */
function hwlAjaxDownloadFonts()
{
    require_once(plugin_dir_path(__FILE__) . 'includes/ajax/download-fonts.php');
}
add_action('wp_ajax_hwlAjaxDownloadFonts', 'hwlAjaxDownloadFonts');

/**
 * Once the stylesheet is generated. We can enqueue it.
 */
function hwlEnqueueStylesheet()
{
    wp_enqueue_style('omgf-fonts', CAOS_WEBFONTS_UPLOAD_URL . '/' . CAOS_WEBFONTS_FILENAME, array(), (CAOS_WEBFONTS_REMOVE_VERSION) ? null : CAOS_WEBFONTS_STATIC_VERSION);
}
add_action('wp_enqueue_scripts', 'hwlEnqueueStylesheet');

/**
 * Stylesheet and Javascript needed in Admin
 */
function hwlEnqueueAdminJs($hook)
{
    if ($hook == 'settings_page_optimize-webfonts') {
        wp_enqueue_script('hwl-admin-js', plugin_dir_url(__FILE__) . 'js/hwl-admin.js', array('jquery'), CAOS_WEBFONTS_STATIC_VERSION, true);
        wp_enqueue_style('hwl-admin-css', plugin_dir_url(__FILE__) . 'css/hwl-admin.css', array(), CAOS_WEBFONTS_STATIC_VERSION);
    }
}
add_action('admin_enqueue_scripts', 'hwlEnqueueAdminJs');

/**
 * When plugin is deactivated. Remove all CSS and JS.
 */
function hwlDequeueJsCss()
{
    wp_dequeue_script('hwl-admin-js');
    wp_dequeue_style('hwl-admin-css');
    wp_dequeue_style('omgf-fonts');
}
register_deactivation_hook(__FILE__, 'hwlDequeueJsCss');

/**
 * Prioritize the loading of fonts by adding a resource hint to the document head.
 */
function hwlAddLinkPreload()
{
    global $wp_styles;

    $handle = 'omgf-fonts';
    $style = $wp_styles->registered[$handle];

    /** Do not add 'preload' if Minification plugins are enabled. */
    if ($style) {
        $source = $style->src . ($style->ver ? "?ver={$style->ver}" : "");
        echo "<link rel='preload' href='{$source}' as='style' />\n";
    }
}

function hwlIsPreloadEnabled()
{
    if (CAOS_WEBFONTS_PRELOAD == 'on') {
        add_action('wp_head', 'hwlAddLinkPreload', 1);
    }
}
add_action('init', 'hwlIsPreloadEnabled');

/**
 * Automatically dequeues any stylesheets loaded from fonts.gstatic.com or
 * fonts.googleapis.com. Also checks for stylesheets dependant on Google Fonts and
 * re-enqueues and registers them.
 */
function hwlRemoveGoogleFonts()
{
    global $wp_styles;

    $registered = $wp_styles->registered;

    $fonts      = array_filter($registered, function ($contents) {
        return strpos($contents->src, 'fonts.googleapis.com') !== false
               || strpos($contents->src, 'fonts.gstatic.com') !== false;
    });

    $dependencies = array_filter($registered, function ($contents) use ($fonts) {
        return !empty(array_intersect(array_keys($fonts), $contents->deps));
    });

    foreach ($fonts as $font) {
        wp_deregister_style($font->handle);
        wp_dequeue_style($font->handle);
    }

    foreach ($dependencies as $dependency) {
        wp_register_style('omgf-dep-' . $dependency->handle, $dependency->src);
        wp_enqueue_style('omgf-dep-' . $dependency->handle, $dependency->src);
    }
}

function hwlIsRemoveGoogleFontsEnabled()
{
    if (CAOS_WEBFONTS_REMOVE_GFONTS == 'on' && !is_admin()) {
        add_action('wp_print_styles', 'hwlRemoveGoogleFonts', PHP_INT_MAX);
    }
}
add_action('init', 'hwlIsRemoveGoogleFontsEnabled');
