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
 * @copyright: © 2022 Daan van den Bergh
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
		add_filter('omgf_detection_settings_content', [$this, 'google_fonts_processing'], 30);
		add_filter('omgf_detection_settings_content', [$this, 'promo_advanced_processing'], 60);

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
			<?= __('These settings affect the detection mechanism and in which areas it searches (i.e. how deep it digs) to find Google Fonts. If you want to remove (instead of replace) the Google Fonts your WordPress configuration currently uses, set <strong>Google Fonts Processing</strong> to Remove.', $this->plugin_text_domain); ?>
		</p>
	<?php
	}

	/**
	 *
	 */
	public function google_fonts_processing()
	{
	?>
		<tr>
			<th scope="row"><?= __('Google Fonts Processing', $this->plugin_text_domain); ?></th>
			<td>
				<p class="description">
					<?= sprintf(__('By default, OMGF replaces Google Fonts stylesheets (e.g. <code>https://fonts.googleapis.com/css?family=Open+Sans</code>) with locally hosted copies. This behavior can be tweaked further using the <strong>Advanced Processing (Pro)</strong> option. To remove/unload Google Fonts, go to <em>Local Fonts</em> > <a href="%s"><em>Optimize Local Fonts</em></a> and click <strong>Unload all</strong> next to the stylesheet handle you\'d like to remove.', $this->plugin_text_domain), admin_url('options-general.php?page=optimize-webfonts&tab=omgf-optimize-settings#omgf-manage-optimized-fonts')); ?>
				</p>
			</td>
		</tr>
	<?php
	}

	/**
	 *
	 */
	public function promo_advanced_processing()
	{
	?>
		<tr>
			<th scope="row"><?= __('Advanced Processing (Pro)', $this->plugin_text_domain); ?></th>
			<td>
				<fieldset id="" class="scheme-list">
					<?php foreach ($this->advanced_processing_pro_options() as $name => $data) : ?>
						<?php
						$checked  = defined(strtoupper($name)) ? constant(strtoupper($name)) : false;
						$disabled = !defined(strtoupper($name)) ? 'disabled' : '';
						?>
						<label for="<?= $name; ?>">
							<input type="checkbox" name="<?= $name; ?>" id="<?= $name; ?>" <?= $checked ? 'checked="checked"' : ''; ?> <?= $disabled; ?> /><?= $data['label']; ?>
							&nbsp;
						</label>
					<?php endforeach; ?>
				</fieldset>
				<p class="description">
					<?= sprintf(__('By default, OMGF scans each page for mentions of URLs pointing to fonts.googleapis.com. If you need OMGF to "dig deeper", e.g. inside a theme\'s/plugin\'s CSS stylesheets or (Web Font Loader) JS files, <a href="%s" target="_blank">enable these options</a> to increase its level of detection. Best used in combination with a page caching plugin.', $this->plugin_text_domain), 'https://daan.dev/docs/omgf-pro/detection-settings-advanced-processing/') . ' ' . $this->promo; ?>
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
			'omgf_pro_process_inline_styles'  => [
				'label'       => __('Process Inline Styles', $this->plugin_text_domain),
				'description' => __('Process all inline <code>@font-face</code> and <code>@import</code> rules loading Google Fonts.', $this->plugin_text_domain)
			],
			'omgf_pro_process_local_stylesheets' => [
				'label'		  => __('Process Local Stylesheets', $this->plugin_text_domain),
				'description' => __('Scan stylesheets loaded by your theme and plugins for <code>@import</code> and <code>@font-face</code> statements loading Google Fonts and process them.', $this->plugin_text_domain)
			],
			'omgf_pro_process_webfont_loader' => [
				'label'       => __('Process Webfont Loader', $this->plugin_text_domain),
				'description' => __('Process <code>webfont.js</code> libraries and the corresponding configuration defining which Google Fonts to load.', $this->plugin_text_domain)
			],
			'omgf_pro_process_early_access'   => [
				'label'       => __('Process Early Access', $this->plugin_text_domain),
				'description' => __('Process Google Fonts loaded from <code>fonts.googleapis.com/earlyaccess</code> or <code>fonts.gstatic.com/ea</code>.', $this->plugin_text_domain)
			]
		];
	}
}
