<?php
/**
 * Plugin Name: OMGF
 * Plugin URI: https://daan.dev/wordpress/omgf/
 * Description: Increase GDPR/DSGVO compliance, reduce DNS requests and leverage browser cache by automatically downloading Google Fonts to your server.
 * Version: 5.6.4
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev
 * License: GPL2v2 or later
 * Text Domain: host-webfonts-local
 */

defined( 'ABSPATH' ) || exit;

/**
 * Define constants.
 */
define( 'OMGF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OMGF_PLUGIN_FILE', __FILE__ );
define( 'OMGF_PLUGIN_BASENAME', plugin_basename( OMGF_PLUGIN_FILE ) );
define( 'OMGF_DB_VERSION', '5.6.0' );

/**
 * Takes care of loading classes on demand.
 *
 * @param $class
 *
 * @return mixed|void
 */
require_once OMGF_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * All systems GO!!!
 *
 * @return Plugin
 */
$omgf = new OMGF\Plugin();
