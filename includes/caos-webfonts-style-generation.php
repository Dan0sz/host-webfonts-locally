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
<table>
	<tbody>
	<tr valign="top">
		<td colspan="2">
			<input type="text" name="search-field"
			       id="search-field" class="form-input-tip ui-autocomplete-input" placeholder="Search fonts..." />
		</td>
	</tr>
	</tbody>
	<tr valign="top">
		<th>
			font-family
		</th>
		<th>
			font-style
		</th>
        <th>
            font-weight
        </th>
		<th>

		</th>
	</tr>
	<tbody id="hwl-results">
    <?php
    $savedFonts = hwlGetTotalFonts();
    ?>
    <?php if ($savedFonts): ?>
    <?php foreach ($savedFonts as $font): ?>
        <?php
        $fontId = $font->font_id;
        $arrayPath = "caos_webfonts_array][$fontId]";
        ?>
        <tr id="row-<?php echo $fontId; ?>" valign="top">
            <td>
                <input readonly type="text" value="<?php echo $font->font_family; ?>" name="<?php echo $arrayPath; ?>[font-family]" />
            </td>
            <td>
                <input readonly type="text" value="<?php echo $font->font_style; ?>" name="<?php echo $arrayPath; ?>[font-style]" />
            </td>
            <td>
                <input readonly type="text" value="<?php echo $font->font_weight; ?>" name="<?php echo $arrayPath; ?>[font-weight]" />
            </td>
            <td>
                <input type="hidden" value="<?php echo $fontId; ?>" name="<?php echo $arrayPath; ?>[id]" />
                <input type="hidden" value="<?php echo $font->url_ttf; ?>" name="<?php echo $arrayPath; ?>[url][ttf]" />
                <input type="hidden" value="<?php echo $font->url_woff; ?>" name="<?php echo $arrayPath; ?>[url][woff]" />
                <input type="hidden" value="<?php echo $font->url_woff2; ?>" name="<?php echo $arrayPath; ?>[url][woff2]" />
                <input type="hidden" value="<?php echo $font->url_eot; ?>" name="<?php echo $arrayPath; ?>[url][eot]" />
                <div class="hwl-remove">
                    <a onclick="hwlRemoveRow('row-<?php echo $fontId; ?>')"><small>remove</small></a>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php endif; ?>
	</tbody>
    <tbody id="hwl-warning">
        <tr class="loading" style="display: none;">
            <td colspan="3" align="center">
                <span class="spinner"></span>
            </td>
        </tr>
        <tr class="error" style="display: none;">
            <td colspan="3" align="center">No fonts available.</td>
        </tr>
    </tbody>
</table>

<table>
	<tbody>
	<tr valign="center" align="center">
        <td>
            <input type="button" onclick="hwlDownloadFonts()" name="save-btn"
                   id="save-btn" class="button-primary" value="Download Fonts" />
        </td>
		<td>
			<input type="button" onclick="hwlGenerateStylesheet()" name="generate-btn"
			       id="generate-btn" class="button-secondary" value="Generate Stylesheet" />
		</td>
        <td>
            <input type="button" onclick="hwlRegenerateStylesheet()" name="regenerate-btn"
                   id="regenerate-btn" class="button-secondary" value="Save & Regenerate" />
        </td>
        <td>
            <a onclick="hwlCleanQueue()" name="clean-btn"
               id="clean-btn" class="button-cancel">Clean Queue</a>
        </td>
        <td>
            <a onclick="hwlEmptyDir()" name="empty-btn"
               id="empty-btn" class="button-cancel">Empty Cache Directory</a>
        </td>
    </tr>
    <tr valign="center">
        <?php
        $downloaded = hwlGetDownloadStatus()['downloaded'];
        $total      = hwlGetDownloadStatus()['total'];
        $width      = $downloaded && $total ? (100 / $total) * $downloaded : 0;
        ?>
        <td colspan="5">
            <div class="caos-status-total-bar" style="">
                <div id="caos-status-progress-bar" style="width: <?php echo $width; ?>%;">
                    <span class="caos-status-progress-percentage"><?php echo $width . '%'; ?></span>
                </div>
            </div>
        </td>
	</tr>
	</tbody>
</table>
