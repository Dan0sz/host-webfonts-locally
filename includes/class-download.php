<?php
defined('ABSPATH') || exit;

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

class OMGF_Download
{
	/** @var string $url */
	private $url;

	/** @var string $filename */
	private $filename;

	/** @var string $extension */
	private $extension;

	/** @var string $path */
	private $path;

	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'host-webfonts-local';

	/**
	 * OMGF_Download constructor.
	 */
	public function __construct(
		string $url,
		string $filename,
		string $extension,
		string $path
	) {
		$this->url = $url;
		$this->filename = $filename;
		$this->extension = $extension;
		$this->path = $path;
	}

	/**
	 * Download $url to $path and return content_url() to $filename.
	 * 
	 * @return string 
	 * 
	 * @throws SodiumException 
	 * @throws TypeError 
	 */
	public function download()
	{
		if (!function_exists('download_url')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		wp_mkdir_p($this->path);

		$file     = $this->path . '/' . $this->filename . '.' . $this->extension;
		$file_uri = str_replace(WP_CONTENT_DIR, '', $file);

		if (file_exists($file)) {
			return urlencode(content_url($file_uri));
		}

		$tmp = download_url($this->url);

		if (is_wp_error($tmp)) {
			/** @var WP_Error $tmp */
			OMGF_Admin_Notice::set_notice(__('OMGF encountered an error while downloading fonts', $this->plugin_text_domain) . ': ' . $tmp->get_error_message(), 'omgf-download-failed', false, 'error', $tmp->get_error_code());

			return '';
		}

		/** @var string $tmp */
		copy($tmp, $file);
		@unlink($tmp);

		return urlencode(content_url($file_uri));
	}
}
