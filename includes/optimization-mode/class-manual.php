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
            $download = $this->do_rest_request($url);

            if (isset($download->status) && $download->status != 200) {
                $this->download_failed($download);

                $error = true;
            }
        }

        if (!$error) {
            $this->optimization_succeeded();
        }
    }

    /**
     * 
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
     * @param $response WP_Error|array
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
        add_settings_error('general', 'omgf_no_urls_found', sprintf(__('No (additional) Google Fonts found to optimize. If you believe this is an error, please refer to the %stroubleshooting%s section of the documentation for possible solutions.', $this->plugin_text_domain), '<a href="https://ffw.press/docs/omgf-pro/troubleshooting">', '</a>'), 'info');
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
     * @param array|WP_Error $response 
     * @return int|string 
     */
    private function get_error_code($response)
    {
        if (is_wp_error($response)) {
            return $response->get_error_code();
        }

        return wp_remote_retrieve_response_code($response);
    }

    /**
     * @param array|WP_Error $response 
     * @return int|string 
     */
    private function get_error_message($response)
    {
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }

        return wp_remote_retrieve_response_message($response);
    }
}
