<?php
/**
 * Plugin Name: OMGF
 * Plugin URI: https://daan.dev/wordpress/omgf/
 * Description: Increase GDPR/DSGVO compliance and leverage browser cache by automatically self-hosting Google Fonts.
 * Version: 6.0.0
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev
 * Text Domain: host-webfonts-local
 * License: GPL2v2 or later
 */

defined( 'ABSPATH' ) || exit;

/**
 * Define constants.
 */
define( 'OMGF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OMGF_PLUGIN_FILE', __FILE__ );
define( 'OMGF_PLUGIN_BASENAME', plugin_basename( OMGF_PLUGIN_FILE ) );
define( 'OMGF_DB_VERSION', '6.0.0' );

/**
 * Takes care of loading classes on demand.
 */
require_once OMGF_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * All systems GO!!!
 */
$omgf = new OMGF\Plugin();
