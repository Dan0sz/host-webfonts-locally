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

class OMGF_AJAX
{
    protected $db;

    /**
     * OMGF_AJAX constructor.
     */
    public function __construct()
    {
        $this->db = new OMGF_DB();

        // @formatter:off
        add_action('wp_ajax_omgf_ajax_search_font_subsets', array($this, 'search_font_subsets'));
        add_action('wp_ajax_omgf_ajax_enable_auto_detect', [$this, 'enable_auto_detect']);
        add_action('wp_ajax_omgf_ajax_search_google_fonts', array($this, 'search_fonts'));
        add_action('wp_ajax_omgf_ajax_download_fonts', array($this, 'download_fonts'));
        add_action('wp_ajax_omgf_ajax_generate_styles', array($this, 'generate_styles'));
        add_action('wp_ajax_omgf_ajax_preload_font_style', array($this, 'preload_font_style'));
        add_action('wp_ajax_omgf_ajax_refresh_font_style_list', array($this, 'refresh_font_style_list'));
        add_action('wp_ajax_omgf_ajax_empty_dir', array($this, 'empty_directory'));
        // @formatter:on
    }

    /**
     * Search the API for the requested fonts and return the available subsets.
     */
    public function search_font_subsets()
    {
        delete_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS);
        delete_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS);

        if (!($query = $_POST['search_query'])) {
            OMGF_Admin_Notice::set_notice(__('Search query not found.', 'host-webfonts-local'), true, 'warning');
        }

        $searchQueries = explode(',', sanitize_text_field($query));

        foreach ($searchQueries as $searchQuery) {
            $api = new OMGF_API();

            $subsets = $api->get_subsets($searchQuery);

            // If subset search comes back empty. Add a notice and skip to the next one.
            if (empty($subsets)) {
                OMGF_Admin_Notice::set_notice(sprintf(__('Font %s not found. Are you sure it\'s a Google Font?', 'host-webfonts-local'), $searchQuery), false, 'error');

                continue;
            }

            $response[] = $subsets;
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS, $response);

        OMGF_Admin_Notice::set_notice(__('Subset search complete. Select subsets to generate a list of available font styles.', 'host-webfonts-local'));
    }

    /**
     * Enable Auto Detect
     */
    public function enable_auto_detect()
    {
        update_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED, true);

        $url = get_permalink(get_posts()[0]->ID);

        OMGF_Admin_Notice::set_notice(__("Auto-detection mode enabled. Open any page on your frontend (e.g. your <a href='$url' target='_blank'>latest post</a>). After the page is fully loaded, return here and <a href='javascript:location.reload()'>click here</a> to refresh this page.", "host-webfonts-local"));
    }

    /**
     * Return the available fonts for the selected subset(s) from the API.
     */
    public function search_fonts()
    {
        if (!isset($_POST['search_google_fonts'])) {
            delete_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS);

            OMGF_Admin_Notice::set_notice(__('Search query not found. Did you select any subsets? If so, try again.'), true, 'warning');
        }

        $search_google_fonts = $_POST['search_google_fonts'];

        $subsets = get_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS);

        // Unset subsets which were checked before, but aren't now.
        foreach ($subsets as $index => &$font) {
            if (!in_array($index, array_keys($search_google_fonts))) {
                $font['selected_subsets'] = [];
            }
        }

        // Overwrite current selected subsets in settings with new values.
        $subsets = array_replace_recursive($subsets, $search_google_fonts);

        update_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS, $subsets);

        // Retrieve available font styles.
        foreach ($search_google_fonts as $font) {
            $selected_subsets = implode(',', $font['selected_subsets']);
            $api              = new OMGF_API();
            $fonts[]          = $api->get_font_styles($font['subset_font'], $selected_subsets);
        }

        // Create font styles list.
        $fonts = array_merge(...$fonts);

        update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $fonts);

        OMGF_Admin_Notice::set_notice(count($fonts) . ' ' . __('font styles found. Trim the list to your needs and click <strong>Download Fonts</strong>.', 'host-webfonts-local'));
    }

    /**
     * @return OMGF_AJAX_Download
     */
    public function download_fonts()
    {
        return new OMGF_AJAX_Download();
    }

    /**
     * @return OMGF_AJAX_Generate
     */
    public function generate_styles()
    {
        return new OMGF_AJAX_Generate();
    }

    /**
     * Update options with font styles selected for preloading.
     */
    public function preload_font_style()
    {
        $fonts = get_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS);
        $preload_styles = $_POST['preload_font_styles'];

        foreach ($fonts as &$font) {
            if (in_array($font['font_id'], $preload_styles)) {
                $font['preload'] = 1;
            }
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $fonts);

        OMGF_Admin_Notice::set_notice(count($preload_styles) . ' ' . __('fonts set to preload. If you haven\'t already, you can now <strong>download</strong> the <strong>fonts</strong>. Otherwise, just (re-)<strong>generate</strong> the <strong>stylesheet</strong>.', 'host-webfonts-local'));
    }

    /**
     * Refresh font style list after rows have been removed.
     */
    public function refresh_font_style_list()
    {
        $fonts = get_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS);

        $current = $_POST['font_styles'];

        $refreshed_list = array_filter($fonts, function ($font_style) use ($current) {
            return in_array($font_style['font_id'], $current);
        });

        update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $refreshed_list);

        OMGF_Admin_Notice::set_notice(count($fonts) - count($refreshed_list) . ' ' . __('fonts removed from list. If you haven\'t already, you can now <strong>download</strong> the <strong>fonts</strong>. Otherwise, just (re-)<strong>generate</strong> the <strong>stylesheet</strong>.', 'host-webfonts-local'));
    }

    /**
     * Empty cache directory.
     */
    public function empty_directory()
    {
        try {
            delete_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS);
            delete_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS);

            array_map('unlink', array_filter((array) glob(OMGF_UPLOAD_DIR . '/*')));

            OMGF_Admin_Notice::set_notice(__('Cache directory successfully emptied.', 'host-webfonts-local'));
        } catch (\Exception $e) {
            OMGF_Admin_Notice::set_notice(__('Something went wrong while emptying the cache directory: ', 'host-webfonts-local') . $e->getMessage(), true, 'error', $e->getCode());
        }
    }
}
