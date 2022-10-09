=== OMGF | GDPR/DSVGO Compliant, Faster Google Fonts. Easy. ===
Contributors: DaanvandenBergh
Tags: google, fonts, gdpr, dsvgo, cache, speed, preload, font-display, webfonts, subsets, remove, minimize, external, requests
Requires at least: 4.6
Tested up to: 6.0
Stable tag: 5.4.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OMGF automagically caches the Google Fonts used by your theme/plugins locally. No configuration (or brains) required!

== Description ==

> How could using fonts via Google's service possibly run afoul of GDPR? The fact of the matter is that, when a font is requested by the user's browser, their IP is logged by Google and used for analytics.
> — Lifehacker

**Leverage Browser Cache**, **reduce DNS lookups/requests**, **reduce Cumulative Layout Shift** and make your Google Fonts **100% GDPR compliant** with OMGF!

OMGF is written with performance and user-friendliness in mind. It uses the Google Fonts API to automatically cache the fonts your theme and plugins use to **minimize DNS requests** and speed up your WordPress website.

= How Does It Work? =

After installing the plugin, OMGF will automatically start looking for Google Fonts whenever a page is requested on your website.

All Google Fonts are listed in the **Optimize Local Fonts** section of OMGF's settings screen. There, you can choose to:

- *Preload* fonts to **reduce Cumulative Layout Shift** above the fold,
- *Unload* fonts that're not used by you, your theme and/or plugins,
- Set a *Fallback Font Stack* (OMGF Pro required), to further **reduce Cumulative Layout Shift**, or
- *Replace* (OMGF Pro required) font-families with system fonts to **speed up loading times**!

= Other Features include =

- **Variable Fonts** support,
- **Remove unused subsets** to reduce the size of the CSS stylesheet,
- **Remove Resource Hints** (preload, preconnect, dns-prefetch) pointing to `fonts.googleapis.com` or `fonts.gstatic.com`,
- **Ensure text remains visible during webfont load** by forcing the *font-display* attribute to your Google Fonts,
- **Ensure text remains visible during webfont load** by forcing the *font-display* attribute to all your other fonts! (OMGF Pro required),

= Additional Features in OMGF Pro =
- **Multisite** support,
- "Dig deeper" to find Google Fonts and optimize further. OMGF Pro supports:
  - `@font-face` and `@import` statements inside **inline `<style>` blocks**,
  - `@font-face` and `@import` statements inside **local stylesheets** loaded by your theme and/or plugins,
  - Web Font Loader (`webfont.js`),
  - Early Access Google Fonts.
  - Material Icons support.
- Modify your fonts' `src: url()` attribute fully integrate with your configuration,
  - Use this to serve fonts and the stylesheets from your CDN, or
  - To serve fonts from an alternative path (e.g. when you're using Security through Obscurity plugins like WP Hide, etc.), or
  - Set a relative path to easily migrate from development/staging areas to production/live, or
  - Anything you like!

*[Purchase OMGF Pro](https://daan.dev/wordpress/omgf-pro/) | [Documentation](https://daan.dev/docs/omgf-pro/) | [Tested Plugins & Themes](https://daan.dev/docs/omgf-pro/tested-themes-plugins/)*

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/host-webfonts-local` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings -> Optimize Google Fonts screen to configure the plugin

For a more comprehensive guide on configuring OMGF, check out the [user manual](https://daan.dev/docs/omgf-pro/)

== Frequently Asked Questions ==

For the FAQ, [click here](https://daan.dev/docs/omgf-pro-faq/).

== Screenshots ==

1. OMGF's Optimize Fonts screen. These settings affect the downloaded files and generated stylesheet(s).
2. The task manager offers a quick overview of the currently detected stylesheets and their status (e.g. loaded, stale or missing) along with quick links to simple management tasks, e.g. Empty Cache and Configure/Remove.
3. After you've completed configuring OMGF, the Optimize Local Fonts section will allow you to tweak all of your Google Fonts stylesheets, by e.g. unloading unused fonts and/or preloading fonts above the fold.
4. OMGF Pro users can further tweak its level of detection under Detection Settings.
5. Advanced Settings. Change these to make OMGF work with your configuration (if needed). The default settings will suffice for most configurations.

== Changelog ==

= 5.4.0 Codename: Einstein | October 9th, 2022 =
* Fixed: if permalinks were outdated after setting up SSL, stylesheets would contain non-SSL links to the font files, causing Mixed Content and CORS errors.
* Fixed: when running Perfmatters' frontend script manager, OMGF would break the page.
* Added: "Warnings" section to Task Manager, which will refer users to specific parts of the documentation in the following situations:
  - When using a theme which requires additional configuration to be compatible with OMGF,
  - When using a theme which uses exotic Google Fonts implementations (WebFont Loader, `@import` statements, etc.),
  - When embedded content (iframes) loading Google Fonts found, like Google Maps, Youtube, etc.
* Fixed: preloaded Google Fonts stylesheets would be removed, breaking styling for plugins/themes using the <noscript> approach to load Google Fonts asynchronously.
* Added: link "How can I verify it's working?" to "Optimization Complete" message.
* Added: compatibility with Bunny.net's "GDPR compliant" CDN.
* Added: compatibility with Visual Composer's Grid elements.

= 5.3.9 | September 22nd, 2022 =
* Fixed: v5.3.7 introduced a bug affecting only users of the Jupiter theme only. The needed compatibility fixes wouldn't run on Save & Optimize.

= 5.3.8 | September 21st, 2022 =
* Fixed: Since Latin Extended is an addon for Latin, it shouldn't be allowed to select it by itself in the Used Subset(s) option.
* Added: Compatibility fix for Logo Carousel (Pro), which (like Category Slider Pro for WooCommerce) adds a random unique identifier to Google Fonts stylesheets on each pageload. Why? Does it hate cache? :'-(

= 5.3.7 | September 14th, 2022 =
* Added: output_array() debug function to allow printing arrays in the debug log.
  * Fixes: Cannot use output buffering in output buffering display handlers
* Added: Mesmerize theme compatibility
* Fixed: decode any special HTML entities to make sure all parameters in the URL are properly parsed.
* Improved: *Preload* option is now reworded to *Load Early*, because many people seemed to confuse it with "Download"
* Improved: *Do Not Load* is shortened to *Don't Load*
* Improved: *Optimize Local Fonts* is reworded to *Optimize Local Fonts* and the **Optimize Fonts** tab is reworded to **Local Fonts** to make more sense as to what its purpose actually is.
* Fixed: Similar stylesheets would sometimes be replaced twice, causing layout breaks.
* Fixed: if there are no options on the page, the Save Changes button is now disabled.
* Added: omgf_admin_optimize_verify_ssl filter for local development areas.
* Added: omgf_frontend_process_before_ob_start filter so other plugins (hint: OMGF Pro) have a proper way to execute only when OMGF is allowed to run.
* Added: compatibility fix for Category Slider Pro for WooCommerce (@see: https://wordpress.org/support/topic/adds-a-unique-identifier-to-google-fonts/)
* Added: Refresh Cache button in Task Manager.

= 5.3.6 | August 10th, 2022 =
* Added: compatibility for Download Manager and other plugin who insert stylesheet into the `head` using multiple lines.
* Added: omgf_generate_stylesheet_after filter.
* Added: omgf_optimize_fonts_object filter.

= 5.3.5 | August 2nd, 2022 =
* Fixed: use a more reliable regular expression to detect Google Fonts.
* Fixed: Divi/Elementor compatibility is now disabled by default.
* Added: DB migration script which invalidates the current stylesheets, if they don't confirm to the new standards yet.
* Fixed: always allow debug log file to be deleted from the admin screen.
* Fixed: filestat() failed-warning when enabling Debug Mode for the first time.

= 5.3.4 =
* Fixed: Debug Mode wouldn't stop logging at 1MB.
* Fixed: "No log file available for download" message would show when Debug Mode was disabled under Advanced Settings.

= 5.3.3 | July 22nd, 2022 =
* Added: Debug Mode with an option to download the log file. Stops logging when file exceeds 1MB for those who forgot to disable it.
* Fixed: Prevent array to string conversion with new Used Subset(s) option by writing defaults to the database: Latin, Latin Extended.
* Added: debug logging points in OMGF_Optimize class.
* Fixed: decode HTML entities in the URL before fetching the stylesheet from the Google Fonts API.
* Fixed: stricter matching for font-family detection, to prevent similar font names (e.g. Roboto and Roboto Condensed) from getting mixed up in the stylesheet.
* Fixed: minor performance optimization in OMGF_Optimize class.

= 5.3.2 =
* Fixed: updated static version to force a browser cache refresh of admin JS files.

= 5.3.1 =
* Fixed: Update notices should never be cached.

= 5.3.0 | July 20th, 2022 =
* Added: Removed Google Webfonts Helper API and implemented a custom Web Font Loader. This adds/fixes:
  * Added: full support for Variable Fonts
  * Fixed: Proper (WOFF2) compression, so files generated by OMGF are no longer bigger than files downloaded from the Google Fonts API.
  * Fixed: The (broken capital A) compression bug is fixed!
* Fixed: Changed logo in Help section to Daan.dev logo
* Fixed: Remove unused subsets from Optimized Fonts object to reduce db size
* Fixed: OMGF_Optimize class will bail earlier, if files and stylesheets already exist, to reduce execution time in the frontend.
* Fixed: Invalid Google Fonts API requests (e.g. without a `family` parameter) are removed from the HTML.
* Fixed: A stricter regulax expression is used for matching stylesheet `link` elements, to prevent backtracking.
* Added: Used Subset(s) option, which allows users to specify which subset(s) they'd like to use when generating stylesheets and down-/preloading (variable) font files.

= 5.2.2 | June 28th, 2022 =
* Fixed: updated links from ffw.press to daan.dev after the migration.

= 5.2.1 | June 25th, 2022 =
* Fixed: shorthand syntax (r,i,b,bi) is now parsed correctly.
* Added: Basic Variable Fonts support (full support coming in 5.3.0!)
* Fixed: "preload" attributes added by 3rd party plugins would somehow be misunderstood as preload resource hints.
* Deprecated: Force Subsets (Pro) and Include File Types (Pro) are marked as deprecated in preparation for the upcoming release which includes full Variable Fonts support, rendering these options useless.

= 5.2.0 | June 14th, 2022 =
* Added: toggle to disable Elementor/Divi compatibility fixes.
* Fixed: when resource hints (e.g. preloads) were located in unusual places (e.g. Themify Builder places its preloads above the `<title>` element) this would cause other elements (e.g. stylesheets) to be removed as well.

= 5.1.4 | June 6th, 2022 =
* Tested with WP 6.0
* Fixed: Font-weights weren't properly detected when stylesheets were loaded using Variable Fonts (CSS2) API.
* Fixed: jQuery.fn.submit() shorthand is deprecated.
* Fixed: Improved compatibility with servers using Nginx reverse proxy.
* Fixed: Filter duplicate font-family requests in same Google Fonts request, e.g. fonts.googleapis.com/css?family=Roboto|Roboto.
* Added: Workaround for Elementor to identify unique Google Fonts stylesheets, because Elementor always uses the (annoyingly generic) 'google-fonts-1' handle as an identifier. :-/
* Fixed: Generate a 'unique' identifier for each stylesheet without an identifier ('id' attribute)
* Several minor performance improvements.

= 5.1.3 =
* Added: workaround for Divi builder to identify unique Google Fonts stylesheets.
* Added: extra links to documentation in Optimize Local Fonts section.
* Fixed: Changed links to new documentation hub.
* Fixed: don't use WP_Filesystem to get and put file contents.

= 5.1.2 =
* Fixed: Minor performance improvement - content_url() is no longer used to generate download file URLs.
* Rewrote Modify Source URL option's description.

= 5.1.1 =
* Fixed: using `print_r()` in an output buffer caused 500 errors.
  - Removed the remaining (one) entries of `OMGF::debug`, because of this and Test Mode now allows for much user-friendly testing, anyway.
  - the `OMGF::debug()` wrapper is still there for hardcore in code debugging.
* Several code tweaks and fixes.

= 5.1.0 =
* Added: Test Mode, which allows you to test optimizations before releasing them to the public.
         This option replaces Optimize for Logged-in Administrators/Editors.
* Fixed: several minor tweaks.

= 5.0.6 =
* Added: Removed Cache Directory in favor of an automatic approach to improve UX, because most people didn't change the default value anyway.
  - I.e. all files generated by OMGF are stored in a subdirectory of the Uploads folder (default `wp-content/uploads`), called `omgf`.
  - For most of you nothing will change, but `omgf_upload_dir` and `omgf_upload_url` filters are available for DEVs who want to modify the default value, although I really can't think of a reason why you'd want to do that.
* Reworded description of Optimize for logged-in Administrators/Editors option.

= 5.0.5 =
* Added: Compatibility with Mesmerize Pro theme; this theme loads Google Fonts asynchronously, which causes CLS.
* Added: UNIX timestamp cached stylesheets to make sure browser cache of visitors is busted, upon cache refresh.
* Fixed: Running Save & Optimize a 2nd time could trigger some firewall rules, due to the serialized array being passed along with the settings form's POST action. This serialized array is now stored in the form using base64_encode() and decoded before being saved to the database.
* Fixed: Since the Google Fonts API has removed the `subsets` paramater and returns all subsets by default, OMGF now does the same. Unlike the Google Fonts API, OMGF does still respect and apply the parameter if it set, because it is still used by many themes and plugins. 
  * Re-worded Force Subsets (Pro) featured to clarify this behavior.
* Fixed: Some resource hints that were added using unconventional methods (i.e. *not* using `wp_resource_hints()`) weren't removed.
* Fixed: If no regular Google Fonts stylesheets were present, the `omgf_processed_html` filter would never be triggered.
* Fixed: Stylesheets on AMP pages would be rewritten to local stylesheets, while this is not supported by AMP.
* Removed: AMP handling (Pro) option from Advanced Settings, because it's no longer supported by AMP.

= 5.0.4 =
* Fixed: don't allow starting buffer twice.

= 5.0.3 =
* Fixed: always run if omgf_optimize parameter is set.

= 5.0.2 =
* Fixed: Use Site Address (URL) to run first optimization scan, instead of WordPress Address (URL), because the WordPress install URL can differ from the frontend URL.
* Fixed: Make sure stylesheet URL is properly decoded and HTML entities, etc. are removed, before attempting to process it. (This would cause parameters, like `subset` or `display`, to get lost).
* Added: `omgf_optimize_query_subset` filter to OMGF_Optimize class.

= 5.0.1 =
* Fixed: Previous versions of OMGF would save stylesheet handles without the appended '-css' string. This is now brought back to guarantee a smooth transition to v5.
* Fixed: If `?nomgf=1` parameter was set, preloads would still be loaded.
* Fixed: Added an extra check if file exists, before adding font files for preloading.
* Enhanced: A small speed boost for Save & Optimize.

= 5.0.0 - **The Better, Bigger, Faster, Stronger, Cooler, Awesomer Edition** | March 4th, 2022 =
* Added: Parse entire HTML document for Google Fonts stylesheets (instead of just wp_head())
* Added: Merged both Optimization Modes option into one automatically running option:
         - A first scan is done upon Save & Optimize,
         - A quick check is done on pageload, to see if other Google Fonts are found than the ones already found, and if so, they're downloaded and replaced on-the-fly.
* Enhanced: The Download API is replaced for an easier, leaner and faster alternative and no longer uses the WordPress API.
         - If the first request fails, a mirror is used to retry the request, before throwing an error.
         - Fixes rest_no_route errors in some configurations.
* Enhanced: The Task Manager now offers a quick overview of downloaded stylesheets and their status, along with simple management tasks, e.g. cache flush, configure stylesheet and/or remove.
         - When cache is marked as stale, it's now possible to refresh the cache and maintain your stylesheet configuration.
* Added: Resource hints enqueued in wp_resource_hints() are now properly removed.
* Fixed: Smart Slider 3 compatibility.
* Several bugfixes, UX improvements and code optimizations.

[ Changelog shortened ... ]

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
