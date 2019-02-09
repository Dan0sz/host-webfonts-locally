<?php
/**
 * @package: CAOS for Webfonts
 * @author: Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url: https://dev.daanvandenbergh.com
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

global $wpdb;

$tableName = $wpdb->prefix . 'caos_webfonts';

/**
 * To match the current queue of fonts. We need to truncate the table first.
 */
$wpdb->query(
	"TRUNCATE TABLE $tableName"
);

/**
 * Get the POST data.
 */
$selectedFonts = $_POST['selected_fonts'][0]['caos_webfonts_array'];

if (!$selectedFonts)
{
	wp_die(_e('No fonts found.', 'host-webfonts-local'));
}

foreach ($selectedFonts as $id => $font)
{
	$wpdb->insert(
		$tableName,
		array(
			'font_id'     => sanitize_text_field($id),
			'font_family' => sanitize_text_field($font['font-family']),
			'font_weight' => sanitize_text_field($font['font-weight']),
			'font_style'  => sanitize_text_field($font['font-style']),
			'downloaded'  => 0,
			'url_ttf'     => esc_url_raw($font['url']['ttf']),
			'url_woff'    => esc_url_raw($font['url']['woff']),
			'url_woff2'   => esc_url_raw($font['url']['woff2']),
			'url_eot'     => esc_url_raw($font['url']['eot'])
		)
	);
}

wp_die(_e('Fonts saved. You can now generate the stylesheet.', 'host-webfonts-local'));
