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
    /** @var array $detected_fonts */
    private $detected_fonts = [];

    /** @var OMGF_API $api */
    private $api;

    /**
     * OMGF_Admin_AutoDetect constructor.
     *
     * @param $detected_fonts
     */
    public function __construct(
        $detected_fonts
    ) {
        $this->detected_fonts = $detected_fonts;
        $this->api = new OMGF_API();

        $this->init();
    }

    /**
     * Initialize Auto Detect.
     */
    private function init()
    {
        $font_properties = $this->extract_font_properties($this->detected_fonts);

        $fonts = $this->build_subsets_array($font_properties);

        // Fetch available font styles from API, except for default WordPress Admin fonts.
        foreach ($fonts as $index => &$font) {
            if (($index == 0 && $font['subset_font'] == 'open-sans') || ($index == 1 && $font['subset_font'] == 'noto-serif')) {
                unset($fonts[$index]);

                continue;
            }

            $font_styles[$font['subset_font']] = $this->api->get_font_styles($font['subset_font'], implode(',', $font['selected_subsets']));
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS, $fonts);

        // Match used styles with available styles.
        foreach ($fonts as $subset) {
            $used_styles[] = $this->process_used_styles($subset['used_styles'], $font_styles[$subset['subset_font']]);
        }

        if (isset($used_styles)) {
            $detected_fonts = array_merge(...$used_styles);

            update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $detected_fonts);
        }

        /** It only needs to run once. */
        delete_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED);
        delete_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS);

        if (empty($fonts)) {
            OMGF_Admin_Notice::set_notice(__('Auto-detection completed successfully, but no Google Fonts were found.', 'host-webfonts-local'), false, 'warning');

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
        foreach ($font_properties as $properties) {
            $parts   = explode(':', $properties['family']);
            $subsets = isset($properties['subset']) ? explode(',', $properties['subset']) : null;

            if (!empty($parts)) {
                $font_family = $parts[0];
                $styles      = explode(',', $parts[1]);
            }

            $fonts[] = [
                'subset_family'     => $font_family,
                'subset_font'       => str_replace(' ', '-', strtolower($font_family)),
                'available_subsets' => $subsets ?? [ 'latin' ],
                'selected_subsets'  => $subsets ?? [ 'latin' ],
                'used_styles'       => $styles
            ];
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
