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
        add_action('wp_ajax_omgf_ajax_download_fonts', array($this, 'download_fonts'));
        add_action('wp_ajax_omgf_ajax_generate_styles', array($this, 'generate_styles'));
        add_action('wp_ajax_omgf_ajax_get_download_status', array($this, 'get_download_status'));
        add_action('wp_ajax_omgf_ajax_clean_queue', array($this, 'clean_queue'));
        add_action('wp_ajax_omgf_ajax_empty_dir', array($this, 'empty_directory'));
        add_action('wp_ajax_omgf_ajax_search_font_subsets', array($this, 'search_font_subsets'));
        add_action('wp_ajax_omgf_ajax_search_google_fonts', array($this, 'search_fonts'));
        add_action('wp_ajax_omgf_ajax_auto_detect', array($this, 'auto_detect'));
        // @formatter:on
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
     * Get download status from DB.
     */
    public function get_download_status()
    {
        $status = json_encode($this->db->get_download_status());

        wp_die($status);
    }

    /**
     * AJAX-wrapper for hwlCleanQueue()
     */
    public function clean_queue()
    {
        update_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS, '');
        update_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED, '');

        wp_die($this->db->clean_queue());
    }

    /**
     * Empty cache directory.
     *
     * @return array
     */
    public function empty_directory()
    {
        update_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS, '');
        update_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED, '');

        return array_map('unlink', array_filter((array) glob(OMGF_UPLOAD_DIR . '/*')));
    }

    /**
     * Search the API for the requested fonts and return the available subsets.
     */
    public function search_font_subsets()
    {
        try {
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
            OMGF_Admin_Notice::set_notice(__('Subset search finished.', 'host-webfonts-local'), 'success');
            wp_send_json_success();
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Return the available fonts for the selected subset(s) from the API.
     */
    public function search_fonts()
    {
        try {
            foreach ($_POST['search_fonts'] as $font) {
                $subsets  = implode($font['subsets'], ',');
                $request  = wp_remote_get(OMGF_HELPER_URL . $font['family'] . '?subsets=' . $subsets);
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
            wp_send_json_success();
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }
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
            $this->throw_error(406, 'Something went wrong while trying to enable Auto Detection. <a href="javascript:location.reload()">Refresh this page</a> and try again.');
        }

        $this->enable_auto_detect();

        $url = get_permalink(get_posts()[0]->ID);

        wp_send_json_success(__("Auto-detection mode enabled. Open any page on your frontend (e.g. your <a href='$url' target='_blank'>latest post</a>). After the page is fully loaded, return here and <a href='javascript:location.reload()'>click here</a> to refresh this page. Then click 'Load fonts'.", "host-webfonts-local"));
    }

    /**
     *
     */
    private function enable_auto_detect()
    {
        update_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED, true);
    }

    /**
     * @param $code
     * @param $message
     */
    protected function throw_error($code, $message)
    {
        OMGF_Admin_Notice::set_notice($code . ': ' . $message, 'error');
        wp_send_json_error(__($message, 'host-webfonts-local'), (int) $code);
    }
}
