<?php

/**
 * Plugin Name: OMGF
 * Plugin URI: https://daan.dev/wordpress/omgf/
 * Description: Increase GDPR/DSVGO compliance, reduce DNS requests and leverage browser cache by automatically downloading Google Fonts to your server.
 * Version: 5.3.8
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev
 * License: GPL2v2 or later
 * Text Domain: host-webfonts-local
 */

defined('ABSPATH') || exit;

/**
 * Define constants.
 */
define('OMGF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OMGF_PLUGIN_FILE', __FILE__);
define('OMGF_PLUGIN_BASENAME', plugin_basename(OMGF_PLUGIN_FILE));
define('OMGF_STATIC_VERSION', '5.3.8');
define('OMGF_DB_VERSION', '5.3.4');

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

omgf_init();
