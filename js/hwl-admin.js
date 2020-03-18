/* * * * * * * * * * * * * * * * * * * * *
 *
 *  ██████╗ ███╗   ███╗ ██████╗ ███████╗
 * ██╔═══██╗████╗ ████║██╔════╝ ██╔════╝
 * ██║   ██║██╔████╔██║██║  ███╗█████╗
 * ██║   ██║██║╚██╔╝██║██║   ██║██╔══╝
 * ╚██████╔╝██║ ╚═╝ ██║╚██████╔╝██║
 *  ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚═╝
 *
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

/**
 * When user is done typing, trigger search.
 */
function hwlClickSearch()
{
    let input   = jQuery('#search-field');
    searchQuery = input.val().replace(/\s/g, '-').toLowerCase();
    hwlSearchFontSubsets(searchQuery)
}

/**
 * Return available subsets for searched font.
 *
 * @param queriedFonts
 */
function hwlSearchFontSubsets(queriedFonts)
{
    let searchField  = jQuery('#search-field');
    let searchButton = jQuery('#search-btn');

    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'omgf_ajax_search_font_subsets',
            search_query: queriedFonts
        },
        dataType: 'json',
        beforeSend: function() {
            hwlUpdateInputValue(searchButton, 'Searching...', '0 20px');
            searchField.val('');
        },
        complete: function() {
            location.reload();
        }
    });
}

function hwlAutoDetectFonts()
{
    let detectButton = jQuery('#detect-btn');

    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'omgf_ajax_auto_detect'
        },
        dataType: 'json',
        beforeSend: function() {
            if (omgf.auto_detect_enabled === '' && omgf.detected_fonts === '') {
                hwlCleanQueue();
            }
        },
        error: function (response) {
            displayError(response.responseJSON.data);
        },
        complete: function(response) {
            if (omgf.auto_detect_enabled === '' && omgf.detected_fonts === '') {
                hwlScrollTop();
                jQuery('#hwl-admin-notices').append("<div class='notice notice-success is-dismissible'><p>" + response.responseJSON.data + "</p></div>");
                hwlUpdateInputValue(detectButton, 'Enabled', '0 38px 1px');
            } else {
                try {
                    hwlUpdateInputValue(detectButton, 'Auto-detect', '0 36px 1px');
                    hwlRenderAvailableSubsets(response.responseJSON);
                } catch(error) {
                    hwlScrollTop();
                    jQuery('#hwl-admin-notices').append("<div class='notice notice-success is-dismissible'><p>Oops! Something went wrong. " + error + ". <a href='javascript:location.reload();'>Refresh this page</a> and try again. If it still fails, <a href='javascript:hwlEmptyDir();'>empty the cache</a> and <a href='javascript:location.reload();'>refresh this page</a>.");
                }
            }
        }
    })
}

/**
 * Run this after refresh when both statements return true.
 */
if (omgf.auto_detect_enabled !== '' && omgf.detected_fonts !== '') {
    hwlAutoDetectFonts();
}

/**
 * Print available subsets
 *
 * @param response
 */
function hwlRenderAvailableSubsets(response)
{
    let data        = response.data;
    let subsetArray = data.subsets === undefined ? data : data.subsets;

    for (let ii = 0; ii < subsetArray.length; ii++) {
        subsets = subsetArray[ii]['subsets'];
        family = subsetArray[ii]['family'];

        id = subsetArray[ii]['id'];
        usedStyles = subsetArray[ii]['used_styles'];

        if (subsets === null) {
            subsets = ['latin'];
        }

        length = subsets.length;
        renderedSubsets = [];

        for (let iii = 0; iii < length; iii++) {
            renderedSubsets[iii] = `<td><label><input name="${id}" value="${subsets[iii]}" type="checkbox" onclick='hwlGenerateSearchQuery("${id}", ${JSON.stringify(usedStyles)})' />${subsets[iii]}</label></td>`;
        }

        jQuery('#hwl-subsets').append('<tr valign="top" id="' + id + '"><td><input type="text" class="hwl-subset-font-family" value="' + family + '" readonly/></td>' + renderedSubsets + '</tr>');
        jQuery('#hwl-results').append("<tbody id='" + 'hwl-section-' + id + "'></tbody>");
    }

    if (data['auto-detect'] === true) {
        jQuery('#hwl-subsets input[type="checkbox"]').each(function() {
            /**
             * These fonts are used by WP Admin. But might be used by front-end as well.
             * That's why we do return them, but do not trigger a search by default.
             */
            if (this.getAttribute('name') !== 'open-sans' && this.getAttribute('name') !== 'noto-serif') {
                this.click();
            }
        });
    }
}

jQuery(document).ready(function ($) {
    var omgf_admin = {
        // XHR
        search_fonts_xhr: false,
        preload_font_style_xhr: false,
        refresh_font_style_list_xhr: false,
        download_fonts_xhr: false,
        generate_stylesheet_xhr: false,
        empty_cache_directory_xhr: false,

        // Data
        font_families: [],
        preload_font_styles: [],
        font_style_list: [],

        // Selectors
        $font_families: $('.omgf-subset-font-family'),
        $subsets: $('.omgf-subset'),
        $loading: $('#hwl-warning .loading'),
        $preload_font_styles: $('.omgf-font-preload'),
        $preload_font_styles_checked: $('.omgf-font-preload:checked'),
        $removed_font_style: $('.omgf-font-remove'),

        /**
         * Initialize all on click events.
         */
        init: function () {
            // Generate Stylesheet Section
            this.$subsets.on('click', function () { setTimeout(omgf_admin.search_google_fonts, 1500)});
            this.$preload_font_styles.on('click', function() { setTimeout(omgf_admin.preload_font_style, 1500)});
            this.$removed_font_style.on('click', this.remove_font_style);

            // Buttons
            $('#omgf-download').on('click', this.download_fonts);
            $('#omgf-generate').on('click', this.generate_stylesheet);
            $('#omgf-empty').on('click', this.empty_cache_directory);
        },

        /**
         * Triggered on Search
         */
        search_google_fonts: function () {
            if (omgf_admin.search_fonts_xhr) {
                omgf_admin.search_fonts_xhr.abort();
            }

            omgf_admin.font_families = omgf_admin.$font_families.map(function () {
                return $(this).data('font-family');
            }).get();

            omgf_admin.font_families.forEach(function(font, index) {
                omgf_admin.font_families[index] = {};
                omgf_admin.font_families[index].subsets = [];

                $('input[name="' + font + '"]:checked').each(function(i) {
                    omgf_admin.font_families[index].family = font;
                    omgf_admin.font_families[index].subsets[i] = this.value;
                });
            });

            omgf_admin.search_fonts_xhr = $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_search_google_fonts',
                    search_google_fonts: omgf_admin.font_families,
                },
                dataType: 'json',
                beforeSend: function() {
                    omgf_admin.$loading.show()
                },
                complete: function () {
                    location.reload()
                }
            });
        },

        /**
         * Triggered when preload is checked. If multiple are checked, all are processed at once.
         */
        preload_font_style: function() {
            if (omgf_admin.preload_font_style_xhr) {
                omgf_admin.preload_font_style_xhr.abort();
            }

            omgf_admin.preload_font_styles = omgf_admin.$preload_font_styles_checked.map(function () {
                return $(this).data('preload');
            }).get();

            omgf_admin.preload_font_style_xhr = $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_preload_font_style',
                    preload_font_styles: omgf_admin.preload_font_styles
                },
                dataType: 'json',
                complete: function() {
                    location.reload();
                }
            });
        },

        /**
         * Triggered when remove is clicked. If multiple are checked, all are processed at once.
         */
        remove_font_style: function() {
            row = $(this).data('row');

            $('#' + row).remove();

            setTimeout(omgf_admin.refresh_font_style_list, 1500);
        },

        /**
         * Triggered after remove to sync data to backend.
         */
        refresh_font_style_list: function () {
            if (omgf_admin.refresh_font_style_list_xhr) {
                omgf_admin.refresh_font_style_list_xhr.abort();
            }

            omgf_admin.font_style_list = $('.omgf-font-style').map(function () {
                return $(this).data('font-id');
            }).get();

            omgf_admin.refresh_font_style_list_xhr = $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_refresh_font_style_list',
                    font_styles: omgf_admin.font_style_list
                },
                dataType: 'json',
                complete: function() {
                    location.reload();
                }
            });
        },

        /**
         * Download fonts and refresh window.
         */
        download_fonts: function () {
            if (omgf_admin.download_fonts_xhr) {
                omgf_admin.download_fonts_xhr.abort();
            }

            let downloadButton = $('#save-btn');

            omgf_admin.download_fonts_xhr = $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_download_fonts'
                },
                beforeSend: function() {
                    hwlUpdateStatusBar(0);
                    hwlGetDownloadStatus();
                    hwlUpdateInputValue(downloadButton, 'Downloading...', '0 14px 1px');
                },
                complete: function() {
                    location.reload();
                }
            })
        },

        /**
         * Generate stylesheet and refresh window.
         */
        generate_stylesheet: function () {
            let generateButton = $('#generate-btn');

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_generate_styles',
                },
                beforeSend: function() {
                    hwlUpdateInputValue(generateButton, 'Generating...', '0 33px 1px');
                },
                complete: function() {
                    location.reload();
                }
            })
        },

        empty_cache_directory: function () {
            if (omgf_admin.empty_cache_directory_xhr) {
                omgf_admin.empty_cache_directory_xhr.abort();
            }

            omgf_admin.empty_cache_directory_xhr = $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_empty_dir'
                },
                beforeSend: function() {

                },
                complete: function() {
                    location.reload();
                }
            });
        }
    };

    omgf_admin.init();
});

/**
 * Generate search query for selected subsets
 *
 * @param id
 * @param usedStyles
 */
function hwlGenerateSearchQuery(id, usedStyles = null)
{
    let subsets = [];
    checked = jQuery("input[name='" + id + "']:checked");

    jQuery.each(checked, function() {
        subsets.push(jQuery(this).val());
    });

    subsets.join();
    hwlSearchGoogleFonts(id, subsets, usedStyles);
}

/**
 * Triggers the AJAX-request to Google Webfont Helper.
 *
 * @param id
 * @param subsets
 * @param usedStyles
 */
function hwlSearchGoogleFonts(id, subsets, usedStyles = null)
{
    let loadingDiv = jQuery('#hwl-warning .loading');
    let errorDiv = jQuery('#hwl-warning .error');
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'omgf_ajax_search_google_fonts',
            search_query: id,
            search_subsets: subsets,
            used_styles: usedStyles
        },
        dataType: 'json',
        beforeSend: function() {
            loadingDiv.show()
        },
        error: function(response) {
            displayError(response.responseJSON.data);
        },
        success: function(response) {
            loadingDiv.hide();
            errorDiv.hide();
            if(response['responseText'] !== 'Not found') {
                hwlRenderAvailableFonts(response);
            } else {
                errorDiv.show();
            }
        }
    })
}

function displayError(message) {
    let loadingDiv = jQuery('#hwl-warning .loading');
    let errorDiv = jQuery('#hwl-warning .error');

    loadingDiv.hide();
    errorDiv.show();
    hwlScrollTop();
    jQuery('#hwl-admin-notices').html("<div class='notice notice-success is-dismissible'><p>Oops! Something went wrong. " + message + ".");
}

/**
 * Displays the search results
 *
 * @param results
 */
function hwlRenderAvailableFonts(results)
{
    variants = results.data.variants === undefined ? results.responseJSON.data.variants : results.data.variants;
    length = variants.length;
    renderedFonts = [];
    for(iii = 0; iii < length; iii++) {
        fontFamily = variants[iii].fontFamily.replace(/'/g, '');
        fontId = variants[iii].id;
        font = fontFamily.replace(/\s+/g, '-').toLowerCase() + '-' + variants[iii].id;
        fontWeight = variants[iii].fontWeight;
        fontStyle = variants[iii].fontStyle;
        fontLocal = variants[iii].local;
        renderedFonts[iii] = `<tr id="row-${font}" valign="top" align="center">
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
                                        <input type="checkbox" value="1" name="caos_webfonts_array][${font}][preload]" />
                                    </td>
                                    <td>
                                        <input type="hidden" value="${fontId}" name="caos_webfonts_array][${font}][id]" />
                                        <input type="hidden" value="${fontLocal}" name="caos_webfonts_array][${font}][local]" />
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
        selectedSubsets.join();

        availableSubsets = [];
        jQuery.each(checkboxes, function() {
            availableSubsets.push(jQuery(this).val());
        });
        availableSubsets.join();

        family = jQuery(this).find('.hwl-subset-font-family').val();

        subsets[id] = {};
        subsets[id]['family'] = {};
        subsets[id]['family'] = family;
        subsets[id]['selected'] = {};
        subsets[id]['selected'] = selectedSubsets;
        subsets[id]['available'] = {};
        subsets[id]['available'] = availableSubsets;
    });

    return subsets;
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
            action: 'omgf_ajax_get_download_status'
        },
        dataType: 'text json',
        success: function(response) {
            downloaded = response.downloaded;
            total = response.total;
            progress = (100 / total) * downloaded;

            hwlUpdateStatusBar(progress);
        }
    });
    downloadStatus = setTimeout(hwlGetDownloadStatus, 1000);
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
 * Updates the value of any input to show status updates
 *
 * @param input
 * @param text
 * @param padding
 */
function hwlUpdateInputValue(input, text, padding = '0 10px 1px')
{
    input.val(text);
    input.css('padding', padding);
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
            action: 'omgf_ajax_empty_dir'
        },
        success: function() {
            hwlCleanQueue();
            hwlUpdateStatusBar(0)
        }
    });
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
            action: 'omgf_ajax_clean_queue'
        },
        success: function() {
            jQuery('.caos-status-progress-percentage').html('0%');
            jQuery('#hwl-subsets, tbody[id^=hwl-section]').empty()
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
    let result = [];
    data.each(function() {
        fields = {};
        jQuery.each(jQuery(this).serializeArray(), function() {
            fields[this.name] = this.value
        });
        result.push(fields)
    });
    return result
}

/**
 * TODO: Move all above functions within document.ready().
 */
jQuery(document).ready(function($) {
    /**
     * Toggle different options that aren't compatible with each other.
     */
    $('#omgf_web_font_loader, #omgf_preload').click(function () {
        if (this.className === 'omgf_web_font_loader' && this.checked === true) {
            $('#omgf_preload').attr('checked', false);
        }

        if (this.className === 'omgf_preload' && this.checked === true) {
            $('#omgf_web_font_loader').attr('checked', false);
        }
    });

    $('#omgf_relative_url').click(function () {
        if (this.checked === true) {
            $('#omgf_cdn_url').prop('disabled', true);
        } else {
            $('#omgf_cdn_url').prop('disabled', false);
        }
    })
});
