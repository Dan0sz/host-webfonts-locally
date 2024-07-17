=== OMGF | GDPR/DSGVO Compliant, Faster Google Fonts. Easy. ===
Contributors: DaanvandenBergh
Tags: google, fonts, gdpr, dsgvo, cache
Requires at least: 5.9
Tested up to: 6.6
Stable tag: 5.9.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OMGF automagically caches the Google Fonts used by your theme/plugins locally. No configuration (or brains) required!

== Description ==

**OMGF can be downloaded for free without any paid subscription from [the official WordPress repository](https://wordpress.org/plugins/host-webfonts-local/).**

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
- Automatically **Remove unused subsets** to reduce the size of the CSS stylesheet with ~90%!
- **Remove Resource Hints** (preload, preconnect, dns-prefetch) pointing to `fonts.googleapis.com` or `fonts.gstatic.com`,
- **Ensure text remains visible during webfont load** by forcing the *font-display* attribute to your Google Fonts,

= Additional Features in OMGF Pro =

- **Multisite** support,
- "Dig deeper" to find Google Fonts and optimize further. OMGF Pro supports:
  - `@font-face` and `@import` statements inside **inline `<style>` blocks**,
  - `@font-face` and `@import` statements inside **local stylesheets** loaded by your theme and/or plugins,
  - `@font-face` and `@import` statements inside **externally hosted stylesheets** loaded by your theme and/or plugins,
  - Web Font Loader (`webfont.js`),
  - Early Access Google Fonts,
  - Material Icons.
- **Ensure text remains visible during webfont load** by adding the selected *font-display* attribute to *all* fonts on your website,
- Modify the stylesheet's `src: url()` attribute to fully integrate with your configuration,
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

= 5.9.1 | July 17th, 2024 =
* Tested with WP 6.6
* Fixed: some strings weren't translatable.
* Improved: incorrect Google Fonts URLs are now somewhat handled to improve UX.
* Added: Compatibility fix for Fruitful theme.

= 5.9.0 | April 4th, 2024 =
* Removed: Early Access promo setting from Detection Settings tab, because it's considered "obsolete and unsupported by Google" and therefore will soon be removed from OMGF Pro.
  - If you have it enabled now in OMGF Pro, it will still work, until you update OMGF Pro (update isn't released yet).
* Improved: OMGF now recognizes the `math` and `symbols` unicode ranges.
* Added: Legacy Mode - anyone running into the broken 'A' issue, should enable it. Be warned that it will impact font compression rates.
* Improved: PHP 8 compatibility (using constants as callable is deprecated in PHP 8)
* Fixed: using Auto-config Used Subsets would cause warnings on new installs.
* Fixed: updating settings would fail if tab parameter wasn't set.
* Improved: prevent duplicate constant defines.
* Several minor code improvements.
* Tested with WP 6.5

= 5.8.3 =
* Fixed: removed a trailing comma to support PHP 7.0 and 7.1.

= 5.8.2 =
* Fixed: fallback to original omgf_optimized_fonts row.

= 5.8.1 =
* Improved: double check if the omgf_fonts row is still present in the wp_options table and remove it.
* Improved: separate the fonts used for displaying the Optimize Local Fonts and fonts used in the frontend, for a small autoload related performance improvement.
* Added: Process External Stylesheets promo option.

= 5.8.0 =
* Added: Top menu item that gives logged in administrators easy access to OMGF's settings, re-run fonts optimization and re-run fonts optimization for the current page.
  - If you don't expect to use it, you can disable the top menu in the plugin's Advanced Settings tab.

= 5.7.16 =
* Improved: PHP 7.2 was no longer supported, due to some string concatenation issues. Fixed this to support 7.2 again.

= 5.7.15 =
* Improved: make sure Helper::optimized_fonts() always returns an array.

= 5.7.14 =
* Fixed: make sure Helper::optimized_fonts() always returns an array.
* Fixed: if fetching stylesheet fails, return empty string (was an array before, causing 500 errors)

= 5.7.13 =
* Fixed: Optimize Local Fonts table wouldn't get populated when running a fresh scan in v5.7.12.
* Fixed: OMGF would always assume Avada was the active theme.

= 5.7.12 | Januari 16th, 2023 =
* Improved: Avada compatibility.
* Minor code clean-up.

= 5.7.11 =
* Tested with WP 6.4.

= 5.7.10 =
* Minor performance improvements.

= 5.7.9 =
* Fixed: this time a proper CSRF fix! It's been a long day.

= 5.7.8 =
* Fixed: undefined array key _wpnonce. (Big Oops! on my end, sorry about that.)

= 5.7.7 | December 12th, 2023 =
* Fixed: CSRF issue in custom Update Settings logic.

= 5.7.6 | November 8th, 2023 =
* Added: Optimize for (D)TAP (Pro) option.
* Tested with WP 6.4

= 5.7.5 | November 2nd, 2023 =
* Fixed: checkboxes couldn't be disabled if their default value was 'on'.

= 5.7.4 | September 25th, 2023 =
* Fixed: get_option() function would always fallback to an empty string as the default, now it properly returns the value set as $default.
* Improved: Minor code improvement in frontend.
* Added: omgf_frontend_process_url filter.
* Added: notice to readme.txt for users viewing this plugin in wordpress.com.

= 5.7.3 | September 11th, 2023 =
* Fixed: warning - attempt to modify propery "response" on string in Updates class.
* Fixed: warning - array_keys() expects first parameter to be array, bool given in Helper class.

= 5.7.2 =
* Fixed: don't minify font-family names.

= 5.7.1 =
* Fixed: users of the premium plugin would still get an update notice, saying **Automatic update unavailable** right after updating to the latest version.

= 5.7.0 | September 5th, 2023 =
* Added: stylesheets (CSS) generated by OMGF are now minified.
* Added: White-label Stylesheets (Pro) option to Advanced Settings, which allows removing branding from stylesheets.
* Fixed: Stale cache notice wasn't shown when relevant options were changed.

= 5.6.7 | August 28th, 2023 =
* Fixed: Don't Load option couldn't be used on Google Fonts URLs using shorthand syntax (e.g. 400i, instead of 400italic).

= 5.6.6 | August 25th, 2023 =
* Fixed: non-static class method was used to register uninstall hook.

= 5.6.5 | August 13th, 2023 =
* Tested with WP 6.3

= 5.6.4 | August 3rd, 2023 =
* Improved: perform a proper stale cache clean-up when changes are made to stylesheets in the Optimize Local Fonts section.
* Added action: `omgf_pre_update_setting_{$setting_name}`
* Improved: updated default User Agent to a Windows 7 machine, to offer backwards compatibility for older OS'.
* Improved: PHP 8.1 compatibility.
* Fixed: saving settings in the Optimize Local Fonts section wouldn't work properly when certain settings were disabled.

= 5.6.3 | July 31st, 2023 =
* Added: premium plugin users will now be notified in the All Plugins screen when updates are failing.
* Fixed: removed void return type from autoloader to provide backwards compatibility with PHP 7.0.0 - 7.2.4.
* Added filter: `omgf_optimize_user_agent` which allows users to change the User-Agent header used by OMGF to fetch font files from the Google Fonts API.

= 5.6.2 | July 19th, 2023 =
* Fixed: notices in Task Manager suggesting to install Pro wouldn't disappear after Pro was activated.

= 5.6.1 | July 16th, 2023 =
* Added filter: omgf_delete_option
* Added filter: omgf_generate_stylesheet_font_variant, which allows devs to manipulate the CSS output before it's generated (e.g. to modify the unicode-range).
* Fixed: options in Optimize Local Fonts section couldn't be disabled all at once.

= 5.6.0 | May 6th, 2023 =
* Major improvements under the hood leading to:
  - ~30% increase of overall performance in code execution.
  - ~33% less DB reads.
* Re-factored code to largely comply with WP coding standards.
* A few minor bug fixes:
  - If there were more than one non-existent stylesheets visible in the Task Manager, only the first one would respond to clicks on the "Remove" link.
  - When Test Mode was enable, OMGF would be spelled in lowercase in the info bubble.
  - Updated the Tweet text to include the #GDPR and #DSVGO hashtags.

= 5.5.6 | March 24th, 2023 =
* Improved: Used Subset(s) - Latin now stays selected when Vietnamese is selected (because it's an add-on)

= 5.5.5.1 | February 25th, 2023 =
* Fixed: 403 (Forbidden) error would be thrown every time when Save & Optimize ran.
* Added: Tooltip to Save & Optimize button.
* Improved: a success message is shown in the frontend when ?omgf_optimize=1 is appended to the URL.

= 5.5.5 | February 20th, 2023 =
* Added: compatibility for languages using logograms, like Traditional Chinese and Japanese.
* Added: Task Manager will now notify users when known incompatible plugins are activated.
* Added: when the 403 (Forbidden) error occurs, the error message now includes a link allowing users to trigger a workaround.
* Added: Essential Grid users are now notified to upgrade to OMGF Pro and the additional configuration required to make it work.
* Improved: Optimize Local Fonts and Don't Load options now contain a clearer tooltip and the info-box has been moved to the top to (hopefully) clarify how it works.

= 5.5.4 | February 7th, 2023 =
* Added: backwards compatibility for protocol relative URLs for users configuring their preloads before v5.5.0.
* Added: compatibility for Convert Pro by Brainstorm Force.
* Added: several enhancements to Task Manager:
  * Added: OMGF will now inform users about plugins that might require additional configuration and/or an upgrading to OMGF Pro.
  * Added: Notices about upgrading to OMGF Pro will disappear automatically when OMGF Pro is installed and activated.

= 5.5.3 | January 17th, 2023 =
* Fixed: Parse error: syntax error, unexpected ')' in host-webfonts-local/includes/optimize/class-run.php on line 142

= 5.5.2 | December 21st, 2022 =
* Fixed: Auto-Configure Subsets option couldn't be disabled.
* Fixed: Resource Hints pointing to bunny.cdn.net or fonts-api.wp.com wouldn't be removed.

= 5.5.1 | December 14th, 2022 =
* Fixed: ArgumentCountError: array_intersect() expects at least 1 argument, 0 given.
* Improved: don't show "optimization success" message if Auto-configure Subsets needs to run again.

= 5.5.0 | December 5th, 2022 =
* Added: 'omgf_optimize_run_args' filter to allow adding attional GET-parameters before running optimization.
* Improved: always use protocol relative ('//' instead of 'https://') URLs when generating and loading stylesheets to avoid SSL- and permalinks related quirks in WordPress.
* Added: Auto-Configure Subsets feature and moved the Used Subset(s) option to the Advanced Settings tab.
* Added: compatibility for WP.com's "GDPR compliant" Google Fonts API.
* Fixed: Array to string conversion in PHPH 8.1 while escaping arrays.

= 5.4.3 | November 7th, 2022 =
* Tested with WP 6.1
* OMGF will now warn you when it detects you're using the following scripts loading Google Fonts in iframes:
  - Active Campaign
  - Channext
  - Conversio
  - Gastronovi
  - Google Campaign Manager 360
  - HubSpot
  - ManyChat
  - Tidio
* Improved: Success message if no conflicts were detected to clarify the use of the Task Manager.
* Improved: Notify users that they have to "Mark" possible detected conflicts as "fixed" themselves.
* Improved: Bad Requests (400) to the Google Fonts API are now removed from the source code.
* Improved: trim invalid characters from end of requests to the Google Fonts API.
* Added: compatibility with Mesmerize theme.

= 5.4.2 | October 18th, 2022 =
* Added: Groovy Menu compatibility
* Added: OMGF now shows a dismissable warning when you're selecting a lot of preloads.
* Fixed: Cannot use object as array error. This error would be thrown if OMGF temporarily failed to fetch Google Fonts.
* Fixed: Not all implementations of Google Maps were properly recognized by the Task Manager.
* Fixed: some debug information was always generated, even when Debug Mode wasn't enabled.

= 5.4.1 | October 12th, 2022 =
* Added: make the Auto-Configure Adv. Processing notice under Detection Settings more noticable.
* Added: Show info box in Task Manager when Test Mode is enabled.
* Fixed: Links to theme compatibility documentation was broken.

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
