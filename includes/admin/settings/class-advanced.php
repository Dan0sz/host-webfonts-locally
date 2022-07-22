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

class OMGF_Admin_Settings_Advanced extends OMGF_Admin_Settings_Builder
{
	/**
	 * OMGF_Admin_Settings_Advanced constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->title = __('Advanced Settings', $this->plugin_text_domain);

		// Open
		add_filter('omgf_advanced_settings_content', [$this, 'do_title'], 10);
		add_filter('omgf_advanced_settings_content', [$this, 'do_description'], 15);
		add_filter('omgf_advanced_settings_content', [$this, 'do_before'], 20);

		// Settings
		add_filter('omgf_advanced_settings_content', [$this, 'do_cache_dir'], 50);
		add_filter('omgf_advanced_settings_content', [$this, 'do_promo_fonts_source_url'], 60);
		add_filter('omgf_advanced_settings_content', [$this, 'do_compatibility'], 70);
		add_filter('omgf_advanced_settings_content', [$this, 'do_debug_mode'], 80);
		add_filter('omgf_advanced_settings_content', [$this, 'do_download_log'], 90);
		add_filter('omgf_advanced_settings_content', [$this, 'do_uninstall'], 100);

		// Close
		add_filter('omgf_advanced_settings_content', [$this, 'do_after'], 200);
	}

	/**
	 * Description
	 */
	public function do_description()
	{
?>
		<p>
			<?= __('Use these settings to make OMGF work with your specific configuration.', $this->plugin_text_domain); ?>
		</p>
	<?php
	}

	/**
	 *
	 */
	public function do_cache_dir()
	{
	?>
		<tr>
			<th scope="row"><?= __('Fonts Cache Directory', $this->plugin_text_domain); ?></th>
			<td>
				<p class="description">
					<?= sprintf(__('Downloaded stylesheets and font files %s are stored in: <code>%s</code>.', $this->plugin_text_domain), is_multisite() ? __('(for this site)', $this->plugin_text_domain) : '', str_replace(ABSPATH, '', OMGF_UPLOAD_DIR)); ?>
				</p>
			</td>
		</tr>
	<?php
	}

	/**
	 *
	 */
	public function do_promo_fonts_source_url()
	{
		$this->do_text(
			__('Modify Source URL (Pro)', $this->plugin_text_domain),
			'omgf_pro_source_url',
			__('e.g. https://cdn.mydomain.com/alternate/relative-path', $this->plugin_text_domain),
			defined('OMGF_PRO_SOURCE_URL') ? OMGF_PRO_SOURCE_URL : '',
			sprintf(
				__("Modify the <code>src</code> attribute for font files and stylesheets generated by OMGF Pro. This can be anything; from an absolute URL pointing to your CDN (e.g. <code>%s</code>) to an alternate relative URL (e.g. <code>/renamed-wp-content-dir/alternate/path/to/font-files</code>) to work with <em>security thru obscurity</em> plugins. Enter the full path to OMGF's files. Default: (empty)", $this->plugin_text_domain),
				'https://your-cdn.com/wp-content/uploads/omgf'
			) . ' ' . $this->promo,
			!defined('OMGF_PRO_SOURCE_URL')
		);
	}

	/**
	 * 
	 */
	public function do_compatibility()
	{
		$this->do_checkbox(
			__('Divi/Elementor Compatibility', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_COMPATIBILITY,
			OMGF_COMPATIBILITY,
			__('Divi and Elementor use the same handle for Google Fonts stylesheets with different configurations. OMGF includes compatibility fixes to make sure these different stylesheets are processed correctly. However, if you have too many different stylesheets and you want to force the usage of 1 stylesheet throughout all your pages, disabling Divi/Elementor Compatibility might help. Default: on', $this->plugin_text_domain)
		);
	}

	public function do_debug_mode()
	{
		$this->do_checkbox(
			__('Debug Mode', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_DEBUG_MODE,
			OMGF_DEBUG_MODE,
			__('Don\'t enable this option, unless when asked by me (Daan) or, if you know what you\'re doing.')
		);
	}

	/**
	 * Show Download Log button if debug mode is on and debug file exists.
	 */
	public function do_download_log()
	{
	?>
		<tr>
			<th></th>
			<td>
				<?php if (OMGF_DEBUG_MODE === 'on' && file_exists(OMGF::$log_file)) : ?>
					<?php
					clearstatcache();
					$nonce = wp_create_nonce(OMGF_Admin_Settings::OMGF_ADMIN_PAGE);
					?>
					<a class="button button-secondary" href="<?php echo admin_url("admin-ajax.php?action=omgf_download_log&nonce=$nonce"); ?>"><?php _e('Download Log', $this->plugin_text_domain); ?></a>
					<?php if (filesize(OMGF::$log_file) > MB_IN_BYTES) : ?>
						<a id="omgf-delete-log" class="button button-cancel" data-nonce="<?php echo $nonce; ?>"><?php _e('Delete log', $this->plugin_text_domain); ?></a>
						<p class="omgf-warning"><?php _e('Your log file is currently larger than 1MB. To protect your filesystem, debug logging has stopped. Delete the log file to enable debug logging again.', $this->plugin_text_domain); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<p class="description"><?php _e('No log file available for download.', $this->plugin_text_domain); ?></p>
				<?php endif; ?>
			</td>
		</tr>
<?php
	}

	/**
	 * Remove Settings/Files at Uninstall.
	 */
	public function do_uninstall()
	{
		$this->do_checkbox(
			__('Remove Settings/Files At Uninstall', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_UNINSTALL,
			OMGF_UNINSTALL,
			__('Warning! This will remove all settings and cached fonts upon plugin deletion.', $this->plugin_text_domain)
		);
	}
}
