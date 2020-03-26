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
    /**
     * Settings Fields
     */
    const OMGF_SETTINGS_FIELD_ADVANCED        = 'omgf-advanced-settings';

    /**
     * Option Values
     */
    const OMGF_FONT_DISPLAY_OPTIONS           = array(
        'Auto (default)' => 'auto',
        'Block'          => 'block',
        'Swap'           => 'swap',
        'Fallback'       => 'fallback',
        'Optional'       => 'optional'
    );

    /**
     * Generate Stylesheet
     */
    const OMGF_SETTING_DB_VERSION              = 'omgf_db_version';
    const OMGF_SETTING_AUTO_DETECTION_ENABLED  = 'omgf_auto_detection_enabled';
    const OMGF_SETTING_DETECTED_FONTS          = 'omgf_detected_fonts';
    const OMGF_SETTING_SUBSETS                 = 'omgf_subsets';
    const OMGF_SETTING_FONTS                   = 'omgf_fonts';

    /**
     * Advanced Settings
     */
    const OMGF_ADV_SETTING_CACHE_PATH          = 'omgf_cache_dir';
    const OMGF_ADV_SETTING_CACHE_URI           = 'omgf_cache_uri';
    const OMGF_ADV_SETTING_CDN_URL             = 'omgf_cdn_url';
    const OMGF_ADV_SETTING_WEB_FONT_LOADER     = 'omgf_web_font_loader';
    const OMGF_ADV_SETTING_REMOVE_VERSION      = 'omgf_remove_version';
    const OMGF_ADV_SETTING_DISPLAY_OPTION      = 'omgf_display_option';
    const OMGF_ADV_SETTING_REMOVE_GOOGLE_FONTS = 'omgf_remove_gfonts';
    const OMGF_ADV_SETTING_ENABLE_PRELOAD      = 'omgf_preload';
    const OMGF_ADV_SETTING_UNINSTALL           = 'omgf_uninstall';
    const OMGF_ADV_SETTING_ENQUEUE_ORDER       = 'omgf_enqueue_order';
    const OMGF_ADV_SETTING_RELATIVE_URL        = 'omgf_relative_url';

    /** @var string $active_tab */
    private $active_tab;

    /**
     * OMGF_Admin_Settings constructor.
     */
    public function __construct()
    {
        $this->active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'generate-stylesheet';

        // @formatter:off
        add_action('admin_menu', [$this, 'create_menu']);
        add_action('omgf_settings_tab', [$this, 'generate_stylesheet_tab'], 1);
        add_action('omgf_settings_tab', [$this, 'advanced_settings_tab'], 2);
        add_action('omgf_settings_content', [$this, 'generate_stylesheet_content'], 1);
        add_action('omgf_settings_content', [$this, 'advanced_settings_content'], 2);
        add_filter('plugin_action_links_' . plugin_basename(OMGF_PLUGIN_FILE), [$this, 'create_settings_link']);
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
            wp_die(__("You're not cool enough to access this page.", 'host-webfonts-local'));
        }
        ?>
        <div class="wrap">
            <h1><?php _e('OMGF | Optimize My Google Fonts', 'host-webfonts-local'); ?></h1>

            <p>
                <?= get_plugin_data(OMGF_PLUGIN_FILE)['Description']; ?>
            </p>

            <div class="settings-column left">
                <h2 class="omgf-nav nav-tab-wrapper">
                    <?php do_action('omgf_settings_tab'); ?>
                </h2>

                <?php do_action('omgf_settings_content'); ?>
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
                return strpos($key, 'OMGF_ADV_SETTING') !== false;
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Add Generate Stylesheet Tab to Settings Screen.
     */
    public function generate_stylesheet_tab()
    {
        $this->generate_tab('generate-stylesheet', 'dashicons-admin-appearance', __('Generate Stylesheet', 'host-webfonts-local'));
    }

    /**
     * Add Advanced Settings Tab to Settings Screen.
     */
    public function advanced_settings_tab()
    {
        $this->generate_tab('advanced-settings', 'dashicons-admin-settings', __('Advanced Settings', 'host-webfonts-local'));
    }

    /**
     * @param      $id
     * @param null $icon
     * @param null $label
     */
    private function generate_tab($id, $icon = null, $label = null)
    {
        ?>
        <a class="nav-tab dashicons-before <?= $icon; ?> <?= $this->active_tab == $id ? 'nav-tab-active' : ''; ?>" href="<?= $this->generate_tab_link($id);?>">
            <?= $label; ?>
        </a>
        <?php
    }

    /**
     * @param $tab
     *
     * @return string
     */
    private function generate_tab_link($tab)
    {
        return admin_url("options-general.php?page=optimize-webfonts&tab=$tab");
    }

    /**
     * Render Generate Stylesheet
     */
    public function generate_stylesheet_content()
    {
        if ($this->active_tab != 'generate-stylesheet') {
            return;
        }
        ?>
        <form id="omgf-generate-stylesheet-form" name="omgf-generate-stylesheet-form">
            <?php
            $this->get_template('generate-stylesheet');
            ?>
        </form>
        <?php
    }

    /**
     * Render Advanced Settings
     */
    public function advanced_settings_content()
    {
        if ($this->active_tab != 'advanced-settings') {
            return;
        }
        ?>
        <form id="omgf-advanced-settings-form" name="omgf-settings-form" method="post" action="options.php">
            <?php
            settings_fields(OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_ADVANCED);
            do_settings_sections(OMGF_Admin_Settings::OMGF_SETTINGS_FIELD_ADVANCED);

            $this->get_template('advanced-settings');

            do_action('omgf_after_settings_form_settings');

            submit_button();
            ?>
        </form>
        <?php
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
