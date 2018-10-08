<?php
/**
 * Plugin Name: CAOS for Webfonts
 * Plugin URI: https://dev.daanvandenbergh.com/wordpress-plugins/host-google-fonts-locally
 * Description: Automagically save the fonts you want to use inside your content-folder, generate a stylesheet for them and enqueue it in your theme's header.
 * Version: 1.3.1
 * Author: Daan van den Bergh
 * Author URI: https://dev.daanvandenbergh.com
 * License: GPL2v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Define constants.
 */
define('CAOS_WEBFONTS_FILENAME'  , 'fonts.css');
define('CAOS_WEBFONTS_CACHE_DIR' , '/cache/caos-webfonts');
define('CAOS_WEBFONTS_UPLOAD_DIR', WP_CONTENT_DIR . CAOS_WEBFONTS_CACHE_DIR);
define('CAOS_WEBFONTS_UPLOAD_URL', content_url() . CAOS_WEBFONTS_CACHE_DIR);

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
}
add_action('admin_menu', 'hwlCreateMenu');

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

        <form id="hwl-options-form" name="hwl-options-form">
			<?php
			settings_fields('host-webfonts-local-basic-settings'
			);
			do_settings_sections('host-webfonts-local-basic-settings'
			);

			/**
			 * Render the upload-functions.
			 */
			hwlSearchForm();

			do_action('hwl_after_form_settings');
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
 * The function for generating the stylesheet and resetting the upload dir to the default.
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

function hwlEnqueueAdminJs()
{
	wp_enqueue_script('hwl-admin-js', plugin_dir_url(__FILE__) . 'js/hwl-admin.js', array('jquery'), null, true);
	wp_enqueue_style('hwl-admin.css', plugin_dir_url(__FILE__) . 'css/hwl-admin.css');
}
add_action('admin_enqueue_scripts', 'hwlEnqueueAdminJs');
