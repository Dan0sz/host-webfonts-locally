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

class OMGF_Admin_Settings_Extensions extends OMGF_Admin_Settings_Builder
{
    /**
     * OMGF_Admin_Settings_Advanced constructor.
     */
    public function __construct()
    {
        $this->title = __('Extensions', $this->plugin_text_domain);

        // Open
        // @formatter:off
        add_filter('omgf_extensions_settings_content', [$this, 'do_title'], 10);
        add_filter('omgf_extensions_settings_content', [$this, 'do_description'], 15);
        add_filter('omgf_extensions_settings_content', [$this, 'do_before'], 20);

        // Settings
        add_filter('omgf_extensions_settings_content', [$this, 'do_promo_remove_stylesheets'], 30);
        add_filter('omgf_extensions_settings_content', [$this, 'do_promo_remove_inline_styles'], 40);
        add_filter('omgf_extensions_settings_content', [$this, 'do_promo_remove_webfont_loader'], 50);
        add_filter('omgf_extensions_settings_content', [$this, 'do_promo_remove_preconnect'], 60);

        // Close
        add_filter('omgf_extensions_settings_content', [$this, 'do_after'], 100);
        // @formatter:on
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

    /**
     * Add (overridable) promo options for Remove Stylesheets Pro.
     */
    public function do_promo_remove_stylesheets()
    {
        $this->do_checkbox(
                __('Remove Stylesheets (Pro)', $this->plugin_text_domain),
                'omgf_pro_remove_stylesheets',
                defined('OMGF_PRO_REMOVE_STYLESHEETS') ? OMGF_PRO_REMOVE_STYLESHEETS : false,
                sprintf(__('Remove all stylesheets loaded from <code>fonts.googleapis.com</code> or <code>fonts.gstatic.com</code>. <a href="%s" target="_blank">Purchase OMGF Pro</a> to enable this option.', $this->plugin_text_domain), OMGF_Admin_Settings_Builder::WOOSH_WORDPRESS_PLUGINS_HOST_GOOGLE_FONTS_PRO),
                false,
                true
        );
    }

    /**
     *
     */
    public function do_promo_remove_inline_styles()
    {
        $this->do_checkbox(
            __('Remove Inline Styles (Pro)', $this->plugin_text_domain),
            'omgf_pro_remove_inline_styles',
            defined('OMGF_PRO_REMOVE_INLINE_STYLES') ? OMGF_PRO_REMOVE_INLINE_STYLES : false,
            sprintf(__('Remove all <code>@font-face</code> and <code>@import</code> rules loading Google Fonts. <a href="%s" target="_blank">Purchase OMGF Pro</a> to enable this option.', $this->plugin_text_domain), OMGF_Admin_Settings_Builder::WOOSH_WORDPRESS_PLUGINS_HOST_GOOGLE_FONTS_PRO),
            false,
            true
        );
    }

    /**
     *
     */
    public function do_promo_remove_webfont_loader()
    {
        $this->do_checkbox(
            __('Remove WebFont Loader (Pro)', $this->plugin_text_domain),
            'omgf_pro_remove_inline_styles',
            defined('OMGF_PRO_REMOVE_WEBFONT_LOADER') ? OMGF_PRO_REMOVE_WEBFONT_LOADER : false,
            sprintf(__('Remove any WebFont Loader (<code>webfont.js</code>) libraries and the corresponding configuration defining which Google Fonts to load (WebFont Config). <a href="%s" target="_blank">Purchase OMGF Pro</a> to enable this option.', $this->plugin_text_domain), OMGF_Admin_Settings_Builder::WOOSH_WORDPRESS_PLUGINS_HOST_GOOGLE_FONTS_PRO),
            false,
            true
        );
    }

    /**
     *
     */
    public function do_promo_remove_preconnect()
    {
        $this->do_checkbox(
            __('Remove Preconnects (Pro)', $this->plugin_text_domain),
            'omgf_pro_remove_preconnect',
            defined('OMGF_PRO_REMOVE_PRECONNECT') ? OMGF_PRO_REMOVE_PRECONNECT : false,
            sprintf(__('Remove all <code>link</code> elements with a <code>rel</code> attribute value of <code>dns-prefetch</code>, <code>preload</code> or <code>preconnect</code>. <a href="%s" target="_blank">Purchase OMGF Pro</a> to enable this option.', $this->plugin_text_domain), OMGF_Admin_Settings_Builder::WOOSH_WORDPRESS_PLUGINS_HOST_GOOGLE_FONTS_PRO),
            false,
            true
        );
    }
}
