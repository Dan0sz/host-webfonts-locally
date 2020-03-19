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
 * @copyright: (c) 2020 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Admin_AutoDetect
{
    private $detected_fonts = [];

    private $api;

    public function __construct(
        $detected_fonts
    ) {
        $this->detected_fonts = $detected_fonts;
        $this->api = new OMGF_API();

        $this->init();
    }

    private function init()
    {
        $font_properties = $this->extract_font_properties($this->detected_fonts);

        $fonts = $this->build_subsets_array($font_properties);

        foreach ($fonts['subsets'] as $subset) {
            $subsets[] = [
                'subset_family'     => $subset['family'],
                'subset_font'       => $subset['id'],
                'available_subsets' => $subset['subsets'] ?? [ 'latin' ],
                'selected_subsets'  => $subset['subsets'] ?? [ 'latin' ]
            ];
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS, $subsets);

        foreach ($subsets as $subset) {
            $font_styles[] = $this->api->get_font_styles($subset['subset_font'], implode(',', $subset['selected_subsets']));
        }

        foreach ($fonts['subsets'] as $index => $subset) {
            $used_styles[] = $this->process_used_styles($subset['used_styles'], $font_styles[$index]);
        }

        $detected_fonts = array_merge(...$used_styles);

        update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $detected_fonts);

        /** It only needs to run once. */
        delete_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED);
        delete_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS);

        if (count($fonts['subsets']) <= 2) {
            OMGF_Admin_Notice::set_notice(__('Auto-detection completed successfully, but no Google Fonts were found besides WordPress\' default fonts. You can safely uncheck these if your theme doesn\'t use them. They will not be loaded in the frontend of your website.', 'host-webfonts-local'), false);

            OMGF_Admin_Notice::set_notice(sprintf(__('Your theme (or plugin) might be using unconventional methods (or Web Font Loader) to load Google Fonts. For a custom integration to load your Google Fonts locally, <a href="%s" target="_blank">hire me</a> or <a href="%s" target="_blank">contact me</a> when in doubt.', 'host-webfonts-local'), 'https://woosh.dev/wordpress-services/omgf-expert-configuration/', OMGF_SITE_URL . '/contact'), false, 'info');
        } else {
            OMGF_Admin_Notice::set_notice(__('Auto-detection completed. Please check the results and proceed to download the fonts and generate the stylesheet.', 'host-webfonts-local'), false);
        }
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

        return $fonts;
    }

    /**
     * @param $usedStyles
     * @param $availableStyles
     *
     * @return array
     */
    private function process_used_styles($usedStyles, $availableStyles)
    {
        foreach ($usedStyles as &$style) {
            $fontWeight = preg_replace('/[^0-9]/', '', $style);
            $fontStyle  = preg_replace('/[^a-zA-Z]/', '', $style);

            if ($fontStyle == 'i') {
                $fontStyle = 'italic';
            }

            $style = $fontWeight . $fontStyle;
        }

        return array_filter(
            $availableStyles,
            function ($style) use ($usedStyles) {
                $fontStyle = $style['font_weight'] . ($style['font_style'] !== 'normal' ? $style['font_style'] : '');

                return in_array($fontStyle, $usedStyles);
            }
        );
    }
}
