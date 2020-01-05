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
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_AJAX_Detect
{
    public function __construct(
        $fonts
    ) {
        $this->save_detected_fonts($fonts);
    }

    /**
     *
     */
    public function save_detected_fonts($used_fonts)
    {
        $font_properties = $this->extract_font_properties($used_fonts);

        $fonts = $this->build_subsets_array($font_properties);

        /** It only needs to run once. */
        update_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED, false);

        wp_send_json_success($fonts);
    }

    /**
     * @param $fontSource
     *
     * @return array
     */
    private function extract_font_properties($fontSource)
    {
        $font_properties = array();

        $i = 0;

        foreach ($fontSource as $source) {
            $parts = parse_url($source);

            parse_str($parts['query'], $font_properties[]);

            /**
             * Some themes (like Twenty Sixteen) do chained requests using a pipe (|).
             * This function explodes these requests and adds them to the query.
             */
            if (strpos($font_properties[$i]['family'], '|') !== false) {
                $parts_parts = explode('|', $font_properties[$i]['family']);
                $font_property_subset = isset($font_properties[$i]['subset']) ? $font_properties[$i]['subset'] : 'latin';

                foreach ($parts_parts as $part) {
                    $font_properties[$i]['family'] = $part;
                    $font_properties[$i]['subset'] = $font_property_subset;
                    $i++;
                }
            }

            $i++;
        }

        return $font_properties;
    }

    /**
     * @param $font_properties
     *
     * @return array
     */
    private function build_subsets_array($font_properties)
    {
        $i = 0;

        foreach ($font_properties as $properties) {
            $parts   = explode(':', $properties['family']);
            $subsets = isset($properties['subset']) ? explode(',', $properties['subset']) : null;

            if (!empty($parts)) {
                $font_family = $parts[0];
                $styles      = explode(',', $parts[1]);
            }

            $fonts['subsets'][$i]['family']      = $font_family;
            $fonts['subsets'][$i]['id']          = str_replace(' ', '-', strtolower($font_family));
            $fonts['subsets'][$i]['subsets']     = $subsets;
            $fonts['subsets'][$i]['used_styles'] = $styles;

            $i++;
        }

        $fonts['auto-detect'] = true;

        return $fonts;
    }
}
