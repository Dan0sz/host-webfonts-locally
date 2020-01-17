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
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Frontend_Functions
{
    const OMGF_STYLE_HANDLE = 'omgf-fonts';

    /**
     * OMGF_Frontend_Functions constructor.
     */
    public function __construct()
    {
        // @formatter:off
        add_action('wp_print_styles', array($this, 'is_remove_google_fonts_enabled'), PHP_INT_MAX - 1000);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_stylesheet'), OMGF_ENQUEUE_ORDER);

        if (OMGF_AUTO_DETECT_ENABLED) {
            add_action('wp_print_styles', array($this, 'auto_detect_fonts'), PHP_INT_MAX - 10000);
        }

        if (!OMGF_WEB_FONT_LOADER) {
            add_action('init', array($this, 'is_preload_enabled'));
        }
        // @formatter:on
    }

    /**
     * Once the stylesheet is generated. We can enqueue it.
     */
    public function enqueue_stylesheet()
    {
        if (OMGF_WEB_FONT_LOADER) {
            $this->get_template('web-font-loader');
        } else {
            wp_enqueue_style(self::OMGF_STYLE_HANDLE, OMGF_UPLOAD_URL . '/' . OMGF_FILENAME, array(), (OMGF_REMOVE_VERSION ? null : OMGF_STATIC_VERSION));
        }
    }

    /**
     * @param $name
     */
    public function get_template($name)
    {
        include OMGF_PLUGIN_DIR . 'templates/frontend-' . $name . '.phtml';
    }

    /**
     * Check if the Remove Google Fonts option is enabled.
     */
    public function is_remove_google_fonts_enabled()
    {
        if (OMGF_REMOVE_GFONTS == 'on' && !is_admin()) {
            // @formatter:off
            add_action('wp_print_styles', array($this, 'remove_google_fonts'), PHP_INT_MAX - 500);
            // Theme: Enfold
            add_filter('avf_output_google_webfonts_script', function() { return false; });
            // @formatter:on
        }
    }

    /**
     * Check if the Preload option is enabled.
     */
    public function is_preload_enabled()
    {
        if (OMGF_PRELOAD == 'on') {
            // @formatter:off
            add_action('wp_head', array($this, 'add_link_preload'), 1);
            // @formatter:on
        }
    }

    /**
     * Automatically dequeues any stylesheets loaded from fonts.gstatic.com or
     * fonts.googleapis.com. Also checks for stylesheets dependant on Google Fonts and
     * re-enqueues and registers them.
     */
    public function remove_google_fonts()
    {
        global $wp_styles;

        $registered = $wp_styles->registered;

        $fonts = $this->detect_registered_google_fonts($registered);

        $dependencies = array_filter(
            $registered, function ($contents) use ($fonts) {
            return !empty(array_intersect(array_keys($fonts), $contents->deps))
                   && $contents->handle !== 'wp-block-editor';
            }
        );

        foreach ($fonts as $font) {
            wp_deregister_style($font->handle);
            wp_dequeue_style($font->handle);
        }

        foreach ($dependencies as $dependency) {
            wp_register_style('omgf-dep-' . $dependency->handle, $dependency->src);
            wp_enqueue_style('omgf-dep-' . $dependency->handle, $dependency->src);
        }
    }

    private function detect_registered_google_fonts($registered_styles)
    {
        return array_filter(
            $registered_styles,
            function ($contents) {
                return strpos($contents->src, 'fonts.googleapis.com') !== false
                       || strpos($contents->src, 'fonts.gstatic.com') !== false;
            }
        );
    }

    /**
     * Prioritize the loading of fonts by adding a resource hint to the document head.
     *
     * Does not work with Web Font Loader enabled.
     */
    public function add_link_preload()
    {
        global $wp_styles;

        $style = isset($wp_styles->registered[self::OMGF_STYLE_HANDLE]) ? $wp_styles->registered[self::OMGF_STYLE_HANDLE] : null;

        /** Do not add 'preload' if Minification plugins are enabled. */
        if ($style) {
            $source = $style->src . ($style->ver ? "?ver={$style->ver}" : "");
            echo "<link rel='preload' href='{$source}' as='style' />\n";
        }
    }

    /**
     * Saves the used Google Fonts in the database, so it can be used by auto-detection.
     */
    public function auto_detect_fonts()
    {
        global $wp_styles;

        $registered = $wp_styles->registered;

        $fonts = $this->detect_registered_google_fonts($registered);

        foreach ($fonts as $font) {
            $google_fonts_src[] = $font->src;
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS, json_encode($google_fonts_src));
    }
}
