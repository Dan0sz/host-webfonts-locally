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

class OMGF_Admin_Settings extends OMGF_Admin
{
    const OMGF_SETTINGS_FIELD_ADVANCED        = 'omgf-advanced-settings';
    const OMGF_FONT_DISPLAY_OPTIONS           = array(
        'Auto (default)' => 'auto',
        'Block'          => 'block',
        'Swap'           => 'swap',
        'Fallback'       => 'fallback',
        'Optional'       => 'optional'
    );
    const OMGF_SETTING_AUTO_DETECTION_ENABLED = 'omgf_auto_detection_enabled';
    const OMGF_SETTING_SUBSETS                = 'omgf_subsets';
    const OMGF_SETTING_FONTS                  = 'omgf_fonts';
    const OMGF_SETTING_DETECTED_FONTS         = 'omgf_detected_fonts';
    const OMGF_SETTING_CACHE_PATH             = 'omgf_cache_dir';
    const OMGF_SETTING_CACHE_URI              = 'omgf_cache_uri';
    const OMGF_SETTING_CDN_URL                = 'omgf_cdn_url';
    const OMGF_SETTING_WEB_FONT_LOADER        = 'omgf_web_font_loader';
    const OMGF_SETTING_REMOVE_VERSION         = 'omgf_remove_version';
    const OMGF_SETTING_DISPLAY_OPTION         = 'omgf_display_option';
    const OMGF_SETTING_REMOVE_GOOGLE_FONTS    = 'omgf_remove_gfonts';
    const OMGF_SETTING_ENABLE_PRELOAD         = 'omgf_preload';
    const OMGF_SETTING_DB_VERSION             = 'omgf_db_version';
    const OMGF_SETTING_UNINSTALL              = 'omgf_uninstall';
    const OMGF_SETTING_ENQUEUE_ORDER          = 'omgf_enqueue_order';
    const OMGF_SETTING_RELATIVE_URL           = 'omgf_relative_url';

    private $active_tab;

    /**
     * OMGF_Admin_Settings constructor.
     */
    public function __construct()
    {
        // @formatter:off
        add_action('admin_menu', array($this, 'create_menu'));

        $caosLink = plugin_basename(OMGF_PLUGIN_FILE);

        add_filter("plugin_action_links_$caosLink", array($this, 'create_settings_link'));
        add_filter('whitelist_options', [$this, 'remove_settings_from_whitelist'], 100);
        // @formatter:on

        // TODO: implement tab param to follow WordPress conventions.
        $this->active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'generate-stylesheet';

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

            <p>
                <?= get_plugin_data(OMGF_PLUGIN_FILE)['Description']; ?>
            </p>

            <div class="settings-column left">
                <h2 class="omgf-nav nav-tab-wrapper">
                    <a class="nav-tab generate-stylesheet dashicons-before dashicons-admin-appearance nav-tab-active"><?php _e('Generate Stylesheet', 'host-webfonts-local'); ?></a>
                    <a class="nav-tab advanced-settings dashicons-before dashicons-admin-settings"><?php _e('Advanced Settings', 'host-webfonts-local'); ?></a>
                </h2>

                <form id="omgf-generate-stylesheet-form" name="omgf-generate-stylesheet-form" style="display: block;">
                    <?php
                    $this->get_template('generate-stylesheet');
                    ?>
                </form>

                <form id="omgf-advanced-settings-form" name="omgf-settings-form" method="post" action="options.php" style="display: none;">
                    <?php
                    settings_fields(OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_ADVANCED);
                    do_settings_sections(OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_ADVANCED);

                    $this->get_template('advanced-settings');

                    do_action('omgf_after_settings_form_settings');

                    submit_button();
                    ?>
                </form>
            </div>

            <div class="settings-column right">
                <div id="omgf-welcome-panel" class="welcome-panel">
                    <?php $this->get_template('welcome'); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Register all settings.
     *
     * @throws ReflectionException
     */
    public function register_settings()
    {
        foreach ($this->get_settings() as $constant => $value)
        {
            register_setting(
                OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_ADVANCED,
                $value
            );
        }
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

    /**
     * Preserve fonts and subsets when saving changes.
     *
     * @param $options
     *
     * @return array
     */
    public function remove_settings_from_whitelist($options)
    {
        if (!isset($options[self::OMGF_SETTINGS_FIELD_ADVANCED])) {
            return $options;
        }

        foreach ($options[self::OMGF_SETTINGS_FIELD_ADVANCED] as $key => &$setting) {
            if ($setting == self::OMGF_SETTING_FONTS || $setting == self::OMGF_SETTING_SUBSETS) {
                unset($options[self::OMGF_SETTINGS_FIELD_ADVANCED][$key]);
            }
        }

        return $options;
    }

    /**
     * Get all settings using the constants in this class.
     *
     * @return array
     * @throws ReflectionException
     */
    public function get_settings()
    {
        $reflection     = new ReflectionClass($this);
        $constants      = $reflection->getConstants();

        return array_filter(
            $constants,
            function ($key) {
                return strpos($key, 'OMGF_SETTING') !== false;
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
