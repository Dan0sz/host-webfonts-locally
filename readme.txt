=== CAOS for Webfonts - Host Google Fonts Locally! ===
Contributors: DaanvandenBergh
Donate link: https://dev.daanvandenbergh.com/donate/
Tags: update, host, save, local, locally, google, fonts, webfonts, minimize, external, requests, leverage, browser, cache
Requires at least: 4.5
Tested up to: 4.9
Stable tag: 1.2.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

With only 2 clicks of a button, CAOS for Webfonts automagically downloads your Google Fonts you want to WordPress' content-folder, generates a stylesheet for it and enqueues it in your theme's header.

== Description ==

Another cool plugin created by [Daan van den Bergh](https://dev.daanvandenbergh.com "Click here to visit my Wordpress Development Blog") that automagically saves the fonts you want to use inside your content-folder, generates a stylesheet for them and enqueues it in your theme's header.

CAOS for Webfonts is written with performance in mind. Other plugins make repeated requests to your Blog's database to replace/remove requests to external Webfonts source (e.g. Google Fonts) on-the-fly. This might be user-friendly, but it's a performance killer, because locally hosting your Google Webfonts should be a set-and-forget feature. The source of your webfonts should be replaced once and kept that way.

That's why I kept CAOS for Webfonts small and useful. It uses the Google Fonts Helper API to automagically download the fonts you want to WordPress' contents folder and generate a stylesheet for it. The stylesheet is automatically included to your site's header and 100% compatible with plugins like Autoptimize or W3 Total Cache. After that, all you need to do is remove any mention of requests to external webfont sources (using e.g. a child theme) and you're done!

This will *decrease your pageload times*, *leverage browser cache*, *minimize DNS requests* and effectively bring you a perfect score on *Pagespeed Insights* and *Pingdom*, without taking toll on the performance of your webserver.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/host-webfonts-local` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings -> Optimize Webfonts screen to configure the plugin

== Frequently Asked Questions ==

= Can I buy you a beer? =

Yes, please! [Click here to buy me a beer](https://dev.daanvandenbergh.com/donate/ "Let's do shots!")!

== Screenshots ==

N/A

== Changelog ==

= 1.2.5 =
Complete overhaul of the plugin. Fonts are now searched using the Google Fonts Helper API and

= 1.1.0 =
Fixed bug where the plugin would sometimes permanently change your uploads-directory to /local-fonts.

= 1.0.1 =
Changed to Github.

= 1.0 =
First release! No changes so far!
