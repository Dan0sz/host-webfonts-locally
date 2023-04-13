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
 * @copyright: © 2017 - 2023 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF;

use OMGF\Admin\Settings;
use OMGF\Frontend\Process;
use OMGF\Helper as OMGF;

defined( 'ABSPATH' ) || exit;

class Plugin {
	/**
	 * @var string $log_file Path where log file is located.
	 */
	public static $log_file;

	/**
	 * OMGF constructor.
	 */
	public function __construct() {
		$this->define_constants();

		self::$log_file = trailingslashit( WP_CONTENT_DIR ) . 'omgf-debug.log';

		if ( version_compare( OMGF_CURRENT_DB_VERSION, OMGF_DB_VERSION ) < 0 ) {
			add_action( 'plugins_loaded', [ $this, 'do_migrate_db' ] );
		}

		if ( is_admin() ) {
			add_action( '_admin_menu', [ $this, 'init_admin' ] );

			$this->add_ajax_hooks();
		}

		if ( ! is_admin() ) {
			add_action( 'init', [ $this, 'init_frontend' ], 50 );
		}

		add_action( 'admin_init', [ $this, 'do_optimize' ] );
		add_action( 'admin_init', [ $this, 'update_settings' ] );
		add_filter( 'omgf_optimize_url', [ $this, 'decode_url' ] );
		add_filter( 'content_url', [ $this, 'force_ssl' ], 1000, 2 );
		add_filter( 'home_url', [ $this, 'force_ssl' ], 1000, 2 );
		add_filter( 'pre_update_option_omgf_optimized_fonts', [ $this, 'base64_decode_optimized_fonts' ] );

		/**
		 * Render plugin update messages.
		 */
		add_action( 'in_plugin_update_message-' . OMGF_PLUGIN_BASENAME, [ $this, 'render_update_notice' ], 11, 2 );

		/**
		 * Visual Composer Compatibility Fix
		 */
		add_filter( 'vc_get_vc_grid_data_response', [ $this, 'parse_vc_grid_data' ], 10 );
	}

	/**
	 * Define constants.
	 */
	public function define_constants() {
		/** Prevents undefined constant in OMGF Pro, if its not at version v3.3.0 (yet) */
		define( 'OMGF_OPTIMIZATION_MODE', false );
		define( 'OMGF_SITE_URL', 'https://daan.dev' );
		define( 'OMGF_CACHE_IS_STALE', esc_attr( OMGF::get_option( Settings::OMGF_CACHE_IS_STALE ) ) );
		define( 'OMGF_CURRENT_DB_VERSION', esc_attr( OMGF::get_option( Settings::OMGF_CURRENT_DB_VERSION ) ) );
		define( 'OMGF_UPLOAD_DIR', apply_filters( 'omgf_upload_dir', WP_CONTENT_DIR . '/uploads/omgf' ) );
		define( 'OMGF_UPLOAD_URL', apply_filters( 'omgf_upload_url', str_replace( [ 'http:', 'https:' ], '', WP_CONTENT_URL . '/uploads/omgf' ) ) );
	}

	/**
	 * Run any DB migration scripts if needed.
	 *
	 * @return void
	 */
	public function do_migrate_db() {
		new \OMGF\DB\Migrate();
	}

	/**
	 * Needs to run before admin_menu and admin_init.
	 *
	 * @action _admin_menu
	 */
	public function init_admin() {
		new Settings();
	}

	/**
	 *
	 */
	private function add_ajax_hooks() {
		new Ajax();
	}

	/**
	 *
	 */
	public function init_frontend() {
		new \OMGF\Frontend\Process();
	}

	/**
	 * @since v5.3.3 Decode HTML entities to prevent URL decoding issues on some systems.
	 *
	 * @since v5.4.3 With encoded URLs the Google Fonts API is much more lenient when it comes to invalid requests,
	 *               but we need the URL to be decoded in order to properly parsed (parse_str() and parse_url()), etc.
	 *               So, as of now, we're trimming invalid characters from the end of the URL. The list will expand
	 *               as I run into to them. I'm not going to make any assumptions on what theme/plugin developers
	 *               might be doing wrong.
	 *
	 * @filter omgf_optimize_url
	 *
	 * @param mixed $url
	 *
	 * @return string
	 */
	public function decode_url( $url ) {
		return rtrim( html_entity_decode( $url ), ',' );
	}

	/**
	 * Initialize the Save & Optimize routine.
	 *
	 * @return void
	 */
	public function do_optimize() {
		new \OMGF\Admin\Optimize();
	}

	/**
	 * We use a custom update action, because we're storing multidimensional arrays upon form submit.
	 *
	 * This prevents us from having to use AJAX, serialize(), stringify() and eventually having to json_decode() it, i.e.
	 * a lot of headaches.
	 *
	 * @since v5.6.0
	 */
	public function update_settings() {
		// phpcs:ignore WordPress.Security
		if ( empty( $_POST['action'] ) || $_POST['action'] !== 'omgf-update' ) {
			return;
		}

		// phpcs:ignore
		$post_data = $this->clean($_POST);

		foreach ( $post_data as $option_name => $option_value ) {
			if ( strpos( $option_name, 'omgf_' ) !== 0 || empty( $option_value ) ) {
				continue;
			}

			$merged = [];

			if ( is_string( $option_value ) ) {
				$merged = $option_value;
			} else {
				$current_options = self::get_option( $option_name, [] );
				$merged          = array_replace( $current_options, $option_value );
			}

			self::update_option( $option_name, $merged );
		}

		/**
		 * Additional update actions can be added here.
		 *
		 * @since v5.6.0
		 */
		do_action( 'omgf_update_settings' );

		// Redirect back to the settings page that was submitted.
		$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
		wp_redirect( $goback );
		exit;
	}

	/**
	 * Clean variables using `sanitize_text_field`.
	 * Arrays are cleaned recursively. Non-scalar values are ignored.
	 *
	 * @param string|array $var Sanitize the variable.
	 *
	 * @since 5.5.7
	 *
	 * @return string|array
	 */
	private function clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( [ __CLASS__, __METHOD__ ], $var );
		}

		return is_scalar( $var ) ? sanitize_text_field( wp_unslash( $var ) ) : $var;
	}

	/**
	 * @since v5.0.5 omgf_optimized_fonts is base64_encoded in the frontend, to bypass firewall restrictions on
	 * some servers.
	 *
	 * @param $old_value
	 * @param $value
	 *
	 * @return bool|array
	 */
	public function base64_decode_optimized_fonts( $value ) {
		if ( is_string( $value ) && base64_decode( $value, true ) ) {
			return base64_decode( $value );
		}

		return $value;
	}

	/**
	 * content_url uses is_ssl() to detect whether SSL is used. This fails for servers behind
	 * load balancers and/or reverse proxies. So, we double check with this filter.
	 *
	 * @since v4.4.4
	 *
	 * @param mixed $url
	 * @param mixed $path
	 * @return mixed
	 */
	public function force_ssl( $url, $path ) {
		/**
		 * Only rewrite URLs requested by this plugin. We don't want to interfere with other plugins.
		 */
		if ( strpos( $url, OMGF_UPLOAD_URL ) === false ) {
			return $url;
		}

		/**
		 * If the user entered https:// in the Home URL option, it's safe to assume that SSL is used.
		 */
		if ( ! is_ssl() && strpos( get_home_url(), 'https://' ) !== false ) {
			$url = str_replace( 'http://', 'https://', $url );
		}

		return $url;
	}

	/**
	 * Render update notices if available.
	 *
	 * @param mixed $plugin
	 * @param mixed $response
	 * @return void
	 */
	public function render_update_notice( $plugin, $response ) {
		$current_version = $plugin['Version'];
		$new_version     = $plugin['new_version'];

		if ( version_compare( $current_version, $new_version, '<' ) ) {
			$response = wp_remote_get( 'https://daan.dev/omgf-update-notices.json?' . substr( uniqid( '', true ), -5 ) );

			if ( is_wp_error( $response ) ) {
				return;
			}

			$update_notices = (array) json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! isset( $update_notices[ $new_version ] ) ) {
				return;
			}

			wp_kses(
				printf(
					' <strong>' . __( 'This update includes major changes, please <a href="%s" target="_blank">read this</a> before continuing.' ) . '</strong>',
					$update_notices[ $new_version ]->url
				),
				[
					'strong' => [],
					'a'      => [],
				]
			);
		}
	}

	/**
	 * @since v5.4.0 [OMGF-75] Parse HTML generated by Visual Composer's Grid elements, which is loaded async using AJAX.
	 *
	 * @filter vc_get_vc_grid_data_response
	 *
	 * @return string Valid HTML generated by Visual Composer.
	 */
	public function parse_vc_grid_data( $data ) {
		$processor = new Process( true );
		$data      = $processor->parse( $data );

		return $data;
	}

	/**
	 * Run uninstall script
	 *
	 * @return void
	 */
	public static function do_uninstall() {
		new \OMGF\Uninstall();
	}
}
