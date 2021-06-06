<?php

/**
 * @formatter:off
 * Plugin Name: OMGF
 * Plugin URI: https://daan.dev/wordpress-plugins/host-google-fonts-locally
 * Description: Minimize DNS requests, leverage browser cache and speed up WordPress by saving Google Fonts to your server and removing external Google Fonts requests.
 * Version: 4.4.3
 * Author: Daan from FFW.Press
 * Author URI: https://ffw.press
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
define('OMGF_PLUGIN_BASENAME', plugin_basename(OMGF_PLUGIN_FILE));
define('OMGF_STATIC_VERSION', '4.4.1');

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

	if (!class_exists('FFWP_Autoloader')) {
		require_once(OMGF_PLUGIN_DIR . 'ffwp-autoload.php');
	}

	$autoload = new FFWP_Autoloader($class);

	return include OMGF_PLUGIN_DIR . 'includes/' . $autoload->load();
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

add_action('plugins_loaded', 'omgf_init', 50);
