<?php
defined('ABSPATH') || exit;

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
 * @copyright: (c) 2021 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

class OMGF_OptimizationMode_Manual
{
    /** @var string */
    private $plugin_text_domain = 'host-webfonts-local';

    /**
     * Build class.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->run();
    }

    /**
     * Run Manual mode.
     * 
     * @return void 
     */
    private function run()
    {
        $url        = esc_url_raw(OMGF_MANUAL_OPTIMIZE_URL);
        $front_html = $this->get_front_html($url);
        $error      = false;

        if (is_wp_error($front_html) || wp_remote_retrieve_response_code($front_html) != 200) {
            $this->frontend_fetch_failed($front_html);

            $error = true;
        }

        $api_request_urls = [];
        $document         = new DOMDocument();

        libxml_use_internal_errors(true);
        @$document->loadHtml(wp_remote_retrieve_body($front_html));

        foreach ($document->getElementsByTagName('link') as $link) {
            /** @var DOMElement $link */
            if ($link->hasAttribute('href') && strpos($link->getAttribute('href'), '/omgf/v1/download/')) {
                $api_request_urls[] = $link->getAttribute('href');
            }
        }

        if (empty($api_request_urls)) {
            $this->no_urls_found();

            $error = true;
        }

        foreach ($api_request_urls as $url) {
            if (strpos($url, 'css2') !== false) {
                $url = $this->convert($url);
            }

            $download = $this->do_rest_request($url);

            /** @var WP_REST_Response $download */
            if ($download->is_error()) {
                $this->download_failed($download);

                $error = true;
            }
        }

        if (!$error) {
            $this->optimization_succeeded();
        }
    }


    /**
     * Converts requests to Google's Variable Fonts (CSS2) API to the 'old' (CSS) API.
     *
     * @param string $css2_url
     */
    private function convert($css2_url)
    {
        $parsed_url    = parse_url($css2_url);
        $query         = $parsed_url['query'];
        parse_str($query, $params);
        $raw_params    = explode('&', $query);
        $font_families = [];
        $fonts         = [];

        foreach ($raw_params as $param) {
            if (strpos($param, 'family') === false) {
                continue;
            }

            parse_str($param, $parts);

            $font_families[] = $parts['family'];
        }

        if (empty($font_families)) {
            return $css2_url;
        }

        foreach ($font_families as $font_family) {
            if (strpos($font_family, ':') !== false) {
                list($family, $weights) = explode(':', $font_family);
            } else {
                $family  = $font_family;
                $weights = '';
            }

            /**
             * @return array [ '300', '400', '500', etc. ]
             */
            $weights = explode(';', substr($weights, strpos($weights, '@') + 1));

            foreach ($weights as &$weight) {
                $properties = explode(',', $weight);
                $weight     = $properties[0] == '1' ? $properties[1] . 'italic' : $properties[1];
            }

            $fonts[] = $family . ':' . implode(',', $weights);
        }

        $params['family'] = implode('|', $fonts);

        return $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'] . '?' . http_build_query($params);
    }

    /**
     * @param string $url
     * 
     * @return WP_REST_Response
     */
    private function do_rest_request($url)
    {
        $parsed_url = parse_url($url);
        $request    = new WP_REST_Request('GET', str_replace('/wp-json', '', $parsed_url['path']));

        parse_str($parsed_url['query'], $query_params);

        if (isset($query_params['_wpnonce'])) {
            unset($query_params['_wpnonce']);
        }

        $request->set_query_params($query_params);

        // TODO: Find out proper WP way to add this param to request.
        $_REQUEST['_wpnonce'] = wp_create_nonce('wp_rest');

        return rest_do_request($request);
    }

    /**
     * @return void
     */
    private function optimization_succeeded()
    {
        add_settings_error('general', 'omgf_optimization_success', __('Optimization completed successfully.'), 'success');

        OMGF_Admin_Notice::set_notice(
            __('If you\'re using any 3rd party optimization plugins (e.g. WP Rocket, Autoptimize, W3 Total Cache, etc.) make sure to flush their caches for OMGF\'s optimizations to take effect.', $this->plugin_text_domain),
            'omgf-cache-notice',
            false,
            'warning'
        );
    }

    /**
     * @param $response WP_REST_Response
     */
    private function download_failed($response)
    {
        add_settings_error('general', 'omgf_download_failed', __('OMGF encountered an error while downloading Google Fonts', $this->plugin_text_domain) . ': ' . $this->get_error_code($response) . ' - ' . $this->get_error_message($response), 'error');
    }

    /**
     * @param $response WP_Error|array
     */
    private function frontend_fetch_failed($response)
    {
        add_settings_error('general', 'omgf_frontend_fetch_failed', __('OMGF encountered an error while fetching this site\'s frontend HTML', $this->plugin_text_domain) . ': ' . $this->get_error_code($response) . ' - ' . $this->get_error_message($response), 'error');
    }

    /**
     * @return void
     */
    private function no_urls_found()
    {
        add_settings_error('general', 'omgf_no_urls_found', sprintf(__('No (additional) Google Fonts found to optimize. If you believe this is an error, please refer to the %stroubleshooting%s section of the documentation for possible solutions.', $this->plugin_text_domain), '<a href="https://docs.ffw.press/category/37-omgf-pro---troubleshooting">', '</a>'), 'info');
    }

    /**
     * Wrapper for wp_remote_get() with preset params.
     *
     * @param mixed $url
     * @return array|WP_Error
     */
    private function get_front_html($url)
    {
        return wp_remote_get(
            $this->no_cache_optimize_url($url),
            [
                'timeout' => 30
            ]
        );
    }

    /**
     * @param $url
     *
     * @return string
     */
    private function no_cache_optimize_url($url)
    {
        return add_query_arg(['omgf_optimize' => 1, 'nocache' => substr(md5(microtime()), rand(0, 26), 5)], $url);
    }

    /**
     * @param WP_REST_Response|WP_Error|array $response 
     * 
     * @return int|string 
     */
    private function get_error_code($response)
    {
        if ($response instanceof WP_REST_Response && $response->is_error()) {
            // Convert to WP_Error if WP_REST_Response
            $response = $response->as_error();
        }

        if (is_wp_error($response)) {
            return $response->get_error_code();
        }

        return wp_remote_retrieve_response_code($response);
    }

    /**
     * @param WP_REST_Response|WP_Error|array $response 
     * 
     * @return int|string 
     */
    private function get_error_message($response)
    {
        if ($response instanceof WP_REST_Response && $response->is_error()) {
            // Convert to WP_Error if WP_REST_Response
            $response = $response->as_error();
        }

        if (is_wp_error($response)) {
            return $response->get_error_message();
        }

        return wp_remote_retrieve_response_message($response);
    }
}
