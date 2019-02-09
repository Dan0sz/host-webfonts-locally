<?php
/**
 * @package: CAOS for Webfonts
 * @author: Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url: https://dev.daanvandenbergh.com
 */

// Exit if accessed directly
if (!defined( 'ABSPATH')) exit;
?>
<div class="">
	<h3><?php _e('Basic Settings'); ?></h3>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Save webfonts to...', 'host-webfonts-local'); ?></th>
			<td>
				<input class="caos_webfonts_cache_dir" type="text" name="caos_webfonts_cache_dir" placeholder="e.g. /cache/caos-webfonts" value="<?php echo CAOS_WEBFONTS_CACHE_DIR; ?>" />
				<p class="description">
					<?php _e("Changes the path where webfonts are cached inside WordPress' content directory (usually <code>wp-content</code>). Defaults to <code>/cache/caos-webfonts</code>.", 'host-webfonts-local'); ?>
				</p>
			</td>
		</tr>
        <tr valign="top">
            <th scope="row"><?php _e('Font-display option', 'host-webfonts-local'); ?></th>
            <td>
                <?php $fontDisplay = hwlFontDisplayOptions(); ?>
                    <label>
                        <select name="caos_webfonts_display_option">
	                        <?php foreach ($fontDisplay as $label => $value): ?>
                            <option value="<?php echo $value; ?>" <?php echo $value == CAOS_WEBFONTS_DISPLAY_OPTION ? 'selected' : ''; ?>><?php _e($label, 'host-webfonts-local'); ?></option>
	                        <?php endforeach; ?>
                        </select>
                    </label>
                    <br/>
                <p class="description">
		            <?php _e('Select which font-display strategy to use. Defaults to \'Auto\'.', 'host-webfonts-local'); ?>
                    <a target="_blank" href="https://developers.google.com/web/updates/2016/02/font-display"><?php _e('Read more', 'host-webfonts-local'); ?></a>
                </p>
            </td>
        </tr>
	</table>
</div>
