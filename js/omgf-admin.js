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

        // Settings screen elements
        $nav: $('.omgf-nav span'),
        $nav_generate_stylesheet: $('.generate-stylesheet'),
        $nav_advanced_settings: $('.advanced-settings'),
        $generate_stylesheet_form: $('#omgf-generate-stylesheet-form'),
        $advanced_settings_form: $('#omgf-advanced-settings-form'),
        $welcome_panel: $('#omgf-welcome-panel'),
        $welcome_panel_clone: $('#omgf-welcome-panel-clone'),

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
            // Nav
            this.$nav.on('click', this.toggle_section);

            // Sidebar
            $(window).scroll(this.scroll_sidebar);

            // Generate Stylesheet Section
            this.$subsets.on('click', function () { setTimeout(omgf_admin.search_google_fonts, this.timeout); });
            this.$preload_font_styles.on('click', this.manage_preload_queue);
            this.$removed_font_style.on('click', this.manage_removal_queue);

            // Buttons
            $('#omgf-search-subsets').on('click', this.click_search);
            $('#omgf-auto-detect, .help.auto-detect').on('click', this.enable_auto_detect);
            $('.omgf-apply.remove').on('click', this.process_removal_queue);
            $('.omgf-apply.preload').on('click', this.preload_font_style);
            $('#omgf-download, .help.download-fonts').on('click', this.download_fonts);
            $('#omgf-generate, .help.generate-stylesheet').on('click', this.generate_stylesheet);
            $('#omgf-empty').on('click', this.empty_cache_directory);
        },

        /**
         * Toggle settings sections.
         */
        toggle_section: function () {
            omgf_admin.$nav.removeClass('selected');
            $(this).addClass('selected');

            if (this.classList.contains('generate-stylesheet')) {
                omgf_admin.$generate_stylesheet_form.fadeIn();
                omgf_admin.$advanced_settings_form.fadeOut(100);
            } else {
                omgf_admin.$advanced_settings_form.fadeIn();
                omgf_admin.$generate_stylesheet_form.fadeOut(100);
            }
        },

        /**
         * Scroll sidebar in settings.
         */
        scroll_sidebar: function () {
            /**
             * Make sure widgetClone has correct width, since its
             * position is fixed.
             */
            widgetWidth = omgf_admin.$welcome_panel.width();
            omgf_admin.$welcome_panel_clone.width(widgetWidth);

            /**
             * Only appear if widget reaches top of screen.
             */
            widgetOffset = omgf_admin.$welcome_panel.offset().top - 20;

            if ($(window).scrollTop() >= widgetOffset) {
                omgf_admin.$welcome_panel.css('opacity', '0');
                omgf_admin.$welcome_panel_clone.css('top', 20);
                omgf_admin.$welcome_panel_clone.show();
            } else {
                omgf_admin.$welcome_panel.css('opacity', '1');
                omgf_admin.$welcome_panel_clone.hide();
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
                    location.reload();
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
         * If any fonts are checked for preload, display Preload apply button.
         */
        manage_preload_queue: function () {
            omgf_admin.toggle_button($('.omgf-font-preload:checked'), $('.omgf-apply.preload'));
        },

        /**
         * Enqueue for removal or undo removal of current item.
         */
        manage_removal_queue: function () {
            if (this.classList.contains('notice-dismiss')) {
                omgf_admin.enqueue_for_removal(this);
            } else {
                omgf_admin.undo_removal(this);
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

            omgf_admin.toggle_button($('.dashicons-undo'), $('.omgf-apply.remove'));
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

            omgf_admin.toggle_button($('.dashicons-undo'), $('.omgf-apply.remove'));
        },

        /**
         * @param conditional
         * @param button
         */
        toggle_button: function(conditional, button) {
            help_text = $('span.omgf-apply');

            if (conditional.length > 0) {
                button.show();
            } else {
                button.hide();
            }

            if ($('.omgf-apply.button').is(':visible')) {
                help_text.show();
            } else {
                help_text.hide();
            }
        },

        /**
         * Processes the removal queue.
         */
        process_removal_queue: function() {
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
            });
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
