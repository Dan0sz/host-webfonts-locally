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

class OMGF_Admin_Settings_Advanced extends OMGF_Admin_Settings_Builder
{
	/**
	 * OMGF_Admin_Settings_Advanced constructor.
	 */
	public function __construct () {
		parent::__construct();
		
		$this->title = __( 'Advanced Settings', $this->plugin_text_domain );
		
		// Open
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_title' ], 10 );
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_description' ], 15 );
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_before' ], 20 );
		
		// Settings
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_promo_fonts_processing' ], 50 );
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_promo_process_resource_hints' ], 60 );
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_promo_force_subsets' ], 70 );
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_cdn_url' ], 80 );
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_cache_uri' ], 90 );
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_relative_url' ], 100 );
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_uninstall' ], 110 );
		
		// Close
		add_filter( 'omgf_advanced_settings_content', [ $this, 'do_after' ], 200 );
	}
	
	/**
	 * Description
	 */
	public function do_description () {
		?>
        <p>
        </p>
		<?php
	}
	
	/**
	 *
	 */
	public function do_promo_fonts_processing () {
		?>
        <tr>
            <th scope="row"><?= __( 'Google Fonts Processing (Pro)', $this->plugin_text_domain ); ?></th>
            <td>
                <fieldset id="" class="scheme-list">
					<?php foreach ( $this->fonts_processing_pro_options() as $name => $data ): ?>
						<?php
						$checked  = defined( strtoupper( $name ) ) ? constant( strtoupper( $name ) ) : false;
						$disabled = apply_filters( $name . '_setting_disabled', true ) ? 'disabled' : '';
						?>
                        <label for="<?= $name; ?>">
                            <input type="checkbox" name="<?= $name; ?>" <?= $checked ? 'checked="checked"' : ''; ?> <?= $disabled; ?> /><?= $data['label']; ?>
                            &nbsp;
                        </label>
					<?php endforeach; ?>
                </fieldset>
                <p class="description">
					<?= $this->promo; ?>
                <ul>
					<?php foreach ( $this->fonts_processing_pro_options() as $name => $data ): ?>
                        <li><strong><?= $data['label']; ?></strong>: <?= $data['description']; ?></li>
					<?php endforeach; ?>
                </ul>
                </p>
            </td>
        </tr>
		<?php
	}
	
	/**
	 * @return array
	 */
	private function fonts_processing_pro_options () {
		return [
			'omgf_pro_process_stylesheets'    => [
				'label'       => __( 'Process Stylesheets', $this->plugin_text_domain ),
				'description' => __( 'Process stylesheets loaded from <code>fonts.googleapis.com</code> or <code>fonts.gstatic.com</code>.', $this->plugin_text_domain )
			],
			'omgf_pro_process_inline_styles'  => [
				'label'       => __( 'Process Inline Styles', $this->plugin_text_domain ),
				'description' => __( 'Process all <code>@font-face</code> and <code>@import</code> rules loading Google Fonts.', $this->plugin_text_domain )
			],
			'omgf_pro_process_webfont_loader' => [
				'label'       => __( 'Process Webfont Loader', $this->plugin_text_domain ),
				'description' => __( 'Process <code>webfont.js</code> libraries and the corresponding configuration defining which Google Fonts to load.', $this->plugin_text_domain )
			]
		];
	}
	
	/**
	 *
	 */
	public function do_promo_process_resource_hints () {
		$this->do_checkbox(
			__( 'Remove Resource Hints (Pro)', $this->plugin_text_domain ),
			'omgf_pro_process_resource_hints',
			defined( 'OMGF_PRO_PROCESS_RESOURCE_HINTS' ) ? OMGF_PRO_PROCESS_RESOURCE_HINTS : false,
			__( 'Remove all <code>link</code> elements with a <code>rel</code> attribute value of <code>dns-prefetch</code>, <code>preload</code> or <code>preconnect</code> pointing to <code>fonts.googleapis.com</code> or <code>fonts.gstatic.com</code>.', $this->plugin_text_domain ) . ' ' . $this->promo,
			true
		);
	}
	
	/**
	 *
	 */
	public function do_promo_force_subsets () {
		$this->do_select(
			__( 'Force Subsets (Pro)', $this->plugin_text_domain ),
			'omgf_pro_force_subsets',
			OMGF_Admin_Settings::OMGF_FORCE_SUBSETS_OPTIONS,
			defined( 'OMGF_PRO_FORCE_SUBSETS' ) ? OMGF_PRO_FORCE_SUBSETS : [],
			__( 'If a theme or plugin loads subsets you don\'t need, use this option to force all Google Fonts to be loaded in the selected subsets.', $this->plugin_text_domain ) . ' ' . $this->promo,
			true,
			true
		);
	}
	
	/**
	 *
	 */
	public function do_cache_uri () {
		$this->do_text(
			__( 'Serve font files from...', $this->plugin_text_domain ),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_URI,
			__( 'e.g. /app/uploads/omgf', $this->plugin_text_domain ),
			OMGF_CACHE_URI,
			__( 'The relative path to serve font files from. Useful for when you\'re using security through obscurity plugins, such as WP Hide. If left empty, the cache directory specified under Basic Settings will be used.', $this->plugin_text_domain )
		);
	}
	
	/**
	 *
	 */
	public function do_relative_url () {
		$this->do_checkbox(
			__( 'Use Relative URLs?', $this->plugin_text_domain ),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_RELATIVE_URL,
			OMGF_RELATIVE_URL,
			__( 'Use relative instead of absolute (full) URLs to generate the stylesheet.', $this->plugin_text_domain )
		);
	}
	
	/**
	 *
	 */
	public function do_cdn_url () {
		$this->do_text(
			__( 'Serve fonts from CDN', $this->plugin_text_domain ),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_CDN_URL,
			__( 'e.g. https://cdn.mydomain.com', $this->plugin_text_domain ),
			OMGF_CDN_URL,
			__( "If you're using a CDN, enter the URL here incl. protocol (e.g. <code>https://</code>.) Leave empty when using CloudFlare.", $this->plugin_text_domain )
		);
	}
	
	/**
	 *
	 */
	public function do_uninstall () {
		$this->do_checkbox(
			__( 'Remove settings and files at uninstall?', $this->plugin_text_domain ),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL,
			OMGF_UNINSTALL,
			__( 'Warning! This will remove all settings and cached fonts upon plugin deletion.', $this->plugin_text_domain )
		);
	}
}
