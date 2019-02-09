<?php
/**
 * Plugin Name: CAOS for Webfonts
 * Plugin URI: https://dev.daanvandenbergh.com/wordpress-plugins/host-google-fonts-locally
 * Description: Automagically save the fonts you want to use inside your content-folder, generate a stylesheet for them and enqueue it in your theme's header.
 * Version: 1.4.1
 * Author: Daan van den Bergh
 * Author URI: https://dev.daanvandenbergh.com
 * License: GPL2v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Define constants.
 */
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

        <form id="hwl-options-form" name="hwl-options-form" style="float: left; width: 49.5%;">
            <div class="">
                <h3><?php _e('Generate Stylesheet'); ?></h3>
	            <?php
	            /**
	             * Render the upload-functions.
	             */
	            hwlSearchForm();
	            ?>
            </div>
        </form>

        <form id="hwl-settings-form" name="hwl-settings-form" method="post" action="options.php" style="float: left; width: 49.5%;">
            <?php
            settings_fields('caos-webfonts-basic-settings'
            );
            do_settings_sections('caos-webfonts-basic-settings'
            );

            include(plugin_dir_path(__FILE__) . 'includes/caos-form.php');

            do_action('hwl_after_form_settings');

            submit_button();
            ?>
        </form>
    </div>
	<?php
}

/**
 * Set custom upload-fields and render upload buttons.
 */
function hwlSearchForm() {
	?>
    <table>
        <tbody>
        <tr valign="top">
            <td colspan="2">
                <input type="text" name="search-field"
                       id="search-field" class="form-input-tip ui-autocomplete-input" placeholder="Search fonts..." />
            </td>
        </tr>
        </tbody>
        <tr valign="top">
            <th>
                font-family
            </th>
            <th>
                font-style
            </th>
            <th>
                remove
            </th>
        </tr>
        <tbody id="hwl-results">
        <tr class="loading" style="display: none;">
            <td colspan="3" align="center">
                <span class="spinner"></span>
            </td>
        </tr>
        <tr class="error" style="display: none;">
            <td colspan="3" align="center">No fonts available.</td>
        </tr>
        </tbody>
    </table>

    <table>
        <tbody>
        <tr valign="bottom">
            <td>
                <input type="button" onclick="hwlGenerateStylesheet()" name="generate-btn"
                       id="generate-btn" class="button-primary" value="Generate Stylesheet" />
            </td>
        </tr>
        </tbody>
    </table>
    <script type="text/javascript">
    </script>
	<?php
}

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
	require_once(plugin_dir_path(__FILE__) . 'includes/generate-stylesheet.php');
}
add_action('wp_ajax_hwlAjaxGenerateStyles', 'hwlAjaxGenerateStyles');

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
function hwlEnqueueAdminJs()
{
	wp_enqueue_script('hwl-admin-js', plugin_dir_url(__FILE__) . 'js/hwl-admin.js', array('jquery'), null, true);
	wp_enqueue_style('hwl-admin.css', plugin_dir_url(__FILE__) . 'css/hwl-admin.css');
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
