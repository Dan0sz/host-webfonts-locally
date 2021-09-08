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

class OMGF_Optimize
{
    /** @var string */
    private $settings_page = '';

    /** @var string */
    private $settings_tab = '';

    /** @var bool */
    private $settings_updated = false;

    /**
     * OMGF_Optimize constructor.
     */
    public function __construct()
    {
        $this->settings_page    = $_GET['page'] ?? '';
        $this->settings_tab     = $_GET['tab'] ?? OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_OPTIMIZE;
        $this->settings_updated = isset($_GET['settings-updated']);

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

        $optimization_mode = apply_filters('omgf_optimization_mode', OMGF_OPTIMIZATION_MODE);

        if ('manual' == $optimization_mode) {
            $this->run_manual();
        }
    }

    /**
     * If this site is non-SSL it makes no sense to verify its SSL certificates.
     *
     * Settings sslverify to false will set CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST
     * to 0 further down the road.
     *
     * @param mixed $url
     * @return array
     */
    public function verify_ssl($args)
    {
        $args['sslverify'] = strpos(home_url(), 'https:') !== false;

        return $args;
    }

    /**
     * Run Manual mode.
     * 
     * @return void 
     */
    private function run_manual()
    {
        new OMGF_OptimizationMode_Manual();
    }
}
