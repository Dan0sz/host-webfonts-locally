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
 * @url      : https://ffw.press
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
		add_filter('omgf_detection_settings_content', [$this, 'advanced_processing'], 30);
		add_filter('omgf_detection_settings_content', [$this, 'advanced_processing_promo'], 60);

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
			<?= __('These settings affect OMGF\'s automatic detection mechanism and how it treats the Google Fonts your theme and plugins use. If you want to use OMGF to remove (instead of replace) the Google Fonts your WordPress configuration currently uses, set <strong>Google Fonts Processing</strong> to Remove.', $this->plugin_text_domain); ?>
		</p>
	<?php
	}

	/**
	 *
	 */
	public function advanced_processing_promo()
	{
	?>
		<tr>
			<th scope="row"><?= __('Advanced Processing (Pro)', $this->plugin_text_domain); ?></th>
			<td>
				<fieldset id="" class="scheme-list">
					<?php foreach ($this->advanced_processing_pro_options() as $name => $data) : ?>
						<?php
						$checked  = defined(strtoupper($name)) ? constant(strtoupper($name)) : false;
						$disabled = apply_filters($name . '_setting_disabled', true) ? 'disabled' : '';
						?>
						<label for="<?= $name; ?>">
							<input type="checkbox" name="<?= $name; ?>" id="<?= $name; ?>" <?= $checked ? 'checked="checked"' : ''; ?> <?= $disabled; ?> /><?= $data['label']; ?>
							&nbsp;
						</label>
					<?php endforeach; ?>
				</fieldset>
				<p class="description">
					<?= __('By default, OMGF scans each page for mentions of URLs pointing to fonts.googleapis.com. If you need OMGF to dig deeper (e.g. inside a theme\'s/plugin\'s stylesheets or (Web Font Loader) JS files) enable these options. These options can impact performance and are best used in combination with a page caching plugin.', $this->plugin_text_domain) . ' ' . $this->promo; ?>
				</p>
				<ul>
					<?php foreach ($this->advanced_processing_pro_options() as $name => $data) : ?>
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
	private function advanced_processing_pro_options()
	{
		return [
			'omgf_pro_process_stylesheet_imports' => [
				'label'		  => __('Process Stylesheet Imports', $this->plugin_text_domain),
				'description' => __('Scan stylesheets loaded by your theme and plugins for <code>@import</code> statements loading Google Fonts and process them.', $this->plugin_text_domain)
			],
			'omgf_pro_process_stylesheet_font_faces' => [
				'label'		  => __('Process Stylesheet Font Faces', $this->plugin_text_domain),
				'description' => __('Scan stylesheets loaded by your theme and plugins for <code>@font-face</code> statements loading Google Fonts and process them.', $this->plugin_text_domain)
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
	public function advanced_processing()
	{
		$this->do_select(
			__('Google Fonts Processing', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_DETECTION_SETTING_FONT_PROCESSING,
			OMGF_Admin_Settings::OMGF_FONT_PROCESSING_OPTIONS,
			OMGF_FONT_PROCESSING,
			sprintf(__("Choose whether OMGF should copy all Google Fonts to the server, or just <strong>remove</strong> them. Choosing Remove will force WordPress to fallback to system fonts.", $this->plugin_text_domain), OMGF_Admin_Settings::FFWP_WORDPRESS_PLUGINS_OMGF_PRO)
		);
	}
}
