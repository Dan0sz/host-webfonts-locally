<?php
/**
 * @package: CAOS for Webfonts
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
	wp_die(__("You're not cool enough to access this page."));
}

/**
 * If cache directory doesn't exist, we should create it.
 */
$uploadDir = CAOS_WEBFONTS_UPLOAD_DIR;
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
	wp_die(__('No fonts or subsets selected.', CAOS_ANALYTICS_TRANSLATE_DOMAIN));
}

/**
 * Save used subsets to database for each font.
 */
foreach ($subsets as $id => $subset)
{
	$availableSubsets = implode($subset['available'], ',');
	$selectedSubsets  = implode($subset['selected'], ',');

	$wpdb->insert(
		CAOS_WEBFONTS_DB_TABLENAME . '_subsets',
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
		CAOS_WEBFONTS_DB_TABLENAME,
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
		$localFile  = CAOS_WEBFONTS_UPLOAD_DIR . '/' . $filename;

		try {
			$fileWritten = file_put_contents($localFile, file_get_contents($remoteFile));
		} catch (\Exception $e) {
			wp_die(__("File ($remoteFile) could not be downloaded: $e"));
		}

		/**
		 * If file is written, change the external URL to the local URL in the POST data.
		 * If it fails, we can still fall back to the external URL and nothing breaks.
		 */
		if($fileWritten) {
			$localFileUrl = CAOS_WEBFONTS_UPLOAD_URL . '/' . $filename;
			$wpdb->update(
				CAOS_WEBFONTS_DB_TABLENAME,
				array(
					$type => $localFileUrl
				),
				array(
					'font_id' => $font->font_id
				)
			);
		}
	}

	/**
	 * After all files are downloaded, set the 'downloaded'-field to 1.
	 */
	$wpdb->update(
		CAOS_WEBFONTS_DB_TABLENAME,
		array(
			'downloaded' => 1
		),
		array(
			'font_id' => $font->font_id
		)
	);
}

wp_die(__('Fonts saved. You can now generate the stylesheet.', 'host-webfonts-local'));
