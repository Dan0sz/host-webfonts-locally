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

class OMGF_Setup
{
    /** @var QM_DB $wpdb */
    private $wpdb;

    /**
     * OMGF_Admin_Setup constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        $this->run_db_updates();
    }

    /**
     * Run initial database updates.
     */
    public function run_db_updates()
    {
        $currentVersion = get_option(OMGF_Admin_Settings::OMGF_SETTING_DB_VERSION) ?: get_option('caos_webfonts_db_version') ?: '1.0.0';
        if (version_compare($currentVersion, '1.6.1') < 0) {
            $this->create_webfonts_table();
        }
        if (version_compare($currentVersion, '1.7.0') < 0) {
            $this->create_subsets_table();
        }
        if (version_compare($currentVersion, '1.8.3') < 0) {
            $this->add_local_column();
        }
        if (version_compare($currentVersion, OMGF_DB_VERSION) < 0) {
            $this->rename_tables();
            $this->migrate_options();
        }
    }

    /**
     * Create the table where downloaded webfonts are registered.
     */
    private function create_webfonts_table()
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . OMGF_DB_TABLENAME . " (
            font_id varchar(191) NOT NULL,
            font_family varchar(191) NOT NULL,
            font_weight mediumint(5) NOT NULL,
            font_style varchar(191) NOT NULL,
            downloaded tinyint(1) DEFAULT 0,
            url_ttf varchar(191) NULL,
            url_woff varchar(191) NULL,
            url_woff2 varchar(191) NULL,
            url_eot varchar(191) NULL,
            UNIQUE KEY (font_id)
            ) " . OMGF_DB_CHARSET . ";";
        $this->wpdb->query($sql);

        $this->set_db_version('1.6.1');
    }

    /**
     * Creates the subsets table.
     */
    private function create_subsets_table()
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . OMGF_DB_TABLENAME . '_subsets' . " (
            subset_font varchar(32) NOT NULL,
            subset_family varchar(191) NOT NULL,
            available_subsets varchar(191) NOT NULL,
            selected_subsets varchar(191) NOT NULL,
            UNIQUE KEY (subset_font)
            ) " . OMGF_DB_CHARSET . ";";
        $this->wpdb->query($sql);

        $this->set_db_version('1.7.0');
    }

    /**
     * Adds 'local' column.
     */
    private function add_local_column()
    {
        $sql = "ALTER TABLE " . OMGF_DB_TABLENAME . " " .
               "ADD COLUMN local varchar(128) AFTER font_style;";
        $this->wpdb->query($sql);

        $this->set_db_version('1.8.3');
    }

    /**
     * Delete options with old plugin names and migrate them to new names.
     */
    private function migrate_options()
    {
        $table          = $this->wpdb->prefix . 'options';
        $sql            = "SELECT * FROM $table WHERE option_name LIKE '%caos_webfonts%'";
        $legacy_options = $this->wpdb->get_results($sql);

        foreach ($legacy_options as &$option) {
            $legacy_name = $option->option_name;
            $option->option_name = str_replace('caos_webfonts', 'omgf', $legacy_name);
            add_option($option->option_name, $option->option_value);
            delete_option($legacy_name);
        }

        $this->set_db_version('2.2.2');
    }

    /**
     * Rename tables using OMGF_DB_TABLENAME.
     */
    private function rename_tables()
    {
        $table       = $this->wpdb->prefix . 'caos_webfonts';
        $subsets     = $table . '_subsets';
        $sql         = "ALTER TABLE $table RENAME TO " . OMGF_DB_TABLENAME;
        $sql_subsets = "ALTER TABLE $subsets RENAME TO " . OMGF_DB_TABLENAME . '_subsets';

        $this->wpdb->query($sql);
        $this->wpdb->query($sql_subsets);

        $this->set_db_version('2.2.2');
    }

    /**
     * @param $value
     */
    private function set_db_version($value)
    {
        update_option(OMGF_Admin_Settings::OMGF_SETTING_DB_VERSION, $value);
    }
}
