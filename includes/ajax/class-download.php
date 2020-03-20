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

class OMGF_AJAX_Download extends OMGF_AJAX
{
    /** @var QM_DB $wpdb */
    private $wpdb;

    /** @var OMGF_DB $db */
    protected $db;

    /**
     * OMGF_Download_Fonts constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        parent::__construct();

        $this->init();
    }

    /**
     * Initialize the download process.
     */
    private function init()
    {
        $this->create_dir_recursive();

        $this->download();
    }

    /**
     * If cache directory doesn't exist, we should create it.
     */
    private function create_dir_recursive()
    {
        $uploadDir = OMGF_UPLOAD_DIR;
        if (!file_exists($uploadDir)) {
            wp_mkdir_p($uploadDir);
        }
    }

    /**
     * Download the fonts and write them to the database.
     */
    private function download()
    {
        $selectedFonts = $this->db->get_total_fonts();

        if (empty($selectedFonts)) {
            OMGF_Admin_Notice::set_notice(__('Hmmm... Seems like there\'s nothing to do here. Have you tried using <strong>search</strong> or <strong>auto detect</strong>?', 'host-webfonts-local'), true, 'error');
        }

        foreach ($selectedFonts as $id => &$font) {
            // If font is marked as downloaded. Skip it.
            if ($font['downloaded']) {
                continue;
            }

            $urls['url_ttf']   = $font['url_ttf'];
            $urls['url_woff']  = $font['url_woff'];
            $urls['url_woff2'] = $font['url_woff2'];
            $urls['url_eot']   = $font['url_eot'];

            foreach ($urls as $type => $url) {
                if (!$url) {
                    continue;
                }

                $remoteFile = esc_url_raw($url);

                /**
                 * We've already downloaded this one before.
                 */
                if (strpos($remoteFile, get_site_url()) !== false) {
                    continue;
                }

                /**
                 * We rewrite the local filename for easier debugging in the waterfall.
                 */
                $filename  = sanitize_title_with_dashes($font['font_family']) . '-' . $font['font_weight'] . '-' . $font['font_style'] . '-' . substr(basename($remoteFile), -10);
                $localFile = OMGF_UPLOAD_DIR . '/' . $filename;

                try {
                    $this->download_file($localFile, $remoteFile);
                    $font['downloaded'] = 1;
                } catch (Exception $e) {
                    OMGF_Admin_Notice::set_notice(__("File ($remoteFile) could not be downloaded: ", 'host-webfonts-local') . $e->getMessage(), false, 'error', $e->getCode());
                }

                clearstatcache();

                $font[$type] = OMGF_UPLOAD_URL . '/' . $filename;
            }
        }

        update_option(OMGF_Admin_Settings::OMGF_SETTING_FONTS, $selectedFonts);

        OMGF_Admin_Notice::set_notice(count($selectedFonts) . ' ' . __('fonts downloaded. You can now proceed to generate the stylesheet.', 'host-webfonts-local'));
    }

    /**
     * Download $remoteFile and write to $localFile
     *
     * @param $localFile
     * @param $remoteFile
     */
    private function download_file($localFile, $remoteFile)
    {
        $file = wp_remote_get($remoteFile, $localFile);

        if (is_wp_error($file)) {
            OMGF_Admin_Notice::set_notice($file->get_error_message(), true, 'error', $file->get_error_code());
        }

        $this->filesystem()->put_contents($localFile, $file['body']);

        if (file_exists($localFile)) {
            return;
        }
    }

    /**
     * Helper to return WordPress filesystem subclass.
     *
     * @return WP_Filesystem_Base $wp_filesystem
     */
    private function filesystem()
    {
        global $wp_filesystem;

        if ( is_null( $wp_filesystem ) ) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        return $wp_filesystem;
    }
}
