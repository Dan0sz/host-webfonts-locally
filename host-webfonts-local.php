<?php
/**
 * Plugin Name: CAOS for Webfonts
 * Plugin URI: https://dev.daanvandenbergh.com/wordpress-plugins/host-google-fonts-locally
 * Description: Automagically save the fonts you want to use inside your content-folder, generate a stylesheet for them and enqueue it in your theme's header.
 * Version: 1.0
 * Author: Daan van den Bergh
 * Author URI: https://dev.daanvandenbergh.com
 * License: GPL2v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Extend WP default Allowed Filetypes
 *
 * @param array $filetypes
 * @return array
 */
function hwlSetAllowedFiletypes($filetypes = array()) {

    $filetypes['woff']  = "application/font-woff";
    $filetypes['woff2'] = "application/font-woff2";
    $filetypes['ttf']   = "application/-x-font-truetype";
    $filetypes['otf']   = "font/opentype";

    return $filetypes;
}

add_filter('upload_mimes', 'hwlSetAllowedFiletypes');

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

    add_action(
        'admin_init',
        'registerHwlSettings'
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
            <a title="Buy me a beer!" href="http://dev.daanvandenbergh.com/donate/">
                Daan van den Bergh</a>.
        </p>
        <div id="hwl-admin-notices"></div>
        <?php require_once('includes/welcome-panel.php'); ?>
        <form id="hwl-options-form" name="hwl-options-form">
            <?php
            settings_fields('host-webfonts-local-basic-settings'
            );
            do_settings_sections('host-webfonts-local-basic-settings'
            );

            /**
             * Render the upload-functions.
             */
            hwlMediaUploadInit();

            do_action('hwl_after_form_settings');
            ?>
        </form>
    </div>
    <?php
}

/**
 * Set custom upload-fields and render upload buttons.
 */
function hwlMediaUploadInit() {
    wp_enqueue_media();

    update_option('upload_path',WP_CONTENT_DIR . '/local-fonts');
    update_option('upload_url_path',content_url() . '/local-fonts');
    update_option('uploads_use_yearmonth_folders', false);
    ?>
    <table>
        <tbody>
        <tr valign="top">
            <th>filename</th>
            <th>font-family</th>
            <th>font-weight</th>
            <th>font-type</th>
            <th>source URL</th>
        </tr>
        </tbody>
        <tbody id="hwl_uploaded_fonts">
        </tbody>
        <tbody>
            <tr valign="bottom">
                <td>
                    <input type="button" onclick="hwlFontUploader()" name="upload-btn"
                           id="upload-btn" class="button-secondary" value="Select Fonts" />
                </td>
                <td>
                    <input type="button" onclick="hwlGenerateStylesheet()" name="generate-btn"
                           id="generate-btn" class="button-primary" value="Generate Stylesheet" />
                </td>
            </tr>
        </tbody>
    </table>
    <script type="text/javascript">
        var media_uploader = null;

        function hwlFontUploader()
        {
            media_uploader = wp.media({
                frame:    "post",
                state:    "insert",
                multiple: true
            }).open();

            media_uploader.on("insert", function(){
                var length = media_uploader.state().get("selection").length;
                var fonts = media_uploader.state().get("selection").models;

                for(var iii = 0; iii < length; iii++)
                {
                    var font_url = fonts[iii].changed.url;
                    var font_name = fonts[iii].changed.title;
                    var font_type = fonts[iii].changed.subtype;

                    var uploadedFont = `<tr valign="top">
                                            <td>
                                                <input type="text" name="hwl_uploaded_font][${font_name}]"
                                                       id="hwl_uploaded_font][${font_name}]"
                                                       value="${font_name}" readonly />
                                            </td>
                                            <td>
                                                <input type="text" name="hwl_uploaded_font][${font_name}][font_family]"
                                                       id="hwl_uploaded_font][${font_name}][font_family]"
                                                       value="" />
                                            </td>
                                            <td>
                                                <input type="text" name="hwl_uploaded_font][${font_name}][font_weight]"
                                                       id="hwl_uploaded_font][${font_name}][font_weight]"
                                                       value="" />
                                            </td>
                                            <td>
                                                <input type="text" name="hwl_uploaded_font][${font_name}][${font_type}]"
                                                       id="hwl_uploaded_font][${font_name}][${font_type}]"
                                                       value="${font_type}" readonly />
                                            </td>
                                            <td>
                                                <input type="text" name="hwl_uploaded_font][${font_name}][${font_type}][url]"
                                                       id="hwl_uploaded_font][${font_name}][${font_type}][url]"
                                                       value="${font_url}" readonly />
                                            </td>
                                        </tr>`;
                    jQuery('#hwl_uploaded_fonts').append(uploadedFont);
                }
            });
        }
        function hwlGenerateStylesheet() {
            var hwlData = hwlSerializeArray($('#hwl-options-form'));

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'hwlAjaxGenerateStyles',
                    uploaded_fonts: hwlData
                },
                success: function(response) {
                    console.log(response);
                    jQuery('#hwl-admin-notices').append(
                        `<div class="updated settings-error notice is-dismissible">
                            <p>${response}</p>
                        </div>`
                    );
                    jQuery('#hwl_uploaded_fonts').each(function() {
                        jQuery(this).fadeOut(700, function() {
                            jQuery(this).remove();
                        })
                    });
                },
                error: function(response) {
                    jQuery('#hwl-admin-notices').append(
                        `<div class="notice notice-error is-dismissible">
                            <p><?php _e( 'The stylesheet could not be created:', 'host-webfonts-local' ); ?> ${response}</p>"
                        </div>`
                    )
                }
            });
        }
        function hwlSerializeArray(data) {
            var result = [];
            data.each(function() {
                var fields = {};
                $.each($(this).serializeArray(), function() {
                    fields[this.name] = this.value;
                });
                result.push(fields);
            });

            return result;
        }
    </script>
<?php
}

function hwlAjaxGenerateStyles() {
    require_once('includes/generate-stylesheet.php');
}
add_action('wp_ajax_hwlAjaxGenerateStyles', 'hwlAjaxGenerateStyles');

/**
 * Once the stylesheet is generated. We can enqueue it.
 */
function hwlEnqueueStylesheet()
{
    $stylesheet = WP_CONTENT_DIR . '/local-fonts/local-fonts.css';
    if (file_exists($stylesheet)) {
	    wp_register_style('hwl-style', content_url() . '/local-fonts/local-fonts.css');
	    wp_enqueue_style('hwl-style');
    }
}

add_action('wp_enqueue_scripts', 'hwlEnqueueStylesheet' );