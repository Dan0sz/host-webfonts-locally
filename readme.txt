=== CAOS for Webfonts | Host Google Fonts Locally ===
Contributors: DaanvandenBergh
Donate link: https://dev.daanvandenbergh.com/donate/
Tags: update, host, save, local, locally, google, fonts, webfonts, minimize, external, requests, leverage, browser, cache
Requires at least: 4.5
Tested up to: 5.0
Stable tag: 1.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

With only 2 clicks of a button, CAOS for Webfonts automagically downloads your Google Fonts you want to WordPress' content-folder, generates a stylesheet for it and enqueues it in your theme's header.

== Description ==

CAOS for Webfonts is written with performance in mind. Other plugins make repeated requests to your Blog's database to replace/remove requests to external Webfonts source (e.g. Google Fonts) on-the-fly. This might be user-friendly, but it's a performance killer, because locally hosting your Google Webfonts should be a set-and-forget feature. The source of your webfonts should be replaced once and kept that way.

That's why I kept CAOS for Webfonts small and useful. It uses the Google Fonts Helper API to automagically download the fonts you want to WordPress' contents folder and generate a stylesheet for it. The stylesheet is automatically included to your site's header and 100% compatible with plugins like Autoptimize or W3 Total Cache. After that, all you need to do is remove any mention of requests to external webfont sources (using e.g. a child theme) and you're done!

This will *decrease your pageload times*, *leverage browser cache*, *minimize DNS requests* and effectively bring you a perfect score on *Pagespeed Insights* and *Pingdom*, without taking toll on the performance of your webserver.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/host-webfonts-local` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings -> Optimize Webfonts screen to configure the plugin

== Frequently Asked Questions ==

= How come my font isn't available in CAOS for Webfonts? =

This could be for several reasons:
1. Have you checked if your font is available on Google Fonts?
1. Is your font listed as an open source font, or is it a premium font? For obvious reasons, CAOS for Webfonts only has access to open source fonts.

= Does this plugin edit template files? =

No, it does not. It creates a CSS Stylesheet which should be added to your theme's header.

= The stylesheet isn't loaded? What's going on? =

CAOS for Webfonts enqueues the stylesheet into WordPress' head. If the stylesheet isn't loaded, this probably means your theme isn't implementing the wp_head() function into it's header section.

= Does this plugin support Multi Site? =

Yes, it does!

= Can I buy you a beer? =

Yes, please! [Click here to buy me a beer](https://dev.daanvandenbergh.com/donate/ "Let's do shots!")!

== Screenshots ==

N/A

== Changelog ==

= 1.5.2 =
Added a download counter, clean queue-button and improved allround UX. Fixed a few minor bugs.

= 1.5.1 =
Fixed a bug where sometimes the fonts weren't downloaded yet when the stylesheet was generated.

= 1.5.0 =
CAOS for Webfonts now remembers which fonts you use, to make it easier to edit your stylesheet.

= 1.4.1 =
Added option to change font-display strategy.

= 1.4.0 =
Added option to change cache directory.

= 1.3.10 =
Fixed bug with detecting wp-content directory.

= 1.3.9 =
Fixed Multisite Bug.

= 1.3.8 =
Revert accidental commit

= 1.3.7 =
Tested with WP 5+

= 1.3.6 =
Changed order of loaded fonts to improve compatibility in Firefox. [Reported by @lofesa]

= 1.3.5 =
When plugin is deactivated, enqueued styles and scripts are removed. Fixed bug where fontnames containing multiple spaces did not return any results. Added console log when no results are returned.

= 1.3.2 =
Finally added 'Settings'-link to Plugins-page.

= 1.3.1 =
Further security measures to remove Path Traversal vulnerabilities.

= 1.2.9 =
Added security measures to prevent XSS.

= 1.2.8 =
Fixed bug where 'remove' would sometimes remove two rows.

= 1.2.5 =
Complete overhaul of the plugin. Fonts are now searched using the Google Fonts Helper API and

= 1.1.0 =
Fixed bug where the plugin would sometimes permanently change your uploads-directory to /local-fonts.

= 1.0.1 =
Changed to Github.

= 1.0 =
First release! No changes so far!
