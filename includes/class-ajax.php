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
        add_action('wp_ajax_omgf_ajax_auto_detect', array($this, 'auto_detect'));
        add_action('wp_ajax_omgf_ajax_search_google_fonts', array($this, 'search_fonts'));
        add_action('wp_ajax_omgf_ajax_download_fonts', array($this, 'download_fonts'));
        add_action('wp_ajax_omgf_ajax_generate_styles', array($this, 'generate_styles'));
        add_action('wp_ajax_omgf_ajax_preload_font_style', array($this, 'preload_font_style'));
        add_action('wp_ajax_omgf_ajax_refresh_font_style_list', array($this, 'refresh_font_style_list'));
        add_action('wp_ajax_omgf_ajax_get_download_status', array($this, 'get_download_status'));
        add_action('wp_ajax_omgf_ajax_empty_dir', array($this, 'empty_directory'));
        // @formatter:on
    }

    /**
     * Search the API for the requested fonts and return the available subsets.
     */
    public function search_font_subsets()
    {
        $searchQueries = explode(',', sanitize_text_field($_POST['search_query']));

        foreach ($searchQueries as $searchQuery) {
            $request    = wp_remote_get(OMGF_HELPER_URL . $searchQuery)['body'];
            $result     = json_decode($request);
            $response[] = array(
                'subset_family'     => $result->family,
                'subset_font'       => $result->id,
                'available_subsets' => $result->subsets,
                'selected_subsets'  => []
            );
        }
        update_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS, $response);

        OMGF_Admin_Notice::set_notice(__('Subset search complete.', 'host-webfonts-local'));
    }

    /**
     *
     */
    public function auto_detect()
    {
        $used_fonts  = json_decode(get_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS));
        $auto_detect = get_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED);

        if ($used_fonts && $auto_detect) {
            new OMGF_AJAX_Detect($used_fonts);
        }

        if ((!$used_fonts && $auto_detect) || ($used_fonts && !$auto_detect)) {
            update_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS, '');
            update_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED, null);
            OMGF_Admin_Notice::set_notice(__('Something went wrong while trying to enable Auto Detection. <a href="javascript:location.reload()">Refresh this page</a> and try again.', 'host-webfonts-local'), true, 'error', 406);
        }

        $this->enable_auto_detect();

        $url = get_permalink(get_posts()[0]->ID);

        OMGF_Admin_Notice::set_notice(__("Auto-detection mode enabled. Open any page on your frontend (e.g. your <a href='$url' target='_blank'>latest post</a>). After the page is fully loaded, return here and <a href='javascript:location.reload()'>click here</a> to refresh this page. Then click 'Load fonts'.", "host-webfonts-local"));
    }

    /**
     *
     */
    private function enable_auto_detect()
    {
        update_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED, true);
    }

    /**
     * Return the available fonts for the selected subset(s) from the API.
     */
    public function search_fonts()
    {
        $subsets = get_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS);

        foreach ($subsets as $index => &$properties) {
            $i = 0;
            foreach ($properties['available_subsets'] as $subset) {
                if (in_array($subset, $_POST['search_fonts'][$index]['subsets'])) {
                    $properties['selected_subsets'][$i] = $subset;
                    $i++;
                }
            }
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS, $subsets);

        foreach ($_POST['search_google_fonts'] as $font) {
            $selected_subsets  = implode($font['subsets'], ',');
            $request  = wp_remote_get(OMGF_HELPER_URL . $font['family'] . '?subsets=' . $selected_subsets);
            $result   = json_decode($request['body']);

            foreach ($result->variants as $variant) {
                $fonts[] = [
                    'font_id'     => $result->id . '-' . $variant->id,
                    'font_family' => $variant->fontFamily,
                    'font_weight' => $variant->fontWeight,
                    'font_style'  => $variant->fontStyle,
                    'local'       => implode($variant->local, ','),
                    'preload'     => 0,
                    'downloaded'  => 0,
                    'url_ttf'     => $variant->ttf,
                    'url_woff'    => $variant->woff,
                    'url_woff2'   => $variant->woff2,
                    'url_eot'     => $variant->eot
                ];
            }
        }

        if (!empty($_POST['used_styles']) && is_object($result) && $variants = $result->variants) {
            $used_styles['variants'] = array_values($this->process_used_styles($_POST['used_styles'], $variants));

            wp_send_json_success($used_styles);
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $fonts);
        OMGF_Admin_Notice::set_notice(__('font styles found. Remove the ones you don\'t need and click \'Download\'.'));
    }

    /**
     * @param $usedStyles
     * @param $availableStyles
     *
     * @return array
     */
    private function process_used_styles($usedStyles, $availableStyles)
    {
        foreach ($usedStyles as &$style) {
            $fontWeight = preg_replace('/[^0-9]/', '', $style);
            $fontStyle  = preg_replace('/[^a-zA-Z]/', '', $style);

            if ($fontStyle == 'i') {
                $fontStyle = 'italic';
            }

            $style = $fontWeight . $fontStyle;
        }

        return array_filter(
            $availableStyles,
            function ($style) use ($usedStyles) {
                $fontStyle = $style->fontWeight . ($style->fontStyle !== 'normal' ? $style->fontStyle : '');

                return in_array($fontStyle, $usedStyles);
            }
        );
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
    }

    /**
     * Get download status from DB.
     */
    public function get_download_status()
    {
        $status = json_encode($this->db->get_download_status());

        wp_die($status);
    }

    /**
     * Empty cache directory.
     */
    public function empty_directory()
    {
        try {
            delete_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS);
            delete_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED);
            delete_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS);
            delete_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS);

            array_map('unlink', array_filter((array) glob(OMGF_UPLOAD_DIR . '/*')));

            OMGF_Admin_Notice::set_notice(__('Cache directory successfully emptied.', 'host-webfonts-local'));
        } catch (\Exception $e) {
            OMGF_Admin_Notice::set_notice(__('Something went wrong while emptying the cache directory: ', 'host-webfonts-local') . $e->getMessage(), true, 'error', $e->getCode());
        }
    }
}
