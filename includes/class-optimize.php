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
    const OMGF_GOOGLE_FONTS_API_URL       = 'https://google-webfonts-helper.herokuapp.com/api/fonts/';
    const OMGF_GOOGLE_FONTS_API_FALLBACK  = 'https://omgf-google-fonts-api.herokuapp.com/api/fonts/';
    const OMGF_USE_FALLBACK_API_TRANSIENT = 'omgf_use_fallback_api';

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

    /** @var string $family */
    private $family = '';

    /** @var string */
    private $handle = '';

    /** @var string $original_handle */
    private $original_handle = '';

    /** @var string $subset */
    private $subset = '';

    /** @var string $return */
    private $return = 'url';

    /** @var string */
    private $path = '';

    /** @var string */
    private $plugin_text_domain = 'host-webfonts-local';

    /**
     * @param string $family          Contents of "family" parameters in Google Fonts API URL, e.g. "?family="Lato:100,200,300,etc."
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
        string $family,
        string $handle,
        string $original_handle,
        string $subset = '',
        string $return = 'url'
    ) {
        $this->family          = $family;
        $this->handle          = sanitize_title_with_dashes($handle);
        $this->original_handle = sanitize_title_with_dashes($original_handle);
        $this->subset          = $subset;
        $this->path            = OMGF_UPLOAD_DIR . '/' . $this->handle;
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

        $font_families = explode('|', $this->family);
        $query         = [];
        $fonts         = [];

        if ($this->subset) {
            $query['subsets'] = $this->subset;
        }

        foreach ($font_families as $key => $font_family) {
            /**
             * Prevent duplicate entries by generating a unique identifier, all lowercase,
             * with (multiple) spaces replaced by dashes.
             * 
             * @since v5.1.4
             */
            $font_name = explode(':', $font_family)[0];
            $font_id   = strtolower(preg_replace("/[\s\+]+/", '-', $font_name));

            $font_families[$font_id] = $font_family;
            unset($font_families[$key]);
        }

        foreach ($font_families as $font_id => $font_family) {
            if (empty($font_family)) {
                continue;
            }

            if (!isset($fonts[$font_id])) {
                $fonts[$font_id] = $this->grab_font_object($font_id, $query, $font_name);
            }
        }

        // Filter out empty elements, i.e. failed requests.
        $fonts = array_filter($fonts);

        if (empty($fonts)) {
            return '';
        }

        foreach ($fonts as $id => &$font) {
            $fonts_request = $font_families[$id];

            /**
             * If no colon is found, @var string $requested_variants will be an empty string.
             * 
             * @since v5.1.4
             */
            list(, $requested_variants) = array_pad(explode(':', $fonts_request), 2, '');

            $requested_variants = $this->parse_requested_variants($requested_variants, $font);

            if ($unloaded_fonts = OMGF::unloaded_fonts()) {
                $font_id = $font->id;

                // Now we're sure we got 'em all. We can safely dequeue those we don't want.
                if (isset($unloaded_fonts[$this->original_handle][$font_id])) {
                    $requested_variants = $this->dequeue_unloaded_variants($requested_variants, $unloaded_fonts[$this->original_handle], $font->id);
                }
            }

            $font->variants = $this->process_unload_queue($font->id, $font->variants, $requested_variants, $this->original_handle);
        }

        /**
         * Which file types should we download and include in the stylesheet?
         * 
         * @since v4.5
         */
        $file_types = apply_filters('omgf_include_file_types', ['woff2', 'woff', 'eot', 'ttf', 'svg']);

        foreach ($fonts as &$font) {
            $font_id = $font->id;

            /**
             * Sanitize font family, because it may contain spaces.
             * 
             * @since v4.5.6
             */
            $font->family = rawurlencode($font->family);

            foreach ($font->variants as &$variant) {
                $filename = strtolower($font_id . '-' . $variant->fontStyle . '-' . $variant->fontWeight);

                /**
                 * Encode font family, because it may contain spaces.
                 * 
                 * @since v4.5.6
                 */
                $variant->fontFamily = rawurlencode($variant->fontFamily);

                foreach ($file_types as $file_type) {
                    if (isset($variant->$file_type)) {
                        $variant->$file_type = OMGF::download($variant->$file_type, $filename, $file_type, $this->path);
                    }
                }
            }
        }

        $local_file = $this->path . '/' . $this->handle . '.css';
        $stylesheet = OMGF::generate_stylesheet($fonts, $this->original_handle);

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
                if ($variant == '400') {
                    // Sometimes the font is defined as 'regular', so we need to check both.
                    return !in_array('regular', $unloaded_fonts[$font_id]) && !in_array($variant, $unloaded_fonts[$font_id]);
                }

                if ($variant == '400italic') {
                    // Sometimes the font is defined as 'italic', so we need to check both.
                    return !in_array('italic', $unloaded_fonts[$font_id]) && !in_array($variant, $unloaded_fonts[$font_id]);
                }

                return !in_array($variant, $unloaded_fonts[$font_id]);
            }
        );
    }

    /**
     * @param $id    Unique identifier for this Font Family, lowercase, dashes instead of spaces.
     * @param $query
     * @param $name  The full name of the requested Font Family, e.g. Roboto Condensed, Open Sans or Roboto.
     *
     * @return mixed|void
     */
    private function grab_font_object($id, $query, $name)
    {
        /**
         * Add fonts to the request's $_GET 'family' parameter. Then pass an array to 'omgf_alternate_fonts' 
         * filter. Then pass an alternate API url to the 'omgf_alternate_api_url' filter to fetch fonts from 
         * an alternate API.
         */
        $alternate_fonts = apply_filters('omgf_alternate_fonts', []);
        $alternate_url   = '';
        $query_string    = '';

        if (in_array($id, array_keys($alternate_fonts))) {
            $alternate_url = apply_filters('omgf_alternate_api_url', '', $id);
            unset($query);
        }

        if (!empty($query)) {
            $query_string = '?' . http_build_query($query);
        }

        /**
         * If a font changed names recently, map their old name to the new name, before triggering the API request.
         */
        if (in_array($id, array_keys(self::OMGF_RENAMED_GOOGLE_FONTS))) {
            $id = self::OMGF_RENAMED_GOOGLE_FONTS[$id];
        }

        if (!$alternate_url) {
            $response = $this->remote_get($id, $query_string);
        } else {
            $response = wp_remote_get(
                sprintf($alternate_url . '%s', $id) . $query_string
            );
        }

        if (is_wp_error($response)) {
            OMGF_Admin_Notice::set_notice(sprintf(__('OMGF encountered an error while trying to fetch fonts: %s', $this->plugin_text_domain), $response->get_error_message()), $response->get_error_code(), 'error', 408);
        }

        /**
         * If no subset was set, do a quick refresh to make sure all available subsets are included.
         */
        if (!$query_string && !$alternate_url) {
            $response_body = wp_remote_retrieve_body($response);
            $body          = json_decode($response_body);
            $query_string  = '?subsets=' . (isset($body->subsets) ? implode(',', $body->subsets) : 'latin,latin-ext');
            $response      = $this->remote_get($id, $query_string);
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code != 200) {
            $error_body    = wp_remote_retrieve_body($response);
            $error_message = wp_remote_retrieve_response_message($response);
            $message       = sprintf(__('OMGF couldn\'t find <strong>%s</strong> while parsing %s. The API returned the following error: %s.', $this->plugin_text_domain), $name, isset($_GET['omgf_optimize']) ? 'your homepage' : $_SERVER['REQUEST_URI'], is_wp_error($response) ? $response->get_error_message() : $error_message);

            OMGF_Admin_Notice::set_notice($message, 'omgf_api_error', 'error');

            if ($error_message == 'Service Unavailable') {
                $message = __('OMGF\'s Google Fonts API is currently unavailable. Try again later.', $this->plugin_text_domain);

                OMGF_Admin_Notice::set_notice($message, 'omgf_api_error', 'error', $response_code);
            }

            if ($error_body == 'Not found') {
                $message = sprintf(__('Please verify that %s is available for free at Google Fonts by doing <a href="%s" target="_blank">a manual search</a>. Maybe it\'s a Premium font?', $this->plugin_text_domain), $name, 'https://fonts.google.com/?query=' . str_replace('-', '+', $id));

                OMGF_Admin_Notice::set_notice($message, 'omgf_api_info_not_found', 'info');
            }

            if ($error_body == 'Internal Server Error') {
                $message = sprintf(__('Try using the Force Subsets option (available in OMGF Pro) to force loading %s in a subset in which it\'s actually available. Use the Language filter <a href="%s" target="_blank">here</a> to verify which subsets are available for %s.', $this->plugin_text_domain), $name, 'https://fonts.google.com/?query=' . str_replace('-', '+', $id), $name);

                OMGF_Admin_Notice::set_notice($message, 'omgf_api_info_internal_server_error', 'info');
            }

            return [];
        }

        return json_decode(wp_remote_retrieve_body($response));
    }

    /**
     * Wrapper for wp_remote_get() which tries a mirror API if the first request fails. (It tends to timeout sometimes)
     * 
     * @param string $family 
     * @param string $query 
     * 
     * @return array|WP_Error 
     */
    private function remote_get($family, $query)
    {
        $response = wp_remote_get(
            sprintf(self::OMGF_GOOGLE_FONTS_API_URL . '%s', $family) . $query
        );

        // Try with mirror, if first request failed.
        if (is_wp_error($response) && $response->get_error_code() == 'http_request_failed') {
            $response = wp_remote_get(
                sprintf(self::OMGF_GOOGLE_FONTS_API_FALLBACK . '%s', $family) . $query
            );
        }

        return $response;
    }

    /**
     * @param $request
     * @param $font
     *
     * @return array
     */
    private function parse_requested_variants($request, $font)
    {
        /**
         * Build an array and filter out empty elements.
         */
        $requested_variants = array_filter(explode(',', $request));

        /**
         * This means by default all fonts are requested, so we need to fill up the queue, before unloading the unwanted variants.
         */
        if (count($requested_variants) == 0) {
            foreach ($font->variants as $variant) {
                $requested_variants[] = $variant->id;
            }
        }

        return $requested_variants;
    }

    /**
     * 
     * @param string $font_id 
     * @param array  $all_variants      An array of all available font family variants.
     * @param array  $wanted_variants   An array of requested variants in this font family request.
     * @param string $stylesheet_handle 
     * @return mixed 
     */
    private function process_unload_queue($font_id, $all_variants, $wanted_variants, $stylesheet_handle)
    {
        /**
         * If $variants is empty and this is the first run, i.e. there are no unloaded fonts (yet)
         * return all available variants.
         */
        if (empty($wanted_variants) && !isset(OMGF::unloaded_fonts()[$stylesheet_handle][$font_id])) {
            return $all_variants;
        }

        return array_filter(
            $all_variants,
            function ($font) use ($wanted_variants) {
                $id = $font->id;

                if ($id == 'regular' || $id == '400') {
                    return in_array('400', $wanted_variants) || in_array('regular', $wanted_variants);
                }

                if ($id == 'italic') {
                    return in_array('400italic', $wanted_variants) || in_array('italic', $wanted_variants);
                }

                return in_array($id, $wanted_variants);
            }
        );
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
