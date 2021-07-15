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
		add_filter('omgf_advanced_settings_content', [$this, 'do_promo_amp_handling'], 40);
		add_filter('omgf_advanced_settings_content', [$this, 'do_promo_exclude_posts'], 50);
		add_filter('omgf_advanced_settings_content', [$this, 'do_cache_dir'], 70);
		add_filter('omgf_advanced_settings_content', [$this, 'do_promo_fonts_source_url'], 80);
		add_filter('omgf_advanced_settings_content', [$this, 'do_uninstall'], 110);

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
			<?= __('If you require the downloaded/generated files to be saved in a different location or served from a different resource (e.g. a CDN) or path, use these settings to make OMGF work with your configuration.', $this->plugin_text_domain); ?>
		</p>
<?php
	}

	public function do_promo_amp_handling()
	{
		$this->do_select(
			__('AMP handling (Pro)', $this->plugin_text_domain),
			'omgf_pro_amp_handling',
			OMGF_Admin_Settings::OMGF_AMP_HANDLING_OPTIONS,
			defined('OMGF_PRO_AMP_HANDLING') ? OMGF_PRO_AMP_HANDLING : '',
			sprintf(__("Decide how OMGF Pro should behave on AMP pages. Only select <strong>enable</strong> if the custom CSS limit of 75kb is not already reached by your theme and/or other plugins and no other <code>amp-custom</code> tag is present on your pages.", $this->plugin_text_domain), OMGF_Admin_Settings::FFWP_WORDPRESS_PLUGINS_OMGF_PRO) . ' ' . $this->promo,
			false,
			true
		);
	}

	/**
	 * Excluded Post/Page IDs (Pro)
	 * 
	 * @return void 
	 */
	public function do_promo_exclude_posts()
	{
		$this->do_text(
			__('Excluded Post/Page IDs (Pro)', $this->plugin_text_domain),
			'omgf_pro_excluded_ids',
			__('e.g. 1,2,5,21,443'),
			defined('OMGF_PRO_EXCLUDED_IDS') ? OMGF_PRO_EXCLUDED_IDS : '',
			__('A comma separated list of post/page IDs where OMGF Pro shouldn\'t run.', $this->plugin_text_domain) . ' ' . $this->promo,
			true
		);
	}

	/**
	 *
	 */
	public function do_cache_dir()
	{
		$this->do_text(
			__('Fonts Cache Directory', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_PATH,
			__('e.g. /uploads/omgf', $this->plugin_text_domain),
			OMGF_CACHE_PATH,
			__("The directory (inside <code>wp-content</code>) where font files should be stored. Give each site a unique value if you're using Multisite. Defaults to <code>/uploads/omgf</code>. After changing this setting, the directory will be created if it doesn't exist and existing files will be moved automatically.", $this->plugin_text_domain)
		);
	}

	/**
	 *
	 */
	public function do_promo_fonts_source_url()
	{
		$this->do_text(
			__('Fonts Source URL (Pro)', $this->plugin_text_domain),
			'omgf_pro_source_url',
			__('e.g. https://cdn.mydomain.com/alternate/relative-path', $this->plugin_text_domain),
			defined('OMGF_PRO_SOURCE_URL') ? OMGF_PRO_SOURCE_URL : '',
			sprintf(
				__("Modify the <code>src</code> URL for each font file in the stylesheet. This can be anything, like an absolute URL (e.g. <code>%s</code>) to an alternate relative URL (e.g. <code>/renamed-wp-content-dir/alternate/path/to/font-files</code>). Make sure you include the full path to where OMGF's files are stored and/or served from. Defaults to <code>%s</code>.", $this->plugin_text_domain),
				str_replace(home_url(), 'https://your-cdn.com', WP_CONTENT_URL . OMGF_CACHE_PATH),
				WP_CONTENT_URL . OMGF_CACHE_PATH
			) . ' ' . $this->promo,
			true
		);
	}

	/**
	 *
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
