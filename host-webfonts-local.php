<?php
/**
 * @formatter:off
 * Plugin Name: OMGF
 * Plugin URI: https://daan.dev/wordpress-plugins/host-google-fonts-locally
 * Description: Minimize DNS requests and leverage browser cache by easily saving Google Fonts to your server and removing the external Google Fonts.
 * Version: 3.4.3
 * Author: Daan van den Bergh
 * Author URI: https://daan.dev
 * License: GPL2v2 or later
 * Text Domain: host-webfonts-local
 * @formatter:on
 */

defined('ABSPATH') || exit;

/**
 * Define constants.
 */
define('OMGF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OMGF_PLUGIN_FILE', __FILE__);
define('OMGF_DB_VERSION', '3.0.0'); // Legacy
define('OMGF_STATIC_VERSION', '3.4.0');
define('OMGF_WEB_FONT_LOADER_VERSION', '1.6.26');
define('OMGF_CACHE_PATH', esc_attr(get_option('omgf_cache_dir')) ?: '/cache/omgf-webfonts');

/**
 * These cache plugins empty the entire cache-folder, instead of just removing
 * its own files.
 */
define('OMGF_EVIL_PLUGINS', [
    'WP Super Cache' => 'wp-super-cache/wp-cache.php'
]);

function check_cache_plugins()
{
    if (!is_admin()) {
        return;
    }

    if (strpos(OMGF_CACHE_PATH, '/cache/') === false) {
        return;
    }

    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    $cache_path = OMGF_CACHE_PATH;
    $admin_url  = admin_url('options-general.php?page=optimize-webfonts&tab=advanced-settings');

    foreach (OMGF_EVIL_PLUGINS as $name => $basename) {
        if (is_plugin_active($basename)): ?>
            <div id="message" class="notice notice-warning is-dismissible">
                <p>
                    <?= sprintf(__("It looks like <strong>you're using %s</strong>. This plugin empties the entire <code>wp-content/cache</code> folder after a cache flush.", 'host-webfonts-local'), $name); ?>
                </p>
                <p>
                    <?= sprintf(__("To prevent this, <strong>move OMGF's fonts</strong> by changing the <em>Save font files to...</em> option under OMGF's <a href='%s'>Advanced Settings</a> from <code>%s</code> to something else, e.g. <code>/omgf-cache</code>."), $admin_url, $cache_path); ?>
                </p>
            </div>
        <?php endif;
    }
}

add_action('admin_notices', 'check_cache_plugins');

/**
 * Takes care of loading classes on demand.
 *
 * @param $class
 *
 * @return mixed|void
 */
function omgf_autoload($class)
{
    $path = explode('_', $class);

    if ($path[0] != 'OMGF') {
        return;
    }

    $filename = '';

    if (count($path) == 1) {
        $filename = 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';
    } elseif (count($path) == 2) {
        array_shift($path);
        $filename = 'class-' . strtolower($path[0]) . '.php';
    } else {
        array_shift($path);
        end($path);
        $i = 0;

        while ($i < key($path)) {
            $filename .= strtolower($path[$i]) . '/';
            $i++;
        }

        $pieces = preg_split('/(?=[A-Z])/', lcfirst($path[$i]));

        $filename .= 'class-' . strtolower(implode('-', $pieces)) . '.php';
    }

    return include OMGF_PLUGIN_DIR . 'includes/' . $filename;
}

spl_autoload_register('omgf_autoload');

/**
 * All systems GO!!!
 *
 * @return OMGF
 */
function omgf_init()
{
    static $omgf = null;

    if ($omgf === null) {
        $omgf = new OMGF();
    }

    return $omgf;
}

add_action('plugins_loaded', 'omgf_init');
