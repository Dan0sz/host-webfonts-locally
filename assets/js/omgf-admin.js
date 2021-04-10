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
 * @copyright: (c) 2021 Daan van den Bergh
 * @url      : https://daan.dev
 * * * * * * * * * * * * * * * * * * * */

jQuery(document).ready(function ($) {
    var omgf_admin = {
        empty_cache_directory_xhr: false,
        optimize_xhr: false,
        cache_prefix: '-ul-',

        /**
         * Initialize all on click events.
         */
        init: function () {
            // Settings
            $('input[name="omgf_optimization_mode"]').on('click', this.toggle_optimization_mode_content);
            $('.omgf-optimize-fonts-manage .unload').on('change', this.unload_stylesheets);
            $('.omgf-optimize-fonts-manage .unload').on('change', this.generate_cache_key);
            $('.omgf-optimize-fonts-manage .unload').on('change', this.toggle_preload);
            $('.omgf-optimize-fonts-manage .preload').on('change', this.toggle_unload);
            $('.omgf-optimize-fonts-manage .unload-italics').on('click', this.unload_italics);
            $('.omgf-optimize-fonts-manage .unload-all').on('click', this.unload_all);
            $('.omgf-optimize-fonts-manage .load-all').on('click', this.load_all);

            // Buttons
            $('.omgf-empty').on('click', this.empty_cache_directory);
            $('#omgf-optimize-settings-form').submit(this.show_loader_before_submit);
        },

        /**
         *
         */
        toggle_optimization_mode_content: function () {
            if (this.value == 'manual') {
                $('.omgf-optimize-fonts-manual').show();
                $('.omgf-optimize-fonts-automatic').hide();
            } else {
                $('.omgf-optimize-fonts-automatic').show();
                $('.omgf-optimize-fonts-manual').hide();
            }
        },

        /**
         * Populates the omgf_unload_stylesheets hidden field.
         */
        unload_stylesheets: function () {
            var handle = $(this).closest('tbody');
            var id = handle[0].id;
            var checked = $('tbody' + '#' + id + ' input.unload:checked').length;
            var total = $('tbody' + '#' + id + ' input.unload').length;
            var unloaded_stylesheets_option = $('#omgf_unload_stylesheets');
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
        generate_cache_key: function () {
            var current_handle = $(this).data('handle'),
                cache_keys_input = $('#omgf_cache_keys'),
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
            if (checked === 0 || checked === total) {
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
                var current_cache_key = cache_keys[cache_key_index];

                var cache_key = omgf_admin.cache_prefix + Math.random().toString(36).substring(2, 7);

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
         * Unload all italic styles for current font family.
         */
        unload_italics: function (e) {
            e.preventDefault();

            var id = $(this).parents('.font-family').data('id');
            var unloads = $('.unload');

            unloads.each(function (index, item) {
                if (item.value.includes('italic') && item.dataset.fontId == id && item.checked == false) {
                    item.click();
                }
            });
        },

        /**
         * Unload all fonts for current font family.
         */
        unload_all: function (e) {
            e.preventDefault();

            var id = $(this).parents('.font-family').data('id');
            var unloads = $('.unload');

            unloads.each(function (index, item) {
                if (item.dataset.fontId == id && item.checked == false) {
                    item.click();
                }
            });
        },

        /**
         * Uncheck all unload checkboxes for the current font family.
         */
        load_all: function (e) {
            e.preventDefault();

            var id = $(this).parents('.font-family').data('id');
            var unloads = $('.unload');

            unloads.each(function (index, item) {
                if (item.dataset.fontId == id && item.checked == true) {
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
            var other_option = $('.' + option + '-' + this_option.data('handle') + '-' + this_option.data('font-id') + '-' + this_option.val() + ' .' + option);

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
                    action: 'omgf_ajax_empty_dir'
                },
                beforeSend: function () {
                    omgf_admin.show_loader();
                },
                complete: function () {
                    location.reload();
                }
            });
        },

        show_loader_before_submit: function (e) {
            omgf_admin.show_loader();
        },

        /**
         *
         */
        show_loader: function () {
            $('#wpcontent').append('<div class="omgf-loading"><span class="spinner is-active"></span></div>');
        }
    };

    omgf_admin.init();

    $('#omgf_relative_url').click(function () {
        if (this.checked === true) {
            $('#omgf_cdn_url').prop('disabled', true);
        } else {
            $('#omgf_cdn_url').prop('disabled', false);
        }
    });
});
