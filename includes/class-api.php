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
        $request    = wp_remote_get(OMGF_HELPER_URL . $query)['body'];

        if ($request == 'Not found') {
            return [];
        }

        $result     = json_decode($request);

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
        $request          = wp_remote_get(OMGF_HELPER_URL . $font_family . '?subsets=' . $selected_subsets);
        $result           = json_decode($request['body']);

        foreach ($result->variants as $variant) {
            $fonts[] = [
                'font_id'     => $result->id . '-' . $variant->id,
                'font_family' => $variant->fontFamily,
                'font_weight' => $variant->fontWeight,
                'font_style'  => $variant->fontStyle,
                'local'       => implode($variant->local, ','),
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
}
