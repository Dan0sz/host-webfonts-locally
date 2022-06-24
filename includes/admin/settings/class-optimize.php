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
 * @url      : https://ffw.press
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

		add_filter('omgf_optimize_settings_content', [$this, 'open_task_manager'], 23);
		add_filter('omgf_optimize_settings_content', [$this, 'do_before'], 24);
		add_filter('omgf_optimize_settings_content', [$this, 'task_manager_status'], 25);
		add_filter('omgf_optimize_settings_content', [$this, 'do_after'], 26);
		add_filter('omgf_optimize_settings_content', [$this, 'close_task_manager'], 27);

		add_filter('omgf_optimize_settings_content', [$this, 'do_before'], 30);
		add_filter('omgf_optimize_settings_content', [$this, 'do_display_option'], 50);
		add_filter('omgf_optimize_settings_content', [$this, 'do_promo_force_font_display'], 60);
		add_filter('omgf_optimize_settings_content', [$this, 'do_promo_convert_to_variable_fonts'], 70);
		add_filter('omgf_optimize_settings_content', [$this, 'do_after'], 100);

		add_filter('omgf_optimize_settings_content', [$this, 'do_optimize_fonts_container'], 200);
		add_filter('omgf_optimize_settings_content', [$this, 'do_optimize_fonts_contents'], 250);
		add_filter('omgf_optimize_settings_content', [$this, 'close_optimize_fonts_container'], 300);

		add_filter('omgf_optimize_settings_content', [$this, 'do_before'], 350);
		add_filter('omgf_optimize_settings_content', [$this, 'do_test_mode'], 375);
		add_filter('omgf_optimize_settings_content', [$this, 'do_after'], 400);
	}

	/**
	 *
	 */
	public function do_description()
	{
?>
		<p>
			<?= __('These settings affect the downloaded files and generated stylesheet(s). If you\'re simply looking to replace your Google Fonts for locally hosted copies, the default settings should suffice.', $this->plugin_text_domain); ?>
		</p>
		<p>
			<?= sprintf(__('To install additional Google Fonts, an add-on is required, which can be downloaded <a href="%s" target="blank">here</a>.', $this->plugin_text_domain), self::FFW_PRESS_OMGF_AF_URL); ?>
		</p>
	<?php
	}

	/**
	 * Opens the Force info screen container.
	 * 
	 * @return void 
	 */
	public function open_task_manager()
	{
	?>
		<div class="omgf-task-manager postbox" style="padding: 0 15px 5px;">
			<h3><?= __('Task Manager', $this->plugin_text_domain); ?></h3>
			<p class="description">
				<?= __('A quick overview of the stylesheets (and their status) currently in your cache folder.', $this->plugin_text_domain); ?>
			</p>
		<?php
	}

	/**
	 * 
	 * 
	 * @return void 
	 */
	public function task_manager_status()
	{
		$stylesheets          = OMGF::optimized_fonts();
		$unloaded_stylesheets = OMGF::unloaded_stylesheets();
		?>
			<tr valign="top">
				<th scope="row"><?= __('Cache Status', $this->plugin_text_domain); ?></th>
				<td class="task-manager-row">
					<?php if (!empty($stylesheets)) : ?>
						<ul>
							<?php foreach ($stylesheets as $handle => $contents) : ?>
								<?php
								$cache_key = OMGF::get_cache_key($handle);

								if (!$cache_key) {
									$cache_key = $handle;
								}

								$downloaded = file_exists(OMGF_UPLOAD_DIR . "/$cache_key/$cache_key.css");
								$unloaded   = in_array($handle, $unloaded_stylesheets);
								?>
								<li class="<?= OMGF_CACHE_IS_STALE ? 'stale' : ($unloaded ? 'unloaded' : ($downloaded ? 'found' : 'not-found')); ?>">
									<strong><?= $handle; ?></strong> <em>(<?= sprintf(__('stored in %s', $this->plugin_text_domain), str_replace(ABSPATH, '', OMGF_UPLOAD_DIR . "/$cache_key")); ?>)</em> <?php if (!$unloaded) : ?><a href="<?php echo $downloaded ? "#$handle" : '#'; ?>" data-handle="<?php echo esc_attr($handle); ?>" id="<?php echo $downloaded ? 'omgf-manage-stylesheet' : 'omgf-remove-stylesheet'; ?>" title="<?php echo sprintf(__('Manage %s', $this->plugin_text_domain), $cache_key); ?>"><?php $downloaded ? _e('Configure', $this->plugin_text_domain) : _e('Remove', $this->plugin_text_domain); ?></a><?php endif; ?>
								</li>
							<?php endforeach; ?>
							<?php if (OMGF_CACHE_IS_STALE) : ?>
								<li class="stale-cache-notice"><em><?= __('The stylesheets in the cache do not reflect the current settings. Either <a href="#" id="omgf-cache-refresh">refresh</a> the cache (and maintain settings) or <a href="#" id="omgf-cache-flush">flush</a> it and start over.', $this->plugin_text_domain); ?></em></li>
							<?php endif; ?>
						</ul>
					<?php else : ?>
						<p>
							<?= __('No stylesheets found. <a href="#" id="omgf-save-optimize">Start optimization</a>?', $this->plugin_text_domain); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Legend', $this->plugin_text_domain); ?></th>
				<td class="task-manager-row">
					<ul>
						<li class="found"> <?php _e('<strong>Found</strong>. Stylesheet exists on your file system.', $this->plugin_text_domain); ?></li>
						<li class="unloaded"> <?php _e('<strong>Unloaded</strong>. Stylesheet exists, but is not loaded in the frontend.', $this->plugin_text_domain); ?></li>
						<li class="stale"> <?php _e('<strong>Stale</strong>. Settings were changed and the stylesheet\'s content do not reflect those changes.', $this->plugin_text_domain); ?></li>
						<li class="not-found"> <?php _e('<strong>Not Found</strong>. Stylesheet was detected once, but is missing now. You can safely remove it.', $this->plugin_text_domain); ?></li>
					</ul>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Flush Cache', $this->plugin_text_domain); ?></th>
				<td class="task-manager-row">
					<a id="omgf-empty" data-init="<?= OMGF_Admin_Settings::OMGF_ADMIN_PAGE; ?>" data-nonce="<?= wp_create_nonce(OMGF_Admin_Settings::OMGF_ADMIN_PAGE); ?>" class="omgf-empty button-cancel"><?php _e('Empty Cache Directory', $this->plugin_text_domain); ?></a>
				</td>
			</tr>
		<?php
	}

	/**
	 * Close the container.
	 * 
	 * @return void 
	 */
	public function close_task_manager()
	{
		?>
		</div>
	<?php
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
	 * 
	 * @return void 
	 */
	public function do_promo_convert_to_variable_fonts()
	{
		$this->do_checkbox(
			__('Convert to Variable Fonts (Pro)', $this->plugin_text_domain),
			'omgf_pro_convert_variable_fonts_api',
			defined('OMGF_PRO_CONVERT_TO_VAR') ? OMGF_PRO_CONVERT_TO_VAR : false,
			sprintf(__('When this option is checked, OMGF Pro will attempt to convert all found Google Fonts to <a href="%s">Variable Fonts</a>. By combining multiple font weights/styles into a single file, it significantly reduces <em>HTTP requests</em>, <em>page size</em> and <em>cumulative layout shifting</em>. If it fails, it will still serve regular webfonts. This only works if the Google Fonts loaded by your website are actually <a href="%s">available as Variable Fonts</a>.'), 'https://fonts.google.com/knowledge/glossary/variable_fonts', 'https://fonts.google.com/?vfonly=true')
		);
	}

	/**
	 *
	 */
	public function do_optimize_fonts_container()
	{
	?>
		<div id="omgf-manage-optimized-fonts" class="omgf-optimize-fonts-container postbox">
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
			<span class="option-title"><?= __('Manage Optimized Fonts', $this->plugin_text_domain); ?><span class="dashicons dashicons-info tooltip"><span class="tooltip-text"><span class="inline-text"><?php echo sprintf(__('Don\'t know where or how to start optimizing your Google Fonts? That\'s okay. <a href="%s">This guide</a> will get you sorted.', $this->plugin_text_domain), 'https://ffw.press/blog/how-to/wordpress-google-fonts/'); ?></span></span></span></span>
			<?php if (!empty($this->optimized_fonts)) : ?>
				<?= $this->do_optimized_fonts_manager(); ?>
			<?php else : ?>
				<div class="omgf-optimize-fonts-description">
					<?php
					$this->do_optimize_fonts_section();
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
							<th><?= __('Preload', $this->plugin_text_domain); ?><span class="dashicons dashicons-info tooltip"><span class="tooltip-text"><span class="inline-text"><?php echo sprintf(__('<a href="%s">Preload font files</a> prior to page rendering to improve perceived loading times. Only use preload for font files that are used above the fold.', $this->plugin_text_domain), 'https://ffw.press/blog/how-to/wordpress-google-fonts/#3-2-preloading-font-files-above-the-fold'); ?></span><img width="230" class="illustration" src="<?= plugin_dir_url(OMGF_PLUGIN_FILE) . 'assets/images/above-the-fold.png'; ?>" /></span></span></th>
							<th><?= __('Do not load', $this->plugin_text_domain); ?></th>
							<th><?= __('Fallback Font Stack (Pro)', $this->plugin_text_domain); ?></th>
							<th><?= __('Replace (Pro)', $this->plugin_text_domain); ?><span class="dashicons dashicons-info tooltip"><span class="tooltip-text"><span class="inline-text"><?php echo sprintf(__('When the <a href="%s">Replace option</a> is checked, the selected Fallback Font Stack will replace the corresponding Google Font family, instead of functioning as a fallback.', $this->plugin_text_domain), 'https://ffw.press/blog/how-to/wordpress-google-fonts/#7-4-specify-a-fallback-font-stack'); ?></span></span></span></th>
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
								$aka = in_array($font->id, OMGF_Optimize::OMGF_RENAMED_GOOGLE_FONTS) ? array_search($font->id, OMGF_Optimize::OMGF_RENAMED_GOOGLE_FONTS) : '';
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
								<?php $id = ''; ?>
								<?php foreach ($font->variants as $variant) : ?>
									<?php
									/**
									 * @since v5.3.0: Variable Fonts are pulled directly from the Google Fonts API,
									 * 				  which creates @font-face statements for each separate subset.
									 * 					
									 * 				  This deals with the duplicate display of font styles. Which also
									 * 				  means unloading and/or preloading will unload/preload all available
									 * 				  subsets. It's a bit bloaty, but there's no alternative.
									 */
									if ($id == $variant->fontWeight . $variant->fontStyle) continue;
									?>
									<?php $id = $variant->fontWeight . $variant->fontStyle; ?>
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
						<em><?= sprintf(__("This list is populated with all Google Fonts stylesheets captured and downloaded throughout your site. It will grow organically if other Google Fonts stylesheets are discovered throughout your site.", $this->plugin_text_domain), get_site_url()); ?></em>
					</p>
				</div>
				<input type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS; ?>" value="<?= base64_encode(serialize($this->optimized_fonts)); ?>" />
				<input id="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS; ?>" type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS; ?>" value="<?= esc_attr(OMGF_UNLOAD_STYLESHEETS); ?>" />
				<input id="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS; ?>" type="hidden" name="<?= OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_CACHE_KEYS; ?>" value="<?= esc_attr(implode(',', $cache_handles)); ?>" />
				<?php echo apply_filters('omgf_optimize_fonts_hidden_fields', ''); ?>
			</div>
		<?php
		}

		/**
		 *
		 */
		public function do_optimize_fonts_section()
		{
		?>
			<div class="omgf-optimize-fonts">
				<div class="omgf-optimize-fonts-tooltip">
					<p>
						<span class="dashicons-before dashicons-info-outline"></span>
						<em><?= sprintf(__('After clicking <strong>Save & Optimize</strong>, this section will be populated with any Google Fonts (along with requested styles and available options) requested on <code>%s</code>. The list will grow organically if other Google Fonts stylesheets are discovered throughout your site.', $this->plugin_text_domain), get_site_url()); ?></em>
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
		 * Test Mode
		 */
		public function do_test_mode()
		{
			$this->do_checkbox(
				__('Test Mode', $this->plugin_text_domain),
				OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_TEST_MODE,
				OMGF_TEST_MODE,
				__('With this setting enabled, OMGF\'s optimizations will only be visible to logged in administrators or when <code>?omgf=1</code> is added to an URL in the frontend.', $this->plugin_text_domain)
			);
		}
	}
