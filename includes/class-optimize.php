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
 * @copyright: (c) 2021 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Optimize
{
    /** @var string */
    private $plugin_text_domain = 'host-webfonts-local';

    /** @var string */
    private $settings_page = '';

    /** @var string */
    private $settings_tab = '';

    /** @var string */
    private $settings_updated = '';

    /**
     * OMGF_Optimize constructor.
     */
    public function __construct()
    {
        $this->settings_page    = $_GET['page'] ?? '';
        $this->settings_tab     = $_GET['tab'] ?? OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_OPTIMIZE;
        $this->settings_updated = $_GET['settings-updated'] ?? '';

        $this->init();
    }

    /**
     * Run either manual or auto mode after settings are updated.
     * 
     * @return void 
     */
    private function init()
    {
        if (OMGF_Admin_Settings::OMGF_ADMIN_PAGE != $this->settings_page) {
            return;
        }

        if (OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_OPTIMIZE != $this->settings_tab) {
            return;
        }

        if (!$this->settings_updated) {
            return;
        }

        add_filter('http_request_args', [$this, 'verify_ssl']);

        if ('manual' == OMGF_OPTIMIZATION_MODE) {
            $this->run_manual();
        }

        if ('auto' == OMGF_OPTIMIZATION_MODE) {
            $this->run_auto();
        }
    }

    /**
     * If this site is non-SSL it makes no sense to verify its SSL certificates.
     *
     * Settings sslverify to false will set CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST
     * to 0 further down the road.
     *
     * @param mixed $url
     * @return bool
     */
    public function verify_ssl($args)
    {
        $args['sslverify'] = strpos(home_url(), 'https:') !== false;

        return $args;
    }

    /**
     * @return void
     */
    private function run_manual()
    {
        $url = esc_url_raw(OMGF_MANUAL_OPTIMIZE_URL);

        $front_html = $this->remote_get($url);

        if (is_wp_error($front_html)) {
            $this->frontend_fetch_failed($front_html);
        }

        $urls     = [];
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        @$document->loadHtml(wp_remote_retrieve_body($front_html));

        foreach ($document->getElementsByTagName('link') as $link) {
            /** @var $link DOMElement */
            if ($link->hasAttribute('href') && strpos($link->getAttribute('href'), '/omgf/v1/download/')) {
                $urls[] = $link->getAttribute('href');
            }
        }

        if (empty($urls)) {
            $this->no_urls_found();
        }

        foreach ($urls as $url) {
            $download = $this->remote_get($url);

            if (is_wp_error($download)) {
                $this->download_failed($download);
            }
        }

        $this->optimization_succeeded();
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
     * @param $download WP_Error
     */
    private function download_failed($download)
    {
        add_settings_error('general', 'omgf_download_failed', __('OMGF encountered an error while downloading Google Fonts', $this->plugin_text_domain) . ': ' . $download->get_error_code() . ' - ' . $download->get_error_message(), 'error');
    }

    /**
     * @param $front_html WP_Error
     */
    private function frontend_fetch_failed($front_html)
    {
        add_settings_error('general', 'omgf_frontend_fetch_failed', __('OMGF encountered an error while fetching this site\'s frontend HTML', $this->plugin_text_domain) . ': ' . $front_html->get_error_code() . ' - ' . $front_html->get_error_message(), 'error');
    }

    /**
     * @return void
     */
    private function no_urls_found()
    {
        add_settings_error('general', 'omgf_no_urls_found', sprintf(__('No (additional) Google Fonts found to optimize. If you believe this is an error, please refer to the %stroubleshooting%s section of the documentation for possible solutions.', $this->plugin_text_domain), '<a href="https://ffw.press/docs/omgf-pro/troubleshooting">', '</a>'), 'info');
    }

    /**
     *
     */
    private function run_auto()
    {
        OMGF_Admin_Notice::set_notice(
            __('OMGF Optimization is silently running in the background. After visiting a few pages, return here to manage the captured Google Fonts.', $this->plugin_text_domain),
            'omgf-auto-running',
            false
        );
    }

    /**
     * Wrapper for wp_remote_get() with preset params.
     *
     * @param mixed $url
     * @return array|WP_Error
     */
    private function remote_get($url)
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
}
