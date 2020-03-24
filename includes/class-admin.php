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
        add_filter('pre_update_option_omgf_cache_dir', [$this, 'cache_dir_changed'], 10, 2);
        add_filter('pre_update_option_omgf_cache_uri', [$this, 'serve_uri_changed'], 10, 2);
        add_filter('pre_update_option_omgf_relative_url', [$this, 'relative_url_changed'], 10, 2);
        add_filter('pre_update_option_omgf_cdn_url', [$this, 'cdn_url_changed'], 10, 2);
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
    public function cache_dir_changed($new_cache_dir, $old_cache_dir)
    {
        if ($new_cache_dir !== $old_cache_dir && !empty($new_cache_dir)) {
            $font_styles = $this->db->get_downloaded_fonts();

            if (empty($font_styles)) {
                return $new_cache_dir;
            }

            $files = array_diff(scandir(OMGF_FONTS_DIR), ['.', '..']);

            $this->move_files($files, $new_cache_dir);

            if (!OMGF_CACHE_URI) {
                $font_styles = $this->rewrite_urls($font_styles, $old_cache_dir, $new_cache_dir);

                OMGF_Admin_Notice::set_notice(__("You've changed OMGF\'s storage folder to $new_cache_dir. Regenerate the stylesheet to implement this change.", 'host-webfonts-local'), false, 'info');
            } else {
                OMGF_Admin_Notice::set_notice(__("You\'ve changed OMGF's storage folder to $new_cache_dir. Make sure the setting <em>Serve font files from...</em> reflects your changes and regenerate the stylesheet.", 'host-webfonts-local'), false);
            }

            update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $font_styles);
        }

        return $new_cache_dir;
    }

    /**
     * @param $new_uri
     * @param $old_uri
     *
     * @return mixed
     */
    public function serve_uri_changed($new_uri, $old_uri)
    {
        if ($new_uri !== $old_uri && !empty($new_uri)) {
            $font_styles = $this->db->get_downloaded_fonts();

            if (empty($font_styles)) {
                return $new_uri;
            }

            preg_match('/[^\/]+$/u', WP_CONTENT_DIR, $match);

            $font_styles = $this->rewrite_urls($font_styles, $old_uri, $new_uri);

            update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $font_styles);

            OMGF_Admin_Notice::set_notice(__("Fonts updated successfully. Regenerate the stylesheet to <em>serve font files from</em> $new_uri.", 'host-webfonts-local'), false, 'info');
        }

        return $new_uri;
    }

    /**
     * @param $new_value
     * @param $old_value
     *
     * @return mixed
     */
    public function relative_url_changed($new_value, $old_value)
    {
        if ($new_value !== $old_value) {
            $font_styles = $this->db->get_downloaded_fonts();

            if (empty($font_styles)) {
                return $new_value;
            }

            $result = $this->unset_downloaded_value($font_styles);

            if ($new_value == 'on') {
                $status = 'enabled';
            } elseif (!$new_value) {
                $status = 'disabled';
            }

            if ($result) {
                OMGF_Admin_Notice::set_notice(sprintf(__('You\'ve %s the setting <em>Use relative URLs</em>. <strong>Download</strong> the <strong>fonts</strong> again and (re-)<strong>generate</strong> the <strong>stylesheet</strong> to implement this change.', 'host-webfonts-local'), $status), false, 'info');

                return $new_value;
            }

            OMGF_Admin_Notice::set_notice(__('You\'ve %s the setting <em>Use relative URLs</em>. Something went wrong while updating the fonts. <strong>Empty</strong> the <strong>cache directory</strong>, <strong>download</strong> the <strong>fonts</strong> and <strong>generate</strong> the <strong>stylesheet</strong> to implement this change.', 'host-webfonts-local'), false, 'error');
        }

        return $new_value;
    }

    public function cdn_url_changed($new_url, $old_url)
    {
        if ($new_url !== $old_url && !empty($new_url)) {
            $font_styles = $this->db->get_downloaded_fonts();

            if (empty($font_styles)) {
                return $new_url;
            }

            $result = $this->unset_downloaded_value($font_styles);

            if ($result) {
                OMGF_Admin_Notice::set_notice(__('Fonts updated successfully. <strong>Download</strong> the <strong>fonts</strong> and (re-)<strong>generate</strong> the <strong>stylesheet</strong> to <em>serve fonts from CDN</em>.', 'host-webfonts-local'), false, 'info');
            } else {
                OMGF_Admin_Notice::set_notice(__('Something went wrong while updating your settings. <strong>Empty</strong> the <strong>Cache Directory</strong>, <strong>download</strong> the <strong>fonts</strong> and <strong>generate</strong> the <strong>stylesheet</strong> to <em>serve fonts from CDN</em>.', 'host-webfonts-local'), false, 'info');
            }
        }

        return $new_url;
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
            $move     = rename($old_path, $new_path);

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
     * @param $fonts
     *
     * @return bool
     */
    private function unset_downloaded_value($fonts)
    {
        foreach ($fonts as &$font_style) {
            $font_style['downloaded'] = 0;
        }

        $updated = update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $fonts);

        if ($updated) {
            return true;
        }

        return false;
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

    /**
     * @param $path
     */
    private function remove_dir_recursive($path)
    {
        $dirs = explode('/', $path);
    }
}
