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

class OMGF_Admin_Settings_Optimize extends OMGF_Admin_Settings_Builder
{
	const FFW_PRESS_OMGF_AF_URL = 'https://ffw.press/wordpress/omgf-additional-fonts/';

	/** @var array $optimized_fonts */
	private $optimized_fonts;

	/**
	 * OMGF_Admin_Settings_Optimize constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->title = __('Optimize Google Fonts', $this->plugin_text_domain);

		add_filter('omgf_optimize_settings_content', [$this, 'do_title'], 10);
		add_filter('omgf_optimize_settings_content', [$this, 'do_description'], 15);

		add_filter('omgf_optimize_settings_content', [$this, 'do_before'], 20);
		add_filter('omgf_optimize_settings_content', [$this, 'do_optimization_mode'], 30);
		add_filter('omgf_optimize_settings_content', [$this, 'do_promo_combine_requests'], 40);
		add_filter('omgf_optimize_settings_content', [$this, 'do_display_option'], 50);
		add_filter('omgf_optimize_settings_content', [$this, 'do_woff2_only'], 60);
		add_filter('omgf_optimize_settings_content', [$this, 'do_promo_force_subsets'], 70);
		add_filter('omgf_optimize_settings_content', [$this, 'do_after'], 100);

		add_filter('omgf_optimize_settings_content', [$this, 'do_optimize_fonts_container'], 200);
		add_filter('omgf_optimize_settings_content', [$this, 'do_optimize_fonts_contents'], 250);
		add_filter('omgf_optimize_settings_content', [$this, 'close_optimize_fonts_container'], 300);

		add_filter('omgf_optimize_settings_content', [$this, 'do_before'], 350);
		add_filter('omgf_optimize_settings_content', [$this, 'do_optimize_edit_roles'], 375);
		add_filter('omgf_optimize_settings_content', [$this, 'do_after'], 400);
	}

	/**
	 *
	 */
	public function do_description()
	{
?>
		<p>
			<?= __('These settings affect the fonts OMGF downloads and the stylesheet it generates. If you\'re simply looking to replace your Google Fonts for locally hosted copies, the default settings should suffice.', $this->plugin_text_domain); ?>
		</p>
		<p>
			<?= sprintf(__('To install additional Google Fonts, an add-on is required, which can be downloaded <a href="%s" target="blank">here</a>.', $this->plugin_text_domain), self::FFW_PRESS_OMGF_AF_URL); ?>
		</p>
	<?php
	}

	public function do_optimization_mode()
	{
		$this->do_radio(
			__('Optimization Mode', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_OPTIMIZATION_MODE,
			OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZATION_MODE,
			OMGF_OPTIMIZATION_MODE,
			__('<strong>Manual</strong> processing mode is best suited for configurations, which use a fixed number of fonts across the entire site. When in manual mode, the generated stylesheet is forced throughout the entire site.<strong>Automatic</strong> processing mode is best suited for configurations using e.g. page builders, which load different fonts on certain pages.', $this->plugin_text_domain)
		);
	}

	/**
	 *
	 */
	public function do_promo_combine_requests()
	{
		$this->do_checkbox(
			__('Combine & Dedupe (Pro)', $this->plugin_text_domain),
			'omgf_pro_combine_requests',
			defined('OMGF_PRO_COMBINE_REQUESTS') ? true : false,
			__('Combine and deduplicate multiple Google Fonts stylesheets into one stylesheet. This feature is always on in OMGF Pro.', $this->plugin_text_domain) . ' ' . $this->promo,
			true
		);
	}

	/**
	 *
	 */
	public function do_display_option()
	{
		$this->do_select(
			__('Font-Display Option', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_DISPLAY_OPTION,
			OMGF_Admin_Settings::OMGF_FONT_DISPLAY_OPTIONS,
			OMGF_DISPLAY_OPTION,
			__('Select which value to set the font-display attribute to. Defaults to Swap (recommended).', $this->plugin_text_domain)
		);
	}

	/**
	 * Display WOFF2 Only
	 * 
	 * @return void 
	 */
	public function do_woff2_only()
	{
		$this->do_checkbox(
			__('<code>WOFF2</code> Only', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_WOFF2_ONLY,
			OMGF_WOFF2_ONLY,
			__('Loading <code>.woff2</code> files only will result in a smaller stylesheet, but will make the stylesheet slightly less Cross Browser compatible. <code>.woff2</code> is supported by ~95% of browsers used by internet users globally.', $this->plugin_text_domain)
		);
	}

	/**
	 *
	 */
	public function do_promo_force_subsets()
	{
		$this->do_select(
			__('Force Subsets (Pro)', $this->plugin_text_domain),
			'omgf_pro_force_subsets',
			OMGF_Admin_Settings::OMGF_FORCE_SUBSETS_OPTIONS,
			defined('OMGF_PRO_FORCE_SUBSETS') ? OMGF_PRO_FORCE_SUBSETS : [],
			__('If a theme or plugin loads subsets you don\'t need, use this option to force all Google Fonts to be loaded in the selected subsets. You can also use this option to force the loading of additional subsets, if a theme/plugin doesn\'t allow you to configure the loaded subsets.', $this->plugin_text_domain) . ' ' . $this->promo,
			true,
			true
		);
	}

	/**
	 *
	 */
	public function do_optimize_fonts_container()
	{
	?>
		<div class="omgf-optimize-fonts-container welcome-panel">
		<?php
	}

	/**
	 *
	 */
	public function do_optimize_fonts_contents()
	{
		$this->optimized_fonts = omgf_init()::optimized_fonts();
		?>
			<span class="option-title"><?= __('Manage Optimized Fonts', $this->plugin_text_domain); ?></span>
			<?php if ($this->optimized_fonts) : ?>
				<?= $this->do_optimized_fonts_manager(); ?>
			<?php else : ?>
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
		private function do_optimized_fonts_manager()
		{
			?>
			<div class="omgf-optimize-fonts-manage">
				<p>

				</p>
				<table>
					<thead>
						<tr>
							<td>&nbsp;</td>
							<th><?= __('Style', $this->plugin_text_domain); ?></th>
							<th><?= __('Weight', $this->plugin_text_domain); ?></th>
							<th><?= __('Preload', $this->plugin_text_domain); ?><span class="dashicons dashicons-info tooltip"><span class="tooltip-text"><span class="inline-text"><?= __('Preload font files (before everything else) so they will be available as soon as they are required for the rendering of the page. Only use preload for font files that are used above the fold.', $this->plugin_text_domain); ?></span><img width="230" class="illustration" src="<?= plugin_dir_url(OMGF_PLUGIN_FILE) . 'assets/images/above-the-fold.png'; ?>" /></span></span></th>
							<th><?= __('Do not load', $this->plugin_text_domain); ?></th>
						</tr>
					</thead>
					<?php
					$cache_handles = omgf_init()::cache_keys();
					?>
					<?php foreach ($this->optimized_fonts as $handle => $fonts) : ?>
						<?php
						if (!omgf_init()::get_cache_key($handle)) {
							$cache_handles[] = $handle;
						}
						?>
						<tbody class="stylesheet" id="<?= $handle; ?>">
							<tr>
								<th colspan="5"><?= sprintf(__('Stylesheet handle: %s', $this->plugin_text_domain), $handle); ?></th>
							</tr>
							<?php foreach ($fonts as $font) : ?>
								<?php if (!is_object($font) || count((array) $font->variants) <= 0) continue; ?>
								<?php
								$aka = in_array($font->id, OMGF_API_Download::OMGF_RENAMED_GOOGLE_FONTS) ? array_search($font->id, OMGF_API_Download::OMGF_RENAMED_GOOGLE_FONTS) : '';
								?>
								<tr class="font-family" data-id="<?= $font->id; ?>">
									<td colspan="5"><span class="family"><em><?= $font->family; ?><?= $aka ? ' (' . sprintf(__('formerly known as <strong>%s</strong>', $this->plugin_text_domain) . ')', ucfirst($aka)) : ''; ?></em></span> <span class="unload-mass-action">(<a href="#" class="unload-italics"><?= __('Unload italics', $this->plugin_text_domain); ?></a> <span class="dashicons dashicons-info tooltip"><span class="tooltip-text"><?= __('In most situations you can safely unload all Italic font styles. Modern browsers are capable of mimicking Italic font styles.', $this->plugin_text_domain); ?></span></span> | <a href="#" class="unload-all"><?= __('Unload all', $this->plugin_text_domain); ?></a> | <a href="#" class="load-all"><?= __('Load all', $this->plugin_text_domain); ?></a>)</span></td>
								</tr>
								<?php foreach ($font->variants as $variant) : ?>
									<tr>
										<td></td>
										<?php
										$preload = OMGF::preloaded_fonts()[$handle][$font->id][$variant->id] ?? '';
										$unload  = OMGF::unloaded_fonts()[$handle][$font->id][$variant->id] ?? '';
										$class   = $handle . '-' . $font->id . '-' . $variant->id;
										?>
										<td><?= $variant->fontStyle; ?></td>
										<td><?= $variant->fontWeight; ?></td>
										<td class="preload-<?= $class; ?>">
											<input data-handle="<?= $handle; ?>" data-font-id="<?= $font->id; ?>" autocomplete="off" type="checkbox" class="preload" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS; ?>[<?= $handle; ?>][<?= $font->id; ?>][<?= $variant->id; ?>]" value="<?= $variant->id; ?>" <?= $preload ? 'checked="checked"' : ''; ?> <?= $unload ? 'disabled' : ''; ?> />
										</td>
										<td class="unload-<?= $class; ?>">
											<input data-handle="<?= $handle; ?>" data-font-id="<?= $font->id; ?>" autocomplete="off" type="checkbox" class="unload" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS; ?>[<?= $handle; ?>][<?= $font->id; ?>][<?= $variant->id; ?>]" value="<?= $variant->id; ?>" <?= $unload ? 'checked="checked"' : ''; ?> <?= $preload ? 'disabled' : ''; ?> />
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
						<?php if (OMGF_OPTIMIZATION_MODE == 'manual') : ?>
							<em><?= sprintf(__("This list is populated with all Google Fonts captured and downloaded from <strong>%s</strong>. Optimizations will be applied on every page using these fonts. If you want to optimize additional Google Fonts from other pages, temporarily switch to <strong>Automatic</strong> and visit the pages containing the stylesheets you'd like to optimize. This list will automatically be populated with the captured fonts. When you feel the list is complete, switch back to <strong>Manual</strong>.", $this->plugin_text_domain), OMGF_MANUAL_OPTIMIZE_URL); ?></em>
						<?php else : ?>
							<?php
							$no_cache_param = '?omgf_optimize=' . substr(md5(microtime()), rand(0, 26), 5);
							?>
							<em><?= sprintf(__("This list is automatically populated with Google Fonts captured throughout your entire site. Optimizations will be applied on every page using these fonts. <strong>Automatic</strong> mode might not work when a Full Page Cache plugin is activated. If this list is not being populated with Google Fonts, you could try to visit your frontend and append the following parameter to the URL: <strong>%s</strong>", $this->plugin_text_domain), $no_cache_param); ?></em>
						<?php endif; ?>
					</p>
				</div>
				<input type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS; ?>" value="<?= serialize($this->optimized_fonts); ?>" />
				<input type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL; ?>" value="<?= OMGF_MANUAL_OPTIMIZE_URL; ?>" />
				<input id="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS; ?>" type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS; ?>" value="<?= OMGF_UNLOAD_STYLESHEETS; ?>" />
				<input id="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS; ?>" type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS; ?>" value="<?= implode(',', $cache_handles); ?>" />
			</div>
		<?php
		}

		/**
		 *
		 */
		public function do_manual_template()
		{
		?>
			<div class="omgf-optimize-fonts-manual" <?= OMGF_OPTIMIZATION_MODE == 'manual' ? '' : 'style="display: none;"'; ?>>
				<p>
					<?= sprintf(__("You've chosen to <strong>optimize your Google Fonts manually</strong>. OMGF will <u>not</u> run automatically and will <strong>%s</strong> the requested Google Fonts throughout your website that were captured on the post/page you defined. A Cross-Browser compatible stylesheet will be generated for all requested Google Fonts.", $this->plugin_text_domain), OMGF_FONT_PROCESSING); ?>
				</p>
				<div class="omgf-optimize-fonts-pros">
					<h3>
						<span class="dashicons-before dashicons-yes"></span> <?= __('Pros:', $this->plugin_text_domain); ?>
					</h3>
					<ul>
						<li><?= __('A small initial performance boost, because no calls to OMGF\'s Download API are made in the frontend.', $this->plugin_text_domain); ?></li>
						<li><?= __('Force one stylesheet to be used throughout the site.', $this->plugin_text_domain); ?></li>
					</ul>
				</div>
				<div class="omgf-optimize-fonts-cons">
					<h3>
						<span class="dashicons-before dashicons-no"></span> <?= __('Cons', $this->plugin_text_domain); ?>
					</h3>
					<ul>
						<li><?= __('A font that is only used on a few pages might be lost if one of those URLs isn\'t scanned for fonts.', $this->plugin_text_domain); ?></li>
					</ul>
				</div>
				<p>
					<?= __('Enter the URL of the post/page you\'d like to scan for Google Fonts. The detected and optimized stylesheets will be applied on all pages where they\'re used.', $this->plugin_text_domain); ?>
				</p>
				<label for="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL; ?>">
					<?= __('URL to Scan', $this->plugin_text_domain); ?>
					<input id="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL; ?>" type="text" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_MANUAL_OPTIMIZE_URL; ?>" value="<?= OMGF_MANUAL_OPTIMIZE_URL; ?>" />
				</label>
				<div class="omgf-optimize-fonts-tooltip">
					<p>
						<span class="dashicons-before dashicons-info-outline"></span>
						<em><?= __('This section will be populated with all captured fonts, font styles and available options after saving changes.', $this->plugin_text_domain); ?></em>
					</p>
				</div>
			</div>
		<?php
		}

		/**
		 *
		 */
		public function do_automatic_template()
		{
		?>
			<div class="omgf-optimize-fonts-automatic" <?= OMGF_OPTIMIZATION_MODE == 'auto' ? '' : 'style="display: none;"'; ?>>
				<p>
					<?= sprintf(__("You've chosen to <strong>optimize your Google Fonts automatically</strong>. OMGF will run silently in the background and <strong>%s</strong> all requested Google Fonts. If the captured stylesheet doesn't exist yet, a call is sent to OMGF's Download API to download the font files and generate a Cross-Browser compatible stylesheet.", $this->plugin_text_domain), OMGF_FONT_PROCESSING); ?>
				</p>
				<div class="omgf-optimize-fonts-pros">
					<h3>
						<span class="dashicons-before dashicons-yes"></span> <?= __('Pros:', $this->plugin_text_domain); ?>
					</h3>
					<ul>
						<li><?= __('No maintenance.', $this->plugin_text_domain); ?></li>
					</ul>
				</div>
				<div class="omgf-optimize-fonts-cons">
					<h3>
						<span class="dashicons-before dashicons-no"></span> <?= __('Cons', $this->plugin_text_domain); ?>
					</h3>
					<ul>
						<li><?= __("The first time an unoptimized Google Fonts stylesheet is found, the API will be triggered in the frontend, which might cause the page to load slower than usual. All subsequent pageviews for that page (and all pages using that same stylesheet will load just as fast as when Manual mode is used.", $this->plugin_text_domain); ?></li>
					</ul>
				</div>
				<div class="omgf-optimize-fonts-tooltip">
					<p>
						<span class="dashicons-before dashicons-info-outline"></span>
						<em><?= __("After saving your changes, this section will be populated with all captured fonts, font styles and available options as your site's frontend is visited by you or others. You will be able to manage your fonts at a later point.", $this->plugin_text_domain); ?></em>
					</p>
				</div>
			</div>
		<?php
		}

		/**
		 *
		 */
		public function close_optimize_fonts_container()
		{
		?>
		</div>
<?php
		}

		/**
		 *
		 */
		public function do_optimize_edit_roles()
		{
			$this->do_checkbox(
				__('Optimize Fonts For Editors/Administrators?', $this->plugin_text_domain),
				OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZE_EDIT_ROLES,
				OMGF_OPTIMIZE_EDIT_ROLES,
				__('Should only be disabled while debugging/testing, e.g. using a page builder or switching themes.', $this->plugin_text_domain)
			);
		}
	}
