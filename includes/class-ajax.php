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
        add_action('wp_ajax_omgf_ajax_auto_detect_fonts', array($this, 'auto_detect_fonts'));
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
        wp_die($this->db->clean_queue());
    }

    /**
     * Empty cache directory.
     *
     * @return array
     */
    public function empty_directory()
    {
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
            $subsets     = implode($_POST['search_subsets'], ',');

            curl_setopt($request, CURLOPT_URL, OMGF_HELPER_URL . $searchQuery . '?subsets=' . $subsets);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($request);
            curl_close($request);

            if (!empty($_POST['used_styles'])) {
                $used_styles['variants'] = $this->process_used_styles($_POST['used_styles'], json_decode($result)->variants);

                wp_die(json_encode($used_styles));
            }

            wp_die($result);
        } catch (\Exception $e) {
            wp_die($e);
        }
    }

    private function process_used_styles($usedStyles, $availableStyles)
    {
        foreach ($usedStyles as &$style) {
            $fontWeight = preg_replace('/[^0-9]/', '', $style);
            $fontStyle = preg_replace('/[^a-zA-Z]/', '', $style);

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

    public function auto_detect_fonts()
    {
        global $wp_styles;

        $registered = $wp_styles->registered;
        $used_fonts = array_filter(
            $registered,
            function ($contents) {
                return strpos($contents->src, 'fonts.googleapis.com') !== false
                       || strpos($contents->src, 'fonts.gstatic.com') !== false;
            }
        );

        $font_properties = array();

        foreach ($used_fonts as $handle => $properties) {
            $parts = parse_url($properties->src);
            parse_str($parts['query'], $font_properties[$handle]);
        }

        $i = 0;

        foreach ($font_properties as $handle => $properties) {
            $parts   = explode(':', $properties['family']);
            $subsets = isset($properties['subset']) ? explode(',', $properties['subset']) : null;

            if (!empty($parts)) {
                $font_family = $parts[0];
                $styles      = explode(',', $parts[1]);
            }

            $fonts['subsets'][$i]['family']      = $font_family;
            $fonts['subsets'][$i]['id']          = str_replace(' ', '-', strtolower($font_family));
            $fonts['subsets'][$i]['subsets']     = $subsets;
            $fonts['subsets'][$i]['used_styles'] = $styles;

            $i++;
        }

        $fonts['auto-detect'] = true;

        wp_die(json_encode($fonts));
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
