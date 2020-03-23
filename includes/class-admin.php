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

class OMGF_Admin
{
    const OMGF_ADMIN_JS_HANDLE  = 'omgf-admin-js';
    const OMGF_ADMIN_CSS_HANDLE = 'omgf-admin-css';

    /** @var OMGF_DB */
    private $db;

    /**
     * OMGF_Admin constructor.
     */
    public function __construct()
    {
        $this->db = new OMGF_DB();

        // @formatter:off
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_notices', [$this, 'add_notice']);
        add_filter('pre_update_option_omgf_cache_dir', [$this, 'reset_fonts_downloaded_value'], 10, 2);
        add_filter('pre_update_option_omgf_cache_uri', [$this, 'rewrite_fonts_urls'], 10, 2);
        // @formatter:on
    }

    /**
     * Enqueues the necessary JS and CSS and passes options as a JS object.
     *
     * @param $hook
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook == 'settings_page_optimize-webfonts') {
            wp_enqueue_script(self::OMGF_ADMIN_JS_HANDLE, plugin_dir_url(OMGF_PLUGIN_FILE) . 'js/omgf-admin.js', array('jquery'), OMGF_STATIC_VERSION, true);
            wp_enqueue_style(self::OMGF_ADMIN_CSS_HANDLE, plugin_dir_url(OMGF_PLUGIN_FILE) . 'css/omgf-admin.css', array(), OMGF_STATIC_VERSION);

            $options = array(
                'auto_detect_enabled' => OMGF_AUTO_DETECT_ENABLED,
                'detected_fonts'      => get_option('omgf_detected_fonts')
            );

            wp_localize_script(self::OMGF_ADMIN_JS_HANDLE, 'omgf', $options);
        }
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function get_template($name)
    {
        return include OMGF_PLUGIN_DIR . 'templates/admin/block-' . $name . '.phtml';
    }

    /**
     * Add notice to admin screen.
     */
    public function add_notice()
    {
        OMGF_Admin_Notice::print_notice();
    }

    /**
     * When the cache path is changed, OMGF moves the entire fonts folder to its new destination and throws a notice to
     * regenerate the stylesheet.
     *
     * @param $new_cache_dir
     * @param $old_cache_dir
     *
     * @return mixed
     */
    public function reset_fonts_downloaded_value($new_cache_dir, $old_cache_dir)
    {
        if ($new_cache_dir !== $old_cache_dir && !empty($new_cache_dir)) {
            $font_styles = $this->db->get_downloaded_fonts();

            if (empty($font_styles)) {
                return $new_cache_dir;
            }

            $files = array_diff(scandir(OMGF_FONTS_DIR), ['.', '..']);

            $this->move_files($files, $new_cache_dir);

            $font_styles = $this->rewrite_urls($font_styles, $old_cache_dir, $new_cache_dir);

            update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $font_styles);

            OMGF_Admin_Notice::set_notice(__('You have changed OMGF\'s storage folder. Don\'t forget to regenerate the stylesheet!', 'host-webfonts-local'), false, 'info');
        }

        return $new_cache_dir;
    }

    /**
     * @param $new_uri
     * @param $old_uri
     *
     * @return mixed
     */
    public function rewrite_fonts_urls($new_uri, $old_uri)
    {
        if ($new_uri !== $old_uri && !empty($new_uri)) {
            $font_styles = $this->db->get_downloaded_fonts();

            if (empty($font_styles)) {
                return $new_uri;
            }

            preg_match('/[^\/]+$/u', WP_CONTENT_DIR, $match);

            $font_styles = $this->rewrite_urls($font_styles, '/' . $match[0] . OMGF_CACHE_PATH, $new_uri);

            update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $font_styles);

            OMGF_Admin_Notice::set_notice(__('You have changed OMGF\'s font URLs. Regenerate the stylesheet to reflect the changes.', 'host-webfonts-local'), false, 'info');
        }

        return $new_uri;
    }

    /**
     * @param $files
     * @param $destination
     *
     * @return bool
     */
    private function move_files($files, $destination)
    {
        $this->create_dir_recursive($destination);

        foreach($files as $filename) {
            $old_path = OMGF_FONTS_DIR . "/$filename";
            $new_path = WP_CONTENT_DIR . $destination . "/$filename";
            $move = rename($old_path, $new_path);

            if ($move == false) {
                $errors[] = $filename;
            }
        }

        if (!empty($errors)) {
            $errored_files = ucfirst(implode(', ', $errors));

            OMGF_Admin_Notice::set_notice($errored_files . __('could not be moved. Do it manually and then regenerate the stylesheet.', 'host-webfonts-local'), false, 'error');

            return false;
        }
        $message    = sprintf(__('Moved %s files', 'host-webfonts-local'), count($files));
        $remove_dir = rmdir(OMGF_FONTS_DIR);

        if ($remove_dir) {
            $message .= ' ' . __('and succesfully removed the previously set storage folder.', 'host-webfonts-local');
        } else {
            $message .= '.';
        }

        OMGF_Admin_Notice::set_notice($message, false);

        return true;
    }

    /**
     * @param $font_styles
     * @param $old_path
     * @param $new_path
     *
     * @return mixed
     */
    private function rewrite_urls($font_styles, $old_path, $new_path)
    {
        foreach ($font_styles as &$font) {
            $urls = array_filter($font, function($key) {
                return strpos($key, 'url') !== false;
            }, ARRAY_FILTER_USE_KEY);

            foreach ($urls as &$url) {
                $url = str_replace($old_path, $new_path, $url);
            }

            $font = array_replace($font, $urls);
        }

        return $font_styles;
    }

    /**
     * @param $dir
     */
    private function create_dir_recursive($dir)
    {
        $uploadDir = WP_CONTENT_DIR . $dir;
        if (!file_exists($uploadDir)) {
            wp_mkdir_p($uploadDir);
        }
    }
}
