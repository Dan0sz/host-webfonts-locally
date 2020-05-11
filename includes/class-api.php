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

class OMGF_API
{
    /**
     * @param $query
     *
     * @return array
     */
    public function get_subsets($query)
    {
        $response = wp_remote_get(OMGF_HELPER_URL . $query);

        if (wp_remote_retrieve_response_code($response) != 200) {
            $this->throw_error($response, $query);

            return [];
        }

        $result = json_decode(wp_remote_retrieve_body($response));

        return [
            'subset_family'     => $result->family,
            'subset_font'       => $result->id,
            'available_subsets' => $result->subsets,
            'selected_subsets'  => []
        ];
    }

    /**
     * @param $font_family
     * @param $selected_subsets
     *
     * @return array
     */
    public function get_font_styles($font_family, $selected_subsets)
    {
        if (empty($font_family)) {
            return [];
        }

        $response = wp_remote_get(OMGF_HELPER_URL . $font_family . '?subsets=' . $selected_subsets);

        if (wp_remote_retrieve_response_code($response) != 200) {
            $this->throw_error($response, $font_family);

            return [];
        }

        $result = json_decode(wp_remote_retrieve_body($response));

        foreach ($result->variants as $variant) {
            $fonts[] = [
                'font_id'     => $result->id . '-' . $variant->id,
                'font_family' => $variant->fontFamily,
                'font_weight' => $variant->fontWeight,
                'font_style'  => $variant->fontStyle,
                'local'       => implode(',', $variant->local ?? []),
                'preload'     => 0,
                'downloaded'  => 0,
                'url_ttf'     => $variant->ttf,
                'url_woff'    => $variant->woff,
                'url_woff2'   => $variant->woff2,
                'url_eot'     => $variant->eot
            ];
        }

        return $fonts;
    }

    /**
     * Throw error based on response
     *
     * @param $response
     * @param $query
     */
    private function throw_error($response, $query)
    {
        $message = wp_remote_retrieve_response_message($response);
        $code    = wp_remote_retrieve_response_code($response);
        OMGF_Admin_Notice::set_notice(sprintf(__('An error occurred while searching for %s: %s', 'host-webfonts-local'), $query, $message), false, 'error', $code);
    }
}
