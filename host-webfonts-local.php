<?php
/**
 * Plugin Name: CAOS for Webfonts
 * Plugin URI: https://dev.daanvandenbergh.com/wordpress-plugins/host-google-fonts-locally
 * Description: Automagically save the fonts you want to use inside your content-folder, generate a stylesheet for them and enqueue it in your theme's header.
 * Version: 1.5.3
 * Author: Daan van den Bergh
 * Author URI: https://dev.daanvandenbergh.com
 * License: GPL2v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

global $wpdb;

/**
 * Define constants.
 */
define('CAOS_WEBFONTS_DB_VERSION'      , '1.5.0');
define('CAOS_WEBFONTS_DB_TABLENAME'    , $wpdb->prefix . 'caos_webfonts');
define('CAOS_WEBFONTS_DB_CHARSET'      , $wpdb->get_charset_collate());
define('CAOS_WEBFONTS_FILENAME'        , 'fonts.css');
define('CAOS_WEBFONTS_CACHE_DIR'       , esc_attr(get_option('caos_webfonts_cache_dir')) ?: '/cache/caos-webfonts');
define('CAOS_WEBFONTS_CURRENT_BLOG_ID' , get_current_blog_id());
define('CAOS_WEBFONTS_UPLOAD_DIR'      , WP_CONTENT_DIR . CAOS_WEBFONTS_CACHE_DIR);
define('CAOS_WEBFONTS_UPLOAD_URL'      , get_site_url(CAOS_WEBFONTS_CURRENT_BLOG_ID, hwlGetContentDirName() . CAOS_WEBFONTS_CACHE_DIR));
define('CAOS_WEBFONTS_DISPLAY_OPTION'  , esc_attr(get_option('caos_webfonts_display_option')) ?: 'auto');

function hwlRegisterSettings()
{
    register_setting('caos-webfonts-basic-settings',
        'caos_webfonts_cache_dir'
    );
    register_setting('caos-webfonts-basic-settings',
        'caos_webfonts_display_option'
    );
}

/**
 * Create the Admin menu-item
 */
function hwlCreateMenu()
{
	add_options_page(
		'CAOS for Webfonts',
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
 * Create table to store downloaded fonts in.
 */
function hwlCreateTable()
{
    $sql = "CREATE TABLE " . CAOS_WEBFONTS_DB_TABLENAME . "(
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

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('caos_webfonts_db_version', CAOS_WEBFONTS_DB_VERSION);
}

/**
 * Check current version and execute required db updates.
 */
function hwlRunDbUpdates()
{
    $currentVersion = get_site_option('caos_webfonts_db_version');
    if(version_compare($currentVersion, CAOS_WEBFONTS_DB_VERSION) < 0) {
        hwlCreateTable();
    }

    // We can trigger update scripts in the future here.
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
$caosLink = plugin_basename( __FILE__ );

add_filter("plugin_action_links_$caosLink", 'hwlSettingsLink');

/**
 * Render the settings page.
 */
function hwlSettingsPage()
{
	if (!current_user_can('manage_options'))
	{
		wp_die(__("You're not cool enough to access this page."));
	}
	?>
    <div class="wrap">
        <h1><?php _e('CAOS for Webfonts', 'host-webfonts-local'); ?></h1>
        <p>
			<?php _e('Developed by: ', 'host-webfonts-local'); ?>
            <a title="Buy me a beer!" href="https://dev.daanvandenbergh.com/donate/">
                Daan van den Bergh</a>.
        </p>

        <div id="hwl-admin-notices"></div>

		<?php require_once(plugin_dir_path(__FILE__) . 'includes/welcome-panel.php'); ?>

        <form id="hwl-options-form" name="hwl-options-form" method="post" action="options.php" style="float: left; width: 60%;">
            <div class="">
                <h3><?php _e('Generate Stylesheet'); ?></h3>
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
 * @return \Exception|false|int
 */
function hwlCleanQueue()
{
    global $wpdb;

    try {
        return $wpdb->query("TRUNCATE TABLE " . CAOS_WEBFONTS_DB_TABLENAME);
    } catch (\Exception $e) {
        return $e;
    }
}

/**
 * AJAX-wrapper for hwlGetDownloadedFonts()
 */
function hwlAjaxGetDownloadedFonts()
{
    return wp_die(count(hwlGetDownloadedFonts()));
}
add_action('wp_ajax_hwlAjaxGetDownloadedFonts', 'hwlAjaxGetDownloadedFonts');

/**
 * AJAX-wrapper for hwlGetTotalFonts()
 */
function hwlAjaxGetTotalFonts()
{
    return wp_die(count(hwlGetTotalFonts()));
}
add_action('wp_ajax_hwlAjaxGetTotalFonts', 'hwlAjaxGetTotalFonts');

/**
 * AJAX-wrapper for hwlCleanQueue()
 */
function hwlAjaxCleanQueue()
{
    return wp_die(hwlCleanQueue());
}
add_action('wp_ajax_hwlAjaxCleanQueue', 'hwlAjaxCleanQueue');

/**
 * Search Fonts in Google Webfonts Helper
 */
function hwlAjaxSearchGoogleFonts() {
	try {
		$request     = curl_init();
		$searchQuery = sanitize_text_field($_POST['search_query']);

		curl_setopt($request, CURLOPT_URL, 'https://google-webfonts-helper.herokuapp.com/api/fonts/' . $searchQuery);
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
register_activation_hook(__FILE__, 'hwlCreateCacheDir' );

/**
 * @return array
 */
function hwlFontDisplayOptions()
{
	$fontDisplay = array(
            'Auto (default)' => 'auto',
            'Block'          => 'block',
            'Swap'           => 'swap',
            'Fallback'       => 'fallback',
            'Optional'       => 'optional'
	);

	return $fontDisplay;
}

/**
 * The function for generating the stylesheet and saving it to the upload-dir.
 */
function hwlAjaxGenerateStyles() {
	require_once(plugin_dir_path(__FILE__) . 'includes/ajax/generate-stylesheet.php');
}
add_action('wp_ajax_hwlAjaxGenerateStyles', 'hwlAjaxGenerateStyles');

/**
 * Saves the chosen webfonts to the database for further processing.
 */
function hwlAjaxSaveWebfontsToDb() {
    require_once(plugin_dir_path(__FILE__) . 'includes/ajax/download-fonts.php');
}
add_action('wp_ajax_hwlAjaxSaveWebfontsToDb', 'hwlAjaxSaveWebfontsToDb');

/**
 * Once the stylesheet is generated. We can enqueue it.
 */
function hwlEnqueueStylesheet()
{
	$stylesheet = CAOS_WEBFONTS_UPLOAD_DIR . '/'. CAOS_WEBFONTS_FILENAME;
	if (file_exists($stylesheet)) {
		wp_register_style('hwl-style', CAOS_WEBFONTS_UPLOAD_URL . '/' . CAOS_WEBFONTS_FILENAME);
		wp_enqueue_style('hwl-style');
	}
}
add_action('wp_enqueue_scripts', 'hwlEnqueueStylesheet' );

/**
 * Stylesheet and Javascript needed in Admin
 */
function hwlEnqueueAdminJs($hook)
{
    if ($hook == 'settings_page_optimize-webfonts') {
	    wp_enqueue_script('hwl-admin-js', plugin_dir_url(__FILE__) . 'js/hwl-admin.js', array('jquery'), CAOS_WEBFONTS_DB_VERSION, true);
	    wp_enqueue_style('hwl-admin.css', plugin_dir_url(__FILE__) . 'css/hwl-admin.css', array(), CAOS_WEBFONTS_DB_VERSION);
    }
}
add_action('admin_enqueue_scripts', 'hwlEnqueueAdminJs');

/**
 * When plugin is deactivated. Remove all CSS and JS.
 */
function hwlDequeueJsCss()
{
	wp_dequeue_script('hwl-admin-js');
	wp_dequeue_style('hwl-admin.css');
	wp_dequeue_style('hwl-style');
}
register_deactivation_hook(__FILE__, 'hwlDequeueJsCss');
