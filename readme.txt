=== OMGF | Host Google Fonts Locally ===
Contributors: DaanvandenBergh
Donate link: https://daan.dev/donate/
Tags: google, fonts, host, save, local, locally, webfonts, update, minimize, external, requests, leverage, browser, cache
Requires at least: 4.6
Tested up to: 5.3
Stable tag: 2.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

With only 2 clicks of a button, OMGF automagically downloads your Google Fonts you want to WordPress' content-folder, generates a stylesheet for it and enqueues it in your theme's header.

== Description ==

Leverage Browser Cache and Minimize DNS requests with OMGF (Optimize My Google Fonts, formerly known as CAOS for Webfonts).

OMGF is written with performance and user-friendliness in mind. It uses the Google Fonts Helper API to automagically download the fonts you want to WordPress' contents folder and generate a stylesheet for it. The stylesheet is automatically included to your site's header and 100% compatible with CSS and JS optimizing/minification plugins like Autoptimize or W3 Total Cache. OMGF can efficiently remove any requests to external Google Fonts (loaded from fonts.gstatic.com or fonts.googleapies.com).

That's it. You're done!

This will *decrease your pageload times*, *leverage browser cache*, *minimize DNS requests* and effectively bring you a perfect score on *Pagespeed Insights* and *Pingdom*, without taking toll on the performance of your webserver.

= Features =
- Easily find and download your fonts in multiple subsets,
- Generate a stylesheet, which is automatically added to your header using WordPress' wp_head()-function,
- Change the caching path (where the fonts and stylesheet are saved) for increased compatibility with Multisite environments and Caching- and Security-plugins, such as WP Super Cache, Autoptimize and WordFence,
- Serve your fonts from your CDN,
- Enable Typekit's [Web Font Loader](https://github.com/typekit/webfontloader) to load your fonts asynchronously and further increase your Pagespeed Insights score (!),
- Control font performance by adding font-display property,
- Auto-generates the local source for webfonts,
- Automatically remove any fonts loaded from fonts.gstatic.com or fonts.googleapis.com,
- Prioritize fonts with rel='preload'.

Please keep in mind that, although I try to make the configuration of this plugin as easy as possible, the concept of locally hosting a file or optimizing Google Fonts for *Pagespeed Insights* or *GT Metrix* has proven to be confusing for some people. If you're not sure of what your doing, please consult a SEO expert or Webdeveloper to help you with the configuration and optimization of your WordPress blog. Or feel free to [contact me](https://daan.dev/contact/) for a quote.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/host-webfonts-local` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings -> Optimize Webfonts screen to configure the plugin

For a more comprehensive guide for configuration, click [here](https://daan.dev/wordpress/host-google-fonts-locally/).

== Frequently Asked Questions ==

= I don't know what I'm doing! Can you help? =

Of course :) But first calm down and read this [comprehensive guide on how to configure OMGF](https://daan.dev/wordpress/host-google-fonts-locally/). If you have any questions afterwards, visit the [Support Forum](https://wordpress.org/support/plugin/host-webfonts-local).

= I have another file I want to host locally. Could you make a plugin? =

Maintaining two plugins besides my daily 9-to-5 job is a handful, so no. If you're looking for a way to host analytics.js locally; please install [CAOS](https://wordpress.org/plugins/host-analyticsjs-local/). For anything else, please follow the steps in [this how-to](https://daan.dev/how-to/host-js-locally-crontab/).

= How come my font isn't available in OMGF? =

This could be for several reasons:
1. Have you checked if your font is available on Google Fonts?
1. Is your font listed as an open source font, or is it a premium font? For obvious reasons, OMGF only has access to open source fonts.

= Can I serve the fonts from my CDN? =

Yes, you can. Enter the url of your CDN and re-download and re-generate the stylesheet. Then the fonts will be saved to and served from your CDN.

= How can I make sure the fonts load asynchronously AKA non-render blocking?

Enable Typekit's Web Font Loader in the settings and OMGF will take care of it for you!

= I'm getting a 'Load resources from a consistent URL' after installing and configuring this plugin. What's going on? =

This must be, because you're still loading the externally hosted Google Fonts, besides the fonts you downloaded using OMGF. Try checking the option 'Remove Google Fonts' and see if that helps. If it doesn't consider using a child theme to 'dequeue' any external requests. If you don't know how to do that, consider using a plugin such as Autoptimize to optimize your CSS and remove the fonts.

= Does this plugin edit template files? =

No, it does not. It creates a CSS Stylesheet which will be automatically added to your theme's header using a built-in WordPress queueing system.

= My fonts aren't being downloaded! What's going on? =

First check your database if the table {prefix}_caos_webfonts exists. If it doesn't, remove the `caos_webfonts_db_version` from the `wp_options` table and reload the page. The table should be created and the issue should be resolved.

If the issue still persists and you're using any caching plugins, such as Autoptimize, W3TC or WP Super Cache? Empty their caches. After that empty your browser's cache and reload the page. Try again.

= The stylesheet isn't loaded? What's going on? =

OMGF enqueues the stylesheet into WordPress' head. If the stylesheet isn't loaded, this probably means your theme isn't implementing the wp_head() function into it's header section.

= Does this plugin support Multi Site? I'm getting CORS errors! =

Yes, it does. When using subdomains, however, you might run into CORS related issues. To get around this, you should configure each site separately. Do the following:

- Go to the site's own dashboard,
- Change OMGF's cache directory (*Save webfonts to...*) to something unique, e.g. `/cache/site1/omgf`,
- Click 'Save Changes',
- If you haven't already, find the fonts you want to use,
- Click 'Download Fonts' and wait for the process to finish,
- Click 'Generate stylesheet'.

Repeat this for every site you want to use with OMGF. A new stylesheet, using the corresponding site's Home-URL and cache directory for each font, has been generated. Bypassing any Cross-Origin Resource Sharing (CORS) issues you might run into.

= Can I buy you a beer? =

Yes, please! [Click here to buy me a beer](https://daan.dev/donate/ "Let's do shots!")!

== Screenshots ==

N/A

== Changelog ==

= 2.0.4 =
Further improvements for downloading of analytics.js.

= 2.0.3 =
Tested with WP 5.3 and replaced `fopen()` with cUrl to make OMGF compatible with servers that have `allow_url_fopen` disabled.

= 2.0.2 =
Added error handling to DownloadFonts-script.

= 2.0.1 =
Using Typekit's Web Font Loader only uses 1 SQL query in the frontend now, regardless of how many fonts you use.

= 2.0.0 =
Added Typekit's Web Font Loader to allow loading fonts asynchronously.

= 1.9.11 =
Fixed bug where a few strings couldn't be translated. Improved responsiveness of settings-screen. Minor re-factor for better structure.

= 1.9.10 =
[Urgent] Errors in some translation strings.

= 1.9.9 =
Fixed 'duplicate column'-error when WP_DEBUG was enabled. Fixed bug which broke preload. Preload is now automatically skipped if you have any CSS optimization plugins enabled.

= 1.9.8 =
Updated Multisite documentation.

= 1.9.7 =
Updated documentation.

= 1.9.6 =
Fixed bug where preload would cause issues.

= 1.9.5 =
'Remove Google Fonts'-options now re-enqueues styles that were dependent on removed Fonts.

= 1.9.4 =
Small improvement to 'Remove Google Fonts'-option.

= 1.9.2 =
Made all strings translatable.

= 1.9.1 =
Changed name to OMGF, because it's hilarious?

= 1.9.0 =
New feature! OMGF can now remove fonts from fonts.googleapis.com or fonts.gstatic.com automatically.

= 1.8.3 =
Extended support for local source attribute.

= 1.8.2 =
Correct support for Legacy IE browsers (EOT).
Added 'local'-attribute to stylesheet.

= 1.8.1 =
Load EOT-files first in stylesheet.

= 1.8.0 =
Minor code optimizations.

= 1.7.9 =
Cleared up instructions in Welcome Panel.
Added option to remove version parameter from stylesheet request.

= 1.7.8 =
Updated welcome panel.

= 1.7.7 =
Quick-fix for re-triggered SQL queries.

= 1.7.6 =
XSS hardening in stylesheet generation form.

= 1.7.5 =
Added CDN support.

= 1.7.4 =
Fluid progress bar now functions correctly and doesn't make any unnecessary Ajax-requests.
Replaced all success-messages with notifications inside the buttons, to increase UX.

= 1.7.3 =
Improved search by adding support for comma-separated lists. Fixed some 404s in welcome panel and minor JavaScript optimizations.

= 1.7.2 =
Minor usability updates.

= 1.7.0 =
Added support for subsets to increase compatibility with other writing.

= 1.6.1 =
Changed domains to new home: daan.dev

= 1.6.0 =
Added experimental option for preload resource hint.

= 1.5.7 =
Replaced download counter for progress-bar. Refactored logic for AJAX-requests for better
performance.

= 1.5.6 =
Optimized AJAX-requests for download-counter.

= 1.5.5 =
Added a clean-up button, which cleans the currently configured cache-dir.

= 1.5.3 =
Plugins admin-JS and -CSS is now updated by force after plugin update to prevent malfunction.

= 1.5.2 =
Added a download counter, clean queue-button and improved allround UX. Fixed a few minor bugs.

= 1.5.1 =
Fixed a bug where sometimes the fonts weren't downloaded yet when the stylesheet was generated.

= 1.5.0 =
OMGF now remembers which fonts you use, to make it easier to edit your stylesheet.

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
