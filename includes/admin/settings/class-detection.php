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
 * @copyright: (c) 2021 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Admin_Settings_Detection extends OMGF_Admin_Settings_Builder
{
	public function __construct()
	{
		parent::__construct();

		$this->title = __('Google Fonts Detection Settings', $this->plugin_text_domain);

		// Open
		add_filter('omgf_detection_settings_content', [$this, 'do_title'], 10);
		add_filter('omgf_detection_settings_content', [$this, 'do_description'], 15);
		add_filter('omgf_detection_settings_content', [$this, 'do_before'], 20);

		// Settings
		add_filter('omgf_detection_settings_content', [$this, 'do_process_google_fonts'], 30);
		add_filter('omgf_detection_settings_content', [$this, 'do_promo_advanced_processing'], 40);
		add_filter('omgf_detection_settings_content', [$this, 'do_promo_safe_mode'], 50);
		add_filter('omgf_detection_settings_content', [$this, 'do_promo_fonts_processing'], 60);
		add_filter('omgf_detection_settings_content', [$this, 'do_promo_process_resource_hints'], 70);

		// Close
		add_filter('omgf_detection_settings_content', [$this, 'do_after'], 100);
	}

	/**
	 * Description
	 */
	public function do_description()
	{
?>
		<p>
			<?= __('These settings affect OMGF\'s automatic detection mechanism and how it treats the Google Fonts your theme and plugins use. If you want to use OMGF to remove the Google Fonts your WordPress configuration currently uses, set <strong>Google Fonts Processing</strong> to Remove.', $this->plugin_text_domain); ?>
		</p>
	<?php
	}

	/**
	 *
	 */
	public function do_promo_fonts_processing()
	{
	?>
		<tr>
			<th scope="row"><?= __('Google Fonts Processing (Pro)', $this->plugin_text_domain); ?></th>
			<td>
				<fieldset id="" class="scheme-list">
					<?php foreach ($this->fonts_processing_pro_options() as $name => $data) : ?>
						<?php
						$checked  = defined(strtoupper($name)) ? constant(strtoupper($name)) : false;
						$disabled = apply_filters($name . '_setting_disabled', true) ? 'disabled' : '';
						?>
						<label for="<?= $name; ?>">
							<input type="checkbox" name="<?= $name; ?>" <?= $checked ? 'checked="checked"' : ''; ?> <?= $disabled; ?> /><?= $data['label']; ?>
							&nbsp;
						</label>
					<?php endforeach; ?>
				</fieldset>
				<p class="description">
					<?= $this->promo; ?>
				</p>
				<ul>
					<?php foreach ($this->fonts_processing_pro_options() as $name => $data) : ?>
						<li><strong><?= $data['label']; ?></strong>: <?= $data['description']; ?></li>
					<?php endforeach; ?>
				</ul>
			</td>
		</tr>
<?php
	}

	/**
	 * @return array
	 */
	private function fonts_processing_pro_options()
	{
		return [
			'omgf_pro_process_stylesheets'    => [
				'label'       => __('Process External Stylesheets', $this->plugin_text_domain),
				'description' => __('Process stylesheets loaded from <code>fonts.googleapis.com</code> or <code>fonts.gstatic.com</code>.', $this->plugin_text_domain)
			],
			'omgf_pro_process_stylesheet_imports' => [
				'label'		  => __('Process Stylesheet Imports', $this->plugin_text_domain),
				'description' => __('Scan stylesheets loaded by your theme and plugins for <code>@import</code> statements loading Google Fonts and process them.', $this->plugin_text_domain)
			],
			'omgf_pro_process_inline_styles'  => [
				'label'       => __('Process Inline Styles', $this->plugin_text_domain),
				'description' => __('Process all inline <code>@font-face</code> and <code>@import</code> rules loading Google Fonts.', $this->plugin_text_domain)
			],
			'omgf_pro_process_webfont_loader' => [
				'label'       => __('Process Webfont Loader', $this->plugin_text_domain),
				'description' => __('Process <code>webfont.js</code> libraries and the corresponding configuration defining which Google Fonts to load.', $this->plugin_text_domain)
			],
			'omgf_pro_process_early_access'   => [
				'label'       => __('Process Early Access', $this->plugin_text_domain),
				'description' => __('Process stylesheets loaded from <code>fonts.googleapis.com/earlyaccess</code> or <code>fonts.gstatic.com/ea</code>.', $this->plugin_text_domain)
			]
		];
	}

	/**
	 *
	 */
	public function do_promo_advanced_processing()
	{
		$this->do_checkbox(
			__('Advanced Processing (Pro)', $this->plugin_text_domain),
			'omgf_pro_advanced_processing',
			defined('OMGF_PRO_ADVANCED_PROCESSING') ? OMGF_PRO_ADVANCED_PROCESSING : false,
			__('By default, OMGF scans for Google Fonts which are registered/enqueued using the <code>wp_enqueue_scripts()</code> action in WordPress\' header (<code>wp_head()</code>). Enabling this option will process all Google Fonts throughout the entire document. This setting can be fine-tuned using the settings below.', $this->plugin_text_domain) . ' ' . $this->promo,
			true
		);
	}

	/**
	 * Add option for Safe Mode (Pro)
	 * 
	 * @return void 
	 */
	public function do_promo_safe_mode()
	{
		$this->do_checkbox(
			__('Safe Mode (Pro)', $this->plugin_text_domain),
			'omgf_pro_safe_mode',
			defined('OMGF_PRO_SAFE_MODE') ? OMGF_PRO_SAFE_MODE : false,
			__('Enable Safe Mode if Advanced Processing (Pro) breaks styling of certain pages.'),
			true
		);
	}

	/**
	 *
	 */
	public function do_promo_process_resource_hints()
	{
		$this->do_checkbox(
			__('Remove Resource Hints (Pro)', $this->plugin_text_domain),
			'omgf_pro_process_resource_hints',
			defined('OMGF_PRO_PROCESS_RESOURCE_HINTS') ? OMGF_PRO_PROCESS_RESOURCE_HINTS : false,
			__('Remove all <code>link</code> elements with a <code>rel</code> attribute value of <code>dns-prefetch</code>, <code>preload</code> or <code>preconnect</code> pointing to <code>fonts.googleapis.com</code> or <code>fonts.gstatic.com</code>.', $this->plugin_text_domain) . ' ' . $this->promo,
			true
		);
	}

	/**
	 *
	 */
	public function do_process_google_fonts()
	{
		$this->do_select(
			__('Google Fonts Processing', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_DETECTION_SETTING_FONT_PROCESSING,
			OMGF_Admin_Settings::OMGF_FONT_PROCESSING_OPTIONS,
			OMGF_FONT_PROCESSING,
			sprintf(__("Choose whether OMGF should (find, download and) <strong>replace</strong> all Google Fonts, or just <strong>remove</strong> them. Choosing Remove will force WordPress to fallback to system fonts.", $this->plugin_text_domain), OMGF_Admin_Settings::FFWP_WORDPRESS_PLUGINS_OMGF_PRO)
		);
	}
}
