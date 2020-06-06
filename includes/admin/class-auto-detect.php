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
     * Run Auto Detect.
     */
    private function init()
    {
        $font_properties = $this->extract_font_properties($this->detected_fonts);

        $subsets = $this->build_subsets_array($font_properties);

        foreach ($subsets as $index => &$font) {
            if (!is_numeric($index)) {
                continue;
            }

            $font_styles[$font['subset_font']] = $this->api->get_font_styles($font['subset_font'], implode(',', $font['selected_subsets']));

            // If subset was already detected, replace styles instead of overwriting them.
            if (isset($subsets[$font['subset_font']])) {
                $subsets[$font['subset_font']] = array_replace_recursive($subsets[$font['subset_font']], $font);
            } else {
                $subsets[$font['subset_font']] = $font;
            }

            unset($subsets[$index]);
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_SUBSETS, $subsets);

        // Match used styles with available styles.
        foreach ($subsets as $subset) {
            $used_styles[] = $this->process_used_styles($subset['used_styles'], $font_styles[$subset['subset_font']]);
        }

        if (isset($used_styles)) {
            $detected_font_styles = array_merge(...$used_styles);

            update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $detected_font_styles);
        }

        /** It only needs to run once. */
        delete_option(OMGF_Admin_Settings::OMGF_SETTING_AUTO_DETECTION_ENABLED);
        delete_option(OMGF_Admin_Settings::OMGF_SETTING_DETECTED_FONTS);

        if (empty($subsets)) {
            OMGF_Admin_Notice::set_notice(__('Auto Detect completed successfully, but no Google Fonts were found.', 'host-webfonts-local'), false, 'warning');

            OMGF_Admin_Notice::set_notice(sprintf(__('Your theme and/or plugins are using unconventional methods (or Web Font Loader) to load Google Fonts. <strong>Upgrade to OMGF Pro</strong> (<em>starting at € 39, -</em>) to automatically detect and replace Google Fonts for your theme and plugins. <a href="%s" target="_blank">Purchase OMGF Pro</a>.', 'host-webfonts-local'), 'https://woosh.dev/wordpress-plugins/host-google-fonts-pro'), false, 'info');
        } else {
            $count_fonts   = count($subsets);
            $count_subsets = 0;
            foreach ($subsets as $subset) {
                $count_subsets += count($subset['available_subsets']);
            }
            $count_font_styles = count($detected_font_styles);

            OMGF_Admin_Notice::set_notice(__("Auto Detect found $count_fonts fonts in $count_subsets different subsets and $count_font_styles font styles. Please check the results and proceed to download the fonts and generate the stylesheet.", 'host-webfonts-local'), false);
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

            /**
             * Skip over default Admin WordPress fonts; Noto Serif and Open Sans, which are always detected first (as far as I know.)
             * We check on iteration value, to make sure these fonts are still returned if they are also used in the theme.
             */
            if (($i == 0 && strpos($parts['query'], 'Open+Sans') !== false) || ($i == 1 && strpos($parts['query'], 'Noto+Serif') !== false)) {
                $i++;
                continue;
            }

            parse_str($parts['query'], $font_properties[$i]);

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
     * @param $used_styles
     * @param $available_styles
     *
     * @return array
     */
    private function process_used_styles($used_styles, $available_styles)
    {
        foreach ($used_styles as &$style) {
            if (empty($style)) {
                $used_styles = $this->process_available_styles($available_styles);

                break;
            }

            $fontWeight = preg_replace('/[^0-9]/', '', $style);
            $fontStyle  = preg_replace('/[^a-zA-Z]/', '', $style);

            if ($fontStyle == 'i') {
                $fontStyle = 'italic';
            }

            $style = $fontWeight . $fontStyle;
        }

        return array_filter(
            $available_styles,
            function ($style) use ($used_styles) {
                $fontStyle = $style['font_weight'] . ($style['font_style'] !== 'normal' ? $style['font_style'] : '');

                return in_array($fontStyle, $used_styles);
            }
        );
    }

    /**
     * Some themes requests font families without specifying font styles. While this is inadvisable, OMGF should be able
     * to deal with this. That's why, when no font styles are detected, all available font styles are returned.
     *
     * @param array $styles
     *
     * @return array
     */
    private function process_available_styles(array $styles)
    {
        foreach ($styles as $style) {
            $font_style = $style['font_style'] !== 'normal' ? $style['font_style'] : '';

            $used_styles[] = $style['font_weight'] . $font_style;
        }

        return $used_styles;
    }
}
