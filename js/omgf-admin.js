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
 * @copyright: (c) 2020 Daan van den Bergh
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
        $loader: $('.omgf-loading'),
        $font_families: $('.omgf-subset-font-family'),
        $subsets: $('.omgf-subset'),
        $preload_font_styles: $('.omgf-font-preload'),
        $removed_font_style: $('.omgf-font-remove'),

        // Timeout for User Interaction
        timeout: 2000,

        /**
         * Initialize all on click events.
         */
        init: function () {
            // Generate Stylesheet Section
            this.$subsets.on('click', function () { setTimeout(omgf_admin.search_google_fonts, this.timeout)});
            this.$preload_font_styles.on('click', function() { setTimeout(omgf_admin.preload_font_style, this.timeout)});
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
            searchQuery = $('#omgf-search').val();
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
                beforeSend: function () {
                    omgf_admin.show_loader('.omgf-search-box');
                },
                complete: function () {
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
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_search_font_subsets',
                    search_query: query
                },
                dataType: 'json',
                beforeSend: function () {
                    omgf_admin.show_loader('.omgf-search-box')
                },
                complete: function() {
                    location.reload();
                }
            });
        },

        /**
         * Show loader on element
         *
         * @param element
         */
        show_loader: function (element) {
            let clone = omgf_admin.$loader.clone();

            $(element).append(clone).css({
                'position': 'relative',
                'opacity': '0.5'
            });

            clone.show();
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
                omgf_admin.font_families[index].selected_subsets = [];

                $('input[data-subset-font-family="' + font + '"]:checked').each(function(i) {
                    omgf_admin.font_families[index].subset_font = font;
                    omgf_admin.font_families[index].selected_subsets[i] = this.value;
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
                    omgf_admin.show_loader('#omgf-font-styles-list');
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

            omgf_admin.preload_font_styles = $('.omgf-font-preload:checked').map(function () {
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
                success: function () {
                    location.reload();
                }
            });
        },

        /**
         * Triggered when remove is clicked. If multiple are checked, all are processed at once.
         */
        remove_font_style: function() {
            row = '#' + $(this).data('row');

            omgf_admin.show_loader(row);
            $(row).removeClass('omgf-font-style');

            setTimeout(omgf_admin.refresh_font_style_list, this.timeout);
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
                success: function() {
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

            omgf_admin.download_fonts_xhr = $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_download_fonts'
                },
                beforeSend: function() {
                    $('#omgf-download').attr('disabled', true);
                    omgf_admin.show_loader('.omgf-search-section');
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
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_generate_styles',
                },
                beforeSend: function() {
                    $('#omgf-generate').attr('disabled', true);
                    omgf_admin.show_loader('.omgf-search-section');
                },
                complete: function() {
                    location.reload();
                }
            })
        },

        /**
         * Empty queue, db and cache directory.
         */
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
                    omgf_admin.show_loader('.omgf-search-section');
                },
                complete: function() {
                    location.reload();
                }
            });
        },
    };

    omgf_admin.init();

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
