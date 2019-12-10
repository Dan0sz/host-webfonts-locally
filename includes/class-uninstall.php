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

        $this->wpdb     = $wpdb;
        $this->options  = $this->get_options();
        $this->table    = OMGF_DB_TABLENAME;
        $this->cacheDir = OMGF_UPLOAD_DIR;

        $this->remove_db_entries();
        $this->drop_tables();
        $this->delete_files();
        $this->delete_dir();
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    private function get_options()
    {
        $settings       = new OMGF_Admin_Settings();
        $reflection     = new ReflectionClass($settings);
        $constants      = $reflection->getConstants();

        return array_filter(
            $constants,
            function ($key) {
                return strpos($key, 'OMGF_SETTING') !== false;
            },
            ARRAY_FILTER_USE_KEY
        );
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
