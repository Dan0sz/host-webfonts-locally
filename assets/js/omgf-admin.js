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
 * @copyright: © 2017 - 2023 Daan van den Bergh
 * @url      : https://ffw.press
 * * * * * * * * * * * * * * * * * * * */

jQuery(document).ready(function ($) {
    var omgf_admin = {
        ticker_items: document.querySelectorAll('.ticker-item'),
        ticker_index: 0,
        empty_cache_directory_xhr: false,
        optimize_xhr: false,
        cache_prefix: '-mod-',
        flush_action_init: $('.omgf-empty').data('init'),
        empty_action_nonce: $('.omgf-empty').data('nonce'),
        delete_log_xhr: false,
        delete_action_nonce: $('#omgf-delete-log').data('nonce'),

        /**
         * Initialize all on click events.
         */
        init: function () {
            // Settings
            $('.omgf-subsets').on('change', this.maybe_select_latin);
            $('.omgf-optimize-fonts-manage .unload').on('change', this.unload_stylesheets);
            $('.omgf-optimize-fonts-manage .unload, .omgf-optimize-fonts-manage .fallback-font-stack select').on('change', this.generate_cache_key);
            $('.omgf-optimize-fonts-manage .unload').on('change', this.toggle_preload);
            $('.omgf-optimize-fonts-manage .preload').on('change', this.toggle_unload);
            $('.omgf-optimize-fonts-manage .preload').on('change', this.maybe_show_preload_warning);
            $('.omgf-optimize-fonts-manage .unload-italics').on('click', this.unload_italics);
            $('.omgf-optimize-fonts-manage .unload-all').on('click', this.unload_all);
            $('.omgf-optimize-fonts-manage .load-all').on('click', this.load_all);

            // Buttons (AJAX, etc.)
            $('#omgf-save-optimize, #omgf-optimize-again').on('click', function () { $('#omgf-optimize-settings-form #submit').click(); });
            $(document).on('click', 'a[id^=omgf-hide-notice-]', this.hide_notice);
            $('.omgf-remove-stylesheet').on('click', this.remove_stylesheet_from_db);
            $('.omgf-refresh, #omgf-cache-refresh').on('click', this.refresh_cache);
            $('.omgf-empty, #omgf-cache-flush').on('click', this.empty_cache_directory);
            $('#omgf-optimize-settings-form').on('submit', this.show_loader_before_submit);
            $('#omgf-delete-log').on('click', this.delete_log);
            $('.omgf-optimize-preload-warning-close').on('click', this.hide_preload_warning);
            $('.omgf-optimize-forbidden').on('click', this.wait_for_page_reload);

            // Ticker
            setInterval(this.loop_ticker_items, 4000);
        },

        /**
         * Also select Latin, if Latin Extended is selected.
         * 
         * @param {'change'} event 
         */
        maybe_select_latin: function (event) {
            var value = this.value,
                target = event.target,
                className = target.className,
                options = ['latin', 'latin-ext'];

            if (value === 'latin-ext') {
                options.forEach(function (element) {
                    var option = document.querySelector('.' + className + ' option[value=' + element + ']');

                    option.selected = true;
                });
            }

            var options = ['latin', 'vietnamese'];

            if (value === 'vietnamese') {
                options.forEach(function (element) {
                    var option = document.querySelector('.' + className + ' option[value=' + element + ']');

                    option.selected = true;
                });
            }
        },

        /**
         * 
         */
        loop_ticker_items: function () {
            omgf_admin.ticker_items.forEach(function (item, index) {
                if (index == omgf_admin.ticker_index) {
                    $(item).fadeIn(500);
                } else {
                    $(item).hide(0);
                }
            });

            omgf_admin.ticker_index++;

            if (omgf_admin.ticker_index === omgf_admin.ticker_items.length) {
                omgf_admin.ticker_index = 0;
            }
        },

        /**
         * 
         */
        hide_notice: function () {
            var warning_id = $(this).data('warning-id');
            var nonce = $(this).data('nonce');

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_hide_notice',
                    warning_id: warning_id,
                    nonce: nonce
                },
                beforeSend: function () {
                    omgf_admin.show_loader();
                },
                complete: function (result) {
                    if (result.responseJSON !== undefined && result.responseJSON.data !== undefined) {
                        $('#task-manager-notice-row').replaceWith(result.responseJSON.data);
                    }

                    omgf_admin.hide_loader();
                }
            });
        },

        remove_stylesheet_from_db: function () {
            var handle = $(this).data('handle');

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_remove_stylesheet_from_db',
                    handle: handle,
                    nonce: omgf_admin.empty_action_nonce,
                },
                beforeSend: function () {
                    omgf_admin.show_loader();
                },
                complete: function () {
                    location.reload();
                }
            });
        },

        /**
         * Changes the cache keys to force a refresh with the current settings.
         */
        refresh_cache: function () {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_refresh_cache',
                    nonce: omgf_admin.empty_action_nonce,
                },
                beforeSend: function () {
                    $('.omgf-optimize-fonts-manage table tbody').each(function (key, elem) {
                        omgf_admin.generate_cache_key(elem);
                    });
                },
                complete: function () {
                    $('#submit').click();
                }
            });
        },

        /**
         * Populates the omgf_unload_stylesheets hidden field.
         */
        unload_stylesheets: function () {
            var handle = $(this).closest('tbody');
            var id = handle[0].id;
            var checked = $('tbody' + '#' + id + ' input.unload:checked').length;
            var total = $('tbody' + '#' + id + ' input.unload').length;
            var unloaded_stylesheets_option = $('#unload_stylesheets');
            var unloaded_stylesheets = unloaded_stylesheets_option.val().split(',');

            if (checked === total) {
                if (unloaded_stylesheets.indexOf(id) === -1) {
                    unloaded_stylesheets.push(id);
                }

                unloaded_stylesheets.join();

                unloaded_stylesheets_option.val(unloaded_stylesheets);
            } else {
                position = unloaded_stylesheets.indexOf(id);

                if (~position) unloaded_stylesheets.splice(position, 1);

                unloaded_stylesheets_option.val(unloaded_stylesheets);
            }
        },

        /**
         * Generate a new cache key upon each unload change.
         */
        generate_cache_key: function (element = null) {
            if (element.target === undefined) {
                var current_handle = $(element).attr('id');
            } else {
                var current_handle = $(this).data('handle');
            }

            var cache_keys_input = $('#cache_keys'),
                cache_keys = cache_keys_input.val().split(','),
                checked = $('#' + current_handle + ' input.unload:checked').length,
                total = $('#' + current_handle + ' input.unload').length,
                cache_key_index = cache_keys.findIndex((key, index) => {
                    if (key.indexOf(current_handle) !== -1) {
                        return true;
                    }
                }),
                no_cache_key = false;

            /**
             * If no or all boxes are checked, (re-)set cache key to default (without random string).
             */
            if (this.nodeName !== 'SELECT' && (checked === 0 || checked === total)) {
                cache_keys[cache_key_index] = current_handle;

                no_cache_key = true;
            }

            if (no_cache_key === true) {
                cache_keys_input.val(cache_keys.join());

                return;
            }

            /**
             * Generate a unique cache key if some of this stylesheet's fonts are unloaded.
             */
            if (cache_key_index !== -1) {
                var current_cache_key = cache_keys[cache_key_index],
                    cache_key = omgf_admin.cache_prefix + Math.random().toString(36).substring(2, 7);

                if (current_cache_key.indexOf(omgf_admin.cache_prefix) !== -1) {
                    var parts = current_cache_key.split(omgf_admin.cache_prefix),
                        last_part = omgf_admin.get_last_element_index(parts);
                    parts[last_part] = Math.random().toString(36).substring(2, 7);
                    current_cache_key = parts[0];
                    cache_key = omgf_admin.cache_prefix + parts[last_part];
                }

                cache_keys[cache_key_index] = current_cache_key + cache_key;
            }

            cache_keys_input.val(cache_keys.join());
        },

        /**
         * Toggle preload option associated with this unload option.
         */
        toggle_preload: function () {
            omgf_admin.toggle(this, 'preload');
        },

        /**
         * Toggle unload option associated with the current preload option.
         */
        toggle_unload: function () {
            omgf_admin.toggle(this, 'unload');
        },

        /**
         * Show users a preload warning when amount of checked preloads equals 5.
         */
        maybe_show_preload_warning: function () {
            if ($('.' + this.className + ':checked').length === 5) {
                $('#omgf-optimize-preload-warning').fadeIn('300');
            }
        },

        /**
         * When the cross is clicked, hide the preload warning.
         */
        hide_preload_warning: function () {
            $('#omgf-optimize-preload-warning').fadeOut('100');
        },

        /**
         * 
         */
        wait_for_page_reload: function (e) {
            e.preventDefault();

            omgf_admin.show_loader();

            var interval = window.setInterval(function () {
                if (document.hasFocus()) {
                    window.location.reload();
                    window.clearInterval(interval);
                }
            }, 500);

            window.open(this.href, '_blank');
        },

        /**
         * Unload all italic styles for current font family.
         */
        unload_italics: function (e) {
            e.preventDefault();

            var id = $(this).parents('.font-family').data('id');
            var unloads = $('.unload');

            unloads.each(function (index, item) {
                if (item.value.includes('italic') && item.dataset.fontId === id && item.checked === false) {
                    item.click();
                }
            });
        },

        /**
         * Unload all fonts for current font family.
         */
        unload_all: function (e, self = this) {
            var id = $(self).parents('.font-family').data('id'),
                unloads = $('input.unload[data-font-id="' + id + '"]');

            unloads.each(function (i, item) {
                if (item.checked === false) {
                    item.click();
                }
            });
        },

        /**
         * Uncheck all unload checkboxes for the current font family.
         */
        load_all: function () {
            var id = $(this).parents('.font-family').data('id'),
                unloads = $('input.unload[data-font-id="' + id + '"]');

            unloads.each(function (i, item) {
                if (item.checked === true) {
                    item.click();
                }
            });
        },

        /**
         * Toggle a checkbox.
         *
         * @param elem
         * @param option
         */
        toggle: function (elem, option) {
            var this_option = $(elem);
            var other_option = $('.' + option + '-' + this_option.data('font-id') + '-' + this_option.val() + ' .' + option);

            if (elem.checked) {
                other_option.attr('disabled', true);
            } else {
                other_option.attr('disabled', false);
            }
        },

        /**
         *
         * @param array
         * @returns {number}
         */
        get_last_element_index: function (array) {
            return array.length - 1;
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
                    action: 'omgf_empty_dir',
                    nonce: omgf_admin.empty_action_nonce,
                    init: omgf_admin.flush_action_init
                },
                beforeSend: function () {
                    omgf_admin.show_loader();
                },
                complete: function () {
                    location.reload();
                }
            });
        },

        /**
         * 
         */
        delete_log: function () {
            if (omgf_admin.delete_log_xhr) {
                omgf_admin.delete_log_xhr.abort();
            }

            omgf_admin.delete_log_xhr = $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'omgf_delete_log',
                    nonce: omgf_admin.delete_action_nonce,
                },
                beforeSend: function () {
                    omgf_admin.show_loader();
                },
                complete: function () {
                    location.reload();
                }
            });
        },

        /**
         * 
         */
        show_loader_before_submit: function () {
            omgf_admin.show_loader();
        },

        hide_loader: function () {
            $('.omgf-loading').fadeOut(300, function () { $('.omgf-loading').remove() });
        },

        /**
         *
         */
        show_loader: function () {
            $('#wpwrap').append('<div class="omgf-loading"><span class="spinner is-active"></span></div>');
        }
    };

    omgf_show_loader = omgf_admin.show_loader;
    omgf_unload_all = omgf_admin.unload_all;
    omgf_load_all = omgf_admin.load_all;

    omgf_admin.init();
});
