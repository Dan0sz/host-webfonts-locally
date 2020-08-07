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

class OMGF_Admin_Settings_Extensions extends OMGF_Admin_Settings_Builder
{
    /**
     * OMGF_Admin_Settings_Advanced constructor.
     */
    public function __construct()
    {
        $this->title = __('Extensions', $this->plugin_text_domain);

        // Open
        // @formatter:off
        add_filter('omgf_extensions_settings_content', [$this, 'do_title'], 10);
        add_filter('omgf_extensions_settings_content', [$this, 'do_description'], 15);
        add_filter('omgf_extensions_settings_content', [$this, 'do_before'], 20);

        // Settings

        // Close
        add_filter('omgf_extensions_settings_content', [$this, 'do_after'], 100);
        // @formatter:on
    }

    /**
     * Description
     */
    public function do_description()
    {
        ?>
        <p>
            <?php _e('* <strong>Generate stylesheet</strong> after changing this setting.', $this->plugin_text_domain); ?>
            <br/>
            <?php _e('** <strong>Download Fonts</strong> and <strong>Generate Stylesheet</strong> after changing this setting.', $this->plugin_text_domain); ?>
        </p>
        <?php
    }
}
