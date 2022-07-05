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

class OMGF_Optimize
{
    const USER_AGENT = [
        'woff2' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:101.0) Gecko/20100101 Firefox/101.0',
    ];

    /** @var string $url */
    private $url = '';

    /** @var string */
    private $handle = '';

    /** @var string $original_handle */
    private $original_handle = '';

    /** @var string $return */
    private $return = 'url';

    /** @var bool $return_early */
    private $return_early = false;

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
     * @param bool   $return_early    If this is set to true, the optimization will skip out early if the object already exists in the database.
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
        string $return = 'url',
        bool   $return_early = false
    ) {
        $this->url             = apply_filters('omgf_optimize_url', $url);
        $this->handle          = sanitize_title_with_dashes($handle);
        $this->original_handle = sanitize_title_with_dashes($original_handle);
        $this->path            = OMGF_UPLOAD_DIR . '/' . $this->handle;
        $this->subsets         = apply_filters('omgf_optimize_query_subset', '');
        $this->return          = $return;
        $this->return_early    = $return_early;
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

        /**
         * Convert protocol relative URLs.
         */
        if (strpos($this->url, '//') === 0) {
            $this->url = 'https:' . $this->url;
        }

        $fonts_bak  = $this->grab_fonts_object($this->url);
        $url        = $this->unload_variants($this->url);
        $local_file = $this->path . '/' . $this->handle . '.css';

        /**
         * @since v3.6.0 Allows us to bail early, if a fresh copy of files/stylesheets isn't necessary.
         */
        if (file_exists($local_file) && $this->return_early) {
            switch ($this->return) {
                case 'path':
                    return $local_file;
                case 'object':
                    return [$this->original_handle => $fonts_bak];
                default:
                    return str_replace(OMGF_UPLOAD_DIR, OMGF_UPLOAD_URL, $local_file);
            }
        }

        $fonts = $this->grab_fonts_object($url);

        if (empty($fonts)) {
            return '';
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

        $stylesheet = OMGF::generate_stylesheet($fonts);

        if (!file_exists($this->path)) {
            wp_mkdir_p($this->path);
        }

        file_put_contents($local_file, $stylesheet);

        $fonts_bak          = array_replace_recursive($fonts_bak, $fonts);
        $current_stylesheet = [$this->original_handle => $fonts_bak];

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
                'variants' => apply_filters('omgf_optimize_fonts_object_variants', $this->parse_variants($stylesheet, $font_family), $stylesheet, $font_family, $this->url),
                'subsets'  => apply_filters('omgf_optimize_fonts_object_subsets', $this->parse_subsets($stylesheet, $font_family), $stylesheet, $font_family, $this->url)
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
        /**
         * This also captures the commented Subset name.
         */
        preg_match_all(apply_filters('omgf_optimize_parse_variants_regex', '/\/\*\s.*?}/s', $this->url), $stylesheet, $font_faces);

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

            if (!empty($subset) && isset($subset[1])) {
                $font_object[$key]->subset = $subset[1];
            }

            if (!empty($range) && isset($range[1])) {
                $font_object[$key]->range = $range[1];
            }

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
     * Modifies the URL to not include unloaded variants.
     * 
     * @param mixed $url 
     * @return void 
     */
    private function unload_variants($url)
    {
        if (!isset(OMGF::unloaded_fonts()[$this->original_handle])) {
            return $url;
        }

        $url = urldecode($url);

        if (strpos($url, '/css2') !== false) {
            $url = $this->unload_css2($url);
        } else {
            $url = $this->unload_css($url);
        }

        return apply_filters('omgf_optimize_unload_variants_url', $url);
    }

    /**
     * Process unload for Variable Fonts API requests.
     * 
     * @param string $url full request to Variable Fonts API.
     * 
     * @return string full requests (excluding unloaded variants)
     */
    private function unload_css2($url)
    {
        $query = parse_url($url, PHP_URL_QUERY);

        foreach ($font_families = explode('&', $query) as $key => $family) {
            preg_match('/family=(?<name>[A-Za-z\s]+)[\:]?/', $family, $name);
            preg_match('/:(?P<axes>[a-z,]+)@/', $family, $axes);
            preg_match('/@(?P<tuples>[0-9,;]+)[&]?/', $family, $tuples);

            if (!isset($name['name']) || empty($name['name'])) {
                continue;
            }

            $name = $name['name'];
            $id   = str_replace(' ', '-', strtolower($name));

            if (!isset(OMGF::unloaded_fonts()[$this->original_handle][$id])) {
                continue;
            }

            if (!isset($axes['axes']) || empty($axes['axes'])) {
                $axes = 'wght';
            } else {
                $axes = $axes['axes'];
            }

            if (!isset($tuples['tuples']) || empty($tuples['tuples'])) {
                /**
                 * Variable Fonts API returns only regular (normal, 400) if no variations are defined.
                 */
                $tuples = ['400'];
            } else {
                $tuples = explode(';', $tuples['tuples']);
            }

            $unloaded_fonts = OMGF::unloaded_fonts()[$this->original_handle][$id];
            $tuples         = array_filter(
                $tuples,
                function ($tuple) use ($unloaded_fonts) {
                    return !in_array(preg_replace('/[0-9]+,/', '', $tuple), $unloaded_fonts);
                }
            );

            /**
             * The entire font-family appears to be unloaded, let's remove it.
             */
            if (empty($tuples)) {
                unset($font_families[$key]);

                continue;
            }

            $font_families[$key] = 'family=' . $name . ':' . $axes . '@' . implode(';', $tuples);
        }

        return 'https://fonts.googleapis.com/css2?' . implode('&', $font_families);
    }

    /**
     * Process unload for Google Fonts API.
     * 
     * @param string $url Full request to Google Fonts API.
     * 
     * @return string     Full request (excluding unloaded variants)
     */
    private function unload_css($url)
    {
        $query = parse_url($url, PHP_URL_QUERY);

        parse_str($query, $font_families);

        foreach ($font_families = explode('|', $font_families['family']) as $key => $font_family) {
            list($name, $tuples) = array_pad(explode(':', $font_family), 2, []);

            $id = str_replace(' ', '-', strtolower($name));

            if (!isset(OMGF::unloaded_fonts()[$this->original_handle][$id])) {
                continue;
            }

            /**
             * Google Fonts API returns 400 if no tuples are defined.
             */
            if (empty($tuples)) {
                $tuples = ['400'];
            } else {
                $tuples = explode(',', $tuples);
            }

            $unloaded_fonts = OMGF::unloaded_fonts()[$this->original_handle][$id];
            $tuples         = array_filter(
                $tuples,
                function ($tuple) use ($unloaded_fonts) {
                    return !in_array($tuple, $unloaded_fonts);
                }
            );

            /**
             * The entire font-family appears to be unloaded, let's remove it.
             */
            if (empty($tuples)) {
                unset($font_families[$key]);

                continue;
            }

            $font_families[$key] = urlencode($name) . ':' . implode(',', $tuples);
        }

        return 'https://fonts.googleapis.com/css?family=' . implode('|', $font_families);
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
