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

class OMGF_Admin_Settings_Optimize extends OMGF_Admin_Settings_Builder
{
	/** @var array $optimized_fonts */
	private $optimized_fonts;
	
	/**
	 * OMGF_Admin_Settings_Optimize constructor.
	 */
	public function __construct () {
		parent::__construct();
		
		$this->title = __( 'Optimize Google Fonts', $this->plugin_text_domain );
		
		add_filter( 'omgf_optimize_settings_content', [ $this, 'do_title' ], 10 );
		
		add_filter( 'omgf_optimize_settings_content', [ $this, 'do_description' ], 15 );
		add_filter( 'omgf_optimize_settings_content', [ $this, 'do_before' ], 20 );
		
		add_filter( 'omgf_optimize_settings_content', [ $this, 'do_optimization_mode' ], 30 );
		
		add_filter( 'omgf_optimize_settings_content', [ $this, 'do_after' ], 100 );
		
		add_filter( 'omgf_optimize_settings_content', [ $this, 'do_optimize_fonts_container' ], 200 );
		
		add_filter( 'omgf_optimize_settings_content', [ $this, 'do_optimize_fonts_contents' ], 250 );
		
		add_filter( 'omgf_optimize_settings_content', [ $this, 'close_optimize_fonts_container' ], 300 );
	}
	
	/**
	 *
	 */
	public function do_description () {
		?>
        <p>
            Testing testing 1, 2, 3...
        </p>
		<?php
	}
	
	public function do_optimization_mode () {
		$this->do_radio(
			__( 'Optimization Mode', $this->plugin_text_domain ),
			OMGF_Admin_Settings::OMGF_OPTIMIZATION_MODE,
			OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZATION_MODE,
			OMGF_OPTIMIZATION_MODE,
			__( '<strong>Manual</strong> gives you all control and allows you to grab stylesheets yourself by specifying pages. <strong>Automatic</strong> will run silently in the background and optimize stylesheets (if necessary) as pages are requested.', $this->plugin_text_domain )
		);
	}
	
	/**
	 *
	 */
	public function do_optimize_fonts_container () {
		?>
        <div class="omgf-optimize-fonts-container welcome-panel">
		<?php
	}
	
	/**
	 *
	 */
	public function do_optimize_fonts_contents () {
		$this->optimized_fonts = omgf_init()::optimized_fonts();
		?>
        <h3><?= $this->optimized_fonts ? 'Manage Optimized Google Fonts' : __( 'Are you ready to Optimize your Google Fonts?', $this->plugin_text_domain ); ?></h3>
		<?php if ( $this->optimized_fonts ): ?>
			<?= $this->do_optimized_fonts_manager(); ?>
		<?php else: ?>
            <div class="omgf-optimize-fonts-description">
				<?php
				$this->do_manual_template();
				$this->do_automatic_template();
				?>
            </div>
		<?php endif;
	}
	
	/**
	 *
	 */
	private function do_optimized_fonts_manager () {
		?>
		<?php if ( $this->optimized_fonts ): ?>
            <div class="omgf-optimize-fonts-manage">
                <p>

                </p>
                <table>
                    <thead>
                    <tr>
                        <td>&nbsp;</td>
                        <th><?= __( 'Style', $this->plugin_text_domain ); ?></th>
                        <th><?= __( 'Weight', $this->plugin_text_domain ); ?></th>
                        <th class="preload"><?= __( 'Preload', $this->plugin_text_domain ); ?></th>
                        <th class="unload"><?= __( 'Do not load', $this->plugin_text_domain ); ?></th>
                    </tr>
                    </thead>
					<?php foreach ( $this->optimized_fonts as $handle => $fonts ): ?>
                        <tbody class="stylesheet" id="<?= $handle; ?>">
                        <?php foreach ( $fonts as $font ): ?>
                            <th><?= $font->family; ?> <span class="handle">(<?= $handle; ?>)</span></th>
	                        <?php foreach ( $font->variants as $variant ): ?>
                                <tr>
                                    <td></td>
			                        <?php
			                        $preload = get_option( OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS )[ $font->id ][ $variant->id ] ?? '';
			                        $unload  = get_option( OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS )[ $font->id ][ $variant->id ] ?? '';
			                        ?>
                                    <td><?= $variant->fontStyle; ?></td>
                                    <td><?= $variant->fontWeight; ?></td>
                                    <td>
                                        <input type="checkbox"
                                               name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS; ?>[<?= $font->id; ?>][<?= $variant->id; ?>]"
                                               value="1" <?= $preload ? 'checked="checked"' : ''; ?> />
                                    </td>
                                    <td>
                                        <input type="checkbox"
                                               name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS; ?>[<?= $font->id; ?>][<?= $variant->id; ?>]"
                                               value="1" <?= $unload ? 'checked="checked"' : ''; ?> />
                                    </td>
                                </tr>
	                        <?php endforeach; ?>
                        <?php endforeach; ?>
                        </tbody>
					<?php endforeach; ?>
                </table>
                <div class="omgf-optimize-fonts-tooltip">
                    <p>
                        <span class="dashicons-before dashicons-info-outline"></span>
						<?php if ( OMGF_OPTIMIZATION_MODE == 'manual' ): ?>
                            <em><?= sprintf( __( "This list is populated with all Google Fonts captured from <strong>%s</strong>. Optimizations will be applied on every page using these fonts. If you want to optimize additional Google Fonts from other pages, temporarily switch to <strong>Automatic</strong> and visit the pages containing the stylesheets you'd like to optimize. This list will automatically be populated with the captured fonts. When you feel the list is complete, switch back to <strong>Manual</strong>.", $this->plugin_text_domain ), OMGF_MANUAL_OPTIMIZE_URL ); ?></em>
						<?php else: ?>
							<?php
							$no_cache_param = '?omgf_optimize=' . substr( md5( microtime() ), rand( 0, 26 ), 5 );
							?>
                            <em><?= sprintf( __( "This list is automatically populated with Google Fonts throughout your entire site. Optimizations will be applied on every page using these fonts. <strong>Automatic</strong> mode might not work when a Full Page Cache plugin is activated. If this list is not being populated with Google Fonts, you could try to visit your frontend and append the following parameter to the URL: <strong>%s</strong>", $this->plugin_text_domain ), $no_cache_param ); ?></em>
						<?php endif; ?>
                    </p>
                </div>
                <input type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS; ?>"
                       value="<?= serialize( $this->optimized_fonts ); ?>"/>
                <input type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL; ?>"
                       value="<?= OMGF_MANUAL_OPTIMIZE_URL; ?>"/>
            </div>
		<?php endif;
	}
	
	/**
	 *
	 */
	public function do_manual_template () {
		?>
        <div class="omgf-optimize-fonts-manual" <?= OMGF_OPTIMIZATION_MODE == 'manual' ? '' : 'style="display: none;"'; ?>>
            <p>
				<?= sprintf( __( "You've chosen to <strong>optimize your Google Fonts manually</strong>. OMGF will <u>not</u> run automatically and will <strong>%s</strong> the requested Google Fonts throughout your website that were captured on the post/page you defined. A Cross-Browser compatible stylesheet will be generated for all requested Google Fonts.", $this->plugin_text_domain ), OMGF_FONT_PROCESSING ); ?>
            </p>
            <div class="omgf-optimize-fonts-pros">
                <h3>
                    <span class="dashicons-before dashicons-yes"></span> <?= __( 'Pros:', $this->plugin_text_domain ); ?>
                </h3>
                <ul>
                    <li><?= __( 'A small performance boost, because no calls to OMGF\'s Download API are made in the frontend.', $this->plugin_text_domain ); ?></li>
                </ul>
            </div>
            <div class="omgf-optimize-fonts-cons">
                <h3>
                    <span class="dashicons-before dashicons-no"></span> <?= __( 'Cons', $this->plugin_text_domain ); ?>
                </h3>
                <ul>
                    <li><?= __( 'High maintenance if you use a lot of different fonts on different pages.', $this->plugin_text_domain ); ?></li>
                </ul>
            </div>
            <p>
				<?= __( 'Enter the URL of the post/page you\'d like to scan for Google Fonts. The detected and optimized stylesheets will be applied on all pages where they\'re used.', $this->plugin_text_domain ); ?>
            </p>
            <label for="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL; ?>">
				<?= __( 'URL to Scan', $this->plugin_text_domain ); ?>
                <input id="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL; ?>" type="text"
                       name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL; ?>" value="<?= OMGF_MANUAL_OPTIMIZE_URL; ?>"/>
            </label>
            <div class="omgf-optimize-fonts-tooltip">
                <p>
                    <span class="dashicons-before dashicons-info-outline"></span>
                    <em><?= __( 'This section will be populated with all captured fonts, font styles and available options after saving changes.', $this->plugin_text_domain ); ?></em>
                </p>
            </div>
        </div>
		<?php
	}
	
	/**
	 *
	 */
	public function do_automatic_template () {
		?>
        <div class="omgf-optimize-fonts-automatic" <?= OMGF_OPTIMIZATION_MODE == 'auto' ? '' : 'style="display: none;"'; ?>>
            <p>
				<?= sprintf( __( "You've chosen to <strong>optimize your Google Fonts automatically</strong>. OMGF will run silently in the background and <strong>%s</strong> all requested Google Fonts. If the captured stylesheet doesn't exist yet, a call is sent to OMGF's Download API to download the font files and generate a Cross-Browser compatible stylesheet.", $this->plugin_text_domain ), OMGF_FONT_PROCESSING ); ?>
            </p>
            <div class="omgf-optimize-fonts-pros">
                <h3>
                    <span class="dashicons-before dashicons-yes"></span> <?= __( 'Pros:', $this->plugin_text_domain ); ?>
                </h3>
                <ul>
                    <li><?= __( 'No maintenance.', $this->plugin_text_domain ); ?></li>
                </ul>
            </div>
            <div class="omgf-optimize-fonts-cons">
                <h3>
                    <span class="dashicons-before dashicons-no"></span> <?= __( 'Cons', $this->plugin_text_domain ); ?>
                </h3>
                <ul>
                    <li><?= __( "Visitors might experience slow loading times, the 1st time they land on a page containing unoptimized Google Fonts. Every subsequent request to that page (and other pages using that same stylesheet) will be fast.", $this->plugin_text_domain ); ?></li>
                </ul>
            </div>
            <div class="omgf-optimize-fonts-tooltip">
                <p>
                    <span class="dashicons-before dashicons-info-outline"></span>
                    <em><?= __( "After saving your changes, this section will be populated with all captured fonts, font styles and available options as your site's frontend is visited by you or others.", $this->plugin_text_domain ); ?></em>
                </p>
            </div>
        </div>
		<?php
	}
	
	/**
	 *
	 */
	public function close_optimize_fonts_container () {
		?>
        </div>
		<?php
	}
}
