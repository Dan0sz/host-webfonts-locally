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
		add_filter('omgf_advanced_settings_content', [$this, 'do_exclude_posts'], 50);
		add_filter('omgf_advanced_settings_content', [$this, 'do_cache_dir'], 70);
		add_filter('omgf_advanced_settings_content', [$this, 'do_cdn_url'], 80);
		add_filter('omgf_advanced_settings_content', [$this, 'do_cache_uri'], 90);
		add_filter('omgf_advanced_settings_content', [$this, 'do_relative_url'], 100);
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

	/**
	 * Excluded Post/Page IDs (Pro)
	 * 
	 * @return void 
	 */
	public function do_exclude_posts()
	{
		$this->do_text(
			__('Excluded Post/Page IDs (Pro)', $this->plugin_text_domain),
			'omgf_pro_excluded_ids',
			__('e.g. 1,2,5,21,443'),
			defined('OMGF_PRO_EXCLUDED_IDS') ? OMGF_PRO_EXCLUDED_IDS : '',
			__('A comma separated list of post/page IDs where OMGF Pro shouldn\'t run.', $this->plugin_text_domain),
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
	public function do_cdn_url()
	{
		$this->do_text(
			__('CDN URL', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_CDN_URL,
			__('e.g. https://cdn.mydomain.com', $this->plugin_text_domain),
			OMGF_CDN_URL,
			__("If you're using a CDN, enter the URL here incl. protocol (e.g. <code>https://</code>.) Leave empty when using CloudFlare.", $this->plugin_text_domain)
		);
	}

	/**
	 *
	 */
	public function do_cache_uri()
	{
		$this->do_text(
			__('Alternative Relative Path', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_CACHE_URI,
			__('e.g. /app/uploads/omgf', $this->plugin_text_domain),
			OMGF_CACHE_URI,
			__('An alternative elative path to serve font files from. Useful for when you\'re using security through obscurity plugins, such as WP Hide. If left empty, the <strong>Fonts Cache Directory</strong> will be used.', $this->plugin_text_domain)
		);
	}

	/**
	 *
	 */
	public function do_relative_url()
	{
		$this->do_checkbox(
			__('Use Relative URLs', $this->plugin_text_domain),
			OMGF_Admin_Settings::OMGF_ADV_SETTING_RELATIVE_URL,
			OMGF_RELATIVE_URL,
			__('Use relative instead of absolute (full) URLs to generate the stylesheet. <em><strong>Warning!</strong> This will disable the CDN URL.</em>', $this->plugin_text_domain)
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
