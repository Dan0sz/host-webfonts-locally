<?php
/**
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 */

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
        update_option(OMGF_Admin_Settings::OMGF_DETECTED_FONTS_LABEL, '');
        update_option(OMGF_Admin_Settings::OMGF_AUTO_DETECTION_ENABLED_LABEL, '');

        wp_die($this->db->clean_queue());
    }

    /**
     * Empty cache directory.
     *
     * @return array
     */
    public function empty_directory()
    {
        update_option(OMGF_Admin_Settings::OMGF_DETECTED_FONTS_LABEL, '');
        update_option(OMGF_Admin_Settings::OMGF_AUTO_DETECTION_ENABLED_LABEL, '');

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
                $request = curl_init();
                curl_setopt($request, CURLOPT_URL, OMGF_HELPER_URL . $searchQuery);
                curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
                $result = curl_exec($request);
                curl_close($request);

                $result     = json_decode($result);
                $response[] = array(
                    'family'  => $result->family,
                    'id'      => $result->id,
                    'subsets' => $result->subsets
                );
            }
            wp_die(json_encode($response));
        } catch (\Exception $e) {
            wp_die($e);
        }
    }

    /**
     * Return the available fonts for the selected subset(s) from the API.
     */
    public function search_fonts()
    {
        try {
            $request     = curl_init();
            $searchQuery = sanitize_text_field($_POST['search_query']);
            $subsets     = implode(isset($_POST['search_subsets']) ? $_POST['search_subsets'] : array(), ',');

            curl_setopt($request, CURLOPT_URL, OMGF_HELPER_URL . $searchQuery . '?subsets=' . $subsets);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($request);
            curl_close($request);

            if (!empty($_POST['used_styles'])) {
                $used_styles['variants'] = array_values($this->process_used_styles($_POST['used_styles'], json_decode($result)->variants));

                wp_die(json_encode($used_styles));
            }

            wp_die($result);
        } catch (\Exception $e) {
            wp_die($e);
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
        $used_fonts  = json_decode(get_option(OMGF_Admin_Settings::OMGF_DETECTED_FONTS_LABEL));
        $auto_detect = get_option(OMGF_Admin_Settings::OMGF_AUTO_DETECTION_ENABLED_LABEL);

        if ($used_fonts && $auto_detect) {
            new OMGF_AJAX_Detect($used_fonts);
        }

        $this->enable_auto_detect();

        $url = get_permalink(get_posts()[0]->ID);

        wp_die(__("Auto-detection mode enabled. Open any page on your frontend (e.g. your <a href='$url' target='_blank'>latest post</a>). After the page is fully loaded, return here and <a href='javascript:location.reload()'>click here</a> to refresh this page. Then click 'Load fonts'."));
    }

    /**
     *
     */
    private function enable_auto_detect()
    {
        update_option(OMGF_Admin_Settings::OMGF_AUTO_DETECTION_ENABLED_LABEL, true);
    }

    /**
     * @param $code
     * @param $message
     */
    protected function throw_error($code, $message)
    {
        wp_send_json_error(__($message, 'host-webfonts-local'), (int) $code);
    }
}
