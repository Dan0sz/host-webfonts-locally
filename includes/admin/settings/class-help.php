<?php
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
 * @copyright: © 2022 Daan van den Bergh
 * @url      : https://ffw.press
 * * * * * * * * * * * * * * * * * * * */

defined('ABSPATH') || exit;

class OMGF_Admin_Settings_Help extends OMGF_Admin_Settings_Builder
{
    /**
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->title = __('Help & Documentation', $this->plugin_text_domain);

        // Title
        add_filter('omgf_help_content', [$this, 'do_title'], 10);

        // Content
        add_filter('omgf_help_content', [$this, 'do_content'], 20);
    }

    public function do_content()
    {
        $utmTags = '?utm_source=omgf&utm_medium=plugin&utm_campaign=support_tab';
        $tweetUrl = sprintf("https://twitter.com/intent/tweet?text=I+am+using+%s+to+speed+up+Google+Fonts+for+@WordPress!+Try+it+for+yourself:&via=Dan0sz&hashtags=GoogleFonts,WordPress,Pagespeed,Insights&url=%s", str_replace(' ', '+', apply_filters('omgf_settings_page_title', 'OMGF')), apply_filters('omgf_help_tab_plugin_url', 'https://wordpress.org/plugins/host-webfonts-local/'));
?>
        <div class="postbox">
            <div class="content">
                <h2><?= sprintf(__('Thank you for using %s!', $this->plugin_text_domain), apply_filters('omgf_settings_page_title', 'OMGF')); ?></h2>
                <p class="about">
                    <?= sprintf(__('Need help configuring %s? Please refer to the links below to get you started.', $this->plugin_text_domain), apply_filters('omgf_settings_page_title', 'OMGF')); ?>
                </p>
                <div class="column-container">
                    <div class="column">
                        <h3>
                            <?php _e('Need Help?', $this->plugin_text_domain); ?>
                        </h3>
                        <ul>
                            <li><a target="_blank" href="<?= apply_filters('omgf_settings_help_quick_start', 'https://docs.ffw.press/article/7-quick-start'); ?>"><i class="dashicons dashicons-controls-forward"></i><?= __('Quick Start Guide', $this->plugin_text_domain); ?></a></li>
                            <li><a target="_blank" href="<?= apply_filters('omgf_settings_help_user_manual', 'https://docs.ffw.press/category/4-omgf-pro'); ?>"><i class="dashicons dashicons-text-page"></i><?= __('User Manual', $this->plugin_text_domain); ?></a></li>
                            <li><a target="_blank" href="<?= apply_filters('omgf_settings_help_faq_link', 'https://docs.ffw.press/article/9-frequently-asked-question-faq'); ?>"><i class="dashicons dashicons-editor-help"></i><?= __('FAQ', $this->plugin_text_domain); ?></a></li>
                            <li><a target="_blank" href="<?= apply_filters('omgf_settings_help_troubleshooting_link', 'https://docs.ffw.press/category/37-omgf-pro---troubleshooting'); ?>"><i class="dashicons dashicons-sos"></i><?= __('Troubleshooting Guide', $this->plugin_text_domain); ?></a></li>
                            <li><a target="_blank" href="<?= apply_filters('omgf_settings_help_support_link', 'https://docs.ffw.press/contact'); ?>"><i class="dashicons dashicons-email"></i><?= __('Get Support', $this->plugin_text_domain); ?></a></li>
                        </ul>
                    </div>
                    <div class="column">
                        <h3><?= sprintf(__('Support %s & Spread the Word!', $this->plugin_text_domain), apply_filters('omgf_settings_page_title', 'OMGF')); ?></h3>
                        <ul>
                            <li><a target="_blank" href="<?= apply_filters('omgf_help_tab_review_link', 'https://wordpress.org/support/plugin/host-webfonts-local/reviews/?rate=5#new-post'); ?>"><i class="dashicons dashicons-star-filled"></i><?= __('Write a 5-star Review or,', $this->plugin_text_domain); ?></a></li>
                            <li><a target="_blank" href="<?= $tweetUrl; ?>"><i class="dashicons dashicons-twitter"></i><?= __('Tweet about it!', $this->plugin_text_domain); ?></a></li>
                        </ul>
                    </div>
                    <div class="column last">
                        <h3 class="signature"><?= sprintf(__('Coded with %s by', $this->plugin_text_domain), '<i class="dashicons dashicons-heart"></i>'); ?> </h3>
                        <p class="signature">
                            <a target="_blank" title="<?= __('Visit FFW Press', $this->plugin_text_domain); ?>" href="https://ffw.press/wordpress-plugins/"><img class="signature-image" alt="<?= __('Visit FFW Press', $this->plugin_text_domain); ?>" src="<?= plugin_dir_url(OMGF_PLUGIN_FILE) . 'assets/images/logo-color.png'; ?>" /></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        </div>
<?php
    }
}
