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
            $('#omgf-search-subsets').on('click', this.click_search);
            $('#omgf-auto-detect').on('click', this.enable_auto_detect);
            $('#omgf-download').on('click', this.download_fonts);
            $('#omgf-generate').on('click', this.generate_stylesheet);
            $('#omgf-empty').on('click', this.empty_cache_directory);
        },

        /**
         * Triggered when Search is clicked.
         */
        click_search: function () {
            searchQuery = $('#omgf-search').val().replace(/\s/g, '-').toLowerCase();
            omgf_admin.search_subsets(searchQuery);
        },

        /**
         * Enable Auto Detect.
         */
        enable_auto_detect: function () {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_enable_auto_detect'
                },
                dataType: 'json',
                complete: function() {
                    location.reload();
                }
            })
        },

        /**
         * Triggered by Click Search
         *
         * @param query
         */
        search_subsets: function (query) {
            let searchButton = $('#omgf-search-subsets');

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_search_font_subsets',
                    search_query: query
                },
                dataType: 'json',
                beforeSend: function() {
                    hwlUpdateInputValue(searchButton, 'Searching...', '0 20px');
                },
                complete: function() {
                    location.reload();
                }
            });
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
