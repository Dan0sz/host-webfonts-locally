<?php
/**
 * @package  : OMGF
 * @author   : Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="welcome-panel" class="welcome-panel">
    <div class="welcome-panel-content">
        <h2><?php _e('Thank you for using OMGF!', 'host-webfonts-local'); ?></h2>
        <p class="about-description">
            <?php _e(
                'OMGF automagically downloads and saves the fonts you want to use inside Wordpress\' content-folder, generates a stylesheet for them and enqueues it in your theme\'s header. This will decrease your pageload times, leverage your browser cache, minimize external requests and effectively bring you a perfect score on Pagespeed Insights and Pingdom.',
                'host-webfonts-local'
            ); ?>
        </p>
        <div class="welcome-panel-column-container">
            <div class="welcome-panel-column" style="width: 32%;">
                <h3><?php _e( 'Quickstart', 'host-webfonts-local') ;?></h3>
                <ul>
                    <li class="welcome-icon dashicons-before dashicons-editor-bold"><?= sprintf(__('For all available fonts, check out %sGoogle Fonts%s', 'host-webfonts-local'), '<a href="https://fonts.google.com/" target="_blank">','</a>'); ?></li>
                    <li class="welcome-icon dashicons-before dashicons-cloud"><?php _e('Search for for your fonts', 'host-webfonts-local'); ?></li>
                    <li class="welcome-icon dashicons-before dashicons-admin-settings"><?php _e('Select the subsets you need', 'host-webfonts-local'); ?></li>
                    <li class="welcome-icon dashicons-before dashicons-admin-tools"><?php _e('Modify the list by removing the fonts you don\'t use', 'host-webfonts-local'); ?></li>
                    <li class="welcome-icon dashicons-before dashicons-update"><?php _e('Click \'Download Fonts\' and wait for the download to complete', 'host-webfonts-local'); ?></li>
                    <li class="welcome-icon dashicons-before dashicons-art"><?php _e('Click \'Generate Stylesheet\' and wait for the process to complete', 'host-webfonts-local'); ?></li>
                    <li class="welcome-icon dashicons-before dashicons-media-text"><?php _e('The stylesheet is generated and added to your theme\'s header', 'host-webfonts-local'); ?></li>
                    <li class="welcome-icon dashicons-before dashicons-editor-removeformatting"><?php _e('Check \'Remove Google Fonts\' and save your changes', 'host-webfonts-local'); ?></li>
                    <li class="welcome-icon dashicons-before dashicons-smiley"><?php _e('Done!', 'host-webfonts-local'); ?></li>
                </ul>
                <p>
                    <?= sprintf(__('%sClick here%s for a more comprehensive guide.', 'host-webfonts-local'), '<a target="_blank" href="' . CAOS_WEBFONTS_SITE_URL . '/wordpress/host-google-fonts-locally/">', '</a>'); ?>
                </p>
            </div>
            <div class="welcome-panel-column" style="width: 32%;">
                <h3><?php _e('Get a Perfect Score on Pagespeed & Pingdom!', 'host-webfonts-local'); ?></h3>
                <p><?= sprintf(__('%sLeverage your browser cache%s and
                    %slower pageload times%s by hosting analytics.js locally with
                    %sCAOS%s.', 'host-webfonts-local'), '<strong>', '</strong>', '<strong>', '</strong>', '<a href="' . CAOS_WEBFONTS_SITE_URL . '/wordpress-plugins/optimize-analytics-wordpress/" target="_blank">', '</a>'); ?>
                </p>
                <p>
                    <a target="_blank" href="https://wordpress.org/plugins/host-analyticsjs-local"><?php _e('Download now', 'host-webfonts-local'); ?></a>
                </p>
                <h3><?php _e('Want to Host other Files Locally?', 'host-webfonts-local'); ?></h3>
                <p><?php _e('Unleash your site\'s true potential by locally hosting as many files as possible.', 'host-webfonts-local'); ?></p>
                <p>
                    <a target="_blank" href="<?= CAOS_WEBFONTS_SITE_URL; ?>/how-to/host-js-locally-crontab/"><?php _e('Read more', 'host-webfonts-local'); ?></a>
                </p>
            </div>
            <div class="welcome-panel-column welcome-panel-last" style="width: 34%;">
                <h3>
                    <?php _e('Need Help?', 'host-webfonts-local'); ?>
                </h3>
                <p>
                    <?php _e('Thank you for using OMGF.', 'host-webfonts-local'); ?>
                </p>
                <p>
                    <?= sprintf(__('I am convinced that knowledge should be free. That\'s why I will never charge you for the plugins I create and I will help you to succeed in your projects through the %stutorials%s on my blog.', 'host-webfonts-local'), '<a href="' . CAOS_WEBFONTS_SITE_URL . '/how-to/" target="_blank">', '</a>'); ?>
                </p>
                <p>
                    <?= sprintf(__('However, my time is just as valuable as yours. Consider supporting me by either %sdonating%s or leaving a %s5-star review%s on Wordpress.org.', 'host-webfonts-local'), '<a href="' . CAOS_WEBFONTS_SITE_URL . '/donate" target="_blank">', '</a>', '<a target="_blank" href="https://wordpress.org/support/plugin/host-analyticsjs-local/reviews/?rate=5#new-post">', '</a>'); ?>
                </p>
                <p>

                    <?= sprintf(__('If you\'re running into any issues, please make sure you\'ve read %sthe manual%s thoroughly. Visit the %sFAQ%s and %sSupport Forum%s to see if your question has already been answered. If not, ask a question on the Support Forum.', 'host-webfonts-local'), '<a href="' . CAOS_WEBFONTS_SITE_URL . '/wordpress/host-google-fonts-locally/" target="_blank">', '</a>', '<a href="https://wordpress.org/plugins/host-webfonts-local/#description" target="_blank">', '</a>', '<a href="https://wordpress.org/support/plugin/host-webfonts-local">', '</a>'); ?>
                </p>
                <p>
                    <a target="_blank" class="button button-primary button-hero" href="<?= CAOS_WEBFONTS_SITE_URL; ?>/donate/"><span class="dashicons-before dashicons-heart"> <?php _e('Donate', 'host-webfonts-local'); ?></span></a>
                    <a target="_blank" class="button button-secondary button-hero" href="https://wordpress.org/support/plugin/host-webfonts-local/reviews/?rate=5#new-post"><span class="dashicons-before dashicons-star-filled"> <?php _e('Review', 'host-webfonts-local'); ?></span></a>
                    <a target="_blank" class="button button-secondary button-hero" href="https://twitter.com/Dan0sz"><span class="dashicons-before dashicons-twitter"> <?php _e('Follow', 'host-webfonts-local'); ?></span></a>
                </p>
            </div>
        </div>
    </div>
</div>
