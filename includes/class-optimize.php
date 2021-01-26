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

    /** @var mixed|string */
    private $optimization_mode = '';

    /**
     * OMGF_Optimize constructor.
     */
    public function __construct()
    {
        $option_page             = $_POST['option_page'] ?? '';
        $this->optimization_mode = $_POST[OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZATION_MODE] ?? '';

        if (
            OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_OPTIMIZE != $option_page
            && !$this->optimization_mode
        ) {
            return;
        }

        // Will die when it fails.
        check_admin_referer('omgf-optimize-settings-options');

        update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, $_POST[OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS] ?? '');
        update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, $_POST[OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS] ?? '');
        update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS, $_POST[OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS] ?? '');
        update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, $_POST[OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS] ?? '');

        if ('manual' == $this->optimization_mode) {
            $this->run_manual();
        }

        if ('auto' == $this->optimization_mode) {
            $this->run_auto();
        }
    }

    private function run_manual()
    {
        $url = esc_url_raw($_POST[OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL]);

        $front_html = wp_remote_get(
            $this->no_cache_optimize_url($url),
            [
                'timeout' => 30,
            ]
        );

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
            $download = wp_remote_get(
                $this->no_cache_optimize_url($url),
                [
                    'timeout' => 30
                ]
            );

            if (is_wp_error($download)) {
                $this->download_failed($download);
            }
        }

        $this->optimization_succeeded();
    }

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

    private function no_urls_found()
    {
        add_settings_error('general', 'omgf_no_urls_found', sprintf(__('No (additional) Google Fonts found to optimize. If you believe this is an error, please refer to the %stroubleshooting%s section of the documentation for possible solutions.', $this->plugin_text_domain), '<a href="https://ffw.press/docs/omgf-pro/troubleshooting">', '</a>'), 'info');
    }

    private function run_auto()
    {
        OMGF_Admin_Notice::set_notice(
            __('OMGF Optimization is silently running in the background. After visiting a few pages, return here to manage the captured Google Fonts.', $this->plugin_text_domain),
            'omgf-auto-running',
            false
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
