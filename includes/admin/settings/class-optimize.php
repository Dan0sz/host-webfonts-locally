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
	private $optimized_fonts = [];

	/**
	 * OMGF_Admin_Settings_Optimize constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->title = __('Optimize Google Fonts', $this->plugin_text_domain);

		add_filter('omgf_optimize_settings_content', [$this, 'do_title'], 10);
		add_filter('omgf_optimize_settings_content', [$this, 'do_description'], 11);

		add_filter('omgf_optimize_settings_content', [$this, 'do_before'], 20);
		add_filter('omgf_optimize_settings_content', [$this, 'do_optimization_mode'], 21);
		add_filter('omgf_optimize_settings_content', [$this, 'do_after'], 22);

		add_filter('omgf_optimize_settings_content', [$this, 'open_manual_optimization_mode'], 23);
		add_filter('omgf_optimize_settings_content', [$this, 'do_before'], 24);
		add_filter('omgf_optimize_settings_content', [$this, 'manual_optimization_status'], 25);
		add_filter('omgf_optimize_settings_content', [$this, 'do_after'], 26);
		add_filter('omgf_optimize_settings_content', [$this, 'close_manual_optimization_mode'], 27);

		add_filter('omgf_optimize_settings_content', [$this, 'do_before'], 30);
		add_filter('omgf_optimize_settings_content', [$this, 'do_promo_combine_requests'], 40);
		add_filter('omgf_optimize_settings_content', [$this, 'do_display_option'], 50);
		add_filter('omgf_optimize_settings_content', [$this, 'do_promo_force_font_display'], 60);
		add_filter('omgf_optimize_settings_content', [$this, 'do_promo_include_file_types'], 70);
		add_filter('omgf_optimize_settings_content', [$this, 'do_promo_force_subsets'], 80);
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

	/**
	 * 
	 * @return void 
	 */
	public function do_optimization_mode()
	{
		$this->do_radio(
			__('Optimization Mode', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_OPTIMIZATION_MODE,
			OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZATION_MODE,
			OMGF_OPTIMIZATION_MODE,
			__('<strong>Force</strong> will apply a single stylesheet to all of your posts/pages. <strong>Scan Each Page</strong> will go through each page one by one and compile separate stylesheets for each different Google Fonts configuration.', $this->plugin_text_domain)
		);
	}

	/**
	 * Opens the Manual Optimization Mode info screen container.
	 * 
	 * @return void 
	 */
	public function open_manual_optimization_mode()
	{
	?>
		<div class="omgf-manual-optimization-mode postbox" style="padding: 0 15px 5px; <?= OMGF_OPTIMIZATION_MODE == 'manual' ? '' : 'display: none;'; ?>">
			<h3><?= __('Optimization Mode: Force — Task Manager', $this->plugin_text_domain); ?></h3>
			<p class="description">
				<?= __('Are you using a regular theme (and a page builder) and are the same Google Fonts loading throughout all your posts/pages? Then <strong>Manual Optimization Mode</strong> is right for you.', $this->plugin_text_domain); ?>
			</p>
			<div class="pro-con-container">
				<div class="pros">
					<h4><?= __('Pros', $this->plugin_text_domain); ?></h4>
					<ul class="pros-list">
						<li><?= __('Fast. Immediate results.', $this->plugin_text_domain); ?></li>
						<li><?= __('One (or a few) stylesheets to manage.', $this->plugin_text_domain); ?></li>
						<li><?= __('Compatible with multilanguage plugins, e.g. WPML or Polylang.', $this->plugin_text_domain); ?></li>
					</ul>
				</div>
				<div class="cons">
					<h4><?= __('Cons', $this->plugin_text_domain); ?></h4>
					<ul class="cons-list">
						<li><?= __('Might miss a font, when using a page builder and a unique font is used on a few separate pages.', $this->plugin_text_domain); ?></li>
					</ul>
				</div>
			</div>
		<?php
	}

	public function manual_optimization_status()
	{
		$stylesheets = OMGF::optimized_fonts();
		?>
			<tr valign="top">
				<th scope="row"><?= __('Stylesheet Status', $this->plugin_text_domain); ?></th>
				<td class="status">
					<?php if (!empty($stylesheets)) : ?>
						<ul>
							<?php foreach ($stylesheets as $handle => $contents) : ?>
								<?php
								$cache_key = OMGF::get_cache_key($handle);

								if (!$cache_key) {
									$cache_key = $handle;
								}

								$downloaded = file_exists(OMGF_FONTS_DIR . "/$cache_key/$cache_key.css");
								$stale      = function_exists('omgf_pro_init') && strpos($cache_key, 'pro-merged') === false;
								?>
								<li class="<?= $stale ? 'stale' : ($downloaded ? 'found' : 'not-found'); ?>">
									<strong><?= $handle; ?></strong> <?php if (!$stale) : ?><em>(<?= sprintf(__('stored in %s', $this->plugin_text_domain), str_replace(ABSPATH, '', OMGF_FONTS_DIR . "/$cache_key")); ?>)</em><?php elseif ($stale) : ?><em>(<?= __('Stale cache item. <a id="omgf-stale-cache" href="#">Empty cache</a> and run optimization again.', $this->plugin_text_domain); ?>)</em><?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p>
							<?= __('No stylesheets found. <a href="#" id="omgf-save-optimize">Run optimization</a>?', $this->plugin_text_domain); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		<?php
	}

	/**
	 * Close the container.
	 * 
	 * @return void 
	 */
	public function close_manual_optimization_mode()
	{
		?>
		</div>
	<?php
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
	 * Force Font-Display Option Site Wide
	 */
	public function do_promo_force_font_display()
	{
		$this->do_checkbox(
			__('Force Font-Display Option Site Wide (Pro)', $this->plugin_text_domain),
			'omgf_pro_force_font_display',
			defined('OMGF_PRO_FORCE_FONT_DISPLAY') ? OMGF_PRO_FORCE_FONT_DISPLAY : false,
			__('Force the above <code>font-display</code> attribute on all <code>@font-face</code> statements to ensure all text is user-visible while webfonts and icon sets are loading.', $this->plugin_text_domain),
			true
		);
	}

	/**
	 * Display WOFF2 Only
	 * 
	 * @return void 
	 */
	public function do_promo_include_file_types()
	{
		$this->do_select(
			__('Include File Types (Pro)', $this->plugin_text_domain),
			'omgf_pro_file_types',
			OMGF_Admin_Settings::OMGF_FILE_TYPES_OPTIONS,
			defined('OMGF_PRO_FILE_TYPES') ? OMGF_PRO_FILE_TYPES : [],
			__('Select which file types should be included in the stylesheet. Loading <strong>WOFF2</strong> files only will result in a smaller stylesheet, but will make the stylesheet slightly less Cross Browser compatible. Using <strong>WOFF</strong> and <strong>WOFF2</strong> together (default) accounts for +98% of browsers. Add <strong>EOT</strong> for IE 6-10 and <strong>TTF</strong> and <strong>SVG</strong> for legacy Android/iOS browsers. <em>Use CTRL + click to select multiple values</em>.', $this->plugin_text_domain) . ' ' . $this->promo,
			true,
			true
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
			__('If a theme or plugin loads subsets you don\'t need, use this option to force all Google Fonts to be loaded in the selected subsets. You can also use this option to force the loading of additional subsets, if a theme/plugin doesn\'t allow you to configure the loaded subsets. <em>Use CTRL + click to select multiple values</em>.', $this->plugin_text_domain) . ' ' . $this->promo,
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
		<div class="omgf-optimize-fonts-container postbox">
		<?php
	}

	/**
	 *
	 */
	public function do_optimize_fonts_contents()
	{
		/**
		 * Note: moving this to the constructor doesn't get it properly refreshed after a page reload.
		 */
		$this->optimized_fonts = OMGF::optimized_fonts();
		?>
			<span class="option-title"><?= __('Manage Optimized Fonts', $this->plugin_text_domain); ?></span>
			<?php if (!empty($this->optimized_fonts)) : ?>
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
							<th><?= __('Fallback Font Stack (Pro)', $this->plugin_text_domain); ?></th>
							<th><?= __('Replace (Pro)', $this->plugin_text_domain); ?><span class="dashicons dashicons-info tooltip"><span class="tooltip-text"><span class="inline-text"><?= __('When the Replace option is checked, the selected Fallback Font Stack will replace the corresponding Google Font family, instead of functioning as a fallback.', $this->plugin_text_domain); ?></span></span></span></th>
						</tr>
					</thead>
					<?php
					$cache_handles = OMGF::cache_keys();
					?>
					<?php foreach ($this->optimized_fonts as $handle => $fonts) : ?>
						<?php
						if (!OMGF::get_cache_key($handle)) {
							$cache_handles[] = $handle;
						}
						?>
						<tbody class="stylesheet" id="<?= $handle; ?>">
							<tr>
								<th colspan="6"><?= sprintf(__('Stylesheet handle: %s', $this->plugin_text_domain), $handle); ?></th>
							</tr>
							<?php foreach ($fonts as $font) : ?>
								<?php if (!is_object($font) || count((array) $font->variants) <= 0) continue; ?>
								<?php
								$aka = in_array($font->id, OMGF_API_Download::OMGF_RENAMED_GOOGLE_FONTS) ? array_search($font->id, OMGF_API_Download::OMGF_RENAMED_GOOGLE_FONTS) : '';
								?>
								<tr class="font-family" data-id="<?= $handle . '-' . $font->id; ?>">
									<td colspan="5">
										<span class="family"><em><?= rawurldecode($font->family); ?><?= $aka ? ' (' . sprintf(__('formerly known as <strong>%s</strong>', $this->plugin_text_domain) . ')', ucfirst($aka)) : ''; ?></em></span> <span class="unload-mass-action">(<a href="#" class="unload-italics"><?= __('Unload italics', $this->plugin_text_domain); ?></a> <span class="dashicons dashicons-info tooltip"><span class="tooltip-text"><?= __('In most situations you can safely unload all Italic font styles. Modern browsers are capable of mimicking Italic font styles.', $this->plugin_text_domain); ?></span></span> | <a href="#" class="unload-all"><?= __('Unload all', $this->plugin_text_domain); ?></a> | <a href="#" class="load-all"><?= __('Load all', $this->plugin_text_domain); ?></a>)</span>
									</td>
									<td class="fallback-font-stack">
										<select data-handle="<?= $handle; ?>" <?= apply_filters('omgf_pro_fallback_font_stack_setting_disabled', true) ? 'disabled' : ''; ?> name="omgf_pro_fallback_font_stack[<?= $handle; ?>][<?= $font->id; ?>]">
											<option value=''><?= __('None (default)', $this->plugin_text_domain); ?></option>
											<?php foreach (OMGF_Admin_Settings::OMGF_FALLBACK_FONT_STACKS_OPTIONS as $value => $label) : ?>
												<option <?= defined('OMGF_PRO_FALLBACK_FONT_STACK') && isset(OMGF_PRO_FALLBACK_FONT_STACK[$handle][$font->id]) && OMGF_PRO_FALLBACK_FONT_STACK[$handle][$font->id] == $value ? 'selected' : ''; ?> value="<?= $value; ?>"><?= $label; ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td class="replace">
										<?php
										$replace  = defined('OMGF_PRO_REPLACE_FONT') && isset(OMGF_PRO_REPLACE_FONT[$handle][$font->id]) && OMGF_PRO_REPLACE_FONT[$handle][$font->id] == 'on' ? 'checked' : '';
										$fallback = defined('OMGF_PRO_FALLBACK_FONT_STACK') && isset(OMGF_PRO_FALLBACK_FONT_STACK[$handle][$font->id]) && OMGF_PRO_FALLBACK_FONT_STACK[$handle][$font->id] !== '';
										?>
										<input autocomplete="off" type="checkbox" class="replace" <?= $replace; ?> <?= $fallback ? '' : 'disabled'; ?> <?= apply_filters('omgf_pro_replace_font_setting_disabled', true) ? 'disabled' : ''; ?> name="omgf_pro_replace_font[<?= $handle; ?>][<?= $font->id; ?>]" />
									</td>
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
											<input data-handle="<?= $handle; ?>" data-font-id="<?= $handle . '-' . $font->id; ?>" autocomplete="off" type="checkbox" class="preload" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS; ?>[<?= $handle; ?>][<?= $font->id; ?>][<?= $variant->id; ?>]" value="<?= $variant->id; ?>" <?= $preload ? 'checked="checked"' : ''; ?> <?= $unload ? 'disabled' : ''; ?> />
										</td>
										<td class="unload-<?= $class; ?>">
											<input data-handle="<?= $handle; ?>" data-font-id="<?= $handle . '-' . $font->id; ?>" autocomplete="off" type="checkbox" class="unload" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS; ?>[<?= $handle; ?>][<?= $font->id; ?>][<?= $variant->id; ?>]" value="<?= $variant->id; ?>" <?= $unload ? 'checked="checked"' : ''; ?> <?= $preload ? 'disabled' : ''; ?> />
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
							<em><?= sprintf(__("This list is populated with all Google Fonts captured and downloaded from <strong>%s</strong>. Optimizations will be applied on every page using these fonts. If you want to optimize additional Google Fonts from other pages, switch to <strong>Automatic (Pro)</strong> and visit the pages containing the stylesheets you'd like to optimize. This list will automatically be populated with the captured fonts.", $this->plugin_text_domain), OMGF_MANUAL_OPTIMIZE_URL); ?></em>
						<?php else : ?>
							<?php $no_cache_param = '?omgf_optimize=' . substr(md5(microtime()), rand(0, 26), 5); ?>
							<em><?= sprintf(__("This list is automatically populated with Google Fonts captured throughout your entire site. Optimizations will be applied on every page using these fonts. <strong>Automatic</strong> mode might not work when a Full Page Cache plugin is activated. If this list is not being populated with Google Fonts, you could try to visit your frontend and append the following parameter to the URL: <strong>%s</strong>", $this->plugin_text_domain), $no_cache_param); ?></em>
						<?php endif; ?>
					</p>
				</div>
				<input type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS; ?>" value='<?= serialize($this->optimized_fonts); ?>' />
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
				<div class="omgf-optimize-fonts-tooltip">
					<p>
						<span class="dashicons-before dashicons-info-outline"></span>
						<em><?= __("After saving your changes, this section will be populated with all captured fonts, font styles and available options as the cron task progresses.", $this->plugin_text_domain); ?></em>
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
