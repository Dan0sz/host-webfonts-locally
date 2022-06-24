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

class OMGF_Optimize
{
    const USER_AGENT                      = [
        'woff2' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:101.0) Gecko/20100101 Firefox/101.0',
    ];

    /**
     * If a font changed names recently, this array will map the old name (key) to the new name (value).
     * 
     * The key of an element should be dashed (no spaces) if necessary, e.g. open-sans.
     */
    const OMGF_RENAMED_GOOGLE_FONTS = [
        'crimson-text' => 'crimson-pro',
        'ek-mukta'     => 'mukta',
        'muli'         => 'mulish'
    ];

    /** @var string $url */
    private $url = '';

    /** @var string */
    private $handle = '';

    /** @var string $original_handle */
    private $original_handle = '';

    /** @var string $return */
    private $return = 'url';

    /** @var string */
    private $path = '';

    /** 
     * @var array $variable_fonts An array of font families in the current stylesheets that're Variable Fonts.
     * 
     * @since v5.3.0
     */
    private $variable_fonts = [];

    /** @var string */
    private $plugin_text_domain = 'host-webfonts-local';

    /**
     * @param string $url             Google Fonts API URL, e.g. "fonts.googleapis.com/css?family="Lato:100,200,300,etc."
     * @param string $handle          The cache handle, generated using $handle + 5 random chars. Used for storing the fonts and stylesheet.
     * @param string $original_handle The stylesheet handle, present in the ID attribute.
     * @param string $subset          Contents of "subset" parameter. If left empty, the downloaded files will support all subsets.
     * @param string $return          Valid values: 'url' | 'path' | 'object'.
     * 
     * @return string Local URL of generated stylesheet.
     * 
     * @throws SodiumException 
     * @throws SodiumException 
     * @throws TypeError 
     * @throws TypeError 
     * @throws TypeError 
     */
    public function __construct(
        string $url,
        string $handle,
        string $original_handle,
        string $return = 'url'
    ) {
        $this->url             = $url;
        $this->handle          = sanitize_title_with_dashes($handle);
        $this->original_handle = sanitize_title_with_dashes($original_handle);
        $this->path            = OMGF_UPLOAD_DIR . '/' . $this->handle;
        $this->subsets         = apply_filters('omgf_optimize_query_subset', '');
        $this->return          = $return;
    }

    /**
     * @return string|array
     * 
     * @throws SodiumException 
     * @throws SodiumException 
     * @throws TypeError 
     * @throws TypeError 
     * @throws TypeError 
     */
    public function process()
    {
        if (!$this->handle || !$this->original_handle) {
            OMGF_Admin_Notice::set_notice(sprintf(__('OMGF couldn\'t find required stylesheet handle parameter while attempting to talk to API. Values sent were <code>%s</code> and <code>%s</code>.', $this->plugin_text_domain), $this->original_handle, $this->handle), 'omgf-api-handle-not-found', 'error', 406);

            return '';
        }

        $fonts = $this->grab_fonts_object($this->url);

        if (empty($fonts)) {
            return '';
        }

        /**
         * @todo font styles should be unloaded, before the stylesheet is fetched from Google.
         */
        foreach ($fonts as $id => &$font) {
            if ($unloaded_fonts = OMGF::unloaded_fonts()) {
                // Dequeue the fonts we don't want.
                if (isset($unloaded_fonts[$this->original_handle][$id])) {
                    $font->variants = $this->dequeue_unloaded_variants($font->variants, $unloaded_fonts[$this->original_handle], $id);
                }
            }
        }

        foreach ($fonts as $id => &$font) {
            /**
             * Sanitize font family, because it may contain spaces.
             * 
             * @since v4.5.6
             */
            $font->family = rawurlencode($font->family);

            foreach ($font->variants as &$variant) {
                /**
                 * @since v5.3.0 Variable fonts use one filename for all font weights/styles. That's why we drop the weight from the filename.
                 * 
                 * @todo  Perhaps we also need to drop the font style?
                 */
                if (isset($this->variable_fonts[$id])) {
                    $filename = strtolower($id . '-' . $variant->fontStyle . '-' . (isset($variant->subset) ? $variant->subset : ''));
                } else {
                    $filename = strtolower($id . '-' . $variant->fontStyle . '-' . (isset($variant->subset) ? $variant->subset . '-' : '') . $variant->fontWeight);
                }

                /**
                 * Encode font family, because it may contain spaces.
                 * 
                 * @since v4.5.6
                 */
                $variant->fontFamily = rawurlencode($variant->fontFamily);

                if (isset($variant->woff2)) {
                    $variant->woff2 = OMGF::download($variant->woff2, $filename, 'woff2', $this->path);
                }
            }
        }

        $local_file = $this->path . '/' . $this->handle . '.css';
        $stylesheet = OMGF::generate_stylesheet($fonts);

        if (!file_exists($this->path)) {
            wp_mkdir_p($this->path);
        }

        file_put_contents($local_file, $stylesheet);

        $current_stylesheet = [$this->original_handle => $fonts];

        /**
         * $current_stylesheet is added to temporary cache layer, if it isn't present in database.
         * 
         * @since v4.5.7
         */
        $optimized_fonts = OMGF::optimized_fonts($current_stylesheet);

        /**
         * When unload is used, this takes care of rewriting the font style URLs in the database.
         * 
         * @since v4.5.7
         */
        $optimized_fonts = $this->rewrite_variants($optimized_fonts, $current_stylesheet);

        update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, $optimized_fonts);

        switch ($this->return) {
            case 'path':
                return $local_file;
                break;
            case 'object':
                return $current_stylesheet;
                break;
            default: // 'url'
                return str_replace(OMGF_UPLOAD_DIR, OMGF_UPLOAD_URL, $local_file);
        }
    }

    /**
     * @param $variants
     * @param $unloaded_fonts
     * @param $font_id
     *
     * @return array
     */
    private function dequeue_unloaded_variants($variants, $unloaded_fonts, $font_id)
    {
        return array_filter(
            $variants,
            function ($variant) use ($unloaded_fonts, $font_id) {
                if ($variant->id == '400') {
                    // Sometimes the font is defined as 'regular', so we need to check both.
                    return !in_array('regular', $unloaded_fonts[$font_id]) && !in_array($variant->id, $unloaded_fonts[$font_id]);
                }

                if ($variant->id == '400italic') {
                    // Sometimes the font is defined as 'italic', so we need to check both.
                    return !in_array('italic', $unloaded_fonts[$font_id]) && !in_array($variant->id, $unloaded_fonts[$font_id]);
                }

                return !in_array($variant->id, $unloaded_fonts[$font_id]);
            }
        );
    }

    /**
     * @since v5.3.0 Parse the stylesheet and build it into a font object which OMGF can understand.
     * 
     * @param $id    Unique identifier for this Font Family, lowercase, dashes instead of spaces.
     * @param $query
     * @param $name  The full name of the requested Font Family, e.g. Roboto Condensed, Open Sans or Roboto.
     *
     * @return mixed|void
     */
    private function grab_fonts_object($url)
    {
        $response = wp_remote_get($url, [
            'user-agent' => self::USER_AGENT['woff2']
        ]);

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return new stdClass();
        }

        $stylesheet = wp_remote_retrieve_body($response);

        preg_match_all('/font-family:\s\'(.*?)\';/', $stylesheet, $font_families);

        if (!isset($font_families[1]) || empty($font_families[1])) {
            return new stdClass();
        }

        $font_families = array_unique($font_families[1]);

        foreach ($font_families as $font_family) {
            $id          = strtolower(str_replace(' ', '-', $font_family));
            $object[$id] = (object) [
                'id'       => $id,
                'family'   => $font_family,
                'variants' => $this->parse_variants($stylesheet, $font_family),
                'subsets'  => $this->parse_subsets($stylesheet, $font_family)
            ];
        }

        return $object;
    }

    /**
     * Parse a stylesheet from Google Fonts' API into a valid Font Object.
     * 
     * @param string $stylesheet 
     * @param string $font_family 
     * 
     * @return array
     */
    private function parse_variants($stylesheet, $font_family)
    {
        preg_match_all('/\/\*\s.*?}/s', $stylesheet, $font_faces);

        if (!isset($font_faces[0]) || empty($font_faces[0])) {
            return [];
        }

        foreach ($font_faces[0] as $key => $font_face) {
            if (strpos($font_face, $font_family) === false) {
                continue;
            }

            preg_match('/font-style:\s(normal|italic);/', $font_face, $font_style);
            preg_match('/font-weight:\s([0-9]+);/', $font_face, $font_weight);
            preg_match('/src:\surl\((.*?woff2)\)/', $font_face, $font_src);
            preg_match('/\/\*\s([a-z\-]+?)\s\*\//', $font_face, $subset);
            preg_match('/unicode-range:\s(.*?);/', $font_face, $range);

            $font_object[$key]             = new stdClass();
            $font_object[$key]->id         = $font_weight[1] . ($font_style[1] == 'normal' ? '' : $font_style[1]);
            $font_object[$key]->fontFamily = $font_family;
            $font_object[$key]->fontStyle  = $font_style[1];
            $font_object[$key]->fontWeight = $font_weight[1];
            $font_object[$key]->woff2      = $font_src[1];
            $font_object[$key]->subset     = $subset[1];
            $font_object[$key]->range      = $range[1];

            /**
             * @since v5.3.0 Is this a variable font i.e. one font file for multiple font weights/styles?
             */
            if (substr_count($stylesheet, $font_src[1]) > 1) {
                $this->variable_fonts[strtolower(str_replace(' ', '-', $font_family))] = true;
            }
        }

        return $font_object;
    }

    /**
     * Parse stylesheets for subsets, which in Google Fonts stylesheets are always
     * included, commented above each @font-face statements, e.g. /* latin-ext */ /*
     */
    private function parse_subsets($stylesheet)
    {
        preg_match_all('/\/\*\s([a-z\-]+?)\s\*\//', $stylesheet, $subsets);

        if (!isset($subsets[1]) || empty($subsets[1])) {
            return [];
        }

        return array_unique($subsets[1]);
    }

    /**
     * When unload is used, insert the cache key in the font URLs for the variants still in use.
     *
     * @param array $all_stylesheets    Contains all font styles, loaded and unloaded.
     * @param array $current_stylesheet Contains just the loaded font styles of current stylesheet.
     *
     * @return mixed
     */
    private function rewrite_variants($all_stylesheets, $current_stylesheet)
    {
        foreach ($all_stylesheets as $stylesheet => &$fonts) {
            foreach ($fonts as $index => &$font) {
                if (empty((array) $font->variants)) {
                    continue;
                }

                foreach ($font->variants as $variant_index => &$variant) {
                    $replace_variant = $current_stylesheet[$stylesheet][$index]->variants[$variant_index] ?? (object) [];

                    if (!empty((array) $replace_variant)) {
                        $variant = $replace_variant;
                    }
                }
            }
        }

        return $all_stylesheets;
    }
}
