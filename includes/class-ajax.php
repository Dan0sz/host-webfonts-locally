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
	 * 
	 * @since v4.5.3: Hardened security.
	 * @since v4.5.5: Added authentication.
	 */
	public function empty_directory()
	{
		check_ajax_referer(OMGF_Admin_Settings::OMGF_ADMIN_PAGE, 'nonce');

		if (!current_user_can('manage_options')) {
			wp_die(__("You're not cool enough to access this page.", $this->plugin_text_domain));
		}

		$section       = str_replace('*', '', $_POST['section']);
		$set_path      = rtrim(OMGF_FONTS_DIR . $section, '/');
		$resolved_path = realpath(OMGF_FONTS_DIR . $section);

		if ($resolved_path != $set_path) {
			wp_die(__('Attempted path traversal detected. Sorry, no script kiddies allowed!', $this->plugin_text_domain));
		}

		try {
			$section = $_POST['section'];
			$entries = array_filter((array) glob(OMGF_FONTS_DIR . $section));

			$instructions = apply_filters(
				'omgf_clean_up_instructions',
				[
					'section' => $section,
					'exclude' => [],
					'queue'   => [
						OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS,
						OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_FONTS,
						OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_PRELOAD_FONTS,
						OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_UNLOAD_STYLESHEETS
					]
				]
			);

			foreach ($entries as $entry) {
				if (in_array($entry, $instructions['exclude'])) {
					continue;
				}

				OMGF::delete($entry);
			}


			foreach ($instructions['queue'] as $option) {
				delete_option($option);
			}

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
