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

        if (version_compare($this->version, OMGF_DB_VERSION) < 0) {
            $this->run_db_updates();
        }
    }

    /**
     * Run initial database updates.
     */
    public function run_db_updates()
    {
        $this->migrate_db();

        $this->drop_tables();
    }

    private function migrate_db()
    {
        $current_fonts = $this->wpdb->get_results("SELECT * FROM " . OMGF_DB_TABLENAME);
        $fonts_array = json_decode(json_encode($current_fonts), true);

        update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $fonts_array);

        $current_subsets = $this->wpdb->get_results("SELECT * FROM " . OMGF_DB_TABLENAME . '_subsets');
        $subsets_array        = json_decode(json_encode($current_subsets), true);

        foreach ($subsets_array as &$subset) {
            $available = $subset['available_subsets'];
            $selected  = $subset['selected_subsets'];
            $subset['available_subsets'] = strpos($available, ',') !== false ? explode(',', $available) : [ $available ];
            $subset['selected_subsets']  = strpos($selected, ',') !== false ? explode(',', $selected) : [ $selected ];
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS, $subsets_array);
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
