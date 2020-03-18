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

class OMGF_Uninstall
{
    /** @var QM_DB $wpdb */
    private $wpdb;

    /** @var array $options */
    private $options;

    /** @var string $table */
    private $table;

    /** @var string $cacheDir */
    private $cacheDir;

    /**
     * OMGF_Uninstall constructor.
     * @throws ReflectionException
     */
    public function __construct()
    {
        if (OMGF_UNINSTALL !== 'on') {
            return;
        }

        global $wpdb;
        $settings = new OMGF_Admin_Settings();

        $this->wpdb     = $wpdb;
        $this->options  = $settings->get_settings();
        $this->table    = OMGF_DB_TABLENAME;
        $this->cacheDir = OMGF_UPLOAD_DIR;

        $this->remove_db_entries();
        $this->drop_tables();
        $this->delete_files();
        $this->delete_dir();
    }

    /**
     * Remove all settings stored in the wp_options table.
     */
    private function remove_db_entries()
    {
        foreach ($this->options as $key => $option) {
            delete_option($option);
        }
    }

    /**
     * Drop all tables associated with OMGF.
     */
    private function drop_tables()
    {
        $this->wpdb->query(
            'DROP TABLE IF EXISTS ' . $this->table . ', ' . $this->table . '_subsets;'
        );
    }

    /**
     * Delete all files stored in the cache directory.
     *
     * @return array
     */
    private function delete_files()
    {
        return array_map('unlink', glob($this->cacheDir . '/*.*'));
    }

    /**
     * Delete the cache directory.
     *
     * @return bool
     */
    private function delete_dir()
    {
        return rmdir($this->cacheDir);
    }
}
