<?php
/**
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 */

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

        // @formatter:off
        add_action('plugins_loaded', array($this, 'run_db_updates'));
        register_deactivation_hook(OMGF_PLUGIN_FILE, array($this, 'dequeue_css_js'));
        // @formatter:on
    }

    /**
     * Run initial database updates.
     */
    public function run_db_updates()
    {
        $currentVersion = get_option('caos_webfonts_db_version') ?: '1.0.0';
        if (version_compare($currentVersion, '1.6.1') < 0) {
            $this->create_webfonts_table();
        }
        if (version_compare($currentVersion, '1.7.0') < 0) {
            $this->create_subsets_table();
        }
        if (version_compare($currentVersion, OMGF_DB_VERSION) < 0) {
            $this->add_local_column();
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

        add_option('caos_webfonts_db_version', '1.6.1');
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

        update_option('caos_webfonts_db_version', '1.7.0');
    }

    /**
     * Adds 'local' column.
     */
    private function add_local_column()
    {
        $sql = "ALTER TABLE " . OMGF_DB_TABLENAME . " " .
               "ADD COLUMN local varchar(128) AFTER font_style;";
        $this->wpdb->query($sql);

        update_option('caos_webfonts_db_version', '1.8.3');
    }

    /**
     * Removes all static files upon plugin deactivation.
     */
    public function dequeue_css_js()
    {
        wp_dequeue_script(OMGF_Admin::OMGF_ADMIN_JS_HANDLE);
        wp_dequeue_style(OMGF_Admin::OMGF_ADMIN_CSS_HANDLE);
        wp_dequeue_style(OMGF_Frontend_Functions::OMGF_STYLE_HANDLE);
    }
}
