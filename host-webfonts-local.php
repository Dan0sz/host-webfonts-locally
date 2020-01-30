<?php
/**
 * @formatter:off
 * Plugin Name: OMGF
 * Plugin URI: https://daan.dev/wordpress-plugins/host-google-fonts-locally
 * Description: Minimize DNS requests and leverage browser cache by easily saving Google Fonts to your server and removing the external Google Fonts.
 * Version: 2.5.0
 * Author: Daan van den Bergh
 * Author URI: https://daan.dev
 * License: GPL2v2 or later
 * Text Domain: host-webfonts-local
 * @formatter:on
 */

defined('ABSPATH') || exit;

/**
 * Define constants.
 */
define('OMGF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OMGF_PLUGIN_FILE', __FILE__);
define('OMGF_DB_VERSION', '2.5.0');
define('OMGF_STATIC_VERSION', '2.5.0');
define('OMGF_WEB_FONT_LOADER_VERSION', '1.6.26');

/**
 * Takes care of loading classes on demand.
 *
 * @param $class
 *
 * @return mixed|void
 */
function omgf_autoload($class)
{
    $path = explode('_', $class);

    if ($path[0] != 'OMGF') {
        return;
    }

    $filename = '';

    if (count($path) == 1) {
        $filename = 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';
    } elseif (count($path) == 2) {
        array_shift($path);
        $filename = 'class-' . strtolower($path[0]) . '.php';
    } else {
        array_shift($path);
        end($path);
        $i = 0;

        while ($i < key($path)) {
            $filename .= strtolower($path[$i]) . '/';
            $i++;
        }

        $filename .= 'class-' . strtolower($path[$i]) . '.php';
    }

    return include OMGF_PLUGIN_DIR . 'includes/' . $filename;
}

spl_autoload_register('omgf_autoload');

/**
 * All systems GO!!!
 *
 * @return OMGF
 */
function omgf_init()
{
    static $omgf = null;

    if ($omgf === null) {
        $omgf = new OMGF();
    }

    return $omgf;
}

omgf_init();
