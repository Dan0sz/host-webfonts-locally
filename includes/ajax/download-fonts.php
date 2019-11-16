<?php
/**
 * @package: OMGF
 * @author: Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url: https://daan.dev
 */

// Exit if accessed directly
if (!defined( 'ABSPATH')) exit;

/**
 * Check if user has the needed permissions.
 */
if (!current_user_can('manage_options'))
{
	wp_die(__("You're not cool enough to access this page.", 'host-webfonts-local'));
}

/**
 * @param $code
 * @param $message
 */
function hwlThrowError($code, $message)
{
    wp_send_json_error(__($message, 'host-webfonts-local'), (int) $code);
}

/**
 * @param $localFile
 * @param $remoteFile
 *
 * @return bool
 */
function hwlDownloadFile($localFile, $remoteFile)
{
    $localFile = fopen($localFile, 'w+');
    $curl      = curl_init($remoteFile);

    curl_setopt_array(
        $curl,
        array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FILE           => $localFile,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER         => false
        )
    );

    curl_exec($curl);
    curl_close($curl);

    return fclose($localFile);
}

/**
 * If cache directory doesn't exist, we should create it.
 */
$uploadDir = OMGF_UPLOAD_DIR;
if (!file_exists($uploadDir)) {
	wp_mkdir_p($uploadDir);
}

global $wpdb;

/**
 * To match the current queue of fonts. We need to truncate the table first.
 */
try {
	hwlCleanQueue();
} catch (\Exception $e) {
	wp_die(__($e));
}

/**
 * Get the POST data.
 */
$selectedFonts = $_POST['fonts'][0]['caos_webfonts_array'];
$subsets       = $_POST['subsets'];

if (!$selectedFonts || !$subsets)
{
    hwlThrowError('400', 'No fonts or subsets selected.');
}

/**
 * Save used subsets to database for each font.
 */
foreach ($subsets as $id => $subset)
{
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


/**
 * Save used fonts to database.
 */
foreach ($selectedFonts as $id => $font)
{
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

/**
 * Loaded fonts from database
 */
$selectedFonts = hwlGetTotalFonts();

/**
 * Download the fonts.
 */
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
        $file = hwlDownloadFile($localFile, $remoteFile);

		if (!$file) {
            hwlThrowError('403', "File ($remoteFile) could not be downloaded. Is <code>allow_url_fopen</code> enabled on your server?");
        }

        $writeFile = file_put_contents($localFile, $file);

		if (!$writeFile) {
            hwlThrowError('403', "File ($localFile) could not be written. Do you have permission to write to <code>" . OMGF_UPLOAD_DIR . '</code>?');
        }

		if(!filesize($localFile) > 0) {
		    hwlThrowError('400', "File ($localFile) exists, but is 0 bytes in size. Is <code>allow_url_fopen</code> enabled on your server?");
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

wp_die(__('Fonts saved. You can now generate the stylesheet.', 'host-webfonts-local'));
