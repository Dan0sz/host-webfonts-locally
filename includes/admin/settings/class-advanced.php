<?php
/* * * * * * * * * * * * * * * * * * * * *
 *
 *  ██████╗ ███╗   ███╗ ██████╗ ███████╗
 * ██╔═══██╗████╗ ████║██╔════╝ ██╔════╝
 * ██║   ██║██╔████╔██║██║  ███╗█████╗
 * ██║   ██║██║╚██╔╝██║██║   ██║██╔══╝
 * ╚██████╔╝██║ ╚═╝ ██║╚██████╔╝██║
 *  ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚═╝
 *
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2020 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Admin_Settings_Advanced extends OMGF_Admin_Settings_Builder
{
    /** @var string $utmTags */
    private $utmTags = '?utm_source=omgf&utm_medium=plugin&utm_campaign=settings';

    /**
     * OMGF_Admin_Settings_Advanced constructor.
     */
    public function __construct()
    {
        $this->title = __('Advanced Settings', $this->plugin_text_domain);

        // Open
        // @formatter:off
        add_filter('omgf_advanced_settings_content', [$this, 'do_title'], 10);
        add_filter('omgf_advanced_settings_content', [$this, 'do_description'], 15);
        add_filter('omgf_advanced_settings_content', [$this, 'do_before'], 20);

        // Settings
        add_filter('omgf_advanced_settings_content', [$this, 'do_remove_google_fonts'], 25);
        add_filter('omgf_advanced_settings_content', [$this, 'do_display_option'], 30);
        add_filter('omgf_advanced_settings_content', [$this, 'do_cache_dir'], 35);
        add_filter('omgf_advanced_settings_content', [$this, 'do_cache_uri'], 40);
        add_filter('omgf_advanced_settings_content', [$this, 'do_force_ssl'], 45);
        add_filter('omgf_advanced_settings_content', [$this, 'do_relative_url'], 50);
        add_filter('omgf_advanced_settings_content', [$this, 'do_cdn_url'], 55);
        add_filter('omgf_advanced_settings_content', [$this, 'do_webfont_loader'], 60);
        add_filter('omgf_advanced_settings_content', [$this, 'do_remove_version'], 65);
        add_filter('omgf_advanced_settings_content', [$this, 'do_enqueue_order'], 70);
        add_filter('omgf_advanced_settings_content', [$this, 'do_optimize_edit_roles'], 75);
        add_filter('omgf_advanced_settings_content', [$this, 'do_uninstall'], 80);

        // Close
        add_filter('omgf_advanced_settings_content', [$this, 'do_after'], 100);
        // @formatter:on
    }

    /**
     * Description
     */
    public function do_description()
    {
        ?>
        <p>
            <?php _e('* <strong>Generate stylesheet</strong> after changing this setting.', $this->plugin_text_domain); ?>
            <br/>
            <?php _e('** <strong>Download Fonts</strong> and <strong>Generate Stylesheet</strong> after changing this setting.', $this->plugin_text_domain); ?>
        </p>
        <?php
    }

    /**
     *
     */
    public function do_remove_google_fonts()
    {
        $this->do_checkbox(
            __('Remove Google Fonts', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_REMOVE_GOOGLE_FONTS,
            OMGF_REMOVE_GFONTS,
            sprintf(__('Remove any externally hosted Google Fonts-stylesheets from your WordPress-blog. If it doesn\'t work, your theme and/or plugin(s) are using unconventional methods or Web Font Loader to load Google Fonts. <a href="%s" target="_blank">Upgrade to OMGF Pro</a> to automatically remove Google Fonts incl. resource hints (e.g. <code>dns-prefetch</code>, <code>preconnect</code> and <code>preload</code>).', $this->plugin_text_domain), 'https://woosh.dev/wordpress-plugins/host-google-fonts-pro/' . $this->utmTags)
        );
    }

    /**
     *
     */
    public function do_display_option()
    {
        $this->do_select(
            __('Font-display option', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_DISPLAY_OPTION,
            OMGF_Admin_Settings::OMGF_FONT_DISPLAY_OPTIONS,
            OMGF_DISPLAY_OPTION,
            __('Select which font-display strategy to use. Defaults to Swap (recommended).', $this->plugin_text_domain),
            '*'
        );
    }

    /**
     *
     */
    public function do_cache_dir()
    {
        $this->do_text(
            __('Save font files to...', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_PATH,
            __('e.g. /uploads/omgf', $this->plugin_text_domain),
            OMGF_CACHE_PATH,
            __("The folder (inside <code>wp-content</code>) where font files should be stored. Give each site a unique value if you're using Multisite. Defaults to <code>/uploads/omgf</code>. After changing this setting, the folder will be created if it doesn't exist and existing files will be moved automatically.", $this->plugin_text_domain),
            '**'
        );
    }

    /**
     *
     */
    public function do_cache_uri()
    {
        $this->do_text(
            __('Serve font files from...', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_URI,
            __('e.g. /app/uploads/omgf', $this->plugin_text_domain),
            OMGF_CACHE_URI,
            __('The relative path to serve font files from. Useful for when you\'re using security through obscurity plugins, such as WP Hide. If left empty, the cache directory specified above will be used.', $this->plugin_text_domain),
            '**'
        );
    }

    /**
     *
     */
    public function do_force_ssl()
    {
        $this->do_checkbox(
            __('Force SSL?', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_FORCE_SSL,
            OMGF_FORCE_SSL,
            __('Some plugins mess up WordPress\' URL structure, which can cause OMGF to generate incorrect URLs in the stylesheet. If OMGF is generating non-SSL (<code>http://...</code>) URLs in the stylesheet, and you have the <strong>Site</strong> and <strong>WordPress Address</strong> (in <strong>Settings</strong> > <strong>General</strong>) set to SSL (<code>https://</code>), then enable this option.', $this->plugin_text_domain),
            '**'
        );
    }

    /**
     *
     */
    public function do_relative_url()
    {
        $this->do_checkbox(
            __('Use Relative URLs?', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_RELATIVE_URL,
            OMGF_RELATIVE_URL,
            __('Use relative instead of absolute (full) URLs to generate the stylesheet. <strong>Warning!</strong> This disables the CDN URL!', $this->plugin_text_domain),
            '**'
        );
    }

    /**
     *
     */
    public function do_cdn_url()
    {
        $this->do_text(
            __('Serve fonts from CDN', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_CDN_URL,
            __('e.g. cdn.mydomain.com', $this->plugin_text_domain),
            OMGF_CDN_URL,
            __("Are you using a CDN? Then enter the URL here. Leave empty when using CloudFlare.", $this->plugin_text_domain),
            '**'
        );
    }

    /**
     *
     */
    public function do_webfont_loader()
    {
        $this->do_checkbox(
            __('Use Web Font Loader?', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_WEB_FONT_LOADER,
            OMGF_WEB_FONT_LOADER,
            __('Use Typekit\'s Web Font Loader to load fonts asynchronously. <strong>Caution:</strong> while this might raise your Pagespeed Score, it could temporarily cause fonts to be displayed unstyled.', $this->plugin_text_domain)
        );
    }

    /**
     *
     */
    public function do_remove_version()
    {
        $this->do_checkbox(
            __('Remove version parameter?', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_REMOVE_VERSION,
            OMGF_REMOVE_VERSION,
            __('This removes the <code>?ver=x.x.x</code> parameter from the Stylesheet\'s (<code>fonts.css</code>) request. ', $this->plugin_text_domain)
        );
    }

    /**
     *
     */
    public function do_enqueue_order()
    {
        $this->do_number(
            __('Change enqueue order of stylesheet? (experimental)', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_ENQUEUE_ORDER,
            OMGF_ENQUEUE_ORDER,
            __('Lower this value if the generated stylesheet (<code>fonts.css</code>) is not captured by your CSS minification/combining plugin. Doesn\'t work with Web Font Loader enabled.', $this->plugin_text_domain),
            0
        );
    }

    /**
     *
     */
    public function do_optimize_edit_roles()
    {
        $this->do_checkbox(
            __('Optimize fonts for logged in editors/administrators?', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_OPTIMIZE_EDIT_ROLES,
            OMGF_OPTIMIZE_EDIT_ROLES,
            __('Should only be used for debugging/testing purposes. Leave disabled when e.g. using a page builder or switching themes.', $this->plugin_text_domain)
        );
    }

    /**
     *
     */
    public function do_uninstall()
    {
        $this->do_checkbox(
            __('Remove settings and files at uninstall?', $this->plugin_text_domain),
            OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL,
            OMGF_UNINSTALL,
            __('Warning! This will remove all settings and cached fonts upon plugin deletion.', $this->plugin_text_domain)
        );
    }
}
