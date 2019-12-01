<?php
/**
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 */

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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_stylesheet'));
        add_action('wp_print_styles', array($this, 'is_remove_google_fonts_enabled'), 100);

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
            add_action('wp_print_styles', array($this, 'remove_google_fonts'), PHP_INT_MAX);
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
            add_action('wp_head', array($this, 'add_link_preload'), 1);
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

        $registered   = $wp_styles->registered;
        $fonts        = array_filter(
            $registered, function ($contents) {
            return strpos($contents->src, 'fonts.googleapis.com') !== false
                   || strpos($contents->src, 'fonts.gstatic.com') !== false;
            }
        );
        $dependencies = array_filter(
            $registered, function ($contents) use ($fonts) {
            return !empty(array_intersect(array_keys($fonts), $contents->deps));
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

    /**
     * Prioritize the loading of fonts by adding a resource hint to the document head.
     *
     * Does not work with Web Font Loader enabled.
     */
    public function add_link_preload()
    {
        global $wp_styles;

        $style  = $wp_styles->registered[self::OMGF_STYLE_HANDLE];

        /** Do not add 'preload' if Minification plugins are enabled. */
        if ($style) {
            $source = $style->src . ($style->ver ? "?ver={$style->ver}" : "");
            echo "<link rel='preload' href='{$source}' as='style' />\n";
        }
    }
}
