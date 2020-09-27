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
        add_filter('omgf_advanced_settings_content', [$this, 'do_title'], 10);
        add_filter('omgf_advanced_settings_content', [$this, 'do_description'], 15);
        add_filter('omgf_advanced_settings_content', [$this, 'do_before'], 20);

        // Settings
        add_filter('omgf_advanced_settings_content', [$this, 'do_force_subsets'], 25);
	    add_filter('omgf_advanced_settings_content', [$this, 'do_cdn_url'], 30);
	    add_filter('omgf_advanced_settings_content', [$this, 'do_cache_uri'], 40);
	    add_filter('omgf_advanced_settings_content', [$this, 'do_relative_url'], 50);
	    add_filter('omgf_advanced_settings_content', [$this, 'do_webfont_loader'], 60);
	    add_filter('omgf_advanced_settings_content', [$this, 'do_force_ssl'], 70);
        add_filter('omgf_advanced_settings_content', [$this, 'do_remove_version'], 80);
        add_filter('omgf_advanced_settings_content', [$this, 'do_uninstall'], 90);

        // Close
        add_filter('omgf_advanced_settings_content', [$this, 'do_after'], 100);
    }

    /**
     * Description
     */
    public function do_description()
    {
        ?>
        <p>
        </p>
        <?php
    }
    
    public function do_force_subsets()
    {
        $this->do_select(
            __('Force Subsets', $this->plugin_text_domain),
            'omgf_pro_force_subsets',
            OMGF_Admin_Settings::OMGF_FORCE_SUBSETS_OPTIONS,
            defined( 'OMGF_PRO_FORCE_SUBSETS' ) ? OMGF_PRO_FORCE_SUBSETS : false,
            __('', $this->plugin_text_domain),
            true,
            true
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
            __('The relative path to serve font files from. Useful for when you\'re using security through obscurity plugins, such as WP Hide. If left empty, the cache directory specified above will be used.', $this->plugin_text_domain)
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
            __('Some plugins mess up WordPress\' URL structure, which can cause OMGF to generate incorrect URLs in the stylesheet. If OMGF is generating non-SSL (<code>http://...</code>) URLs in the stylesheet, and you have the <strong>Site</strong> and <strong>WordPress Address</strong> (in <strong>Settings</strong> > <strong>General</strong>) set to SSL (<code>https://</code>), then enable this option.', $this->plugin_text_domain)
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
            __('Use relative instead of absolute (full) URLs to generate the stylesheet. <strong>Warning!</strong> This disables the CDN URL!', $this->plugin_text_domain)
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
            __("Are you using a CDN? Then enter the URL here. Leave empty when using CloudFlare.", $this->plugin_text_domain)
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
