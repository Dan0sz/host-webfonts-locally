<?php

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
	</table>
</div>
