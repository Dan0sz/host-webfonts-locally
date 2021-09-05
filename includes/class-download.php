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
	private $url;

	private $filename;

	private $extension;

	private $path;

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
			return content_url($file_uri);
		}

		$tmp = download_url($this->url);
		copy($tmp, $file);
		@unlink($tmp);

		return urlencode(content_url($file_uri));
	}
}
