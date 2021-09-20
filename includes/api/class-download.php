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

class OMGF_API_Download extends WP_REST_Controller
{
    const OMGF_GOOGLE_FONTS_API_URL       = 'https://google-webfonts-helper.herokuapp.com/api/fonts/';
    const OMGF_GOOGLE_FONTS_API_FALLBACK  = 'https://n8n-google-fonts-helper.herokuapp.com/api/fonts/';
    const OMGF_USE_FALLBACK_API_TRANSIENT = 'omgf_use_fallback_api';

    /**
     * If a font changed names recently, this array will map the old name (key) to the new name (value).
     * 
     * The key of an element should be dashed (no spaces) if necessary, e.g. open-sans.
     */
    const OMGF_RENAMED_GOOGLE_FONTS   = [
        'ek-mukta' => 'mukta',
        'muli'     => 'mulish'
    ];

    private $plugin_text_domain = 'host-webfonts-local';

    /** @var array */
    private $endpoints = ['css', 'css2'];

    /** @var string */
    protected $namespace = 'omgf/v1';

    /** @var string */
    protected $rest_base = '/download/';

    /** @var string */
    private $handle = '';

    /** @var string */
    private $path = '';

    /**
     * @return void 
     */
    public function register_routes()
    {
        foreach ($this->endpoints as $endpoint) {
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . $endpoint,
                [
                    [
                        'methods'             => 'GET',
                        'callback'            => [$this, 'process'],
                        'permission_callback' => [$this, 'permissions_check']
                    ],
                    'schema' => null,
                ]
            );
        }
    }

    /**
     * Prevent CSRF.
     * 
     * @since v4.5.4
     * 
     * @return bool
     */
    public function permissions_check()
    {
        if (!isset($_REQUEST['_wpnonce'])) {
            return false;
        }

        return wp_verify_nonce($_REQUEST['_wpnonce'], 'wp_rest') > 0;
    }

    /**
     * @param $request WP_Rest_Request
     */
    public function process($request)
    {
        if (strpos($request->get_route(), 'css2') !== false) {
            $this->convert_css2($request);
        }

        $params          = $request->get_params();
        $this->handle    = sanitize_title_with_dashes($params['handle']) ?? '';
        $original_handle = sanitize_title_with_dashes($request->get_param('original_handle'));

        if (!$this->handle || !$original_handle) {
            wp_die(__('Handle not provided.', $this->plugin_text_domain), 406);
        }

        $this->path       = WP_CONTENT_DIR . OMGF_CACHE_PATH . '/' . $this->handle;
        $font_families    = explode('|', $params['family']);
        $query['subsets'] = $params['subset'] ?? 'latin,latin-ext';
        $fonts            = [];

        foreach ($font_families as $font_family) {
            if (empty($font_family)) {
                continue;
            }

            $fonts[] = $this->grab_font_family($font_family, $query);
        }

        // Filter out empty elements, i.e. failed requests.
        $fonts = array_filter($fonts);

        if (empty($fonts)) {
            exit();
        }

        foreach ($fonts as $font_key => &$font) {
            $fonts_request = $this->build_fonts_request($font_families, $font);

            if (strpos($fonts_request, ':') != false) {
                list($family, $requested_variants) = explode(':', $fonts_request);
            } else {
                $family   = $fonts_request;
                $requested_variants = '';
            }

            $requested_variants = $this->parse_requested_variants($requested_variants, $font);

            if ($unloaded_fonts = OMGF::unloaded_fonts()) {
                $font_id = $font->id;

                // Now we're sure we got 'em all. We can safely dequeue those we don't want.
                if (isset($unloaded_fonts[$original_handle][$font_id])) {
                    $requested_variants = $this->dequeue_unloaded_variants($requested_variants, $unloaded_fonts[$original_handle], $font->id);
                    $fonts_request      = $family . ':' . implode(',', $requested_variants);
                }
            }

            $font->variants = $this->process_unload_queue($font->id, $font->variants, $fonts_request, $original_handle);
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

        $stylesheet = OMGF::generate_stylesheet($fonts, $original_handle);
        $local_file = $this->path . '/' . $this->handle . '.css';

        file_put_contents($local_file, $stylesheet);

        $current_fonts   = [$original_handle => $fonts];
        $optimized_fonts = OMGF::optimized_fonts();

        // At first run, simply override the optimized_fonts array.
        if (empty($optimized_fonts)) {
            $optimized_fonts = $current_fonts;
            // When a new font is detected, add it to the list.
        } elseif (!isset($optimized_fonts[$original_handle])) {
            $optimized_fonts = $optimized_fonts + $current_fonts;
            // Unload is probably used. Let's rewrite the variants still in use.
        } else {
            $optimized_fonts = $this->rewrite_variants($optimized_fonts, $current_fonts);
        }

        update_option(OMGF_Admin_Settings::OMGF_OPTIMIZE_SETTING_OPTIMIZED_FONTS, $optimized_fonts);

        // After generating it, serve it.
        header('Content-Type: text/css');
        header('Content-Length: ' . filesize($local_file));
        flush();
        readfile($local_file);
        exit();
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
            function ($value) use ($unloaded_fonts, $font_id) {
                if ($value == '400') {
                    // Sometimes the font is defined as 'regular', so we need to check both.
                    return !in_array('regular', $unloaded_fonts[$font_id]) && !in_array($value, $unloaded_fonts[$font_id]);
                }

                if ($value == '400italic') {
                    // Sometimes the font is defined as 'italic', so we need to check both.
                    return !in_array('italic', $unloaded_fonts[$font_id]) && !in_array($value, $unloaded_fonts[$font_id]);
                }

                return !in_array($value, $unloaded_fonts[$font_id]);
            }
        );
    }

    /**
     * Converts requests to OMGF's Download/CSS2 API to a format readable by the regular API.
     *
     * @param $request WP_Rest_Request
     */
    private function convert_css2(&$request)
    {
        $query         = $this->get_query_from_request();
        $params        = explode('&', $query);
        $font_families = [];
        $fonts         = [];

        foreach ($params as $param) {
            if (strpos($param, 'family') === false) {
                continue;
            }

            parse_str($param, $parts);

            $font_families[] = $parts;
        }

        foreach ($font_families as $font_family) {
            if (strpos($font_family, ':') !== false) {
                list($family, $weights) = explode(':', reset($font_family));
            } else {
                $family  = $font_family;
                $weights = '';
            }

            /**
             * @return array [ '300', '400', '500', etc. ]
             */
            $weights = explode(';', substr($weights, strpos($weights, '@') + 1));

            $fonts[] = $family . ':' . implode(',', $weights);
        }

        $request->set_param('family', implode('|', $fonts));
    }

    /**
     * Since Google Fonts' variable fonts API uses the same name for each parameter ('family') we need to parse the url manually.
     *
     * @return mixed
     */
    private function get_query_from_request()
    {
        return parse_url($_SERVER['REQUEST_URI'])['query'];
    }

    /**
     * @param $font_family
     * @param $url
     * @param $query
     *
     * @return mixed|void
     */
    private function grab_font_family($font_family, $query)
    {
        $url = $this->get_working_service_url();

        list($family) = explode(':', $font_family);
        $family       = strtolower(str_replace([' ', '+'], '-', $family));

        /**
         * Add fonts to the request's $_GET 'family' parameter. Then pass an array to 'omgf_alternate_fonts' 
         * filter. Then pass an alternate API url to the 'omgf_alternate_api_url' filter to fetch fonts from 
         * an alternate API.
         */
        $alternate_fonts = apply_filters('omgf_alternate_fonts', []);

        if (in_array($family, array_keys($alternate_fonts))) {
            $url = apply_filters('omgf_alternate_api_url', $url);
            unset($query);
        }

        $query_string = '';

        if ($query) {
            $query_string = '?' . http_build_query($query);
        }

        /**
         * If a font changed names recently, map their old name to the new name, before triggering the API request.
         */
        if (in_array($family, array_keys(self::OMGF_RENAMED_GOOGLE_FONTS))) {
            $family = self::OMGF_RENAMED_GOOGLE_FONTS[$family];
        }

        $response = wp_remote_get(
            sprintf($url . '%s', $family) . $query_string
        );

        if (is_wp_error($response)) {
            OMGF_Admin_Notice::set_notice(sprintf(__('An error occurred while trying to fetch fonts: %s', $this->plugin_text_domain), $response->get_error_message()), $response->get_error_code(), true, 'error', 500);
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code != 200) {
            $font_family   = str_replace('-', ' ', $family);
            $error_body    = wp_remote_retrieve_body($response);
            $error_message = wp_remote_retrieve_response_message($response);
            $message       = sprintf(__('<strong>%s</strong> could not be found. The API returned the following error: %s.', $this->plugin_text_domain), ucwords($font_family), $error_message);

            OMGF_Admin_Notice::set_notice($message, 'omgf_api_error', false, 'error');

            if ($error_message == 'Service Unavailable') {
                $message = __('Google Fonts Helper API is currently unavailable. Try again later.', $this->plugin_text_domain);

                OMGF_Admin_Notice::set_notice($message, 'omgf_api_error', true, 'error', $response_code);
            }

            if ($error_body == 'Not found') {
                $message = sprintf(__('Please verify that %s is available for free at Google Fonts by doing <a href="%s" target="_blank">a manual search</a>. Maybe it\'s a Premium font?', $this->plugin_text_domain), ucwords($font_family), 'https://fonts.google.com/?query=' . str_replace('-', '+', $family));

                OMGF_Admin_Notice::set_notice($message, 'omgf_api_info_not_found', false, 'info');
            }

            if ($error_body == 'Internal Server Error') {
                $message = sprintf(__('Try using the Force Subsets option (available in OMGF Pro) to force loading %s in a subset in which it\'s actually available. Use the Language filter <a href="%s" target="_blank">here</a> to verify which subsets are available for %s.', $this->plugin_text_domain), ucwords($font_family), 'https://fonts.google.com/?query=' . str_replace('-', '+', $family), ucwords($font_family));

                OMGF_Admin_Notice::set_notice($message, 'omgf_api_info_internal_server_error', false, 'info');
            }

            return [];
        }

        return json_decode(wp_remote_retrieve_body($response));
    }

    /**
     * Because the regular Google Webfonts Helper API tends to go offline sometimes, this function allows us
     * to use fallback services.
     * 
     * TODO: Setup own mirror.
     * 
     * @return string Will return regular API url if fallback API url fails, too. Error handling later on will display a
     *                proper message to the user.
     */
    private function get_working_service_url()
    {
        /**
         * If this transient returns true, then that means that the regular API has failed in the last 24 hours.
         */
        if (get_transient(self::OMGF_USE_FALLBACK_API_TRANSIENT)) {
            return self::OMGF_GOOGLE_FONTS_API_FALLBACK;
        }

        $url      = self::OMGF_GOOGLE_FONTS_API_URL;
        $response = wp_remote_get($url);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            set_transient(self::OMGF_USE_FALLBACK_API_TRANSIENT, true, DAY_IN_SECONDS);

            $response = wp_remote_get(self::OMGF_GOOGLE_FONTS_API_FALLBACK);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                $url = self::OMGF_GOOGLE_FONTS_API_FALLBACK;
            }
        }

        return $url;
    }

    /**
     * @param $font_families
     * @param $font
     *
     * @return mixed
     */
    private function build_fonts_request($font_families, $font)
    {
        $font_request = array_filter(
            $font_families,
            function ($value) use ($font) {
                if (isset($font->early_access)) {
                    return strpos($value, strtolower(str_replace(' ', '', $font->family))) !== false;
                }

                return strpos($value, $font->family) !== false;
            }
        );

        return reset($font_request);
    }

    /**
     * @param $request
     * @param $font
     *
     * @return array
     */
    private function parse_requested_variants($request, $font)
    {
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
     * @param mixed $font_id 
     * @param mixed $available 
     * @param mixed $wanted 
     * @param mixed $stylesheet_handle 
     * @return mixed 
     */
    private function process_unload_queue($font_id, $available, $wanted, $stylesheet_handle)
    {
        if (strpos($wanted, ':') !== false) {
            // We don't need the first variable.
            list(, $variants) = explode(':', $wanted);
        } else {
            $variants = '';
        }

        /**
         * Build array and filter out empty elements.
         */
        $variants = array_filter(explode(',', $variants));

        /**
         * If $variants is empty and this is the first run, i.e. there are no unloaded fonts (yet)
         * return all available variants.
         */
        if (empty($variants) && !isset(OMGF::unloaded_fonts()[$stylesheet_handle][$font_id])) {
            return $available;
        }

        return array_filter(
            $available,
            function ($font) use ($variants) {
                $id = $font->id;

                if ($id == 'regular' || $id == '400') {
                    return in_array('400', $variants) || in_array('regular', $variants);
                }

                if ($id == 'italic') {
                    return in_array('400italic', $variants) || in_array('italic', $variants);
                }

                return in_array($id, $variants);
            }
        );
    }

    /**
     * When unload is used, insert the cache key for the variants still in use.
     *
     * @param $stylesheets
     * @param $current_font
     *
     * @return mixed
     */
    private function rewrite_variants($stylesheets, $current_font)
    {
        foreach ($stylesheets as $stylesheet => &$fonts) {
            foreach ($fonts as $index => &$font) {
                if (empty((array) $font->variants)) {
                    continue;
                }

                foreach ($font->variants as $variant_index => &$variant) {
                    $replace_variant = $current_font[$stylesheet][$index]->variants[$variant_index] ?? (object) [];

                    if (!empty((array) $replace_variant)) {
                        $variant = $replace_variant;
                    }
                }
            }
        }

        return $stylesheets;
    }
}
