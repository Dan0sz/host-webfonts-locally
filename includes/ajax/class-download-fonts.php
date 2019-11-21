<?php
/**
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(dirname(dirname(__FILE__)) . '/class-ajax.php');

class OMGF_AJAX_Download_Fonts extends OMGF_AJAX
{
    /** @var $fonts */
    private $fonts;

    /** @var $subsets */
    private $subsets;

    /**
     * OMGF_Download_Fonts constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the download process.
     */
    private function init()
    {
        $this->create_dir_recursive();

        /**
         * To match the current queue of fonts. We need to truncate the table first.
         */
        try {
            hwlCleanQueue();
        } catch (\Exception $e) {
            $this->throw_error($e->getCode(), $e->getMessage());
        }

        /**
         * Get the POST data.
         */
        $this->fonts   = $_POST['fonts'][0]['caos_webfonts_array'];
        $this->subsets = $_POST['subsets'];

        if (!$this->fonts || !$this->subsets) {
            $this->throw_error('400', 'No fonts or subsets selected.');
        }

        $this->save_subsets_to_db();
        $this->save_fonts_to_db();

        $this->download_fonts();
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
     * Save used subsets to database for each font.
     */
    private function save_subsets_to_db()
    {
        global $wpdb;

        foreach ($this->subsets as $id => $subset) {
            $availableSubsets = implode($subset['available'], ',');
            $selectedSubsets  = implode($subset['selected'], ',');

            $wpdb->insert(
                OMGF_DB_TABLENAME . '_subsets',
                array(
                    'subset_font'       => $id,
                    'subset_family'     => $subset['family'],
                    'available_subsets' => $availableSubsets,
                    'selected_subsets'  => $selectedSubsets,
                )
            );
        }
    }

    /**
     * Save used fonts to database.
     */
    private function save_fonts_to_db()
    {
        global $wpdb;

        foreach ($this->fonts as $id => $font) {
            $wpdb->insert(
                OMGF_DB_TABLENAME,
                array(
                    'font_id'     => sanitize_text_field($id),
                    'font_family' => sanitize_text_field($font['font-family']),
                    'font_weight' => sanitize_text_field($font['font-weight']),
                    'font_style'  => sanitize_text_field($font['font-style']),
                    'local'       => sanitize_text_field($font['local']),
                    'downloaded'  => 0,
                    'url_ttf'     => esc_url_raw($font['url']['ttf']),
                    'url_woff'    => esc_url_raw($font['url']['woff']),
                    'url_woff2'   => esc_url_raw($font['url']['woff2']),
                    'url_eot'     => esc_url_raw($font['url']['eot'])
                )
            );
        }
    }

    /**
     *
     */
    private function download_fonts()
    {
        global $wpdb;

        $selectedFonts = hwlGetTotalFonts();

        foreach ($selectedFonts as $id => $font) {
            // If font is marked as downloaded. Skip it.
            if ($font->downloaded) {
                continue;
            }

            $urls['url_ttf']   = $font->url_ttf;
            $urls['url_woff']  = $font->url_woff;
            $urls['url_woff2'] = $font->url_woff2;
            $urls['url_eot']   = $font->url_eot;

            foreach ($urls as $type => $url) {
                $remoteFile = esc_url_raw($url);
                $filename   = basename($remoteFile);
                $localFile  = OMGF_UPLOAD_DIR . '/' . $filename;

                try {
                    $this->download_file_curl($localFile, $remoteFile);
                } catch (Exception $e) {
                    $this->throw_error($e->getCode(), "File ($remoteFile) could not be downloaded: " . $e->getMessage());
                }

                if (file_exists($localFile) && !filesize($localFile) > 0) {
                    $this->throw_error('400', "File ($localFile) exists, but is 0 bytes in size. Is <code>allow_url_fopen</code> enabled on your server?");
                }

                /**
                 * If file is written, change the external URL to the local URL in the POST data.
                 * If it fails, we can still fall back to the external URL and nothing breaks.
                 */
                $localFileUrl = OMGF_UPLOAD_URL . '/' . $filename;
                $wpdb->update(
                    OMGF_DB_TABLENAME,
                    array(
                        $type => $localFileUrl
                    ),
                    array(
                        'font_id' => $font->font_id
                    )
                );
            }

            /**
             * After all files are downloaded, set the 'downloaded'-field to 1.
             */
            $wpdb->update(
                OMGF_DB_TABLENAME,
                array(
                    'downloaded' => 1
                ),
                array(
                    'font_id' => $font->font_id
                )
            );
        }
    }

    /**
     * Download $remoteFile using cUrl and write to $localFile
     *
     * @param $localFile
     * @param $remoteFile
     */
    private function download_file_curl($localFile, $remoteFile)
    {
        $file = fopen($localFile, 'w+');
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => $remoteFile,
                CURLOPT_FILE           => $file,
                CURLOPT_HEADER         => false,
                CURLOPT_FOLLOWLOCATION => true
            )
        );

        curl_exec($curl);
        curl_close($curl);
        fclose($file);

        if (file_exists($localFile) && filesize($localFile) > 1) {
            return;
        }

        $this->download_file_fallback($localFile, $remoteFile);
    }

    /**
     * Fallback download method if cUrl failed.
     *
     * @param $localFile
     * @param $remoteFile
     */
    private function download_file_fallback($localFile, $remoteFile)
    {
        file_put_contents($localFile, file_get_contents($remoteFile));
    }
}

new OMGF_AJAX_Download_Fonts();
