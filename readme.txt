=== OMGF | Host Google Fonts Locally ===
Contributors: DaanvandenBergh
Tags: google, fonts, gdpr, cache, speed, preload, font-display, webfonts, subsets, remove, minimize, external, requests
Requires at least: 4.6
Tested up to: 5.4
Stable tag: 3.5.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

With only 2 clicks of a button, OMGF automagically downloads your Google Fonts you want to WordPress' content-folder, generates a stylesheet for it and enqueues it in your theme's header.

== Description ==

> How could using fonts via Google's service possibly run afoul of GDPR? The fact of the matter is that, when a font is requested by the user's browser, their IP is logged by Google and used for analytics.
> — Lifehacker

Leverage Browser Cache, Minimize DNS requests and serve your Google Fonts in a 100% GDPR compliant way with OMGF!

OMGF is written with performance and user-friendliness in mind. It uses the Google Fonts Helper API to automagically download the fonts you want to WordPress' contents folder and generate a stylesheet for it. The stylesheet is automatically included to your site's header and 100% compatible with CSS and JS optimizing/minification plugins like Autoptimize or W3 Total Cache. OMGF can efficiently remove any requests to external Google Fonts (loaded from fonts.gstatic.com or fonts.googleapis.com).

That's it. You're done!

This will *decrease your pageload times*, *leverage browser cache*, *minimize DNS requests* and effectively bring you a perfect score on *Pagespeed Insights* and *Pingdom*, without taking toll on the performance of your webserver.

Please keep in mind that, although I try to make the configuration of this plugin as easy as possible, the concept of locally hosting a file or optimizing Google Fonts for *Pagespeed Insights* or *GT Metrix* has proven to be confusing for some people. If you're not sure of what your doing, please consult a SEO expert or Webdeveloper to help you with the configuration of this plugin or [hire me to do it for you](https://woosh.dev/wordpress-services/omgf-expert-configuration/).

== Features ==
- *Automatically detect* which Google Fonts your theme is using or,
- Easily find fonts in multiple subsets,
- Download them and generate a stylesheet, which is automatically added to your header using WordPress' wp_head()-function,
- Change the caching path (where the fonts and stylesheet are saved) for increased compatibility with Multisite environments and Caching- and Security-plugins, such as WP Super Cache, Autoptimize and WordFence,
- Serve your fonts from your CDN,
- Enable Typekit's [Web Font Loader](https://github.com/typekit/webfontloader) to load your fonts asynchronously and further increase your Pagespeed Insights score (!),
- Preload the entire stylesheet or just fonts loaded above-the-fold,
- Control font performance by adding font-display property,
- Auto-generates the local source for webfonts,
- Automatically remove any fonts loaded from fonts.gstatic.com or fonts.googleapis.com.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/host-webfonts-local` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings -> Optimize Webfonts screen to configure the plugin

For a more comprehensive guide for configuration, click [here](https://daan.dev/wordpress/host-google-fonts-locally/).

== Frequently Asked Questions ==

= I don't know what I'm doing! Can you help? =

Of course :) But first calm down and read this [comprehensive guide on how to configure OMGF](https://daan.dev/wordpress/host-google-fonts-locally/). If you have any questions afterwards, visit the [Support Forum](https://wordpress.org/support/plugin/host-webfonts-local).

= I have another file I want to host locally. Could you make a plugin? =

Maintaining three plugins besides my daily 9-to-5 job is a handful, so no. If you're looking for a way to host analytics.js locally; please install [CAOS](https://wordpress.org/plugins/host-analyticsjs-local/). To host other 3rd party scripts and styles locally, try [HELL](https://wordpress.org/plugins/host-everything-local/). For anything else, please follow the steps in [this how-to](https://daan.dev/how-to/host-js-locally-crontab/).

= How come my font isn't available in OMGF? =

This could be for several reasons:
1. Have you checked if your font is available on Google Fonts?
1. Is your font listed as an open source font, or is it a premium font? For obvious reasons, OMGF only has access to open source fonts.

= Can I serve the fonts from my CDN? =

Yes, you can. Enter the url of your CDN and re-download and re-generate the stylesheet. Then the fonts will be saved to and served from your CDN.

= How can I make sure the fonts load asynchronously AKA non-render blocking?

Enable Typekit's Web Font Loader in the settings and OMGF will take care of it for you!

= I'm getting a 'Load resources from a consistent URL' after installing and configuring this plugin. What's going on? =

This must be, because you're still loading the externally hosted Google Fonts, besides the fonts you downloaded using OMGF. Try checking the option 'Remove Google Fonts' and see if that helps. If it doesn't consider using a child theme to 'dequeue' any external requests. If you don't know how to do that, please [contact](https://daan.dev/contact/) me.

= I have 'Remove Google Fonts' enabled, but the fonts from fonts.gstatic.com|fonts.googleapis.com are still loaded. What's going on? =

The option in OMGF removes any fonts that are loaded in the conventional way. However, if it doesn't work for you and you're using a popular theme, I'd love to help and make OMGF compatible. So don't hesitate to [contact](https://daan.dev/contact/) me.

= Does this plugin edit template files? =

No, it does not. It creates a CSS Stylesheet which will be automatically added to your theme's header using a built-in WordPress queueing system.

= The stylesheet isn't loaded? What's going on? =

OMGF enqueues the stylesheet into WordPress' head. If the stylesheet isn't loaded, this probably means your theme isn't implementing the wp_head() function into it's header section.

= Does this plugin support Multi Site? I'm getting CORS errors! =

Yes, it does. When using subdomains, however, you might run into CORS related issues. To get around this, you should configure each site separately. Do the following:

- Go to the site's own dashboard,
- Change OMGF's cache directory (*Save webfonts to...*) to something unique, e.g. `/uploads/site1/omgf`,
- Click 'Save Changes',
- If you haven't already, find the fonts you want to use,
- Click 'Download Fonts' and wait for the process to finish,
- Click 'Generate stylesheet'.

Repeat this for every site you want to use with OMGF. A new stylesheet, using the corresponding site's Home-URL and cache directory for each font, has been generated. Bypassing any Cross-Origin Resource Sharing (CORS) issues you might run into.

= Is this plugin compatible with WPML? =

No, not yet. But I will definitely try to make it compatible in the future!

== Screenshots ==

N/A

== Changelog ==

= 3.5.0 =
* Added Force SSL option, to force the usage of SSL while generating the stylesheet.
* Added WP Rocket to list of Evil Plugins, because it empties the entire wp-content/cache folder instead of just its own files.

= 3.4.5 =
* Preload path should include absolute url, instead of relative, to prevent issues with CDN usage.

= 3.4.4 =
* OMGF is now loaded inline with other plugins, not last. And,
* only Auto Detect is now triggered (if enabled) after all other plugins are loaded.
* An 'Evil Cache Plugin' warning is now thrown, when OMGF is activated and one of the Evil Cache Plugins are installed, prompting the user to move the webfonts-folder **outside** of the `wp-content/cache` folder.
  * 'Evil Cache Plugins' aren't necessarily evil. They just empty the entire cache folder (include OMGF's fonts) when a cache flush is triggered.
* Fixed bug where Pre Update functions weren't triggered anymore, e.g. move fonts after cache path change.
* Minor code optimizations/clean up.

= 3.4.3 =
* Better error handling for Auto Detect.
* Increased performance in admin area.

= 3.4.2 =
* OMGF now returns a readable error if Auto Detect detects a misformatted fonts URL.

= 3.4.1 =
* 'Optimize fonts for logged in users?' should be on by default, cause it causes to much confusion.
* Fixed bug where Auto Detect would fail if no font styles were specified in the Google Font URL.

= 3.4.0 =
* Added 'Downloaded' indicator in 'Generate Stylesheet' tab.
* Added 'Also optimize fonts for logged in users?' option. This means that all users with editor
  capabilities will (from now on) only view the optimizations when this option is checked, or when
  they view the frontend of the website in a private/incognito browser session.

= 3.3.6 =
* Modified preload feature to comply with Mozilla's regulations for the crossorigin attribute.

= 3.3.5 =
* Minor bug and usability fixes.

= 3.3.4 =
* Fixed bug in preload.

= 3.3.3 =
* Added error handling for API-calls.

= 3.3.2 =
* Auto Detect is now compatible with Newspaper theme.

= 3.3.1 =
* Use WordPress' tabs system for navigation to comply with Plugin Conventions.
* Fixed bug which caused preload font styles to be saved incorrectly.
* Replaced separate apply buttons with one apply button, which handles the entire queue for removal as well as preload at once.
* Known bug: 'Apply' button isn't clickable when sticky. Available workaround: scroll to the bottom of the list and click 'Apply'.

= 3.3.0 =
* Introduced a queueing system for font-styles search, preload and remove for easier management. The 'Apply' buttons now process all your changes at once.
* The 'Apply' buttons are sticky, so they're visible for long lists.

= 3.2.1 =
* Fixes in responsiveness of admin screen.
* Fixed links in Quick Start and Support block.

= 3.2.0 =
* Fonts are now automatically updated and font files and stylesheet are automatically moved after the 'Serve fonts from...' or 'Save fonts to...' options are changed.
* Added several reminder notices to improve UX and reduce the level of complexity.
* Notices/warnings/errors are now grouped.

= 3.1.3 =
* Added toggleable navigation menu. Made it a bit more UX friendly.
* Enhanced search and auto-detect: search results of one, are now appended to the result of the other. Also, duplicate search queries are now filtered, so they will not return duplicate subset results.

= 3.1.2 =
* Comma-separated search now works better (supports ',' as well as ', ')
* Search results are now added to the old subsets results. Allowing for more flexible search.

= 3.1.1 =
* Fixed bug in Web Font Loader.
* Fixed bug where sometimes stylesheet would still be enqueued, even though the file didn't exist.

= 3.1.0 =
* OMGF can now rewrite the URI from where fonts are served using the 'Serve webfonts from...' setting. This is particularly useful when using seurity through obscurity plugins (e.g. WP Hide.)
* Fixed bug where clicking 'save changes' would remove listed fonts and subsets.
* Gave some settings more accurate descriptions.

= 3.0.1 =
* [BUGFIX] Passing glue string after array is deprecated. Swap the parameters.

= 3.0.0 =
*OMGF - CORONA EDITION*
* Moved Welcome-panel to the side.
* wp_remote_get() is now used instead of cURL.
* Complete code overhaul to increase performance and UX.
* Notices and errors are now more explanatory and dismissable.
* Fixed several bugs.
* OMGF now uses wp_options table, instead of own tables.
* Old tables are removed and data is migrated.
* Auto detect now works better than ever.
* Search now works bug free.
* WordPress' default admin fonts no longer show up as results.

= 2.5.0 =
Updated Welcome-panel with WoOSH!-services.
Preload can now be used for certain fonts only (also combined with Web Font Loader).

= 2.4.1 =
Filenames are now rewritten to be more informative and for easier debugging.

= 2.4.0 =
Added option to use relative URLs in the generated stylesheet.

= 2.3.0 =
Added experimental enqueue order option.

= 2.2.9 =
Fixed bug that would throw excessive notices if PHP logging is enabled.
fonts.css was loaded too late, so some minification plugins couldn't capture it.

= 2.2.8 =
Throw clear error if any of the new tables don't exist. To prevent confusion.

= 2.2.7 =
Forget to up static version after changes to Admin JS files.

= 2.2.6 =
Throw errors less aggressive.

= 2.2.5 =
Improved overall error handling for Auto Detect and downloading using cURL.

= 2.2.4 =
Auto-detect is now loaded before 'Remove Google Fonts' and both are loaded absolute last.

= 2.2.3 =
Improved UX for error message when Auto-detect doesn't work properly.

= 2.2.2 =
Added long overdue migration script for options and tables. Code optimizations.

= 2.2.1 =
wp-block-editor style is now ignored when detecting stylesheets that depend on Google Fonts, when the remove function is enabled.

= 2.2.0 =
Added uninstall script.

= 2.1.6 =
Fixed bug where tables weren't created upon installation. Moved logic to plugin activation, instead of 'plugins_loaded'.

= 2.1.5 =
No new features or bugfixes. Just a re-arrangement of the support tab to be more in line with the new feature set.

= 2.1.4 =
Code clean-up in Generate-script. Improved error-handling.

= 2.1.3 =
Added error handling for when certain URLs return 'undefined' from Google Fonts API. Auto-detect now loads detected fonts immediately after settings page refresh. Code optimizations.

= 2.1.2 =
Added compatibility for (more efficient) chained requests to Google Fonts (separated by a pipe (|)) to the Auto-detect feature. Some themes (like Twenty Sixteen) use this feature.

= 2.1.1 =
Bugfix where Auto-detect would retrieve the fonts used by WordPress' Administrator area, instead of the frontend.

= 2.1.0 =
Complete overhaul of code. Major performance upgrades. Added Auto-detect feature.

= 2.0.8 =
Fixed 400-error when re-downloading fonts. Added compatibility for Enfold-theme.

= 2.0.6 =
Fixed bug with include paths.

= 2.0.5 =
OMGF now retries downloading the fonts using fopen, if the cURL attempt failed. Code improvements.

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
