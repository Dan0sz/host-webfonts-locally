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

defined( 'ABSPATH' ) || exit;

class OMGF_Admin_Settings extends OMGF_Admin
{
	/**
	 * Settings Fields
	 */
	const OMGF_SETTINGS_FIELD_BASIC               = 'omgf-basic-settings';
	const OMGF_SETTINGS_FIELD_GENERATE_STYLESHEET = 'omgf-generate-stylesheet';
	const OMGF_SETTINGS_FIELD_ADVANCED            = 'omgf-advanced-settings';
	const OMGF_SETTINGS_FIELD_EXTENSIONS          = 'omgf-extensions-settings';
	
	/**
	 * Option Values
	 */
	const OMGF_FONT_PROCESSING_OPTIONS = [
		'replace' => 'Replace (default)',
		'remove'  => 'Remove only'
	];
	const OMGF_FONT_DISPLAY_OPTIONS    = [
		'swap'     => 'Swap (recommended)',
		'auto'     => 'Auto',
		'block'    => 'Block',
		'fallback' => 'Fallback',
		'optional' => 'Optional'
	];
	
	/**
	 * Basic Settings
	 */
	const OMGF_BASIC_SETTING_FONT_PROCESSING = 'omgf_font_processing';
	const OMGF_BASIC_SETTING_DISPLAY_OPTION  = 'omgf_display_option';
	
	/**
	 * Generate Stylesheet
	 */
	const OMGF_SETTING_DB_VERSION             = 'omgf_db_version';
	const OMGF_SETTING_AUTO_DETECTION_ENABLED = 'omgf_auto_detection_enabled';
	const OMGF_SETTING_DETECTED_FONTS         = 'omgf_detected_fonts';
	const OMGF_SETTING_SUBSETS                = 'omgf_subsets';
	const OMGF_SETTING_FONTS                  = 'omgf_fonts';
	
	/**
	 * Advanced Settings
	 */
	const OMGF_ADV_SETTING_CACHE_PATH            = 'omgf_cache_dir';
	const OMGF_ADV_SETTING_CACHE_URI             = 'omgf_cache_uri';
	const OMGF_ADV_SETTING_CDN_URL               = 'omgf_cdn_url';
	const OMGF_ADV_SETTING_WEB_FONT_LOADER       = 'omgf_web_font_loader';
	const OMGF_ADV_SETTING_REMOVE_VERSION        = 'omgf_remove_version';
	const OMGF_ADV_SETTING_REMOVE_GOOGLE_FONTS   = 'omgf_remove_gfonts';
	const OMGF_BASIC_SETTING_OPTIMIZE_EDIT_ROLES = 'omgf_optimize_edit_roles';
	const OMGF_ADV_SETTING_UNINSTALL             = 'omgf_uninstall';
	const OMGF_ADV_SETTING_ENQUEUE_ORDER         = 'omgf_enqueue_order';
	const OMGF_ADV_SETTING_FORCE_SSL             = 'omgf_force_ssl';
	const OMGF_ADV_SETTING_RELATIVE_URL          = 'omgf_relative_url';
	
	/** @var string $active_tab */
	private $active_tab;
	
	/** @var string $page */
	private $page;
	
	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'host-webfonts-local';
	
	/**
	 * OMGF_Admin_Settings constructor.
	 */
	public function __construct () {
		parent::__construct();
		
		$this->active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : self::OMGF_SETTINGS_FIELD_GENERATE_STYLESHEET;
		$this->page       = isset( $_GET['page'] ) ? $_GET['page'] : '';
		
		add_action( 'admin_menu', [ $this, 'create_menu' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( OMGF_PLUGIN_FILE ), [ $this, 'create_settings_link' ] );
		
		if ( $this->page !== 'optimize-webfonts' ) {
			return;
		}
		
		// Tabs
		add_action( 'omgf_settings_tab', [ $this, 'basic_settings_tab' ], 0 );
		add_action( 'omgf_settings_tab', [ $this, 'advanced_settings_tab' ], 1 );
		add_action( 'omgf_settings_tab', [ $this, 'generate_stylesheet_tab' ], 2 );
		add_action( 'omgf_settings_tab', [ $this, 'extensions_settings_tab' ], 3 );
		
		// Content
		add_action( 'omgf_settings_content', [ $this, 'basic_settings_content' ], 0 );
		add_action( 'omgf_settings_content', [ $this, 'advanced_settings_content' ], 1 );
		add_action( 'omgf_settings_content', [ $this, 'generate_stylesheet_content' ], 2 );
		add_action( 'omgf_settings_content', [ $this, 'extensions_settings_content' ], 3 );
	}
	
	/**
	 * Creates the menu item.
	 */
	public function create_menu () {
		add_options_page(
			'OMGF',
			'Optimize Google Fonts',
			'manage_options',
			'optimize-webfonts',
			[ $this, 'create_settings_page' ]
		);
		
		// @formatter:off
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		// @formatter:on
	}
	
	/**
	 * Display the settings page.
	 */
	public function create_settings_page () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "You're not cool enough to access this page.", $this->plugin_text_domain ) );
		}
		?>
        <div class="wrap">
            <h1><?= apply_filters( 'omgf_settings_page_title', __( 'OMGF | Optimize My Google Fonts', $this->plugin_text_domain ) ); ?></h1>

            <p>
				<?= get_plugin_data( OMGF_PLUGIN_FILE )['Description']; ?>
            </p>

            <div class="settings-column left">
                <h2 class="omgf-nav nav-tab-wrapper">
					<?php do_action( 'omgf_settings_tab' ); ?>
                </h2>
				
				<?php do_action( 'omgf_settings_content' ); ?>
            </div>

            <div class="settings-column right">
                <div id="omgf-welcome-panel" class="welcome-panel">
					<?php $this->get_template( 'welcome' ); ?>
                </div>
            </div>
        </div>
		<?php
	}
	
	/**
	 * Register all settings.
	 *
	 * @throws ReflectionException
	 */
	public function register_settings () {
		if ( $this->active_tab !== self::OMGF_SETTINGS_FIELD_BASIC
		     && $this->active_tab !== self::OMGF_SETTINGS_FIELD_GENERATE_STYLESHEET
		     && $this->active_tab !== self::OMGF_SETTINGS_FIELD_ADVANCED
		     && $this->active_tab !== self::OMGF_SETTINGS_FIELD_EXTENSIONS ) {
			$this->active_tab = self::OMGF_SETTINGS_FIELD_BASIC;
		}
		
		foreach ( $this->get_settings() as $constant => $value ) {
			register_setting(
				$this->active_tab,
				$value
			);
		}
	}
	
	/**
	 * Get all settings using the constants in this class.
	 *
	 * @return array
	 * @throws ReflectionException
	 */
	public function get_settings () {
		if ( $this->active_tab == self::OMGF_SETTINGS_FIELD_GENERATE_STYLESHEET ) {
			return [];
		}
		
		$reflection = new ReflectionClass( $this );
		$constants  = apply_filters( 'omgf_settings_constants', $reflection->getConstants() );
		
		switch ( $this->active_tab ) {
			case ( self::OMGF_SETTINGS_FIELD_BASIC ):
				$needle = 'OMGF_BASIC_SETTING_';
				break;
			case ( self::OMGF_SETTINGS_FIELD_EXTENSIONS ):
				$needle = 'OMGF_EXT_SETTING';
				break;
			default:
				$needle = 'OMGF_ADV_SETTING';
		}
		
		return array_filter(
			$constants,
			function ( $key ) use ( $needle ) {
				return strpos( $key, $needle ) !== false;
			},
			ARRAY_FILTER_USE_KEY
		);
	}
	
	/**
	 * Add Basic Settings Tab to Settings Screen.
	 */
	public function basic_settings_tab () {
		$this->generate_tab( self::OMGF_SETTINGS_FIELD_BASIC, 'dashicons-analytics', __( 'Basic Settings', $this->plugin_text_domain ) );
	}
	
	/**
	 * Add Generate Stylesheet Tab to Settings Screen.
	 */
	public function generate_stylesheet_tab () {
		$this->generate_tab(
			self::OMGF_SETTINGS_FIELD_GENERATE_STYLESHEET,
			'dashicons-admin-appearance',
			__( 'Generate Stylesheet', $this->plugin_text_domain )
		);
	}
	
	/**
	 * Add Advanced Settings Tab to Settings Screen.
	 */
	public function advanced_settings_tab () {
		$this->generate_tab( self::OMGF_SETTINGS_FIELD_ADVANCED, 'dashicons-admin-settings', __( 'Advanced Settings', $this->plugin_text_domain ) );
	}
	
	/**
	 * Add Extensions tab to Settings Screen.
	 */
	public function extensions_settings_tab () {
		$this->generate_tab( self::OMGF_SETTINGS_FIELD_EXTENSIONS, 'dashicons-admin-plugins', __( 'Extensions', $this->plugin_text_domain ) );
	}
	
	/**
	 * @param      $id
	 * @param null $icon
	 * @param null $label
	 */
	private function generate_tab ( $id, $icon = null, $label = null ) {
		?>
        <a class="nav-tab dashicons-before <?= $icon; ?> <?= $this->active_tab == $id ? 'nav-tab-active' : ''; ?>"
           href="<?= $this->generate_tab_link( $id ); ?>">
			<?= $label; ?>
        </a>
		<?php
	}
	
	/**
	 * @param $tab
	 *
	 * @return string
	 */
	private function generate_tab_link ( $tab ) {
		return admin_url( "options-general.php?page=optimize-webfonts&tab=$tab" );
	}
	
	/**
	 * Render Basic Settings content
	 */
	public function basic_settings_content () {
		$this->do_settings_content( self::OMGF_SETTINGS_FIELD_BASIC );
	}
	
	/**
	 * Render Generate Stylesheet
	 */
	public function generate_stylesheet_content () {
		if ( $this->active_tab != self::OMGF_SETTINGS_FIELD_GENERATE_STYLESHEET ) {
			return;
		}
		?>
        <form id="omgf-generate-stylesheet-form" name="omgf-generate-stylesheet-form">
			<?php
			$this->get_template( 'generate-stylesheet' );
			?>
        </form>
		<?php
	}
	
	/**
	 * Render Advanced Settings content
	 */
	public function advanced_settings_content () {
		$this->do_settings_content( self::OMGF_SETTINGS_FIELD_ADVANCED );
	}
	
	/**
	 * Render Extensions content
	 */
	public function extensions_settings_content () {
		$this->do_settings_content( self::OMGF_SETTINGS_FIELD_EXTENSIONS );
	}
	
	/**
	 * @param $id
	 * @param $field
	 */
	private function do_settings_content ( $field ) {
		if ( $this->active_tab != $field ) {
			return;
		}
		?>
        <form id="<?= $field; ?>-form" name="omgf-settings-form" method="post" action="options.php?tab=<?= $this->active_tab; ?>">
			<?php
			settings_fields( $field );
			do_settings_sections( $field );
			
			do_action( 'omgf_before_settings_form_settings' );
			
			echo apply_filters( str_replace( '-', '_', $field ) . '_content', '' );
			
			do_action( 'omgf_after_settings_form_settings' );
			
			submit_button();
			?>
        </form>
		<?php
	}
	
	/**
	 * Adds the 'settings' link to the Plugin overview.
	 *
	 * @return mixed
	 */
	public function create_settings_link ( $links ) {
		$adminUrl     = admin_url() . 'options-general.php?page=optimize-webfonts';
		$settingsLink = "<a href='$adminUrl'>" . __( 'Settings' ) . "</a>";
		array_push( $links, $settingsLink );
		
		return $links;
	}
}
