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
* @copyright: © 2025 Daan van den Bergh
* @url      : https://daan.dev
* * * * * * * * * * * * * * * * * * * */

namespace OMGF\Admin\Settings;

use OMGF\Admin\Dashboard;
use OMGF\Admin\Settings;
use OMGF\Helper as OMGF;

defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * @codeCoverageIgnore
 */
class Optimize extends Builder {
	/** @var array $optimized_fonts */
	private $optimized_fonts = [];

	/**
	 * Settings_Optimize constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->title = __( 'Optimize Local Google Fonts', 'host-webfonts-local' );

		add_action( 'omgf_optimize_settings_content', [ $this, 'open_task_manager' ], 20 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_before' ], 22 );
		add_action( 'omgf_optimize_settings_content', [ Dashboard::class, 'render_warnings' ], 24 );
		add_action( 'omgf_optimize_settings_content', [ Dashboard::class, 'render_status' ], 26 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_google_fonts_checker' ], 28 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_cache_management' ], 30 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_after' ], 30 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'close_task_manager' ], 31 );

		add_action( 'omgf_optimize_settings_content', [ $this, 'do_optimize_fonts_container' ], 40 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_before' ], 50 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_display_option' ], 60 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_promo_apply_font_display_globally' ], 70 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_promo_auto_preload' ], 80 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_after' ], 90 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_optimize_fonts_contents' ], 100 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'close_optimize_fonts_container' ], 300 );

		add_action( 'omgf_optimize_settings_content', [ $this, 'do_before' ], 350 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_test_mode' ], 375 );
		add_action( 'omgf_optimize_settings_content', [ $this, 'do_after' ], 400 );
	}

	/**
	 * Opens the Force info screen container.
	 *
	 * @return void
	 */
	public function open_task_manager() {
		?>
		<div class="omgf-task-manager postbox">
		<h3><?php echo __( 'Dashboard', 'host-webfonts-local' ); ?></h3>
		<p>
			<?php echo apply_filters(
				'omgf_dashboard_intro',
				__(
					'OMGF (Optimize My Google Fonts) automatically replaces Google Fonts stylesheets (e.g. https://fonts.googleapis.com/css?family=Open+Sans) with locally hosted copies. To remove/unload Google Fonts entirely or by style/weight, go to <a href="#omgf-manage-optimized-fonts">Optimize Local Fonts</a>.',
					'host-webfonts-local'
				),
			); ?>
		</p>
		<?php
	}

	public function do_google_fonts_checker() {
		?>
		<tr>
			<?php
			$this->do_checkbox(
				__( 'Run Google Fonts Checker in the background (Pro)', 'host-webfonts-local' ),
				'google_fonts_checker', ! empty( OMGF::get_option( 'google_fonts_checker' ) ),
				sprintf(
					__(
						'Normally, the Google Fonts Checker only runs for logged-in administrators. If you want to organically scan all your pages for Google Fonts, enable this option. %s',
						'host-webfonts-local'
					),
					$this->promo
				), ! defined( 'OMGF_PRO_ACTIVE' )
			); ?>
			<?php
			$this->do_select(
				__( 'Disable Google Fonts Checker after... (Pro)', 'host-webfonts-local' ),
				'checker_timeout',
				[
					'7'      => __( '7 Days (default)', 'host-webfonts-local' ),
					'14'     => __( '14 Days', 'host-webfonts-local' ),
					'30'     => __( '1 Month', 'host-webfonts-local' ),
					'always' => __( 'Always', 'host-webfonts-local' ),
				],
				OMGF::get_option( 'checker_timeout', 7 ),
				sprintf(
					__(
						'When enabled, the Google Fonts Checker loads a tiny snippet (<3KB) of JS code in your frontend. While the impact is barely noticeable, it\'s advised to run it only for a set period of time e.g. after you first installed OMGF Pro, switched themes or installed plugins. %s',
						'host-webfonts-local'
					),
					$this->promo
				),
				false, ! defined( 'OMGF_PRO_ACTIVE' ) || empty( OMGF::get_option( 'google_fonts_checker' ) )
			); ?>
		</tr>
		<?php
	}

	/**
	 * @return void
	 */
	public function do_cache_management() {
		?>
		<tr>
			<th class="omgf-align-row-header" scope="row"><?php _e( 'Manage Cache', 'host-webfonts-local' ); ?></th>
			<td class="task-manager-row">
				<a id="omgf-empty" data-init="<?php echo Settings::OMGF_ADMIN_PAGE; ?>"
				   data-nonce="<?php echo wp_create_nonce( Settings::OMGF_ADMIN_PAGE ); ?>"
				   class="omgf-empty button button-cancel"><?php _e(
						'Empty Cache',
						'host-webfonts-local'
					); ?></a>
				<a id="omgf-refresh" data-init="<?php echo Settings::OMGF_ADMIN_PAGE; ?>"
				   data-nonce="<?php echo wp_create_nonce( Settings::OMGF_ADMIN_PAGE ); ?>"
				   class="omgf-refresh button button-cancel"><?php _e(
						'Refresh Cache (and maintain settings)',
						'host-webfonts-local'
					); ?></a>
			</td>
		</tr>
		<?php
	}

	/**
	 * Close the container.
	 *
	 * @return void
	 */
	public function close_task_manager() {
		?>
		</div>
		<?php
	}

	/**
	 *
	 */
	public function do_display_option() {
		$options           = Settings::OMGF_FONT_DISPLAY_OPTIONS;
		$options[ 'swap' ] .= ' (' . __( 'recommended', 'host-webfonts-local' ) . ')';

		$this->do_select(
			__( 'Font-Display Option', 'host-webfonts-local' ),
			Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION,
			$options,
			OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION ),
			__(
				'Select which value to set the font-display attribute to. Defaults to Swap (recommended).',
				'host-webfonts-local'
			)
		);
	}

	/**
	 * Force Font-Display Option Site Wide
	 */
	public function do_promo_apply_font_display_globally() {
		$this->do_checkbox(
			__( 'Apply Font-Display Option Globally (Pro)', 'host-webfonts-local' ),
			'force_font_display', ! empty( OMGF::get_option( 'force_font_display' ) ),
			__(
				'Apply the above <code>font-display</code> attribute value to all <code>@font-face</code> statements found on your site to <strong>ensure text remains visible during webfont load</strong>.',
				'host-webfonts-local'
			) . ' ' . $this->promo, ! defined( 'OMGF_PRO_ACTIVE' )
		);
	}

	/**
	 * Auto Preload Fonts (Pro) option.
	 *
	 * @return void
	 */
	public function do_promo_auto_preload() {
		$this->do_checkbox(
			__( 'Smart Preload (Pro)', 'host-webfonts-local' ),
			'auto_preload', ! empty( OMGF::get_option( 'auto_preload' ) ),
			__(
				'When enabled, OMGF Pro will automatically detect which Google Fonts are loaded above the fold and preload them. Preload settings can be overwritten on a per-page level by clicking <strong>Auto-configure preload settings for this page</strong> in the top Admin Bar menu.',
				'host-webfonts-local'
			) . ' ' . $this->promo, ! defined( 'OMGF_PRO_ACTIVE' )
		);
	}

	/**
	 *
	 */
	public function do_optimize_fonts_container() {
		?>
		<div id="omgf-manage-optimized-fonts" class="omgf-optimize-fonts-container postbox">
		<h3>
			<?php echo __( 'Optimize Local Fonts', 'host-webfonts-local' ); ?>
		</h3>
		<p>
			<?php echo sprintf(
				__(
					'The options below allow you to tweak the performance of all fonts detected by %s. Don\'t know where or how to start optimizing your Google Fonts? That\'s okay. <a href="%s">This guide</a> will get you sorted.',
					'host-webfonts-local'
				),
				apply_filters( 'omgf_settings_page_title', 'OMGF' ),
				'https://daan.dev/blog/how-to/wordpress-google-fonts/'
			); ?>
		</p>
		<?php
	}

	/**
	 *
	 */
	public function do_optimize_fonts_contents() {
		/**
		 * Note: moving this to the constructor doesn't get it properly refreshed after a page reload.
		 */
		$this->optimized_fonts = OMGF::admin_optimized_fonts();
		?>
		<div class="omgf-optimize-fonts-contents">
		<span class="option-title">
			<?php echo __( 'Local Fonts', 'host-webfonts-local' ); ?>
			<span class="dashicons dashicons-info tooltip">
				<span class="tooltip-text">
					<span class="inline-text"><?php echo sprintf(
							__(
								'This list is populated with all Google Fonts stylesheets captured and downloaded throughout your site. It will grow organically when other Google Fonts stylesheets are discovered.',
								'host-webfonts-local'
							),
							'https://daan.dev/blog/how-to/wordpress-google-fonts/'
						); ?></span>
				</span>
			</span>
		</span>
			<?php if ( ! empty( $this->optimized_fonts ) ) : ?>
				<?php $this->do_optimized_fonts_manager(); ?>
			<?php else : ?>
				<div class="omgf-optimize-fonts-description">
					<?php $this->do_optimize_fonts_section(); ?>
				</div>
			<?php
			endif; ?>
		</div>
		<?php
	}

	/**
	 *
	 */
	private function do_optimized_fonts_manager() {
		?>
		<div class="omgf-optimize-fonts-manage">
			<div id="omgf-optimize-preload-warning" style="display: none;">
				<span class="omgf-optimize-preload-warning-close">×</span>
				<h3><?php echo __( 'Wow! That\'s a lot of Preloads! 😲', 'host-webfonts-local' ); ?></h3>
				<p>
					<?php echo __(
						'You\'ve selected 5 (!) font styles to load early. Selecting more font styles to preload will affect your site\'s performance. The <code>preload</code> attribute should only be used for font styles loaded above the fold i.e. <strong>The top portion of a web page that\'s visible without scrolling</strong>.',
						'host-webfonts-local'
					); ?>
				</p>
				<p>
					<?php echo sprintf(
						__(
							'Refer to the <a href="%s" target="_blank">Plugin documentation</a> for more information.',
							'host-webfonts-local'
						),
						'https://daan.dev/docs/omgf-pro/optimize-local-fonts/'
					); ?>
				</p>
			</div>
			<?php
			/**
			 * @since  v5.6.1 These hidden fields will make sure these options always appear in POST, even when
			 *               no boxes are checked.
			 * @action omgf_optimize_fonts_hidden_fields Allow add-ons to add hidden fields.
			 */ ?>
			<input type="hidden" name="<?php echo esc_attr( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS ); ?>"
				   value="0"/>
			<input type="hidden" name="<?php echo esc_attr( Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS ); ?>"
				   value="0"/>
			<?php do_action( 'omgf_optimize_local_fonts_hidden_fields' ); ?>
			<table>
				<thead>
				<tr>
					<td>&nbsp;</td>
					<th><?php echo __( 'Style', 'host-webfonts-local' ); ?></th>
					<th><?php echo __( 'Weight', 'host-webfonts-local' ); ?></th>
					<th><?php echo __( 'Load Early', 'host-webfonts-local' ); ?><span
							class="dashicons dashicons-info tooltip"><span
								class="tooltip-text"><span class="inline-text"><?php echo sprintf(
										__(
											'<a href="%s">Preload font files</a> prior to page rendering to improve perceived loading times. Only use preload for font files that are used above the fold.',
											'host-webfonts-local'
										),
										'https://daan.dev/blog/how-to/wordpress-google-fonts/#3-2-preloading-font-files-above-the-fold'
									); ?></span><img width="230" class="illustration"
													 src="<?php echo plugin_dir_url( OMGF_PLUGIN_FILE ) . 'assets/images/above-the-fold.png'; ?>"/></span></span></th>
					<th><?php echo __( 'Don\'t Load', 'host-webfonts-local' ); ?><span
							class="dashicons dashicons-info tooltip"><span
								class="tooltip-text"><span class="inline-text"><?php echo __(
										'Many themes/plugins load Google Fonts you\'re not using. Checking <strong>Don\'t Load</strong> will make sure they\'re not loaded in the frontend to save bandwidth.',
										'host-webfonts-local'
									); ?></span></span></span></th>
					<th><?php echo __( 'Fallback (Pro)', 'host-webfonts-local' ); ?><span
							class="dashicons dashicons-info tooltip"><span
								class="tooltip-text"><span class="inline-text"><?php echo sprintf(
										__(
											'Reduce Cumulative Layout Shift (CLS) by making sure all text using Google Fonts has a similar system font to display while the Google Fonts are being downloaded. %s',
											'host-webfonts-local'
										),
										$this->promo
									); ?></span></span></span></th>
					<th><?php echo __( 'Replace (Pro)', 'host-webfonts-local' ); ?><span
							class="dashicons dashicons-info tooltip"><span
								class="tooltip-text"><span class="inline-text"><?php echo sprintf(
										__(
											'When the <a href="%s">Replace option</a> is checked, the selected Fallback Font Stack will replace the corresponding Google Font family, instead of functioning as a fallback. %s',
											'host-webfonts-local'
										),
										'https://daan.dev/blog/how-to/wordpress-google-fonts/#7-4-specify-a-fallback-font-stack',
										$this->promo
									); ?></span></span></span></th>
				</tr>
				</thead>
				<?php
				$cache_handles   = OMGF::cache_keys();
				$disable_preload = apply_filters( 'omgf_local_fonts_disable_preload', false );
				?>
				<?php foreach ( $this->optimized_fonts as $handle => $fonts ) : ?>
					<?php
					if ( ! OMGF::get_cache_key( $handle ) ) {
						$cache_handles[] = $handle;
					}
					?>
					<tbody class="stylesheet" id="<?php echo $handle; ?>">
					<tr>
						<th colspan="6"><?php echo sprintf(
								__( 'Stylesheet handle: %s', 'host-webfonts-local' ),
								$handle
							); ?></th>
					</tr>
					<?php foreach ( $fonts as $font ) : ?>
						<?php
						if ( ! is_object( $font ) || count( (array) $font->variants ) <= 0 ) {
							continue;
						}
						?>
						<tr class="font-family" data-id="<?php echo esc_attr( $handle . '-' . $font->id ); ?>">
							<td colspan="5">
                                <span class="family"><em><?php echo esc_html(
											rawurldecode( $font->family )
										); ?></em></span> <span class="unload-mass-action">(<a class="unload-italics"><?php echo esc_html__(
											'Unload italics',
											'host-webfonts-local'
										); ?></a> <span class="dashicons dashicons-info tooltip"><span class="tooltip-text"><?php echo __(
												'In most situations you can safely unload all Italic font styles. Modern browsers are capable of mimicking Italic font styles.',
												'host-webfonts-local'
											); ?></span></span> | <a class="unload-all"><?php echo esc_html__( 'Unload all', 'host-webfonts-local' ); ?></a> | <a class="load-all"><?php echo esc_html__(
											'Load all',
											'host-webfonts-local'
										); ?></a>)</span></td>
							<td class="fallback-font-stack">
								<select data-handle="<?php echo esc_attr( $handle ); ?>" data-font-id="<?php echo esc_attr( $handle . '-' . $font->id ); ?>" <?php echo esc_attr(
									! defined( 'OMGF_PRO_ACTIVE' ) ? 'disabled="disabled"' : ''
								); ?> name="omgf_pro_fallback_font_stack[<?php echo esc_attr( $handle ); ?>][<?php echo esc_attr( $font->id ); ?>]">
									<option value=''><?php echo esc_attr__( 'None (default)', 'host-webfonts-local' ); ?></option>
									<?php foreach ( apply_filters( 'omgf_pro_fallback_font_stacks', Settings::OMGF_FALLBACK_FONT_STACKS_OPTIONS ) as $value => $label ) : ?>
										<option <?php echo esc_attr(
											defined( 'OMGF_PRO_ACTIVE' ) &&
											isset( OMGF::get_option( 'omgf_pro_fallback_font_stack' )[ $handle ][ $font->id ] ) &&
											OMGF::get_option( 'omgf_pro_fallback_font_stack' )[ $handle ][ $font->id ] === $value ? 'selected' : ''
										); ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td class="replace">
								<?php
								$replace  = defined( 'OMGF_PRO_ACTIVE' ) &&
								isset( OMGF::get_option( 'omgf_pro_replace_font' )[ $handle ][ $font->id ] ) &&
								OMGF::get_option( 'omgf_pro_replace_font' )[ $handle ][ $font->id ] === 'on' ? 'checked' : '';
								$fallback = defined( 'OMGF_PRO_ACTIVE' ) && isset(
										OMGF::get_option( 'omgf_pro_fallback_font_stack' )[ $handle ][ $font->id ]
									) && OMGF::get_option( 'omgf_pro_fallback_font_stack' )[ $handle ][ $font->id ] !== '';
								?>
								<?php do_action( 'omgf_optimize_local_fonts_replace', $handle, $font->id ); ?>
								<input autocomplete="off" type="checkbox" class="replace" <?php echo esc_attr(
									$replace
								); ?> <?php echo esc_attr(
									$fallback ? '' : 'disabled'
								); ?> <?php echo ! defined( 'OMGF_PRO_ACTIVE' ) ? 'disabled' : ''; ?>
									   name="omgf_pro_replace_font[<?php echo esc_attr(
										   $handle
									   ); ?>][<?php echo esc_attr( $font->id ); ?>]"/>
							</td>
						</tr>
						<?php $id = ''; ?>
						<?php foreach ( $font->variants as $variant ) : ?>
							<?php
							/**
							 * @since v5.3.0: Variable Fonts are pulled directly from the Google Fonts API,
							 *                which creates @font-face statements for each separate subset.
							 *                This deals with the duplicate display of font styles. Which also
							 *                means unloading and/or preloading will unload/preload all available
							 *                subsets. It's a bit bloaty, but there's no alternative.
							 *                To better deal with this, I've introduced the Used Subset(s) feature
							 *                in this version.
							 */
							// phpcs:ignore
							if ( $id === $variant->fontWeight . $variant->fontStyle ) {
								continue;
							}

							// phpcs:ignore
							$id = $variant->fontWeight . $variant->fontStyle;
							?>
							<tr>
								<td></td>
								<?php
								$preload = OMGF::preloaded_fonts()[ $handle ][ $font->id ][ $variant->id ] ?? '';

								if ( $preload ) {
									$unload = false;
								} else {
									$unload = OMGF::unloaded_fonts()[ $handle ][ $font->id ][ $variant->id ] ?? '';
								}

								$class = $handle . '-' . $font->id . '-' . $variant->id;
								?>
								<td><?php echo esc_attr( $variant->fontStyle ); ?></td>
								<td><?php echo esc_attr( $variant->fontWeight ); ?></td>
								<td class="preload-<?php echo esc_attr( $class ); ?>">
									<input type="hidden" name="<?php echo esc_attr( Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS ); ?>[<?php echo esc_attr( $handle ); ?>][<?php echo esc_attr(
										$font->id
									); ?>][<?php echo esc_attr( $variant->id ); ?>]" value="0"/>
									<input data-handle="<?php echo esc_attr( $handle ); ?>"
										   data-font-id="<?php echo esc_attr( $handle . '-' . $font->id ); ?>"
										   autocomplete="off" type="checkbox"
										   class="preload"
										   name="<?php echo esc_attr(
											   Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS
										   ); ?>[<?php echo esc_attr(
											   $handle
										   ); ?>][<?php echo esc_attr( $font->id ); ?>][<?php echo esc_attr(
											   $variant->id
										   ); ?>]"
										   value="<?php echo esc_attr( $variant->id ); ?>" <?php echo $preload ? 'checked="checked"' : ''; ?> <?php echo $unload || $disable_preload ? 'disabled' : ''; ?> />
								</td>
								<td class="unload-<?php echo esc_attr( $class ); ?>">
									<input type="hidden"
										   name="<?php echo esc_attr(
											   Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS
										   ); ?>[<?php echo esc_attr(
											   $handle
										   ); ?>][<?php echo esc_attr( $font->id ); ?>][<?php echo esc_attr(
											   $variant->id
										   ); ?>]" value="0"/>
									<input data-handle="<?php echo esc_attr( $handle ); ?>"
										   data-font-id="<?php echo esc_attr( $handle . '-' . $font->id ); ?>"
										   autocomplete="off" type="checkbox"
										   class="unload"
										   name="<?php echo esc_attr(
											   Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS
										   ); ?>[<?php echo esc_attr(
											   $handle
										   ); ?>][<?php echo esc_attr( $font->id ); ?>][<?php echo esc_attr(
											   $variant->id
										   ); ?>]"
										   value="<?php echo esc_attr( $variant->id ); ?>" <?php echo $unload ? 'checked="checked"' : ''; ?> <?php echo $preload ? 'disabled' : ''; ?> />
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
					</tbody>
				<?php endforeach; ?>
			</table>
			<input type="hidden" name="<?php echo Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS; ?>"
				   value="<?php echo base64_encode( serialize( $this->optimized_fonts ) ); ?>"/>
			<input id="<?php echo Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS; ?>" type="hidden"
				   name="omgf_settings[<?php echo Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS; ?>]"
				   value="<?php echo esc_attr( implode( ',', OMGF::unloaded_stylesheets() ) ); ?>"/>
			<input id="<?php echo Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS; ?>" type="hidden"
				   name="omgf_settings[<?php echo Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS; ?>]"
				   value="<?php echo esc_attr( implode( ',', $cache_handles ) ); ?>"/>
		</div>
		<?php
	}

	/**
	 *
	 */
	public function do_optimize_fonts_section() {
		?>
		<div class="omgf-optimize-fonts">
			<div class="omgf-optimize-fonts-tooltip">
				<p>
					<span class="dashicons-before dashicons-info-outline"></span>
					<em><?php echo sprintf(
							__(
								'After clicking <strong>Save & Optimize</strong>, this section will be populated with any Google Fonts (along with requested styles and available options) requested on <code>%s</code>. The list will grow organically if other Google Fonts stylesheets are discovered throughout your site.',
								'host-webfonts-local'
							),
							get_site_url()
						); ?></em> [<a href="https://daan.dev/docs/omgf-pro-troubleshooting/no-fonts-detected/"
									   target="_blank"><?php echo __(
							'Why aren\'t my Google Fonts showing up on this list?',
							'host-webfonts-local'
						); ?></a>]
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 *
	 */
	public function close_optimize_fonts_container() {
		?>
		</div>
		<?php
	}

	/**
	 * Test Mode
	 */
	public function do_test_mode() {
		$this->do_checkbox(
			__( 'Test Mode', 'host-webfonts-local' ),
			Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE, ! empty( OMGF::get_option( Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE ) ),
			__(
				'With this setting enabled, OMGF\'s optimizations will only be visible to logged in administrators or when <code>?omgf=1</code> is added to an URL in the frontend.',
				'host-webfonts-local'
			)
		);
	}
}
