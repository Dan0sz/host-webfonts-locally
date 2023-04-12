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
 * @copyright: © 2022 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

namespace OMGF;

use OMGF\Admin\Settings;
use OMGF\Download;
use OMGF\Frontend\Process;
use OMGF\StylesheetGenerator;

defined( 'ABSPATH' ) || exit;

class Plugin {


	/**
	 * @since v5.5.6 Plugins which can't run alongside OMGF, mostly plugins which remove Google Fonts.
	 */
	const INCOMPATIBLE_PLUGINS = [
		'disable-google-fonts',
		'disable-remove-google-fonts',
		'embed-google-fonts',
		'local-google-fonts',
		// 'use-bunnyfont-host-google-fonts' TODO: Since OMGF supports Bunny CDN, this should be tested.
	];

	/**
	 * @since v5.5.4 Plugins which require additional configuration to be compatible with
	 *               OMGF Pro.
	 */
	const PLUGINS_ADDTNL_CONF = [
		'autoptimize',
		'essential-grid',
		'perfmatters',
		'thrive-visual-editor',
	];

	/**
	 * @since v5.5.4 Plugins which require an upgrade to OMGF Pro.
	 */
	const PLUGINS_REQ_PRO = [
		'essential-grid',
		'optimizepress',
		'oxygen',
		'popup-maker',
		'premium-stock-market-widgets',
		'woozone',
	];

	/**
	 * @since v5.4.0 List of template handles which require additional configuration to be
	 *               compatible with OMGF.
	 */
	const THEMES_ADDTNL_CONF = [
		'Avada',
		'customizr',
		'enfold',
		'Divi',
		'Extra',
		'thrive-theme',
	];

	/**
	 * @since v5.4.0 Themes which require an upgrade to OMGF Pro to properly detect and
	 *               fetch their Google Fonts.
	 */
	const THEMES_REQ_PRO = [
		'Avada',
		'customizr',
		'enfold',
		'jupiter',
		'jupiterx',
		'kadence',
		'thrive-theme',
	];

	/**
	 * JS libraries loading Google Fonts in iframes.
	 */
	const IFRAMES_LOADING_FONTS = [
		'active-campaign'             => '.activehosted.com/f/embed.php', // ActiveCampaign
		'channext'                    => '//content.channext.com/js/', // Channext
		'conversio'                   => '//app.conversiobot.com', // Conversio
		'google-ads'                  => '//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', // Google Ads
		'google-campaign-manager-360' => '//www.googletagservices.com/dcm/dcmads.js', // Google Campaign Manager 360
		'youtube'                     => '//www.youtube.com/embed/', // Youtube Embeds
		'gastronovi'                  => '//services.gastronovi.com', // Gastronovi
		'google-maps'                 => '.google.com/maps', // Google Maps
		'hubspot'                     => '.hs-scripts.com/', // Hubspot
		'manychat'                    => '//widget.manychat.com/', // ManyChat
		'recaptcha'                   => '//www.google.com/recaptcha/api.js', // Recaptcha
		'tawk.to'                     => '//embed.tawk.to', // Tawk.to
		'tidio'                       => '//code.tidio.co/', // Tidio
	];

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
		define( 'OMGF_CACHE_IS_STALE', esc_attr( self::get( Settings::OMGF_CACHE_IS_STALE ) ) );
		define( 'OMGF_CURRENT_DB_VERSION', esc_attr( self::get( Settings::OMGF_CURRENT_DB_VERSION ) ) );
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
	 * Gets all settings for OMGF.
	 *
	 * @filter omgf_settings
	 *
	 * @since 5.5.7
	 *
	 * @return array
	 */
	public static function get_settings() {
		static $settings;

		$defaults = apply_filters(
			'omgf_settings_defaults',
			[
				Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS => '',
				Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION => '',
				Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE => '',
				Settings::OMGF_ADV_SETTING_COMPATIBILITY  => '',
				Settings::OMGF_ADV_SETTING_SUBSETS        => [],
				Settings::OMGF_ADV_SETTING_DEBUG_MODE     => '',
				Settings::OMGF_ADV_SETTING_UNINSTALL      => '',
			]
		);

		if ( empty( $settings ) ) {
			$settings = get_option( 'omgf_settings', [] );
		}

		return apply_filters( 'omgf_settings', wp_parse_args( $settings, $defaults ) );
	}

	/**
	 * Method to retrieve OMGF's settings from database.
	 *
	 * WARNING: DO NOT ATTEMPT TO RETRIEVE WP CORE SETTINGS USING THIS METHOD. IT WILL FAIL.
	 *
	 * @filter omgf_setting_{$name}
	 *
	 * @param string $name
	 * @param mixed  $default (optional)
	 *
	 * @since v5.6.0
	 */
	public static function get_option( $name, $default = null ) {
		// If $name starts with 'omgf_' it means it is saved in a separate row.
		if ( strpos( $name, 'omgf_' ) === 0 ) {
			$value = get_option( $name, $default );

			return apply_filters( 'omgf_setting_' . str_replace( 'omgf_', '', $name ), $value );
		}

		$value = self::get_settings()[ $name ] ?? '';

		if ( empty( $value ) && ! $default && $name === Settings::OMGF_ADV_SETTING_SUBSETS ) {
			$default = [ 'latin', 'latin-ext' ];
		}

		if ( empty( $value ) && $default !== null ) {
			$value = $default;
		}

		return apply_filters( "omgf_setting_$name", $value );
	}

	/**
	 * This is basically a wrapper around update_option() to offer a centralized interface for
	 * storing OMGF's settings in the wp_options table.
	 *
	 * @since v5.6.0
	 *
	 * @param string $setting
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public static function update_option( $setting, $value ) {
		// If $setting starts with 'omgf_' it should be saved in a separate row.
		if ( strpos( $setting, 'omgf_' ) === 0 ) {
			update_option( $setting, $value );

			return;
		}

		$settings             = self::get_settings();
		$settings[ $setting ] = $value;

		update_option( 'omgf_settings', $settings );
	}

	/**
	 * This is basically a wrapper around delete_option() to offer a centralized interface for
	 * removing OMGF's settings in the wp_options table.
	 *
	 * @since v5.6.0
	 *
	 * @param string $setting
	 *
	 * @return void
	 */
	public static function delete_option( $setting ) {
		if ( strpos( $setting, 'omgf_' ) === 0 ) {
			delete_option( $setting );

			return;
		}

		$settings = self::get_settings();

		unset( $settings[ $setting ] );

		update_option( 'omgf_settings', $settings );
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
				$current_options = self::get( $option_name, [] );
				$merged          = array_replace( $current_options, $option_value );
			}

			self::update( $option_name, $merged );
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

			printf(
				' <strong>' . __( 'This update includes major changes, please <a href="%s" target="_blank">read this</a> before continuing.' ) . '</strong>',
				$update_notices[ $new_version ]->url
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
	 * Optimized Local Fonts to be displayed in the Optimize Local Fonts table.
	 *
	 * Use a static variable to reduce database reads/writes.
	 *
	 * @since v4.5.7
	 *
	 * @param array $maybe_add If it doesn't exist, it's added to the cache layer.
	 * @param bool  $force_add
	 *
	 * @return array
	 */
	public static function optimized_fonts( $maybe_add = [], $force_add = false ) {
		/** @var array $optimized_fonts Cache layer */
		static $optimized_fonts;

		/**
		 * Get a fresh copy from the database if $optimized_fonts is empty|null|false (on 1st run)
		 */
		if ( empty( $optimized_fonts ) ) {
			$optimized_fonts = self::get( Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, [] );
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 *
		 * @since v4.5.6
		 */
		if ( is_string( $optimized_fonts ) ) {
			$optimized_fonts = unserialize( $optimized_fonts );
		}

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 *
		 * @since v4.5.7
		 */
		if ( ! empty( $maybe_add ) && ( ! isset( $optimized_fonts[ key( $maybe_add ) ] ) || $force_add ) ) {
			$optimized_fonts = array_merge( $optimized_fonts, $maybe_add );
		}

		return $optimized_fonts;
	}

	/**
	 * @return array
	 */
	public static function preloaded_fonts() {
		static $preloaded_fonts = [];

		if ( empty( $preloaded_fonts ) ) {
			$preloaded_fonts = self::get( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS, [] );
		}

		return $preloaded_fonts;
	}

	/**
	 * @return array
	 */
	public static function unloaded_fonts() {
		static $unloaded_fonts = [];

		if ( empty( $unloaded_fonts ) ) {
			$unloaded_fonts = self::get( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS, [] );
		}

		return $unloaded_fonts;
	}

	/**
	 * @return array
	 */
	public static function unloaded_stylesheets() {
		static $unloaded_stylesheets = [];

		if ( empty( $unloaded_stylesheets ) ) {
			$unloaded_stylesheets = explode( ',', self::get( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS, '' ) );
		}

		return array_filter( $unloaded_stylesheets );
	}

	/**
	 * @return array
	 */
	public static function cache_keys() {
		static $cache_keys = [];

		if ( empty( $cache_keys ) ) {
			$cache_keys = explode( ',', self::get( Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS, '' ) );
		}

		return array_filter( $cache_keys );
	}

	/**
	 * @param $handle
	 *
	 * @return string
	 */
	public static function get_cache_key( $handle ) {
		$cache_keys = self::cache_keys();

		foreach ( $cache_keys as $index => $key ) {
			/**
			 * @since v4.5.16 Convert $handle to lowercase, because $key is saved lowercase, too.
			 */
			if ( strpos( $key, strtolower( $handle ) ) !== false ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * @since v5.4.4 Returns the subsets that're available in all requested fonts/stylesheets.
	 *
	 *               Functions as a temporary cache layer to reduce DB reads with get_option().
	 *
	 * @return array
	 */
	public static function available_used_subsets( $maybe_add = [], $intersect = false ) {
		static $subsets = [];

		if ( empty( $subsets ) ) {
			$subsets = self::get( Settings::OMGF_AVAILABLE_USED_SUBSETS, [] );
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 */
		if ( is_string( $subsets ) ) {
			$subsets = unserialize( $subsets );
		}

		/**
		 * If $maybe_add doesn't exist in the cache layer yet, add it.
		 */
		if ( ! empty( $maybe_add ) && ( ! isset( $subsets[ key( $maybe_add ) ] ) ) ) {
			$subsets = array_merge( $subsets, $maybe_add );
		}

		/**
		 * Return only subsets that're available in all font families.
		 *
		 * @see OMGF_Optimize_Run
		 */
		if ( $intersect ) {
			/**
			 * @var array $filtered_subsets Contains an array of Font Families along with the available selected subsets, e.g.
			 *                              { 'Lato' => { 'latin', 'latin-ext' } }
			 */
			$filtered_subsets = array_values( array_filter( $subsets ) );

			self::debug_array( __( 'Filtered Subsets', 'host-webfonts-local' ), $filtered_subsets );

			if ( count( $filtered_subsets ) === 1 ) {
				return reset( $filtered_subsets );
			}

			if ( ! empty( $filtered_subsets ) ) {
				return call_user_func_array( 'array_intersect', $filtered_subsets );
			}

			return $filtered_subsets;
		}

		return $subsets;
	}

	/**
	 * Download $url and save as $filename.$extension to $path.
	 *
	 * @param mixed $url
	 * @param mixed $filename
	 * @param mixed $extension
	 * @param mixed $path
	 *
	 * @return string
	 */
	public static function download( $url, $filename, $extension, $path ) {
		$download = new Download( $url, $filename, $extension, $path );

		return $download->download();
	}

	/**
	 * @param mixed $fonts
	 *
	 * @return string
	 */
	public static function generate_stylesheet( $fonts, $plugin = 'OMGF' ) {
		$generator = new StylesheetGenerator( $fonts, $plugin );

		return $generator->generate();
	}

	/**
	 * Renders the Task Manager Warnings box.
	 */
	public static function task_manager_warnings() {
		if ( ! empty( self::get( Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE ) ) && ! wp_doing_ajax() ) : ?>
			<tr valign="top" id="task-manager-notice-test-mode-row">
				<td colspan="2" class="task-manager-row">
					<div class="task-manager-notice info">
						<h4><?php echo __( 'Test Mode is Enabled', 'host-webfonts-local' ); ?></h4>
						<p>
							<?php echo sprintf( __( 'All optimizations made by %s are <strong>only visible to you</strong> and users who append <code>?omgf=1</code> to the URL. Disable Test Mode (at the bottom of this page) to make optimizations visible for everyone.', 'host-webfonts-local' ), apply_filters( 'omgf_settings_page_title', 'omgf' ) ); ?>
						</p>
					</div>
				</td>
			</tr>
			<?php
		endif;
		?>
		<tr valign="top" id="task-manager-notice-row">
			<td colspan="2" class="task-manager-row">
				<?php
				$warnings = self::get_task_manager_warnings();
				$plugins  = self::get_active_plugins();
				if ( empty( $warnings ) ) :
					?>
					<div class="task-manager-notice success">
						<h4><?php echo __( 'No potential conflicts found in your configuration.', 'host-webfonts-local' ); ?></h4>
						<ol style="list-style: none; margin-left: 0;">
							<li><?php echo sprintf( __( 'Great job! %s hasn\'t detected any potential conflicts in your configuration.*', 'host-webfonts-local' ), apply_filters( 'omgf_settings_page_title', 'OMGF' ) ); ?></li>
						</ol>
						<p>
							<sub>*<em><?php echo __( 'Check back regularly to make sure no conflicts are detected on any of your subpages.', 'host-webfonts-local' ); ?></em></sub>
						</p>
					</div>
				<?php else : ?>
					<div class="task-manager-notice warning">
						<h4><?php echo sprintf( _n( '%s potential conflict found in your configuration.', '%s potential conflicts found in your configuration.', count( $warnings ), 'host-webfonts-local' ), count( $warnings ) ); ?>*</h4>
						<ol <?php echo count( $warnings ) === 1 ? "style='list-style: none; margin-left: 0;'" : ''; ?>>
							<?php foreach ( $warnings as $warning_id ) : ?>
								<?php $show_mark_as_fixed = true; ?>
								<li id="omgf-notice-<?php echo $warning_id; ?>">
									<?php if ( $warning_id == 'is_multisite' ) : ?>
										<?php echo sprintf( __( 'It seems like Multisite is enabled. OMGF doesn\'t natively support Multisite. If you\'re getting CORS related errors on any of your network\'s sites, consider <a href="%s" target="_blank">upgrading to OMGF Pro</a>.', 'host-webfonts-local' ), Settings::DAAN_WORDPRESS_OMGF_PRO ); ?>
									<?php endif; ?>
									<?php if ( $warning_id == 'no_ssl' ) : ?>
										<?php echo __( 'Your WordPress configuration isn\'t setup to use SSL (https://). If your frontend is showing System Fonts after optimization, this might be due to Mixed-Content and/or CORS warnings. Follow <a href="https://daan.dev/docs/omgf-pro-troubleshooting/system-fonts/" target="_blank">these steps</a> to fix it.', 'host-webfonts-local' ); ?>
									<?php endif; ?>
									<?php if ( in_array( str_replace( '-req-pro', '', $warning_id ), self::THEMES_REQ_PRO ) ) : ?>
										<?php $show_mark_as_fixed = false; ?>
										<?php echo sprintf( __( 'Due to the exotic way your theme (%1$s) implements Google Fonts, OMGF Pro\'s Advanced Processing features are required to detect them. <a href="%2$s" target="_blank">Upgrade and install OMGF Pro</a> to continue.', 'host-webfonts-local' ), ucfirst( str_replace( '-req-pro', '', $warning_id ) ), Settings::DAAN_WORDPRESS_OMGF_PRO ); ?>
									<?php endif; ?>
									<?php if ( in_array( str_replace( '-addtnl-conf', '', $warning_id ), self::THEMES_ADDTNL_CONF ) ) : ?>
										<?php $template_id = str_replace( '-addtnl-conf', '', strtolower( $warning_id ) ); ?>
										<?php echo sprintf( __( 'Your theme (%1$s) requires additional configuration to be compatible with %2$s, check the list of <a href="%3$s" target="_blank">known issues</a> to fix it.', 'host-webfonts-local' ), ucfirst( $template_id ), apply_filters( 'omgf_settings_page_title', 'OMGF' ), Settings::DAAN_DOCS_OMGF_PRO_KNOWN_ISSUES ); ?>
									<?php endif; ?>
									<?php if ( in_array( str_replace( '-incompatible', '', $warning_id ), self::INCOMPATIBLE_PLUGINS ) ) : ?>
										<?php $plugin_name = $plugins[ str_replace( '-incompatible', '', $warning_id ) ]; ?>
										<?php echo sprintf( __( 'The plugin, <strong>%1$s</strong>, is incompatible with %2$s and needs to be disabled for %2$s to function properly. View the list of <a href="%3$s" target="_blank">known issues</a> for more information.', 'host-webfonts-local' ), $plugin_name, apply_filters( 'omgf_settings_page_title', 'OMGF' ), Settings::DAAN_DOCS_OMGF_PRO_KNOWN_ISSUES ); ?>
									<?php endif; ?>
									<?php if ( in_array( str_replace( '-req-pro', '', $warning_id ), self::PLUGINS_REQ_PRO ) ) : ?>
										<?php $show_mark_as_fixed = false; ?>
										<?php $plugin_name = $plugins[ str_replace( '-req-pro', '', $warning_id ) ]; ?>
										<?php echo sprintf( __( 'Due to the exotic way the plugin, <strong>%1$s</strong>, implements Google Fonts, OMGF Pro\'s Advanced Processing features are required to detect them. <a href="%2$s" target="_blank">Upgrade and install OMGF Pro</a> to continue.', 'host-webfonts-local' ), $plugin_name, Settings::DAAN_WORDPRESS_OMGF_PRO ); ?>
									<?php endif; ?>
									<?php if ( in_array( str_replace( '-addtnl-conf', '', $warning_id ), self::PLUGINS_ADDTNL_CONF ) ) : ?>
										<?php $plugin_name = $plugins[ str_replace( '-addtnl-conf', '', $warning_id ) ]; ?>
										<?php echo sprintf( __( 'The plugin, <strong>%1$s</strong>, requires additional configuration to be compatible with %2$s. Check the <a href="%3$s" target="_blank">list of known issues</a> to fix it.', 'host-webfonts-local' ), $plugin_name, apply_filters( 'omgf_settings_page_title', 'OMGF' ), Settings::DAAN_DOCS_OMGF_PRO_KNOWN_ISSUES ); ?>
									<?php endif; ?>
									<?php if ( in_array( $warning_id, array_keys( self::IFRAMES_LOADING_FONTS ) ) ) : ?>
										<?php $iframe_name = ucwords( str_replace( '-', ' ', $warning_id ) ); ?>
										<?php echo sprintf( __( '%1$s is loading an embedded iframe on your site. %2$s can\'t process Google Fonts inside iframes. <a href="%3$s" target="_blank">Click here</a> to find out why and what you can do about it.', 'host-webfonts-local' ), $iframe_name, apply_filters( 'omgf_settings_page_title', 'OMGF' ), 'https://daan.dev/docs/omgf-pro-faq/iframes/' ); ?>
									<?php endif; ?>
									<?php if ( $show_mark_as_fixed ) : ?>
										<small>[<a href="#" class="hide-notice" data-nonce="<?php echo wp_create_nonce( Settings::OMGF_ADMIN_PAGE ); ?>" data-warning-id="<?php echo $warning_id; ?>" id="omgf-hide-notice-<?php echo $warning_id; ?>"><?php echo __( 'Mark as fixed', 'host-webfonts-local' ); ?></a>]</small>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ol>
						<p>
							<sub>*<em><?php echo __( 'After making the proposed changes where needed, click <strong>Mark as fixed</strong> to remove the notice. It won\'t disappear by itself.', 'host-webfonts-local' ); ?></em></sub>
						</p>
					</div>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Check if WordPress setup has known issues.
	 *
	 * @return array
	 */
	public static function get_task_manager_warnings() {
		$warnings       = [];
		$hidden_notices = self::get( Settings::OMGF_HIDDEN_NOTICES, [] );

		/**
		 * @since v5.5.4 Throw a warning if Multisite is enabled and OMGF Pro isn't installed/activated.
		 */
		if ( is_multisite() && ! function_exists( 'omgf_pro_init' ) ) {
			$warnings[] = 'is_multisite';
		}

		/**
		 * @since v5.4.0 OMGF-50 Not using SSL on your site (or at least, not having it properly configured in WordPress) will cause OMGF to
		 *               add non-ssl (http://) links to stylesheets, and will lead to CORS and/or Mixed Content warnings in your frontend,
		 *               effectively showing nothing but system fonts.
		 */
		if ( strpos( get_option( 'home' ), 'http://' ) !== false || strpos( get_option( 'siteurl' ), 'http://' ) !== false ) {
			$warnings[] = 'no_ssl';
		}

		/**
		 * @since v5.4.0 OMGF-60 Warn the user if they're using a theme with known compatibility issues.
		 */
		$theme = wp_get_theme();

		if ( in_array( $theme->template, self::THEMES_ADDTNL_CONF ) ) {
			$warnings[] = $theme->template . '-addtnl-conf';
		}

		/**
		 * @since v5.4.0 Warn the user if they're using a theme which requires OMGF Pro's Advanced Processing features.
		 */
		if ( in_array( $theme->template, self::THEMES_REQ_PRO ) && ! function_exists( 'omgf_pro_init' ) ) {
			$warnings[] = $theme->template . '-req-pro';
		}

		$plugins = self::get_active_plugins();
		$slugs   = array_keys( $plugins );

		/**
		 * @since v5.5.6 Notify users if they're using a plugin which is incompatible with OMGF (Pro)
		 */
		foreach ( self::INCOMPATIBLE_PLUGINS as $incompatible_plugin ) {
			if ( in_array( $incompatible_plugin, $slugs ) ) {
				$warnings[] = $incompatible_plugin . '-incompatible';
			}
		}

		/**
		 * @since v5.5.4 OMGF-74 Notify users if they're using a plugin which requires additional configuration due to known compatibility issues.
		 */
		foreach ( self::PLUGINS_ADDTNL_CONF as $plugin_addtnl_conf ) {
			if ( in_array( $plugin_addtnl_conf, $slugs ) ) {
				$warnings[] = $plugin_addtnl_conf . '-addtnl-conf';
			}
		}

		/**
		 * @since v5.5.4 OMGF-74 Notify users if they're using a plugin which requires OMGF Pro's Advanced Processing feature.
		 */
		foreach ( self::PLUGINS_REQ_PRO as $plugin_req_pro ) {
			if ( in_array( $plugin_req_pro, $slugs ) && ! function_exists( 'omgf_pro_init' ) ) {
				$warnings[] = $plugin_req_pro . '-req-pro';
			}
		}

		/**
		 * @since v5.4.0 OMGF-70 Notify users if they're loading scripts loading embedded iframes, e.g. Google Maps, Youtube, etc.
		 */
		$iframe_scripts = self::get( Settings::OMGF_FOUND_IFRAMES, [] );

		foreach ( $iframe_scripts as $script_id ) {
			$warnings[] = $script_id;
		}

		/**
		 * Process hidden warnings.
		 */
		foreach ( $warnings as $i => $warning ) {
			if ( in_array( $warning, $hidden_notices ) ) {
				unset( $warnings[ $i ] );
			}
		}

		return $warnings;
	}

	/**
	 * @return array List of plugin names { (string) slug => (string) full name }
	 */
	private static function get_active_plugins() {
		$plugins        = [];
		$active_plugins = array_intersect_key( get_plugins(), array_flip( array_filter( array_keys( get_plugins() ), 'is_plugin_active' ) ) );

		foreach ( $active_plugins as $basename => $plugin ) {
			$slug = preg_replace( '/\/.*?\.php$/', '', $basename );

			$plugins[ $slug ] = $plugin['Name'];
		}

		return $plugins;
	}

	/**
	 * Run uninstall script
	 *
	 * @return void
	 */
	public static function do_uninstall() {
		 new \OMGF\Uninstall();
	}

	/**
	 * @param $entry
	 */
	public static function delete( $entry ) {
		if ( is_dir( $entry ) ) {
			$file = new \FilesystemIterator( $entry );

			// If dir is empty, valid() returns false.
			while ( $file->valid() ) {
				self::delete( $file->getPathName() );
				$file->next();
			}

			rmdir( $entry );
		} else {
			unlink( $entry );
		}
	}

	/**
	 * Global debug logging function. Stops logging if log size exceeds 1MB.
	 *
	 * @param mixed $message
	 * @return void
	 */
	public static function debug( $message ) {
		if (
			! self::get( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ||
			( self::get( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) && file_exists( self::$log_file ) && filesize( self::$log_file ) > MB_IN_BYTES )
		) {
			return;
		}

		error_log( current_time( 'Y-m-d H:i:s' ) . ' ' . microtime() . ": $message\n", 3, self::$log_file );
	}

	/**
	 * To prevent "Cannot use output buffering  in output buffering display handlers" errors, I introduced a debug array feature,
	 * to easily display, well, arrays in the debug log (duh!)
	 *
	 * @since v5.3.7
	 *
	 * @param $name  A desriptive name to be shown in the debug log
	 * @param $array The array to be displayed in the debug log
	 *
	 * @return void
	 */
	public static function debug_array( $name, $array ) {
		if (
			! self::get( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ||
			( self::get( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) && file_exists( self::$log_file ) && filesize( self::$log_file ) > MB_IN_BYTES )
		) {
			return;
		}

		if ( ! is_array( $array ) && ! is_object( $array ) ) {
			return;
		}

		self::debug( __( 'Showing debug information for', 'host-webfonts-local' ) . ': ' . $name );

		foreach ( $array as $key => $elem ) {
			if ( is_array( $elem ) || is_object( $elem ) ) {
				self::debug_array( sprintf( __( 'Subelement %s is array/object', 'host-webfonts-local' ), $key ), $elem );

				continue;
			}

			error_log( current_time( 'Y-m-d H:i:s' ) . ' ' . microtime() . ': ' . $key . ' => ' . $elem . "\n", 3, self::$log_file );
		}
	}
}
