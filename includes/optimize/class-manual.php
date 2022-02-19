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
 * @copyright: © 2022 Daan van den Bergh
 * @url      : https://ffw.press
 * * * * * * * * * * * * * * * * * * * */

class OMGF_Optimize_Run
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
     * Does a quick fetch to the site_url to trigger all the action.
     * 
     * @return void 
     */
    private function run()
    {
        $front_html = $this->get_front_html(get_site_url());
        $error      = false;

        if (is_wp_error($front_html) || wp_remote_retrieve_response_code($front_html) != 200) {
            $this->frontend_fetch_failed($front_html);

            $error = true;
        }

        if (!$error) {
            $this->optimization_succeeded();
        }
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
    private function frontend_fetch_failed($response)
    {
        add_settings_error('general', 'omgf_frontend_fetch_failed', __('OMGF encountered an error while fetching this site\'s frontend HTML', $this->plugin_text_domain) . ': ' . $this->get_error_code($response) . ' - ' . $this->get_error_message($response), 'error');
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
