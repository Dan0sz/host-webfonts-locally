<?php
/**
 * @package: CAOS for Webfonts
 * @author: Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url: https://daan.dev
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
?>
<div id="welcome-panel" class="welcome-panel">
    <div class="welcome-panel-content">
        <h2>Thank you for using CAOS for Webfonts!</h2>
        <p class="about-description">
            CAOS for Webfonts automagically downloads and saves the fonts you want to use inside Wordpress' content-folder, generates a stylesheet for them and enqueues it in your theme's header.
            This will decrease your pageload times, leverage your browser cache, minimize external requests and effectively bring you a perfect score on Pagespeed Insights and Pingdom.
        </p>
        <div class="welcome-panel-column-container">
            <div class="welcome-panel-column" style="width: 32%;">
                <h3>Quickstart</h3>
                <ul>
                    <li class="welcome-icon dashicons-before dashicons-editor-bold">For all available fonts, check out <a href="https://fonts.google.com/" target="_blank">Google Fonts</a></li>
                    <li class="welcome-icon dashicons-before dashicons-cloud">Search for for your fonts</li>
                    <li class="welcome-icon dashicons-before dashicons-admin-settings">Select the subsets you need</li>
                    <li class="welcome-icon dashicons-before dashicons-admin-tools">Modify the list by removing the fonts you don't need/want</li>
                    <li class="welcome-icon dashicons-before dashicons-update">Click 'Download Fonts' and wait for the download to complete</li>
                    <li class="welcome-icon dashicons-before dashicons-art">Click 'Generate Stylesheet' and wait for the process to complete</li>
                    <li class="welcome-icon dashicons-before dashicons-media-text">The stylesheet is generated and added to your theme's header</li>
                    <li class="welcome-icon dashicons-before dashicons-editor-removeformatting">To <i>remove externally hosted Google Fonts</i>, read <a target="_blank" href="<?= CAOS_WEBFONTS_SITE_URL; ?>/how-to/remove-google-fonts-wordpress">this</a>.</li>
                    <li class="welcome-icon dashicons-before dashicons-smiley">Done!</li>
                </ul>
                <p><a target="_blank" href="<?= CAOS_WEBFONTS_SITE_URL; ?>/wordpress/host-google-fonts-locally/">Click here</a> for a more comprehensive guide.</p>
            </div>
            <div class="welcome-panel-column" style="width: 32%;">
                <h3>Get a Perfect Score on Pagespeed & Pingdom!</h3>
                <p><strong>Leverage your browser cache</strong> and <strong>lower pageload times</strong> by hosting analytics.js locally with <a href="<?= CAOS_WEBFONTS_SITE_URL; ?>/wordpress-plugins/optimize-analytics-wordpress/" target="_blank">CAOS for Analytics</a>.</p>
                <p><a target="_blank" href="https://wordpress.org/plugins/host-analyticsjs-local">Download now</a></p>
                <h3>Want to Host other Files Locally?</h3>
                <p>Unleash your site's true potential by locally hosting as many files as possible.</p>
                <p><a target="_blank" href="<?= CAOS_WEBFONTS_SITE_URL; ?>/how-to/host-js-locally-crontab/">Read more</a></p>
            </div>
            <div class="welcome-panel-column welcome-panel-last" style="width: 34%;">
                <h3>
                    <?php _e('Need Help?', 'host-webfonts-local'); ?>
                </h3>
                <p>
                    <?php _e('Thank you for using CAOS for Webfonts.', 'host-webfonts-local'); ?>
                </p>
                <p>
                    <?php _e('I am convinced that knowledge should be free. That\'s why I will never charge you for the plugins I create and I will help you to succeed in your projects through the <a href="' . CAOS_WEBFONTS_SITE_URL . '/how-to/" target="_blank">tutorials</a> on my blog.', 'host-webfonts-local'); ?>
                </p>
                <p>
                    <?php _e("However, my time is just as valuable as yours. Consider supporting me by either <a href='" . CAOS_WEBFONTS_SITE_URL . "/donate' target='_blank'>donating</a> or leaving a <a target='_blank' href='https://wordpress.org/support/plugin/host-analyticsjs-local/reviews/?rate=5#new-post'>5-star review</a> on Wordpress.org.", 'host-webfonts-local'); ?>
                </p>
                <p>
                    <?php _e('If you\'re running into any issues, please make sure you\'ve read <a href="' . CAOS_WEBFONTS_SITE_URL . '/wordpress-plugins/optimize-analytics-wordpress/" target="_blank">the manual</a> thoroughly. Visit the <a href="https://wordpress.org/plugins/host-analyticsjs-local/#description" target="_blank">FAQ</a> and <a href="https://wordpress.org/support/plugin/host-analyticsjs-local">Support Forum</a> to see if your question has already been answered. If not, ask a question on the Support Forum.', 'host-webfonts-local'); ?>
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
