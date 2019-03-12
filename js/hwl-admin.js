/**
 * @package: CAOS for Webfonts
 * @author: Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url: https://daan.dev
 */

/**
 * Timer which triggers search after waiting for user to finish typing.
 */
var typingTimer
var doneTypingInterval = 300
var $input = jQuery('#search-field')
// on keyup, start the countdown
$input.on('keyup', function() {
    clearTimeout(typingTimer)
    typingTimer = setTimeout(doneTyping, doneTypingInterval)
})
// on keydown, clear the countdown
$input.on('keydown', function() {
    clearTimeout(typingTimer)
})

/**
 * When user is done typing, trigger search.
 */
function doneTyping()
{
    searchQuery = $input.val().replace(/\s/g, '-').toLowerCase()
    hwlSearchFontSubsets(searchQuery)
}

/**
 * Return available subsets for searched font.
 *
 * @param query
 */
function hwlSearchFontSubsets(queriedFonts)
{
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'hwlAjaxSearchFontSubsets',
            search_query: queriedFonts
        },
        dataType: 'json',
        complete: function(response) {
            hwlRenderAvailableSubsets(response);
        }
    })
}

/**
 * Print available subsets
 *
 * @param response
 */
function hwlRenderAvailableSubsets(response)
{
    data = response['responseJSON'];
    dataLength = data.length;
    for (ii = 0; ii <= dataLength; ii++) {
        subsets = data[ii]['subsets']
        family = data[ii]['family'];
        id = data[ii]['id'];
        length = subsets.length;
        renderedSubsets = [];
        for (iii = 0; iii < length; iii++) {
            renderedSubsets[iii] = `<td><label><input name="${id}" value="${subsets[iii]}" type="checkbox" onclick="hwlGenerateSearchQuery('${id}')" />${subsets[iii]}</label></td>`;
        }
        jQuery('#hwl-subsets').append('<tr valign="top" id="' + id + '"><td><input class="hwl-subset-font-family" value="' + family + '" readonly/></td>' + renderedSubsets + '</tr>');
        jQuery('#hwl-results').append("<tbody id='" + 'hwl-section-' + id + "'></tbody>");
    }
}

/**
 * Generate search query for selected subsets
 *
 * @param id
 */
function hwlGenerateSearchQuery(id)
{
    var subsets = [];
    checked = jQuery("input[name='" + id + "']:checked");
    jQuery.each(checked, function() {
        subsets.push(jQuery(this).val());
    });
    subsets.join()
    hwlSearchGoogleFonts(id, subsets);
}

/**
 * Triggers the AJAX-request to Google Webfont Helper.
 *
 * @param id
 * @param subsets
 */
function hwlSearchGoogleFonts(id, subsets)
{
    var loadingDiv = jQuery('#hwl-warning .loading')
    var errorDiv = jQuery('#hwl-warning .error')
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'hwlAjaxSearchGoogleFonts',
            search_query: id,
            search_subsets: subsets
        },
        dataType: 'json',
        beforeSend: function() {
            loadingDiv.show()
        },
        error: function() {
            errorDiv.show()
        },
        complete: function(response) {
            loadingDiv.hide()
            errorDiv.hide()
            if(response['responseText'] !== 'Not found') {
                hwlRenderAvailableFonts(response)
            } else {
                errorDiv.show()
            }
        }
    })
}

/**
 * Displays the search results
 *
 * @param results
 */
function hwlRenderAvailableFonts(results)
{
    var response = JSON.parse(results['responseText'])
    var variants = response['variants']
    var length = variants.length
    var renderedFonts = []
    for(var iii = 0; iii < length; iii++) {
        var fontFamily = variants[iii].fontFamily.replace(/'/g, '')
        var fontId = variants[iii].id
        var font = fontFamily.replace(/\s+/g, '-').toLowerCase() + '-' + variants[iii].id
        var fontWeight = variants[iii].fontWeight
        var fontStyle = variants[iii].fontStyle
        renderedFonts[iii] = `<tr id="row-${font}" valign="top">
                                    <td>
                                        <input readonly type="text" value="${fontFamily}" name="caos_webfonts_array][${font}][font-family]" />
                                    </td>
                                    <td>
                                        <input readonly type="text" value="${fontStyle}" name="caos_webfonts_array][${font}][font-style]" />
                                    </td>
                                    <td>
                                        <input readonly type="text" value="${fontWeight}" name="caos_webfonts_array][${font}][font-weight]" />
                                    </td>
                                    <td>
                                        <input type="hidden" value="${fontId}" name="caos_webfonts_array][${font}][id]" />
                                        <input type="hidden" value="${variants[iii].ttf}" name="caos_webfonts_array][${font}][url][ttf]" />
                                        <input type="hidden" value="${variants[iii].woff}" name="caos_webfonts_array][${font}][url][woff]" />
                                        <input type="hidden" value="${variants[iii].woff2}" name="caos_webfonts_array][${font}][url][woff2]" />
                                        <input type="hidden" value="${variants[iii].eot}" name="caos_webfonts_array][${font}][url][eot]" />
                                        <div class="hwl-remove">
                                            <a onclick="hwlRemoveRow('row-${font}')"><small>remove</small></a>
                                        </div>
                                    </td>
                                 </tr>`
    }
    console.log(fontFamily.replace(/\s+/g, '-').toLowerCase());
    jQuery('#hwl-section-' + fontFamily.replace(/\s+/g, '-').toLowerCase()).html(renderedFonts)
}

/**
 * Call the generate-stylesheet script.
 */
function hwlGenerateStylesheet()
{
    var hwlFonts = hwlSerializeArray(jQuery('#hwl-options-form'))
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'hwlAjaxGenerateStyles',
            selected_fonts: hwlFonts
        },
        success: function(response) {
            jQuery('#hwl-admin-notices').append(
                `<div class="updated settings-success notice is-dismissible">
                    <p>${response}</p>
                </div>`
            )
            hwlScrollTop()
        },
        error: function(response) {
            jQuery('#hwl-admin-notices').append(
                `<div class="notice notice-error is-dismissible">
                    <p>The stylesheet could not be created: ${response}</p>
                </div>`
            )
            hwlScrollTop()
        }
    })
}

/**
 * Gathers all information about the subsets
 *
 * @returns {{}}
 */
function hwlGatherSelectedSubsets()
{
    rows = jQuery('#hwl-subsets tr');
    length = rows.length;
    subsets = {};
    jQuery(rows).each(function() {
        id = this.id;
        checkboxes = jQuery("input[name='" + id + "']");
        checked = jQuery("input[name='" + id + "']:checked");
        
        selectedSubsets = [];
        jQuery.each(checked, function() {
            selectedSubsets.push(jQuery(this).val());
        });
        selectedSubsets.join()
        
        availableSubsets = [];
        jQuery.each(checkboxes, function() {
            availableSubsets.push(jQuery(this).val());
        });
        availableSubsets.join()
        
        family = jQuery(this).find('.hwl-subset-font-family').val();
        
        subsets[id] = {};
        subsets[id]['family'] = {};
        subsets[id]['family'] = family;
        subsets[id]['selected'] = {};
        subsets[id]['selected'] = selectedSubsets;
        subsets[id]['available'] = {};
        subsets[id]['available'] = availableSubsets;
    })
    
    return subsets;
}

/**
 * Triggered when 'Save Webfonts' is clicked.
 */
function hwlDownloadFonts()
{
    var hwlFonts  = hwlSerializeArray(jQuery('#hwl-options-form'));
    var hwlSubsets = hwlGatherSelectedSubsets();
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'hwlAjaxDownloadFonts',
            subsets: hwlSubsets,
            fonts: hwlFonts,
        },
        beforeSend: function() {
            downloadStatus = window.setInterval(hwlGetDownloadStatus, 1000);
        },
        success: function(response) {
            jQuery('#hwl-admin-notices').append(
                `<div class="notice notice-success is-dismissible">
                    <p>${response}</p>
                </div>`
            )
            hwlScrollTop();
            window.clearInterval(downloadStatus);
        }
    })
}

/**
 * Gets a JSON object with the download progress information
 */
function hwlGetDownloadStatus()
{
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'hwlAjaxGetDownloadStatus'
        },
        dataType: 'text json',
        success: function(response) {
            downloaded = response.downloaded;
            total = response.total;
            progress = (100 / total) * downloaded;
            hwlUpdateStatusBar(progress);
        }
    })
}

/**
 * Updated Status-bar with the set progress
 *
 * @param progress
 */
function hwlUpdateStatusBar(progress)
{
    progress = Math.round(progress) + '%';
    jQuery('#caos-status-progress-bar').width(progress);
    jQuery('.caos-status-progress-percentage').html(progress);
}

/**
 * Remove all files within the configured cache dir.
 */
function hwlEmptyDir()
{
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'hwlAjaxEmptyDir'
        },
        success: function() {
            jQuery('#hwl-admin-notices').append(
                `<div class="notice notice-success is-dismissible">
                    <p>Cache-dir emptied.</p>
                </div>`
            )
            hwlCleanQueue()
            hwlUpdateStatusBar(0)
            hwlScrollTop()
        }
    })
}

/**
 * Trigger the DB clean-up and clean list.
 */
function hwlCleanQueue()
{
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'hwlAjaxCleanQueue'
        },
        success: function() {
            jQuery('.caos-status-progress-percentage').html('0%')
            jQuery('#hwl-results, #hwl-subsets').empty()
        }
    })
}

/**
 * Scroll to top-effect.
 */
function hwlScrollTop()
{
    setTimeout(function () {
        jQuery('html, body').animate({
            scrollTop: 0
        }, 1500)
    }, 1500)
}

/**
 * Serialize form data to a multi-dimensional array.
 */
function hwlSerializeArray(data)
{
    var result = []
    data.each(function() {
        var fields = {}
        jQuery.each(jQuery(this).serializeArray(), function() {
            fields[this.name] = this.value
        })
        result.push(fields)
    })
    return result
}

/**
 * Remove selected row.
 *
 * @param rowId
 */
function hwlRemoveRow(rowId)
{
    jQuery('#' + rowId).remove()
}
