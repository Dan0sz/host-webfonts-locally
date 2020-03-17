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

class OMGF_DB
{
    /** @var QM_DB $wpdb */
    private $wpdb;

    /**
     * OMGF_DB constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }

    /**
     * @return array
     */
    public function get_download_status()
    {
        return array(
            "downloaded" => count($this->get_downloaded_fonts()),
            "total"      => count($this->get_total_fonts())
        );
    }

    /**
     * @return array|\Exception
     */
    public function get_downloaded_fonts()
    {
        try {
            return $this->wpdb->get_results("SELECT * FROM " . OMGF_DB_TABLENAME . " WHERE downloaded = 1");
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @return array|Exception|object|null
     */
    public function get_preload_fonts()
    {
        try {
            return $this->wpdb->get_results("SELECT * FROM " . OMGF_DB_TABLENAME . " WHERE preload = 1");
        } catch(\Exception $e) {
            return $e;
        }
    }

    /**
     * @return array|\Exception
     */
    public function get_total_fonts()
    {
        try {
            return get_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS);
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @return array|\Exception|null|object
     */
    public function get_subsets()
    {
        try {
            return get_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS);
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @param $family
     *
     * @return array|Exception|object|null
     */
    public function get_fonts_by_family($family)
    {
        try {
            return $this->wpdb->get_results("SELECT * FROM " . OMGF_DB_TABLENAME . " WHERE font_family = '$family'");
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * @return Exception|void
     */
    public function clean_queue()
    {
        try {
            $this->wpdb->query("TRUNCATE TABLE " . OMGF_DB_TABLENAME);
            $this->wpdb->query("TRUNCATE TABLE " . OMGF_DB_TABLENAME . "_subsets");
        } catch (\Exception $e) {
            return $e;
        }
    }
}
