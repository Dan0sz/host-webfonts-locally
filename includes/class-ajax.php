<?php
/**
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 */

class OMGF_AJAX
{
    /**
     * @param $code
     * @param $message
     */
    protected function throw_error($code, $message)
    {
        wp_send_json_error(__($message, 'host-webfonts-local'), (int) $code);
    }
}

new OMGF_AJAX();
