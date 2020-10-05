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

jQuery(document).ready(function($) {
    var omgf_admin = {
        empty_cache_directory_xhr : false,
        optimize_xhr : false,
        
        /**
         * Initialize all on click events.
         */
        init : function() {
            // Settings
            $('input[name="omgf_optimization_mode"]').on('click', this.toggle_optimization_mode_content);
            
            // Buttons
            $('.omgf-empty').on('click', this.empty_cache_directory);
            $('#omgf-optimize-settings-form').submit(this.show_loader_before_submit);
        },
    
        /**
         *
         */
        toggle_optimization_mode_content : function() {
            if (this.value == 'manual') {
                $('.omgf-optimize-fonts-manual').show();
                $('.omgf-optimize-fonts-automatic').hide();
            } else {
                $('.omgf-optimize-fonts-automatic').show();
                $('.omgf-optimize-fonts-manual').hide();
            }
        },
        
        
        
        /**
         * Empty queue, db and cache directory.
         */
        empty_cache_directory : function() {
            if (omgf_admin.empty_cache_directory_xhr) {
                omgf_admin.empty_cache_directory_xhr.abort();
            }
            
            omgf_admin.empty_cache_directory_xhr = $.ajax({
                type : 'POST',
                url : ajaxurl,
                data : {
                    action : 'omgf_ajax_empty_dir'
                },
                beforeSend : function() {
                    omgf_admin.show_loader();
                },
                complete : function() {
                    location.reload();
                }
            });
        },
    
        show_loader_before_submit: function(e) {
            omgf_admin.show_loader();
        },
        
        /**
         *
         */
        show_loader : function() {
            $('#wpcontent').append('<div class="omgf-loading"><span class="spinner is-active"></span></div>');
        }
    };
    
    omgf_admin.init();
    
    $('#omgf_relative_url').click(function() {
        if (this.checked === true) {
            $('#omgf_cdn_url').prop('disabled', true);
        } else {
            $('#omgf_cdn_url').prop('disabled', false);
        }
    });
});
