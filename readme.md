# CAOS for Webfonts | Host Google Fonts Locally

With only 2 clicks of a button, CAOS for Webfonts automagically downloads your Google Fonts you want to WordPress' content-folder, generates a stylesheet for it and enqueues it in your theme's header.

## Description

CAOS for Webfonts is written with performance in mind. Other plugins make repeated requests to your Blog's database to replace/remove requests to external Webfonts source (e.g. Google Fonts) on-the-fly. This might be user-friendly, but it's a performance killer, because locally hosting your Google Webfonts should be a set-and-forget feature. The source of your webfonts should be replaced once and kept that way.

That's why I kept CAOS for Webfonts small and useful. It uses the Google Fonts Helper API to automagically download the fonts you want to WordPress' contents folder and generate a stylesheet for it. The stylesheet is automatically included to your site's header and 100% compatible with plugins like Autoptimize or W3 Total Cache. After that, all you need to do is remove any mention of requests to external webfont sources (using e.g. a child theme) and you're done!

This will *decrease your pageload times*, *leverage browser cache*, *minimize DNS requests* and effectively bring you a perfect score on *Pagespeed Insights* and *Pingdom*, without taking toll on the performance of your webserver.

## Installation

### Using GIT

1. From your terminal, `cd` to your plugins directory (usually `wp-content/plugins`)
1. Run the following command: `git clone https://github.com/Dan0sz/host-webfonts-locally.git host-webfonts-local`

### From the Wordpress Repository

1. From your WordPress administrator area, go to *Plugins > Add New*
1. Search for 'Daan van den Bergh'
1. Click the 'Install' button next to *CAOS for Webfonts | Host Google Fonts Locally*
1. Click 'Activate'

## Frequently Asked Questions

**Can I buy you a beer?**

Yes, please! [Click here to buy me a beer](https://dev.daanvandenbergh.com/donate/ "Let's do shots!")!

Visit the [FAQ at Wordpress.org](https://wordpress.org/plugins/host-webfonts-local/#faq)

## Support

For Support Queries, checkout the [Support Forum at Wordpress.org](https://wordpress.org/support/plugin/host-webfonts-local)

## Changelog

Visit the [Changelog at Wordpress.org](https://wordpress.org/plugins/host-webfonts-local/#developers)
