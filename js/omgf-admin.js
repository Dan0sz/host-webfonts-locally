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
        process_font_styles_xhr: false,
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
        $manage_font_styles: $('.omgf-font-preload, .omgf-font-remove'),

        /**
         * Initialize all on click events.
         */
        init: function () {
            // Manage queues
            /**
             * TODO: Save state on pageload, to prevent display of apply-button when current selection matches saved state.
             */
            this.$subsets.on('click', this.manage_subset_queue);
            this.$manage_font_styles.on('click', this.manage_font_styles_queues);

            // Buttons
            $('#omgf-search-subsets').on('click', this.click_search);
            $('#omgf-auto-detect, .help.auto-detect').on('click', this.enable_auto_detect);
            $('.omgf-apply.font-styles-search').on('click', this.search_google_fonts);
            $('.omgf-apply.font-styles').on('click', this.process_font_styles_queue);
            $('#omgf-download, .help.download-fonts').on('click', this.download_fonts);
            $('#omgf-generate, .help.generate-stylesheet').on('click', this.generate_stylesheet);
            $('#omgf-empty').on('click', this.empty_cache_directory);
        },

        /**
         * If any subsets are checked for search.
         */
        manage_subset_queue: function () {
            section = $('#omgf-subsets');
            colspan = section.find("tr:first td").length - 1;
            $('.omgf-subsets-search').attr('colspan', colspan);

            omgf_admin.toggle_button($('.omgf-subset:checked'), $('.omgf-apply.font-styles-search'), section);
        },

        /**
         *
         */
        manage_font_styles_queues: function () {
            if (this.classList.contains('omgf-font-preload')) {
                omgf_admin.manage_preload_queue();
            } else {
                omgf_admin.manage_removal_queue(this);
            }
        },

        /**
         * If any fonts are checked for preload, display Preload apply button.
         */
        manage_preload_queue: function () {
            omgf_admin.toggle_stylesheet_apply_button();
        },

        /**
         * Enqueue for removal or undo removal of current item.
         */
        manage_removal_queue: function (element) {
            if (element.classList.contains('notice-dismiss')) {
                omgf_admin.enqueue_for_removal(element);
            } else {
                omgf_admin.undo_removal(element);
            }
        },

        /**
         * Add current item to removal queue.
         *
         * @param item
         */
        enqueue_for_removal: function (item) {
            row = '#' + $(item).data('row');

            $(row).css({
                'opacity': '0.5'
            });

            $(item).addClass('dashicons-before dashicons-undo');
            $(item).removeClass('notice-dismiss');
            $(row).removeClass('omgf-font-style');

            omgf_admin.toggle_stylesheet_apply_button();
        },

        /**
         * Remove current item from removal queue.
         *
         * @param item
         */
        undo_removal: function (item) {
            row = '#' + $(item).data('row');

            $(row).css({
                'opacity': '1'
            });

            $(item).removeClass('dashicons-before dashicons-undo');
            $(item).addClass('notice-dismiss');
            $(row).addClass('omgf-font-style');

            omgf_admin.toggle_stylesheet_apply_button();
        },

        /**
         *
         */
        toggle_stylesheet_apply_button: function () {
            omgf_admin.toggle_button($('.dashicons-undo, .omgf-font-preload:checked'), $('.omgf-apply.button.font-styles'), $('#omgf-font-styles-list'));
        },

        /**
         * @param conditional
         * @param button
         * @param section
         */
        toggle_button: function(conditional, button, section) {
            help_text = section.find('.omgf-apply.help');
            buttons   = section.find('.omgf-apply.button');

            if (conditional.length > 0) {
                button.show();
                help_text.show();
            } else {
                button.hide();
                help_text.hide();
            }
        },

        /**
         * Triggered when Search is clicked.
         */
        click_search: function () {
            searchQuery = $('#omgf-search').val();
            omgf_admin.search_subsets(searchQuery);
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
                    omgf_admin.show_loader('.omgf-search-box');
                },
                complete: function() {
                    location.reload();
                }
            });
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
            });
        },

        /**
         * Triggered on Search
         * TODO: Refactor to more sensible names.
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
                    location.reload();
                }
            });
        },

        /**
         *
         */
        process_font_styles_queue: function () {
            if (omgf_admin.process_font_styles_xhr) {
                omgf_admin.process_font_styles_xhr.abort();
            }

            omgf_admin.font_style_list = $('.omgf-font-style').map(function () {
                return $(this).data('font-id');
            }).get();

            omgf_admin.preload_font_styles = $('.omgf-font-preload:checked').map(function () {
                return $(this).data('preload');
            }).get();

            omgf_admin.process_font_styles_xhr = $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_ajax_process_font_styles_queue',
                    font_styles: omgf_admin.font_style_list,
                    preload_font_styles: omgf_admin.preload_font_styles
                },
                dataType: 'json',
                beforeSend: function() {
                    omgf_admin.show_loader('#omgf-font-styles-list');
                },
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
            });
        },

        /**
         * Generate stylesheet and refresh window.
         */
        generate_stylesheet: function () {
            if (omgf_admin.generate_stylesheet_xhr) {
                omgf_admin.generate_stylesheet_xhr.abort();
            }

            omgf_admin.generate_stylesheet_xhr = $.ajax({
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
            });
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

        /**
         * Show loader on element
         *
         * @param element
         */
        show_loader: function (element) {
            var clone = omgf_admin.$loader.clone();

            $(element).append(clone).css({
                'position': 'relative',
                'opacity': '0.5'
            });

            clone.show();
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
    });
});
