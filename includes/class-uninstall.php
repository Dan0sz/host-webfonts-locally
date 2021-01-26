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

class OMGF_Uninstall
{
    /** @var QM_DB $wpdb */
    private $wpdb;

    /** @var array $options */
    private $options;

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
        $this->cacheDir = OMGF_FONTS_DIR;

        $this->remove_db_entries();
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
