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
 * @copyright: (c) 2020 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Setup
{
    /** @var QM_DB $wpdb */
    private $wpdb;

    /** @var string $version */
    private $version;

    /** @var string $table */
    private $table;

    /**
     * OMGF_Admin_Setup constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb    = $wpdb;
        $this->version = get_option(OMGF_Admin_Settings::OMGF_SETTING_DB_VERSION);
        $this->table   = OMGF_DB_TABLENAME;

//        if (version_compare($this->version, OMGF_DB_VERSION) < 0) {
            $this->run_db_updates();
//        }
    }

    /**
     * Run initial database updates.
     */
    public function run_db_updates()
    {
        $this->migrate_db();

//        $this->drop_tables();
    }

    private function migrate_db()
    {
        $current_fonts = $this->wpdb->get_results("SELECT * FROM " . OMGF_DB_TABLENAME);

        update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, (array) $current_fonts);

        $current_subsets = $this->wpdb->get_results("SELECT * FROM " . OMGF_DB_TABLENAME . '_subsets');

        update_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS, (array) $current_subsets);
    }

    /**
     * Drop all tables associated with OMGF.
     */
    private function drop_tables()
    {
        $this->wpdb->query(
            'DROP TABLE IF EXISTS ' . $this->table . ', ' . $this->table . '_subsets;'
        );

        $this->set_db_version(OMGF_DB_VERSION);
    }

    /**
     * @param $value
     */
    private function set_db_version($value)
    {
        update_option(OMGF_Admin_Settings::OMGF_SETTING_DB_VERSION, $value);
    }
}
