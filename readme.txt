=== OMGF | Host Google Fonts Locally ===
Contributors: DaanvandenBergh
Tags: google, fonts, gdpr, cache, speed, preload, font-display, webfonts, subsets, remove, minimize, external, requests
Requires at least: 4.6
Tested up to: 5.8
Stable tag: 4.5.13
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
  - Automatically generate different stylesheets for pages with different Google Fonts configurations.
  - Material Icons support.
- Combine all Google Fonts stylesheets (requested by your theme and/or plugins) into one file,
- Deduplicate Google Fonts stylesheets,
- Rewrite stylesheets added by other plugins and/or themes to include the configured font-display (e.g. swap) option, this will remove *the ensure text remains visible during webfont load* optimization suggestion from Google PageSpeed Insights.
- Define file types to include in stylesheet (WOFF, WOFF2, EOT, TTF, SVG),
- Reduce loading time and page size, by forcing a certain subset(s) to be used for all Google Fonts requests,
- Remove Resource Hints (preload, preconnect, dns-prefetch) pointing to fonts.googleapis.com or fonts.gstatic.com,
- Modify `src` attribute for fonts in stylesheet using the Fonts Source URL option to fully integrate with your configuration,
  - Use this to serve fonts and the stylesheets from your CDN, or
  - To serve fonts from an alternative path (e.g. when you're using Security through Obscurity plugins like WP Hide, etc.), or
  - Anything you like!
- Proper handling for AMP pages (Fallback to or remove Google Fonts).

*[Purchase OMGF Pro](https://ffw.press/wordpress/omgf-pro/) | [Documentation](https://docs.ffw.press/category/4-omgf-pro/) | [Tested Plugins & Themes](https://docs.ffw.press/article/40-list-of-compatible-themes-and-plugins-omgf-pro)*

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/host-webfonts-local` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings -> Optimize Google Fonts screen to configure the plugin

For a more comprehensive guide on configuring OMGF, check out the [user manual](https://docs.ffw.press/category/4-omgf-pro/)

== Frequently Asked Questions ==

For the FAQ, [click here](https://docs.ffw.press/category/76-omgf-pro---faq).

== Screenshots ==

1. OMGF's Optimize Fonts screen. These settings affect the downloaded files and generated stylesheet(s).
2. After you've saved your changes, the Manage Optimized Google Fonts overview will show a list of detected fonts and will allowing you to easily unload and preload fonts.
3. Tweak how OMGF's detection mechanism will work and how it'll treat detected fonts.
4. Advanced Settings. Change these to make OMGF work with your configuration (if needed). The default settings will suffice for most configurations.

== Changelog ==

= 4.5.13 | January 4th, 2022 =
* Sec: Properly check permissions when Download API is accessed.

= 4.5.12 | November 27th, 2021 =
* Sec: Prevent path traversal when cache directory setting is changed. (Thanks, @jsgm!)

= 4.5.11 | November 17th, 2021 =
* Doc: Updated links to fancy new documentation hub: docs.ffw.press
* Dev: Added $font_family to omgf_alternate_api_url filter.
* Dev: Added filter to detect_registered_stylesheets().
* Fix: disable preload/unload when opposite checkbox is checked.
* Fix: Updated RSS feed URL and properly encode retrieved XML to prevent parse error simplexml_load_string().
* Promo: Added force font-display promo material.

= 4.5.10 | October 18th, 2021 =
* Enhancement: API now cleans up excessive spacing and + symbols in requests before fetching fonts. This comes in handy when e.g. @import statements in CSS stylesheets are auto-formatted by IDEs.
* Fix: API would crash when Google Fonts request turned up empty.
* Fix: Added proper error handling for when downloading fonts failed.
* Doc: Added link to Troubleshooting Guide to Help tab.

= 4.5.9 | October 5th, 2021 =
* Fix: content_url() should always be encoded, also if file already exists.
* Enhancement: If stylesheet is already generated, stop execution to decrease API request time.

= 4.5.8 =
* Fix: use array_merge() to prevent unsupported operand types error.

= 4.5.7 | September 29th, 2021 = 
* Enhancement: significantly reduced code running frontend.
* Fix: internal requests to OMGF's Download API are no longer treated as 'remote'.
* Fix: stylesheets are no longer skipped in some situations by the temp storage layer, before writing them to the database.
* Fix: using the mass actions (e.g. unload all, unload italics) no longer affect font families with the same name in a stylesheet with a different handle.
* Fix: Italic fonts are now properly detected by the API when CSS2 (variable fonts) API is used by themes and/or plugins.
* Fix: Added my own self-managed fallback API mirror to prevent more Google Fonts API downtime.
* Enhancement: reduced code in Download API by ~20%.
* Dev: add-ons for OMGF can now use the show_loader() method.
* Several UX and performance tweaks.

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

[ Changelog shortened ... ]

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

[ Changelog shortened ... ]

= 2.0.0 =
Added Typekit's Web Font Loader to allow loading fonts asynchronously.

[ Changelog shortened... ]

= 1.0 =
First release! No changes so far!
