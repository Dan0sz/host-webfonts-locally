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

class OMGF_Admin_Settings extends OMGF_Admin
{
    const OMGF_FONT_DISPLAY_OPTIONS           = array(
        'Auto (default)' => 'auto',
        'Block'          => 'block',
        'Swap'           => 'swap',
        'Fallback'       => 'fallback',
        'Optional'       => 'optional'
    );
    const OMGF_SETTING_AUTO_DETECTION_ENABLED = 'omgf_auto_detection_enabled';
    const OMGF_SETTING_DETECTED_FONTS         = 'omgf_detected_fonts';
    const OMGF_SETTING_CACHE_DIR              = 'caos_webfonts_cache_dir';
    const OMGF_SETTING_CDN_URL                = 'caos_webfonts_cdn_url';
    const OMGF_SETTING_WEB_FONT_LOADER        = 'omgf_web_font_loader';
    const OMGF_SETTING_REMOVE_VERSION         = 'caos_webfonts_remove_version';
    const OMGF_SETTING_DISPLAY_OPTION         = 'caos_webfonts_display_option';
    const OMGF_SETTING_REMOVE_GOOGLE_FONTS    = 'caos_webfonts_remove_gfonts';
    const OMGF_SETTING_ENABLE_PRELOAD         = 'caos_webfonts_preload';
    const OMGF_SETTING_DB_VERSION             = 'caos_webfonts_db_version';
    const OMGF_SETTING_UNINSTALL              = 'omgf_uninstall';

    /**
     * OMGF_Admin_Settings constructor.
     */
    public function __construct()
    {
        // @formatter:off
        add_action('admin_menu', array($this, 'create_menu'));

        $caosLink = plugin_basename(OMGF_PLUGIN_FILE);

        add_filter("plugin_action_links_$caosLink", array($this, 'create_settings_link'));
        // @formatter:on

        parent::__construct();
    }

    /**
     * Creates the menu item.
     */
    public function create_menu()
    {
        add_options_page(
            'OMGF',
            'Optimize Webfonts',
            'manage_options',
            'optimize-webfonts',
            array(
                $this,
                'create_settings_page'
            )
        );
        // @formatter:off
        add_action('admin_init', array($this, 'register_settings'));
        // @formatter:on
    }

    /**
     * Display the settings page.
     */
    public function create_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__("You're not cool enough to access this page."));
        }
        ?>
        <div class="wrap">
            <h1><?php _e('OMGF | Optimize My Google Fonts', 'host-webfonts-local'); ?></h1>

            <div id="hwl-admin-notices"></div>

            <?php $this->get_template('welcome'); ?>

            <form id="hwl-options-form" class="settings-column left" name="hwl-options-form" method="post">
                <div class="">
                    <?php $this->get_template('generate-stylesheet'); ?>
                </div>
            </form>

            <form id="hwl-settings-form" class="settings-column right" name="hwl-settings-form" method="post" action="options.php">
                <?php
                settings_fields('caos-webfonts-basic-settings');
                do_settings_sections('caos-webfonts-basic-settings');

                $this->get_template('basic-settings');

                do_action('hwl_after_settings_form_settings');

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register our settings.
     */
    public function register_settings()
    {
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_AUTO_DETECTION_ENABLED
        );
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_DETECTED_FONTS
        );
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_CACHE_DIR
        );
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_CDN_URL
        );
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_WEB_FONT_LOADER
        );
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_REMOVE_VERSION
        );
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_DISPLAY_OPTION
        );
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_REMOVE_GOOGLE_FONTS
        );
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_ENABLE_PRELOAD
        );
        register_setting(
            'caos-webfonts-basic-settings',
            self::OMGF_SETTING_UNINSTALL
        );
    }

    /**
     * Adds the 'settings' link to the Plugin overview.
     *
     * @return mixed
     */
    public function create_settings_link($links)
    {
        $adminUrl     = admin_url() . 'options-general.php?page=optimize-webfonts';
        $settingsLink = "<a href='$adminUrl'>" . __('Settings') . "</a>";
        array_push($links, $settingsLink);

        return $links;
    }
}
