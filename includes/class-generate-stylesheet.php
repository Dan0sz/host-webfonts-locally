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

class OMGF_GenerateStylesheet
{
	/** @var $fonts */
	private $fonts;

	/** @var string $handle */
	private $handle;

	/** @var string $plugin */
	private $plugin;

	/**
	 * OMGF_GenerateStylesheet constructor.
	 */
	public function __construct(
		$fonts,
		string $handle,
		string $plugin
	) {
		$this->fonts  = $fonts;
		$this->handle = $handle;
		$this->plugin = $plugin;
	}

	/**
	 * Generate a stylesheet based on the provided $fonts.
	 * 
	 * @return string 
	 */
	public function generate()
	{
		/**
		 * Which file types should we download and include in the stylesheet?
		 * 
		 * @since v4.5
		 */
		$file_types   = apply_filters('omgf_include_file_types', ['woff2', 'woff', 'eot', 'ttf', 'svg']);
		/**
		 * Load Fallback Font Stacks.
		 * 
		 * @since v4.5.1
		 */
		$fallbacks    = apply_filters('omgf_fallback_font_stacks', []);
		$font_display = OMGF_DISPLAY_OPTION;
		$stylesheet   = "/**\n * Auto Generated by $this->plugin\n * @author: Daan van den Bergh\n * @url: https://ffw.press\n */\n\n";

		foreach ($this->fonts as $font) {
			/**
			 * If Font Family's name was recently renamed, the old name should be used so no manual changes have to be made 
			 * to the stylesheet after processing.
			 */
			$renamed_font_family = in_array($font->id, OMGF_API_Download::OMGF_RENAMED_GOOGLE_FONTS)
				? array_search($font->id, OMGF_API_Download::OMGF_RENAMED_GOOGLE_FONTS)
				: '';

			foreach ($font->variants as $variant) {
				$font_family = $renamed_font_family ? ucfirst($renamed_font_family) : $variant->fontFamily;
				$font_family .= isset($fallbacks[$this->handle][$font->id]) ? ', ' . $fallbacks[$this->handle][$font->id] : '';
				$font_style  = $variant->fontStyle;
				$font_weight = $variant->fontWeight;
				$stylesheet .= "@font-face {\n";
				$stylesheet .= "    font-family: $font_family;\n";
				$stylesheet .= "    font-style: $font_style;\n";
				$stylesheet .= "    font-weight: $font_weight;\n";
				$stylesheet .= "    font-display: $font_display;\n";

				/**
				 * For IE compatibility, EOT is added before the local family name is defined.
				 */
				if (in_array('eot', $file_types)) {
					$stylesheet .= "    src: url('" . $variant->eot . "');\n";
					$eot_key     = array_search('eot', $file_types);
					unset($file_types[$eot_key]);
				}

				$local_src = '';

				if (isset($variant->local) && is_array($variant->local)) {
					foreach ($variant->local as $local) {
						$local_src .= "local('$local'), ";
					}
				}

				$stylesheet  .= "    src: $local_src\n";
				$font_src_url = [];

				foreach ($file_types as $file_type) {
					$font_src_url = $font_src_url + (isset($variant->$file_type) ? [$file_type => $variant->$file_type] : []);
				}

				$stylesheet .= $this->build_source_string($font_src_url);
				$stylesheet .= "}\n";
			}
		}

		return $stylesheet;
	}

	/**
	 * @param        $sources
	 * @param string $type
	 * @param bool   $end_semi_colon
	 *
	 * @return string
	 */
	private function build_source_string($sources, $type = 'url', $end_semi_colon = true)
	{
		$last_src = end($sources);
		$source   = '';

		foreach ($sources as $format => $url) {
			$source .= "    $type('$url')" . (!is_numeric($format) ? " format('$format')" : '');

			if ($url === $last_src && $end_semi_colon) {
				$source .= ";\n";
			} else {
				$source .= ",\n";
			}
		}

		return $source;
	}
}
