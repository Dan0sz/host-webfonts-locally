/**
 * @package: CAOS for Webfonts
 * @author: Daan van den Bergh
 * @copyright: (c) 2018 Daan van den Bergh
 * @url: https://dev.daanvandenbergh.com
 */

var typingTimer;
var doneTypingInterval = 300;
var $input = jQuery('#search-field');

// on keyup, start the countdown
$input.on('keyup', function () {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(doneTyping, doneTypingInterval);
});

// on keydown, clear the countdown
$input.on('keydown', function () {
    clearTimeout(typingTimer);
});

// user is "finished typing," do something
function doneTyping()
{
    query = $input.val().replace(' ', '-').toLowerCase();
    hwlSearchGoogleFonts(query);
}

/**
 * Triggers the AJAX-request to Google Webfont Helper.
 * @param $data
 */
function hwlSearchGoogleFonts($data)
{
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'hwlAjaxSearchGoogleFonts',
            search_query: $data
        },
        datatype: 'json',
        beforeSend: function () {
            jQuery('#hwl-results .loading').show();
        },
        error: function() {
            jQuery('#hwl-results .error').show();
        },
        complete: function (response) {
            jQuery('#hwl-results .loading').hide();
            hwlGenerateResults(response);
        }
    });
}

function hwlGenerateResults(results)
{
    var response = JSON.parse(results['responseText']);
    var variants = response['variants'];
    var length = variants.length;
    var renderedFonts = [];
    for (var iii = 0; iii < length; iii++) {
        var fontFamily = variants[ iii ].fontFamily.replace(/'/g, '');
        var fontId = variants[ iii ].id;
        var font = fontFamily.replace(/\s+/g, '-').toLowerCase() + '-' + variants[ iii ].id;
        var fontWeight = variants[ iii ].fontWeight;
        var fontStyle = variants[ iii ].fontStyle;
        renderedFonts[ iii ] = `<tr id="row-${font}" valign="top">
                                    <td>
                                        <input readonly type="text" value="${fontFamily}" name="hwl-rendered-fonts][${font}][font-family]" />
                                    </td>
                                    <td>
                                        <input readonly type="text" value="${fontId}" name="hwl-rendered-fonts][${font}][id]" />
                                    </td>
                                    <td>
                                        <input type="hidden" value="${fontWeight}" name="hwl-rendered-fonts][${font}][font-weight]" />
                                        <input type="hidden" value="${fontStyle}" name="hwl-rendered-fonts][${font}][font-style]" />
                                        <input type="hidden" value="${variants[ iii ].ttf}" name="hwl-rendered-fonts][${font}][url][ttf]" />
                                        <input type="hidden" value="${variants[ iii ].woff}" name="hwl-rendered-fonts][${font}][url][woff]" />
                                        <input type="hidden" value="${variants[ iii ].woff2}" name="hwl-rendered-fonts][${font}][url][woff2]" />
                                        <input type="hidden" value="${variants[ iii ].eot}" name="hwl-rendered-fonts][${font}][url][eot]" />
                                        <div class="hwl-remove">
                                            <a onclick="hwlRemoveRow('row-${font}')"><small>remove</small></a>
                                        </div>
                                    </td>
                                 </tr>`;
    }
    jQuery('#hwl-results').append(renderedFonts);
}

/**
 * Call the generate-stylesheet script and reset the upload dir to the default setting.
 */
function hwlGenerateStylesheet()
{
    var hwlData = hwlSerializeArray(jQuery('#hwl-options-form'));
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'hwlAjaxGenerateStyles',
            selected_fonts: hwlData
        },
        beforeSend: function() {
        
        },
        success: function(response) {
            jQuery('#hwl-admin-notices').append(
                `<div class="updated settings-error notice is-dismissible">
                    <p>${response}</p>
                </div>`
            );
            jQuery('#hwl-results tr').each(function () {
                jQuery(this).fadeOut(700, function () {
                    jQuery(this).remove();
                });
            });
            jQuery('#hwl-results').html('Stylesheet generated.');
        },
        error: function(response) {
            jQuery('#hwl-admin-notices').append(
                `<div class="notice notice-error is-dismissible">
                    <p>The stylesheet could not be created: ${response}</p>
                </div>`
            );
        }
    });
}

/**
 * Serialize form data to a multi-dimensional array.
 */
function hwlSerializeArray(data)
{
    var result = [];
    data.each(function () {
        var fields = {};
        jQuery.each(jQuery(this).serializeArray(), function () {
            fields[ this.name ] = this.value;
        });
        result.push(fields);
    });
    return result;
}

/**
 * Remove selected row.
 *
 * @param rowId
 */
function hwlRemoveRow(rowId)
{
    jQuery('#' + rowId).remove();
}
