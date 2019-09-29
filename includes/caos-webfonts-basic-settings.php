<?php
/**
 * @package: OMGF
 * @author: Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url: https://daan.dev
 */

// Exit if accessed directly
if (!defined( 'ABSPATH')) exit;
?>
<div class="">
	<h3><?php _e('Basic Settings'); ?></h3>
    <p class="description">
        <?php _e('Do not forget to re-generate the stylesheet after changing settings.', 'host-webfonts-local'); ?>
    </p>
    <p class="description">
        <?php _e('* Empty Cache Directory, Download Fonts and Generate Stylesheet after changing this setting.', 'host-webfonts-local'); ?>
    </p>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">
                <label for="caos_webfonts_cache_dir">
                    <?php _e('Save webfonts to...', 'host-webfonts-local'); ?>
                </label>
            </th>
			<td>
				<input id="caos_webfonts_cache_dir" class="caos_webfonts_cache_dir" type="text" name="caos_webfonts_cache_dir" placeholder="e.g. /cache/omgf-webfonts" value="<?= CAOS_WEBFONTS_CACHE_DIR; ?>" />
				<p class="description">
					<?php _e("Changes the path where webfonts are cached inside WordPress' content directory (usually <code>wp-content</code>). Defaults to <code>/cache/caos-webfonts</code>.*", 'host-webfonts-local'); ?>
				</p>
			</td>
		</tr>
        <tr valign="top">
            <th scope="row">
                <label for="caos_webfonts_cdn_url">
                    <?php _e('Serve fonts from CDN', 'host-webfonts-local'); ?>
                </label>
            </th>
            <td>
                <input id="caos_webfonts_cdn_url" class="caos_webfonts_cdn_url" type="text" name="caos_webfonts_cdn_url" placeholder="e.g. cdn.mydomain.com" value="<?= CAOS_WEBFONTS_CDN_URL; ?>" />
                <p class="description">
                    <?php _e("Are you using a CDN? Then enter the URL here.*", 'host-webfonts-local'); ?>
                </p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="caos_webfonts_remove_version">
                    <?php _e('Remove version parameter?', 'host-webfonts-local'); ?>
                </label>
            </th>
            <td>
                <input id="caos_webfonts_remove_version" class="caos_webfonts_remove_version" type="checkbox" name="caos_webfonts_remove_version" <?= CAOS_WEBFONTS_REMOVE_VERSION ? "checked = 'checked'" : ""; ?> />
                <p class="description">
                    <?php _e('This removes the <code>?ver=x.x.x</code> parameter from the Stylesheet\'s (<code>fonts.css</code>) request.', 'host-webfonts-local'); ?>
                </p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="caos_webfonts_display_option">
                    <?php _e('Font-display option', 'host-webfonts-local'); ?>
                </label>
            </th>
            <td>
                <?php $fontDisplay = hwlFontDisplayOptions(); ?>
                    <select id="caos_webfonts_display_option" name="caos_webfonts_display_option">
                        <?php foreach ($fontDisplay as $label => $value): ?>
                        <option value="<?= $value; ?>" <?= $value == CAOS_WEBFONTS_DISPLAY_OPTION ? 'selected' : ''; ?>><?php _e($label, 'host-webfonts-local'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br/>
                <p class="description">
		            <?php _e('Select which font-display strategy to use. Defaults to \'Auto\'.', 'host-webfonts-local'); ?>
                    <a target="_blank" href="https://developers.google.com/web/updates/2016/02/font-display"><?php _e('Read more', 'host-webfonts-local'); ?></a>
                </p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="caos_webfonts_remove_gfonts">
                    <?php _e('Remove Google Fonts (experimental)', 'host-webfonts-local'); ?>
                </label>
            </th>
            <td>
                <input class="caos_webfonts_remove_gfonts" id="caos_webfonts_remove_gfonts" type="checkbox" name="caos_webfonts_remove_gfonts" <?= CAOS_WEBFONTS_REMOVE_GFONTS == 'on' ? 'checked = "checked"' : ''; ?> />
                <p class="description">
                    <?= sprintf(__('Enabling this option will attempt to remove any externally hosted Google Fonts-stylesheets from your WordPress-blog. If it doesn\'t work for you, click %shere%s for a more comprehensive guide.', 'host-webfonts-local'), '<a target="_blank" href="' . CAOS_WEBFONTS_SITE_URL . '/how-to/remove-google-fonts-wordpress">', '</a>'); ?>
                </p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="caos_webfonts_preload">
                    <?php _e('Enable preload for stylesheet (experimental)', 'host-webfonts-local'); ?>
                </label>
            </th>
            <td>
                <input class="caos_webfonts_preload" id="caos_webfonts_preload" type="checkbox" name="caos_webfonts_preload" <?= CAOS_WEBFONTS_PRELOAD == 'on' ? 'checked = "checked"' : ''; ?> />
                <p class="description">
                    <?php _e('Leave this disabled if you\'re using a CSS minification plugin, such as Autoptimize or W3 Total Cache.', 'host-webfonts-local'); ?> <a target="_blank" href="https://developers.google.com/web/fundamentals/performance/resource-prioritization#preload"><?php _e('Read more', 'host-webfonts-local'); ?></a>
                </p>
            </td>
        </tr>
	</table>
</div>
