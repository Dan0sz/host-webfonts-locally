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
* @copyright: © 2024 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Admin\Settings;

use OMGF\Helper as OMGF;
use OMGF\Admin\Settings;
use OMGF\Helper;

/**
 * @codeCoverageIgnore
 */
class Advanced extends Builder {
	/**
	 * Settings_Advanced constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->title = __( 'Advanced Settings', 'host-webfonts-local' );

		// Open
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_title' ], 10 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_description' ], 15 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_before' ], 20 );

		// Settings
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_cache_dir' ], 50 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_promo_white_label_css' ], 60 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_promo_dtap' ], 70 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_promo_fonts_source_url' ], 80 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_legacy_mode' ], 90 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_compatibility' ], 100 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_auto_config_subsets' ], 110 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_used_subsets' ], 120 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_disable_quick_access_menu' ], 130 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_debug_mode' ], 140 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_download_log' ], 150 );
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_uninstall' ], 160 );

		// Close
		add_action( 'omgf_advanced_settings_content', [ $this, 'do_after' ], 200 );
	}

	/**
	 * Description
	 */
	public function do_description() {
		?>
		<p>
			<?php echo __(
				'Use these settings to make OMGF work with your specific configuration.',
				'host-webfonts-local'
			); ?>
		</p>
		<?php
	}

	/**
	 *
	 */
	public function do_cache_dir() {
		?>
		<tr>
			<th scope="row"><?php echo __( 'Fonts Cache Directory', 'host-webfonts-local' ); ?></th>
			<td>
				<p class="description">
					<?php printf(
						__(
							'Downloaded stylesheets and font files %1$s are stored in: <code>%2$s</code>.',
							'host-webfonts-local'
						),
						is_multisite() ? __( '(for this site)', 'host-webfonts-local' ) : '',
						str_replace( ABSPATH, '', OMGF_UPLOAD_DIR )
					); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * @return void
	 */
	public function do_promo_white_label_css() {
		$this->do_checkbox(
			__( 'White-label Stylesheets (Pro)', 'host-webfonts-local' ),
			'white_label', ! empty( OMGF::get_option( 'white_label', 'on' ) ),
			sprintf(
				__(
					'Enable this option to remove all branding and comments from generated stylesheets, further decreasing their size. %s',
					'host-webfonts-local'
				),
				$this->promo
			), ! defined( 'OMGF_PRO_ACTIVE' )
		);
	}

	public function do_promo_dtap() {
		$this->do_checkbox(
			__( 'Optimize for (D)TAP (Pro)', 'host-webfonts-local' ),
			'dtap', ! empty( OMGF::get_option( 'dtap' ) ),
			sprintf(
				__(
					'Enable this option (on all instances) if you\'re planning to use %s in a (variation of a) Development > Testing > Acceptance/Staging > Production street. %s',
					'host-webfonts-local'
				),
				apply_filters( 'omgf_settings_page_title', 'OMGF' ),
				$this->promo
			), ! defined( 'OMGF_PRO_ACTIVE' ),
			'task-manager-row'
		);
	}

	/**
	 *
	 */
	public function do_promo_fonts_source_url() {
		$description = OMGF::get_option( 'dtap' ) === 'on' ? __(
			'This option is disabled, because <strong>Optimize for DTAP (Pro)</strong> is enabled.',
			'host-webfonts-local'
		) : sprintf(
			__(
				"Modify the <code>src</code> attribute for font files and stylesheets generated by OMGF Pro. This can be anything; from an absolute URL pointing to your CDN (e.g. <code>%s</code>) to an alternate relative URL (e.g. <code>/renamed-wp-content-dir/alternate/path/to/font-files</code>) to work with <em>security thru obscurity</em> plugins. Enter the full path to OMGF's files. Default: (empty) %s",
				'host-webfonts-local'
			),
			'https://your-cdn.com/wp-content/uploads/omgf',
			$this->promo
		);

		$this->do_text(
			__( 'Modify Source URL (Pro)', 'host-webfonts-local' ),
			'source_url',
			__( 'e.g. https://cdn.mydomain.com/alternate/relative-path', 'host-webfonts-local' ),
			OMGF::get_option( 'source_url' ),
			$description, ! defined( 'OMGF_PRO_ACTIVE' ) || OMGF::get_option( 'dtap' ) === 'on'
		);
	}

	/**
	 *
	 */
	public function do_legacy_mode() {
		$this->do_checkbox(
			__( 'Legacy Browser Compatibility', 'host-webfonts-local' ),
			Settings::OMGF_ADV_SETTING_LEGACY_MODE, ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_LEGACY_MODE ) ),
			__(
				'Enable this option to use an older (Windows 7) User-Agent to add support for legacy browsers. Enabling this option negatively impacts file compression and disables Variable Fonts support. Default: off.',
				'host-webfonts-local'
			)
		);
	}

	/**
	 *
	 */
	public function do_compatibility() {
		$this->do_checkbox(
			__( 'Divi/Elementor Compatibility', 'host-webfonts-local' ),
			Settings::OMGF_ADV_SETTING_COMPATIBILITY, ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_COMPATIBILITY ) ),
			__(
				'Divi and Elementor use the same handle for Google Fonts stylesheets with different configurations. OMGF includes compatibility fixes to make sure these different stylesheets are processed correctly. Enable this if you see some fonts not appearing correctly. Default: off',
				'host-webfonts-local'
			)
		);
	}

	public function do_auto_config_subsets() {
		$this->do_checkbox(
			__( 'Auto-Configure Subsets', 'host-webfonts-local' ),
			Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS, ! empty( OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS, 'on' ) ),
			sprintf(
				__(
					'When this option is checked, %s will set the <strong>Used Subset(s)</strong> option to only use subsets that\'re available for <u>all</u> detected font families. Novice users are advised to leave this enabled.',
					'host-webfonts-local'
				),
				apply_filters( 'omgf_settings_page_title', 'OMGF' )
			),
			false,
			'task-manager-row'
		);
	}

	/**
	 * Preload Subsets
	 *
	 * @return void
	 */
	public function do_used_subsets() {
		$this->do_select(
			__( 'Used Subset(s)', 'host-webfonts-local' ),
			Settings::OMGF_ADV_SETTING_SUBSETS,
			Settings::OMGF_SUBSETS,
			OMGF::get_option( Settings::OMGF_ADV_SETTING_SUBSETS ),
			( ! empty( OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_AUTO_SUBSETS ) ) ? '<span class="used-subsets-notice info">' . sprintf(
					__(
						'Any changes made to this setting will be overwritten, because <strong>Auto-configure Subsets</strong> is enabled. <a href="%s">Disable it</a> if you wish to manage <strong>Used Subset(s)</strong> yourself. <u>Novice users shouldn\'t change this setting</u>!',
						'host-webfonts-local'
					),
					admin_url( Settings::OMGF_OPTIONS_GENERAL_PAGE_OPTIMIZE_WEBFONTS )
				) . '</span>' : '' ) . __(
				'A subset is a (limited) set of characters belonging to an alphabet. Default: <code>latin</code>, <code>latin-ext</code>. Limit the selection to subsets your site actually uses. Selecting <u>too many</u> subsets can negatively impact performance! <em>Latin Extended and Vietnamese are an add-ons for Latin and can\'t be used by itself. Use CTRL + click to select multiple values.</em>',
				'host-webfonts-local'
			),
			true
		);
	}

	public function do_disable_quick_access_menu() {
		$this->do_checkbox(
			__( 'Disable Quick Access Menu', 'host-webfonts-local' ),
			Settings::OMGF_ADV_SETTING_DISABLE_QUICK_ACCESS, ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_DISABLE_QUICK_ACCESS ) ),
			sprintf(
				__(
					'Disable the top menu links that give logged in administrators quick access to %s\'s settings and allow you to refresh its cache from the frontend. Re-running fonts optimizations for a page can still be done by appending <code>?omgf_optimize=1</code> to an URL.',
					'host-webfonts-local'
				),
				apply_filters( 'omgf_settings_page_title', 'OMGF' )
			)
		);
	}

	public function do_debug_mode() {
		$this->do_checkbox(
			__( 'Debug Mode', 'host-webfonts-local' ),
			Settings::OMGF_ADV_SETTING_DEBUG_MODE, ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ),
			__(
				'Don\'t enable this option, unless when asked by me (Daan) or, if you know what you\'re doing.',
				'host-webfonts-local'
			)
		);
	}

	/**
	 * Show Download Log button if debug mode is on and debug file exists.
	 */
	public function do_download_log() {
		if ( ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_DEBUG_MODE ) ) ) :
			?>
			<tr>
				<th></th>
				<td>
					<?php if ( file_exists( Helper::log_file() ) ) : ?>
						<?php
						clearstatcache();
						$nonce = wp_create_nonce( Settings::OMGF_ADMIN_PAGE );
						?>
						<a class="button button-secondary"
						   href="<?php echo admin_url(
							   "admin-ajax.php?action=omgf_download_log&nonce=$nonce"
						   ); ?>"><?php _e(
								'Download Log',
								'host-webfonts-local'
							); ?></a>
						<a id="omgf-delete-log" class="button button-cancel"
						   data-nonce="<?php echo $nonce; ?>"><?php _e(
								'Delete log',
								'host-webfonts-local'
							); ?></a>
						<?php if ( filesize( Helper::log_file() ) > MB_IN_BYTES ) : ?>
							<p class="omgf-warning"><?php _e(
									'Your log file is currently larger than 1MB. To protect your filesystem, debug logging has stopped. Delete the log file to enable debug logging again.',
									'host-webfonts-local'
								); ?></p>
						<?php endif; ?>
					<?php else : ?>
						<p class="description"><?php _e(
								'No log file available for download.',
								'host-webfonts-local'
							); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		<?php
		endif;
	}

	/**
	 * Remove Settings/Files at Uninstall.
	 */
	public function do_uninstall() {
		$this->do_checkbox(
			__( 'Remove Settings/Files At Uninstall', 'host-webfonts-local' ),
			Settings::OMGF_ADV_SETTING_UNINSTALL, ! empty( OMGF::get_option( Settings::OMGF_ADV_SETTING_UNINSTALL ) ),
			__( 'Warning! This will remove all settings and cached fonts upon plugin deletion.', 'host-webfonts-local' )
		);
	}
}
