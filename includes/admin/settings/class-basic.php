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

class OMGF_Admin_Settings_Basic extends OMGF_Admin_Settings_Builder
{
	public function __construct () {
		$this->title = __( 'Basic Settings', $this->plugin_text_domain );
		
		// Open
		add_filter( 'omgf_basic_settings_content', [ $this, 'do_title' ], 10 );
		add_filter( 'omgf_basic_settings_content', [ $this, 'do_description' ], 15 );
		add_filter( 'omgf_basic_settings_content', [ $this, 'do_before' ], 20 );
		
		// Settings
		add_filter( 'omgf_basic_settings_content', [ $this, 'do_process_google_fonts' ], 30 );
		add_filter( 'omgf_basic_settings_content', [ $this, 'do_display_option' ], 40 );
		add_filter( 'omgf_basic_settings_content', [ $this, 'do_combine_requests' ], 50 );
		add_filter( 'omgf_basic_settings_content', [ $this, 'do_cache_dir' ], 60 );
		add_filter( 'omgf_basic_settings_content', [ $this, 'do_optimize_edit_roles' ], 70 );
		
		// Close
		add_filter( 'omgf_basic_settings_content', [ $this, 'do_after' ], 100 );
	}
	
	/**
	 * Description
	 */
	public function do_description () {
		?>
        <p>
            * <?= __( 'If you\'re looking to replace your Google Fonts for locally hosted copies, then the default settings will suffice. OMGF will run silently in the background and download any Google Fonts while you and/or your visitors are browsing your site.', $this->plugin_text_domain ); ?>
        </p>
        <p>
			<?= __( 'If <strong>Google Fonts Processing</strong> is set to Replace, loading the locally hosted stylesheet for the first time (or after emptying the OMGF\'s cache directory) might take a few seconds. This depends on your server\'s capacity and the size of the stylesheet. This is because OMGF\'s Download API captures the request and automatically downloads the fonts, before serving the local copy. Once the stylesheet and fonts are downloaded, every consecutive request will be fast again.', $this->plugin_text_domain ); ?>
        </p>
		<?php
	}
	
	/**
	 *
	 */
	public function do_process_google_fonts () {
		$this->do_select(
			__( 'Google Fonts Processing', $this->plugin_text_domain ),
			OMGF_Admin_Settings::OMGF_BASIC_SETTING_FONT_PROCESSING,
			OMGF_Admin_Settings::OMGF_FONT_PROCESSING_OPTIONS,
			OMGF_FONT_PROCESSING,
			sprintf( __( "Choose whether OMGF should (find, download and) <strong>replace</strong> all Google Fonts, or just <strong>remove</strong> them. Choosing Remove will force WordPress to fallback to system fonts. OMGF only scans for Google Fonts which are enqueued in WordPress' <code>head</code>. Upgrade to <a href='%s' target='_blank'>OMGF Pro</a> to process all fonts. E.g. fonts that're loaded using Web Font Loader and/or in WP's <code>footer</code>.", $this->plugin_text_domain ), self::FFWP_WORDPRESS_PLUGINS_OMGF_PRO )
		);
	}
	
	/**
	 *
	 */
	public function do_display_option () {
		$this->do_select(
			__( 'Font-display option', $this->plugin_text_domain ),
			OMGF_Admin_Settings::OMGF_BASIC_SETTING_DISPLAY_OPTION,
			OMGF_Admin_Settings::OMGF_FONT_DISPLAY_OPTIONS,
			OMGF_DISPLAY_OPTION,
			__( 'Select which font-display strategy to use. Defaults to Swap (recommended).', $this->plugin_text_domain )
		);
	}
	
	/**
	 *
	 */
	public function do_combine_requests () {
		$this->do_checkbox(
			__( 'Combine Google Fonts Requests (Pro)', $this->plugin_text_domain ),
			'omgf_pro_combine_requests',
			defined( 'OMGF_PRO_COMBINE_REQUESTS' ) ? true : false,
			sprintf( __( 'Combine multiple font requests into one request. This feature is enabled by default in OMGF Pro. <a target="_blank" href="%s">Upgrade now</a>.', $this->plugin_text_domain ), self::FFWP_WORDPRESS_PLUGINS_OMGF_PRO ),
			true
		);
	}
	
	/**
	 *
	 */
	public function do_cache_dir () {
		$this->do_text(
			__( 'Save font files to...', $this->plugin_text_domain ),
			OMGF_Admin_Settings::OMGF_BASIC_SETTING_CACHE_PATH,
			__( 'e.g. /uploads/omgf', $this->plugin_text_domain ),
			OMGF_CACHE_PATH,
			__( "The folder (inside <code>wp-content</code>) where font files should be stored. Give each site a unique value if you're using Multisite. Defaults to <code>/uploads/omgf</code>. After changing this setting, the folder will be created if it doesn't exist and existing files will be moved automatically.", $this->plugin_text_domain )
		);
	}
	
	/**
	 *
	 */
	public function do_optimize_edit_roles () {
		$this->do_checkbox(
			__( 'Optimize fonts for logged in editors/administrators?', $this->plugin_text_domain ),
			OMGF_Admin_Settings::OMGF_BASIC_SETTING_OPTIMIZE_EDIT_ROLES,
			OMGF_OPTIMIZE_EDIT_ROLES,
			__( 'Should only be disabled while debugging/testing, e.g. using a page builder or switching themes.', $this->plugin_text_domain )
		);
	}
}
