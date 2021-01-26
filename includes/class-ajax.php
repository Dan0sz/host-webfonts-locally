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

class OMGF_AJAX
{
	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'host-webfonts-local';

	/**
	 * OMGF_AJAX constructor.
	 */
	public function __construct()
	{
		add_action('wp_ajax_omgf_ajax_empty_dir', [$this, 'empty_directory']);
	}

	/**
	 * Empty cache directory.
	 */
	public function empty_directory()
	{
		try {
			$entries = array_filter((array) glob(OMGF_FONTS_DIR . '/*'));

			foreach ($entries as $entry) {
				OMGF::delete($entry);
			}

			/**
			 * Empty related options.
			 */
			delete_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS);
			delete_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS);
			delete_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS);
			delete_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS);

			OMGF_Admin_Notice::set_notice(__('Cache directory successfully emptied.', $this->plugin_text_domain));
		} catch (\Exception $e) {
			OMGF_Admin_Notice::set_notice(
				__('OMGF encountered an error while emptying the cache directory: ', $this->plugin_text_domain) . $e->getMessage(),
				'omgf-cache-error',
				true,
				'error',
				$e->getCode()
			);
		}
	}
}
