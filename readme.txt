=== OMGF | Host Google Fonts Locally ===
Contributors: DaanvandenBergh
Tags: google, fonts, gdpr, cache, speed, preload, font-display, webfonts, subsets, remove, minimize, external, requests
Requires at least: 4.6
Tested up to: 5.8
Stable tag: 4.5.6
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OMGF automagically caches the Google Fonts used by your theme/plugins locally. No configuration (or brains) required!

== Description ==

> How could using fonts via Google's service possibly run afoul of GDPR? The fact of the matter is that, when a font is requested by the user's browser, their IP is logged by Google and used for analytics.
> — Lifehacker

Leverage Browser Cache, Minimize DNS requests, reduce Cumulative Layout Shift and serve your Google Fonts in a 100% GDPR compliant way with OMGF!

OMGF is written with performance and user-friendliness in mind. It uses the Google Fonts Helper API to automatically cache the fonts your theme and plugins use to **minimize DNS requests** and speed up your WordPress website.

= How Does It Work? =

After installing the plugin, choose your Optimization Mode: Manual (default) or Automatic (only available in Pro).

When *Manual* is selected, you can simply configure OMGF to work in the way you want, and run its detection mechanism on an address of your choosing. Tweak the stylesheet(s) as you wish and these will be used throughout your site.

In *Automatic* (Pro) Mode, OMGF runs silently in the background and captures any requests made to fonts.googleapis.com or fonts.gstatic.com. When a webpage is first loaded, it reroutes these requests to its own Download API and copies the fonts over to your server. Then it generates a stylesheet for your fonts including SVG, EOT, TTF, WOFF and WOFF2 formats to guarantee maximum cross browser compatibility!

When the fonts are downloaded and the stylesheet is generated, it rewrites every URL (pointing to fonts.googleapis.com or fonts.gstatic.com) to the locally hosted stylesheet and/or font.

Please keep in mind that, although I try to make the configuration of this plugin as easy as possible, the concept of locally hosting a file or optimizing Google Fonts for *Pagespeed Insights* or *GT Metrix* has proven to be confusing for some people. If you're not sure of what your doing, please consult a SEO expert or Webdeveloper to help you with the configuration of this plugin or [hire me to do it for you](https://ffw.press/wordpress/omgf-expert-configuration/).

= Features =
- Automatically replace registered/enqueued Google Fonts in wp_head() with local copies,
- Automatically remove registered/enqueued Google Fonts from wp_head(),
- Manage Optimized Google Fonts,
  - Preload above the fold fonts,
  - Don't load certain fonts or entire stylesheets.
- Leverage the font-display (swap) option.

= Additional Features in OMGF Pro =

Everything in the free version, plus:

- Specify a Fallback Font Stack for every Google Font, to reduce Cumulative Layout Shift,
- Automatically remove/replace all Google Fonts throughout the entire document/page,
  - Also supports WebFont Loader (webfont.js), Early Access Google Fonts and requests in stylesheets using @import and @font-face statements.
  - Automatically generate different stylesheets for pages with another configuration of Google Fonts.
- Combine all Google Fonts stylesheets (requested by your theme and/or plugins) into one file,
- Deduplicate Google Fonts stylesheets,
- Define file types to include in stylesheet (WOFF, WOFF2, EOT, TTF, SVG),
- Reduce loading time and page size, by forcing the used subset(s) for all Google Fonts requests,
- Remove Resource Hints (preload, preconnect, dns-prefetch) pointing to fonts.googleapis.com or fonts.gstatic.com,
- Modify `src` attribute for fonts in stylesheet using the Fonts Source URL option to fully integrate with your configuration,
  - Use this to serve fonts and the stylesheets from your CDN, or
  - To serve fonts from an alternative path (e.g. when you're using Security through Obscurity plugins like WP Hide, etc.), or
  - Anything you like!
- Proper handling for AMP pages (Fallback to or remove Google Fonts).

*[Purchase OMGF Pro](https://ffw.press/wordpress/omgf-pro/) | [Documentation](https://ffw.press/docs/omgf-pro/) | [Tested Plugins & Themes](https://ffw.press/docs/omgf-pro/troubleshooting/compatibility/)*

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/host-webfonts-local` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings -> Optimize Google Fonts screen to configure the plugin

For a more comprehensive guide on configuring OMGF, check out the [user manual](https://ffw.press/docs/omgf-pro/user-manual/)

== Frequently Asked Questions ==

= Why do my fonts load slow the first time? =

When OMGF runs in Automatic (Pro) mode, all requests to Google Fonts' API are rewritten and point to OMGF's on-premise download API. The API downloads the fonts and generates the stylesheet, which takes a while. When this is finished, the API will not be used anymore and the stylesheet and its fonts will be loaded directly, just like any other file.

= I don't know what I'm doing! Can you help? =

Of course :) But first calm down and read the [user manual](https://ffw.press/docs/omgf-pro/user-manual/). If you have any questions afterwards, visit the [Support Forum](https://wordpress.org/support/plugin/host-webfonts-local).

= I have another file I want to host locally. Could you make a plugin? =

I already have my hands full with the plugins I maintain, so no. If you're looking for a way to host analytics.js locally; please install [CAOS](https://wordpress.org/plugins/host-analyticsjs-local/). For anything else, please follow the steps in [this how-to](https://daan.dev/how-to/host-js-locally-crontab/).

= How come my font isn't available in OMGF? =

This could be for several reasons:
1. Have you checked if your font is available on Google Fonts?
2. Is your font listed as an open source font, or is it a premium font? For obvious reasons, OMGF only has access to open source fonts.
3. Your font's name was changed, if so, please send in a support ticket, so we can figure out the new name and I can add support for it to OMGF.

= Does this plugin remove resource hints, e.g. preconnect, preload or dns-prefetch? =

No, to automatically remove resource hints pointing to fonts.googleapis.com or fonts.gstatic.com, [upgrade to OMGF Pro](https://ffw.press/wordpress/omgf-pro/).

= Can I serve the fonts from my CDN? =

Yes, using the Fonts Source URL (Pro) feature, you can modify the source of the stylesheet and fonts to be served from your CDN. [Upgrade to OMGF Pro](https://ffw.press/wordpress/omgf-pro/).

= I have Google Fonts Processing set to Replace/Remove but the fonts from fonts.gstatic.com|fonts.googleapis.com are still loaded. What's going on? =

The free version of OMGF removes any fonts that are loaded in the conventional way: wp_enqueue_scripts(). If it doesn't work for you, you're theme or plugins using other methods to load Google Fonts, e.g. in the footer, WebFont Loader, etc. [Upgrade to OMGF Pro](https://ffw.press/wordpress/omgf-pro/) to automatically replace these fonts with a locally hosted version.

= Does this plugin edit template files? =

No, it does not. It creates a CSS Stylesheet which will be automatically added to your theme's header using WordPress built-in queueing system.

= The stylesheet isn't loaded? What's going on? =

The free version of OMGF enqueues the stylesheet into WordPress' head using wp_enqueue_scripts(). If the stylesheet isn't loaded, this probably means your theme isn't implementing wp_head() correctly. [Upgrade to OMGF Pro](https://ffw.press/wordpress/omgf-pro/) to automatically add the stylesheet into WordPress' head.

= Does this plugin support Multi Site? I'm getting CORS errors! =

Yes, it does. When using subdomains, however, you might run into CORS related issues. To get around this, you should configure each site separately. Do the following:

- Go to the site's own dashboard,
- Change OMGF's cache directory (*Save font files to...*) to something unique, e.g. `/uploads/site1/omgf`,
- Click 'Save Changes'.

Repeat this for every site you want to use with OMGF. A new stylesheet, using the corresponding site's Home-URL and cache directory for each font, will be generated. Bypassing any Cross-Origin Resource Sharing (CORS) issues you might run into.

= Is this plugin compatible with WPML? =

No, not yet. But I will definitely try to make it compatible in the future!

== Screenshots ==

1. OMGF's Optimize Fonts screen. These settings affect the downloaded files and generated stylesheet(s).
2. After you've saved your changes, the Manage Optimized Google Fonts overview will show a list of detected fonts and will allowing you to easily unload and preload fonts.
3. Tweak how OMGF's detection mechanism will work and how it'll treat detected fonts.
4. Advanced Settings. Change these to make OMGF work with your configuration (if needed). The default settings will suffice for most configurations.

== Changelog ==

= 4.5.6 =
* Fix: Added Fallback API URL for when Google Fonts Helper is down.
* Enhancement: Added extra error handling in Manual Optimization Mode.
* Fix: API requests made in Manual Optimization Mode are no longer forced to SSL. It now uses the protocol configured in Settings > General > WordPress URL.
* Fix: Stylesheet handles containing spaces would prevent Optimize Google Fonts screen from rendering properly.
* Several refactors and code optimizations.

= 4.5.5 =
* Fix: Prevent collision with other plugins when authenticating AJAX-calls.

= 4.5.4 | August 18th, 2021 =
* Security: Access to the Download API now requires a valid nonce to prevent CSRF.
* Security: Added authentication to Empty Cache Directory AJAX-call.

= 4.5.3 | August 17th, 2021 =
* Fix: "Too few arguments to function OmgfPro_Frontend_AutoReplace::passthru_handle()" would occur if OMGF Pro was updated to v2.5.1 before OMGF was updated to v4.5.2.
* Security: Added checks to prevent path traversal and CSRF in Empty Cache Directory AJAX call.

= 4.5.2 | August 16th, 2021 = 
* Pro Feature: Added promo material for @font-face detection in local stylesheets.
* Fix: Fixed several warnings and notices.

= 4.5.1 | August 2nd, 2021 =
* Enhancement: Added post update notice to inform user of the plugin's database changes. The current notice you were viewed was simply, because the current DB version wasn't logged yet on your system. So if you're reading this: Ha! Made you look! ;)
* Pro Feature: Added promo material for Fallback Font Stack (Pro) feature.
* Enhancement: moved Stylesheet Generator to its own backend API.
* Enhancement: moved Font Downloader to its own backend API.
* Enhancement: Updated description of Optimization Modes.
* Fix: Fixed glitch in footer news ticker.
* Enhancement: Added several filter and action hooks to allow a more seamless integration with OMGF Pro and OMGF Additional Fonts.
* Several code and performance optimizations.

= 4.5.0 | July 28th, 2021 =
* [Removed] WOFF2 only option is superseded by Include File Types option, only available in OMGF Pro.
* [Removed] CDN URL, Cache URI and Relative URL options are combined into Fonts Source URL option, only available in OMGF Pro.
* [Removed] Optimization Mode > Automatic is only available in OMGF Pro.
* Tested with WordPress 5.8
* Several code optimizations.

= 4.4.4 =
* Fixed logo for Safari compatibility.
* Added updater notices for future important updates.

= 4.4.3 =
* Fixed a few warnings/notices.
* Re-worded some options and option descriptions.

= 4.4.2 =
* Upped static version of Admin CSS files.

= 4.4.1 | April 23rd, 2021 =
* Fixed footer logo (load from local source instead of external URL).
* Added tooltip for preload option.
* Added link to OMGF Additional Fonts under Optimize tab.

= 4.4.0 | April 10th, 2021 =
* Moved sidebar to its own 'Help' tab to clean up the interface.
* Manage Optimize Fonts panel is now shown inline with other options (and has its own label).
* Each stylesheet's handle is now more prominently visible and the font family is more readable.
* Added mass actions to each font family for easier management of each stylesheet.
* Took a different approach to deal with SSL/Non-SSL for local Dev environments.
* Performance improvements to manual detection mode (decreased risk of timeouts!)
* Overall UX tweaks and performance improvements.

= 4.3.2 | April 5th, 2021 =
* Fixed MIME type (`X-Content-Type-Options: nosniff`) related errors when using Download API.
* When site is not using SSL, sslverify is disabled when contacting the Download API.
* When OMGF Pro is running in Automatic Mode, only preloads for the currently used stylesheet are loaded.

= 4.3.1 | March 29th, 2021 =
* Added Mukta (FKA Ek Mukta) to list of renamed Google Fonts.

= 4.3.0 | March 17th, 2021 =
* [FEAT] Renamed fonts will now be captured using their new name (e.g. Mulish), but remain in the stylesheet with their old name (e.g. Muli) to prevent manual changes to the stylesheet after optimization.
* [FEAT] Added Load WOFF2 Only option.
* Small code optimizations in Download API's code.

= 4.2.8 | March 12th, 2021 =
* [FIX] Strings with a + instead of a space would returned errors in the API.

= 4.2.7 | March 10th, 2021 =
* Addding ?nomgf=1 to any URL will now temporarily bypass fonts optimization, which allows for easier debugging.

= 4.2.6 | March 6th, 2021 =
* Tested with WP 5.7
* [FIX] All fonts would be loaded, when all fonts of one font-family were checked for unloading.
* [FIX] Fixed some notices and warnings.
* Added compatibility for OMGF Pro's Early Access compatibility.
* OMGF's admin JS is now only loaded on OMGF's settings screens.
* [FIX] Fixed bug where Italic 400 fonts couldn't be unloaded.

= 4.2.5 | January 27th, 2021 =
* Improved compatibility with WordPress subdirectory installs.
* Implemented some actions/filters needed for upcoming release of OMGF Additional Fonts.
* Fixed duplicate preload ID's issue.
* Fixed some notices/warnings.
* Minor UX improvements.

= 4.2.4 | December 8th, 2020 =
* Cache keys are now fixed values instead of dynamically generated. This fixes the bug where preloads wouldn't load properly when combined with unloaded fonts of the same stylesheet.
  * **IMPORTANT**: To fix any bugs with preloads/unloads, emptying the cache directory is required.
* Cleaned up the sidebar and added a notification to reassure people that no features were moved from Free to Pro after upgrading to v4.
* Advanced Processing can now be disabled even when OMGF Pro is active. Before it was always on (accidentally).
* When preload is enabled for a font style, its associated unload checkbox is disabled and vice versa.
* Minor fixes, increased usability and optimizations.

= 4.2.3 =
* Fixed invalid preload header,
* Fixed warning: `array_keys() expects parameter 1 to be array, null given` when multiple stylesheets are loaded, but preloads are only enabled for one of them.

= 4.2.2 =
* Small fix for themes/page builders which requests Google Fonts with protocol relative URI i.e. '//fonts.googleapis.com' instead of 'https://fonts.googleapis.com'.
  * Tested with Elementor. Works.

= 4.2.1 =
* OMGF now checks secure (https://) and non-secure (http://) requests to Google Fonts, because apparently some themes still do that, even though it's 2020, but whatever.
  * Tested with Divi and Bridge Theme. Works.

= 4.2.0 | The What-4.0-should've-been Edition | October 7th, 2020 =
* **IMPORTANT NOTICE: If you're upgrading from v4.x.x it's required to Empty your Cache Directory. Otherwise the Optimized Google Fonts Overview will not work.**
* Added CSS2 (Variable Fonts) compatiblity,
* No more spaces in filenames of downloaded fonts,
* Added Optimize Fonts tab, which resembles the 'Generate Stylesheet' tab from v3, and features,
  * Optimization Mode: Manual or Automatic,
    * If Manual is selected, the URL can be specified which should be scanned for Google Fonts,
  * A complete overview of all detected fonts, grouped by stylesheet,
  * Options to preload or unload for each font.
* Move settings to more sensible places and re-grouped them in 3 groups:
  * Optimize Fonts,
  * Detection Settings,
  * Advanced Settings.
* OMGF will now throw a notice when a settings is changed which requires the cache to be flushed.
* Several tweaks and fixes in OMGF's Auto Detection mechanism and Fonts Download API.
* Fixed issue where OMGF wouldn't detect fonts in weight 400 (and 400 italic).
* Major UX improvements,
  * Pros and Cons of each Optimization Mode are outlined upon selection,
  * Show loaded while actions are taking place,
  * Cleaned up sidebar and added a clear overview of available documentation.
* Several tweaks and optimizations in overall performance.

= 4.1.3 =
* Fixed bug which would continuously show 'No fonts founds' notice in admin, among others.
* Increased compatibility with caching plugins, which would cause static pages to be served and block OMGF from pointing requests to its Download API.
* Added some notices (which disappear ;-)) for manual optimization process in admin area, making it clear when optimization is finished.

= 4.1.2 =
* Fixed syntax error (unexpected ')' on line 147).

= 4.1.1 =
* Use transients instead of options.
* Fixed some minor notices and warnings.

= 4.1.0 | October 1st, 2020 =
* Added some on-boarding to ease the use of the new interface.
  * OMGF will now show a notice in the admin area, if the optimization never ran, to increase UX.
  * Added a loader when any of the following actions are triggered:
    * Empty Cache Directory
    * Start Optimization
* Minor tweaks and optimizations.

= 4.0.2 =
* Fixed bug where OMGF would trigger too late for the requests to fonts.googleapis.com to be captured.

= 4.0.1 =
* The tiniest bugfix ever: one space too much in a str_replace() caused Font Names with spaces (e.g. Roboto Condensed, or Open Sans) to not be captured correctly.

= 4.0.0 | September 30th, 2020 =
* OMGF now runs fully automatic to replace/remove Google Fonts from your pages using OMGF's new Download API. No initial configuration required!
  * This means that if you use different fonts on different pages, all of them will be cached and served locally.
* HUGE performance increase in OMGF's automatic replacing/removing methods.
* Major overhaul of Settings Page:
  * Removed Extensions Tab
  * Some settings were moved to a new tab: Basic Settings.
  * Improved Welcome and Documentation tab.
  * Clarified option descriptions.
* Removed 'Generate Stylesheet' tab, which'll be released in a separate add-on plugin soon.
* Removed 'Use Web Font Loader?' option, because it causes Cumulative Layout Shift and will not work with OMGF's new Auto Replace feature.
* Removed 'Remove Version Parameter' option, because it has become obsolete. The new detection method uses the initial script's version, if set.
* Font Preloading is temporarily removed and will be re-introduced (in a different form, along with new features) in a later release.

= 3.8.3 | September 15th, 2020 =
* Performance improvements for Class autoloader.

= 3.8.2 =
* Fixed notice: invalid operand type.

= 3.8.1 | August 27th, 2020 =
* Bugfix: if Auto Remove was enabled, but no stylesheet was yet generated, this would break some themes' stylesheet.

= 3.8.0 | August 16th, 2020 =
* Tested with WP 5.5.
* Cleaned up the sidebar.
* Improved re-enqueueing of stylesheets, if a stylesheet depends on the removed Google Font stylesheet.
* Pressing Enter in the Search Bar triggers search now.
* Developers can now easily add functionalities to OMGF's brand new Extensions tab, using several new filters and actions.
* If OMGF is used along with other Fast FW Press plugins (e.g. CAOS, OMGF Pro, etc.) the same class loader is used, significantly increasing performance.
* Added dates to changelog :)

= 3.7.0 | June 10th, 2020 =
* OMGF settings screen is now easily extendable: added filters, actions, etc.
* Overall performance improvements and reduced code footprint.

= 3.6.2 | June 7th, 2020 =
* Added filter so Auto Remove can be disabled by other plugins (OMGF Pro, in this case.)

= 3.6.1 =
* Fixed bug in Auto Detect where duplicate fonts would override the earlier detected font styles. Newer styles are now appended to the list.
* Added multiple filters and action hooks to prepare OMGF for the release of OMGF Pro.
* Removed the code to detect incompatible themes/plugins, because an upgrade to OMGF Pro will solve all of your problems :)
  * *OMGF Pro is able to detect, replace and remove all Google Fonts (incl. WebFont Loader) regardless of how they are added by the theme or plugin, incl. dns prefetch, preconnect and preload resource hint headers.*

= 3.6.0 | May 30th, 2020 =
* OMGF now supports add-ons to extend its Auto Detect and Auto Removal feature.
* From now on, a notice containing a link to the required add-on will be thrown for known themes and frameworks which follow unconventional methods to include Google Fonts.
  * This list now contains Thrive Themes and Redux Framework, but other themes (I'm researching Avada) will be added in the near future.
* Generated stylesheets and downloaded fonts are now be saved to the 'uploads/omgf' folder by default.

= 3.5.0 | May 24th, 2020 =
* Added Force SSL option, to force the usage of SSL while generating the stylesheet.
* Added WP Rocket to list of Evil Plugins, because it empties the entire wp-content/cache folder instead of just its own files.

= 3.4.5 | May 21st, 2020 =
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

= 3.4.0 | May 4th, 2020  =
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

= 3.3.0 | March 25th, 2020 =
* Introduced a queueing system for font-styles search, preload and remove for easier management. The 'Apply' buttons now process all your changes at once.
* The 'Apply' buttons are sticky, so they're visible for long lists.

= 3.2.1 =
* Fixes in responsiveness of admin screen.
* Fixed links in Quick Start and Support block.

= 3.2.0 | March 24th, 2020 =
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

= 3.1.0 | March 21st, 2020 =
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

= 2.5.0 | January 30st, 2020 =
Updated Welcome-panel with Fast FW Press-services.
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
