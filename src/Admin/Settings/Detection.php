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

use OMGF\Admin\Settings;

/**
 * @codeCoverageIgnore
 */
class Detection extends Builder {
	public function __construct() {
		parent::__construct();

		$this->title = __( 'Google Fonts Detection Settings (deprecated)', 'host-webfonts-local' );

		// Open
		add_action( 'omgf_detection_settings_content', [ $this, 'do_title' ], 10 );
		add_action( 'omgf_detection_settings_content', [ $this, 'do_description' ], 15 );
		add_action( 'omgf_detection_settings_content', [ $this, 'do_before' ], 20 );

		// Settings
		add_action( 'omgf_detection_settings_content', [ $this, 'google_fonts_processing' ], 30 );
		add_action( 'omgf_detection_settings_content', [ $this, 'promo_advanced_processing' ], 50 );

		// Close
		add_action( 'omgf_detection_settings_content', [ $this, 'do_after' ], 100 );
	}

	/**
	 * Description
	 */
	public function do_description() { ?>
		<p>
			<?php echo __(
				'These settings used to affect the detection mechanism and in which areas it searches (i.e. how deep it digs) to find Google Fonts. This tab will be removed in upcoming updates.',
				'host-webfonts-local'
			); ?>
		</p>
		<?php
	}

	/**
	 *
	 */
	public function google_fonts_processing() {
		?>
		<tr>
			<th scope="row"><?php echo __( 'Google Fonts Processing', 'host-webfonts-local' ); ?></th>
			<td>
				<p class="description">
					<?php echo sprintf(
						__(
							'By default, OMGF replaces Google Fonts stylesheets (e.g. <code>https://fonts.googleapis.com/css?family=Open+Sans</code>) with locally hosted copies. To remove/unload Google Fonts entirely, go to <em>Local Fonts</em> > <a href="%s"><em>Optimize Local Fonts</em></a> and click <strong>Unload all</strong> next to the stylesheet handle you\'d like to remove.',
							'host-webfonts-local'
						),
						admin_url(
							'options-general.php?page=' . Settings::OMGF_ADMIN_PAGE . '&tab=' . Settings::OMGF_SETTINGS_FIELD_OPTIMIZE . '#omgf-manage-optimized-fonts'
						)
					); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 *
	 */
	public function promo_advanced_processing() {
		?>
		<tr>
			<th scope="row"><?php echo __( 'Advanced Processing (Pro)', 'host-webfonts-local' ); ?></th>
			<td>
				<p class="description">
					<?php echo apply_filters(
						'omgf_detection_settings_advanced_processing_description',
						sprintf(
							__(
								'By default, OMGF scans each page for mentions of URLs pointing to fonts.googleapis.com. Since v6, OMGF configures these (Pro) enhancements automatically when following instructions provided by the Google Fonts Checker in the <a href="%s">Dashboard</a>.',
								'host-webfonts-local'
							),
							admin_url( 'admin.php?page=' . Settings::OMGF_ADMIN_PAGE . '&tab=' . Settings::OMGF_SETTINGS_FIELD_OPTIMIZE )
						)
					); ?>
				</p>
			</td>
		</tr>
		<?php
	}
}
