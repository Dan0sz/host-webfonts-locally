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
* @copyright: © 2023 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Admin\Settings;

use OMGF\Helper as OMGF;

defined( 'ABSPATH' ) || exit;

class Detection extends Builder {

	public function __construct() {
		 parent::__construct();

		$this->title = __( 'Google Fonts Detection Settings', 'host-webfonts-local' );

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
	public function do_description() {      ?>
		<p>
			<?php echo __( 'These settings affect the detection mechanism and in which areas it searches (i.e. how deep it digs) to find Google Fonts. If you want to remove (instead of replace) the Google Fonts your WordPress configuration currently uses, set <strong>Google Fonts Processing</strong> to Remove.', 'host-webfonts-local' ); ?>
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
					<?php echo sprintf( __( 'By default, OMGF replaces Google Fonts stylesheets (e.g. <code>https://fonts.googleapis.com/css?family=Open+Sans</code>) with locally hosted copies. This behavior can be tweaked further using the <strong>Advanced Processing (Pro)</strong> option. To remove/unload Google Fonts, go to <em>Local Fonts</em> > <a href="%s"><em>Optimize Local Fonts</em></a> and click <strong>Unload all</strong> next to the stylesheet handle you\'d like to remove.', 'host-webfonts-local' ), admin_url( 'options-general.php?page=optimize-webfonts&tab=omgf-optimize-settings#omgf-manage-optimized-fonts' ) ); ?>
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
				<fieldset id="" class="scheme-list">
					<?php foreach ( $this->advanced_processing_pro_options() as $name => $data ) : ?>
						<?php
						$checked  = ! empty( OMGF::get_option( $name ) );
						$disabled = ! defined( 'OMGF_PRO_ACTIVE' ) ? 'disabled' : '';
						?>
						<label for="<?php echo esc_attr( $name ); ?>">
							<input type="hidden" name="omgf_settings[<?php echo esc_attr( $name ); ?>]" value="0" />
							<input type="checkbox" name="omgf_settings[<?php echo esc_attr( $name ); ?>]" id="<?php echo esc_attr( $name ); ?>" <?php echo esc_attr( $checked ? 'checked="checked"' : '' ); ?> <?php echo esc_attr( $disabled ); ?> value="on" /><?php echo wp_kses( $data['label'], $this->allowed_html ); ?>
							&nbsp;
						</label>
					<?php endforeach; ?>
				</fieldset>
				<p class="description">
					<?php echo apply_filters( 'omgf_detection_settings_advanced_processing_description', sprintf( __( 'By default, OMGF scans each page for mentions of URLs pointing to fonts.googleapis.com. If you need OMGF to "dig deeper", e.g. inside a theme\'s/plugin\'s CSS stylesheets or (Web Font Loader) JS files, <a href="%s" target="_blank">enable these options</a> to increase its level of detection. Best used in combination with a page caching plugin.', 'host-webfonts-local' ), 'https://daan.dev/docs/omgf-pro/detection-settings-advanced-processing/' ) ) . ' ' . $this->promo; ?>
				</p>
				<ul>
					<?php foreach ( $this->advanced_processing_pro_options() as $name => $data ) : ?>
						<li><strong><?php echo wp_kses( $data['label'], $this->allowed_html ); ?></strong>: <?php echo wp_kses( $data['description'], $this->allowed_html ); ?></li>
					<?php endforeach; ?>
				</ul>
			</td>
		</tr>
		<?php
	}

	/**
	 * @return array
	 */
	private function advanced_processing_pro_options() {
		return [
			'process_inline_styles'     => [
				'label'       => __( 'Process Inline Styles', 'host-webfonts-local' ),
				'description' => __( 'Process all inline <code>@font-face</code> and <code>@import</code> rules loading Google Fonts.', 'host-webfonts-local' ),
			],
			'process_local_stylesheets' => [
				'label'       => __( 'Process Local Stylesheets', 'host-webfonts-local' ),
				'description' => __( 'Scan stylesheets loaded by your theme and plugins for <code>@import</code> and <code>@font-face</code> statements loading Google Fonts and process them.', 'host-webfonts-local' ),
			],
			'process_webfont_loader'    => [
				'label'       => __( 'Process Webfont Loader', 'host-webfonts-local' ),
				'description' => __( 'Process <code>webfont.js</code> libraries and the corresponding configuration defining which Google Fonts to load.', 'host-webfonts-local' ),
			],
			'process_early_access'      => [
				'label'       => __( 'Process Early Access', 'host-webfonts-local' ),
				'description' => __( 'Process Google Fonts loaded from <code>fonts.googleapis.com/earlyaccess</code> or <code>fonts.gstatic.com/ea</code>.', 'host-webfonts-local' ),
			],
		];
	}
}
