 === OMGF | GDPR/DSGVO Compliant, Faster Google Fonts. Easy. ===
Contributors: DaanvandenBergh
Tags: google, fonts, gdpr, dsgvo, cache
Requires at least: 5.9
Tested up to: 6.8
Stable tag: 6.0.6
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

After installing and configuring the plugin, OMGF will automatically start looking for Google Fonts whenever a page is requested on your website.

All Google Fonts are listed in the **Optimize Local Fonts** section of OMGF's settings screen. There, you can choose to:

- *Preload* fonts to **reduce Cumulative Layout Shift** above the fold,
- *Unload* fonts that're not used by you, your theme and/or plugins,
- Set a *Fallback Font Stack* (OMGF Pro required), to further **reduce Cumulative Layout Shift**, or
- *Replace* (OMGF Pro required) font-families with system fonts to **speed up loading times**!

= Other Features include =

- The **integrated Google Fonts checker** notifies you if a plugin or your theme has added Google Fonts (e.g. after an update) it can't process.
- **Variable Fonts** support,
- Automatically **Remove unused subsets** to reduce the size of the CSS stylesheet up to 90%!
- **Remove Resource Hints** (preload, preconnect, dns-prefetch) pointing to `fonts.googleapis.com` or
  `fonts.gstatic.com`,
- **Ensure text remains visible during webfont load** by forcing the _font-display_ attribute to your Google Fonts,
- **Ensure text remains visible during webfont load** by forcing the _font-display_ attribute to all your other fonts! (
  OMGF Pro required),

= Additional Features in OMGF Pro =

- Run the **Google Fonts checker** in the frontend (for all users) to **organically check for present external Google Fonts** throughout your site.
- **Smart Preload** automatically configures which fonts should be preloaded i.e., loaded early to reduce Cumulative Layout Shift, Largest Contentful Paint and [Ensure Text Remains Visible During Webfont Load](https://daan.dev/blog/how-to/ensure-text-remains-visible-during-webfont-load/).
- Automatically configures itself to make sure all externally hosted Google Fonts on your site are hosted locally. OMGF Pro supports:
	- `@font-face` and `@import` statements inside **inline `<style>` blocks**,
	- `@font-face` and `@import` statements inside **local stylesheets** loaded by e.g. your theme and/or plugins,
	- `@font-face` and `@import` statements inside **externally hosted stylesheets** loaded by your theme and/or
	  plugins,
	- Web Font Loader (`webfont.js`),
	- Async Google Fonts (loaded using JS)
	- Material Icons.
- **Multisite** and **WPML** support.
- **Whitelabel stylesheets**, which removes branding and comments from the stylesheets to further reduce the size.
- Modify your fonts' `src: url()` attribute to fully integrate with your configuration,
	- Use this to serve fonts and the stylesheets from your CDN, or
	- To serve fonts from an alternative path (e.g. when you're using Security through Obscurity plugins like WP Hide,
	  etc.), or
	- Anything you like!
- **Developer Mode**, which allows you to easily migrate between Development, Staging/Testing, Acceptance and Production
  environments.

*[Purchase OMGF Pro](https://daan.dev/wordpress/omgf-pro/) | [Documentation](https://daan.dev/docs/omgf-pro/) | [Tested Plugins & Themes](https://daan.dev/docs/omgf-pro/tested-themes-plugins/)*

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/host-webfonts-local` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings -> Optimize Google Fonts screen to configure the plugin

For a more comprehensive guide on configuring OMGF, check out the [user manual](https://daan.dev/docs/omgf-pro/)

== Frequently Asked Questions ==

For the FAQ, [click here](https://daan.dev/docs/omgf-pro-faq/).

== Screenshots ==

1. OMGF's Local Fonts screen. These settings affect the downloaded files and generated stylesheet(s).
2. The dashboard offers a quick overview of possible configurational issues (and solutions), the status of cached fonts/stylesheets along with quick links to simple management tasks e.g., Empty Cache and Configure/Remove.
3. After you've completed configuring OMGF, the Optimize Local Fonts section will allow you to tweak all of your Google Fonts stylesheets by e.g., unloading unused fonts and/or preloading fonts above the fold.
4. Advanced Settings. Change these to make OMGF work with your configuration (if needed). The default settings will suffice for most configurations.

== Changelog ==

= 6.0.6 | July 30th, 2025 =
* Improved: PHP 8.3 compatibility.
* Fixed: Unloads (Don't Load) now work properly on Elementor stylesheets.
* Improved: logic to process unloaded font styles in stylesheets is more streamlined now.
* Fixed: a fatal error which occurred if nothing was selected in the Advanced Settings > Used Subsets option.

= 6.0.5 | July 7th, 2025 =
* Fixed: class WPTT not found error. Oops!

= 6.0.4 | July 4th, 2025 =
* Added: compatibility for themes/plugins using the WPTT webfont loader.
* Improved: Optimize Local Fonts section is now full width, like the Dashboard.
* Improved: Moved Test Mode into the Dashboard section.

= 6.0.3 | June 24th, 2025 =
* Added: Smart Preload (Pro) promotional option.
* Added: Compatibility for the upcoming Elementor v3.30 release.
* Improved: Compatibility fixes are now moved into one place, and are only loaded on the condition of the respective plugin actually being activated.
* Improved: All settings related to fonts optimization are now grouped in the Optimize Local Fonts section under the Local Fonts tab.
* Several code improvements.

= 6.0.2 | June 12th, 2025 =
* Improved: the Google Fonts checker now runs through its own API endpoint, instead of WP's AJAX actions.
* Improved: the Disable Admin Bar Menu option now also disables the Google Fonts checker.
* Added: Real Cookie Banner, Borlabs Cookie Banner and Trustmary to the list of plugins which require additional configuration.
* Minor refactors for cleaner code and to fix minor security flaws.

= 6.0.1 | May 27th, 2025 =
* Fixed: Frontend Assets would still load, even when Disable Top Admin Bar Menu option was enabled.
* Fixed: some themes (like Enfold) are incompatible with wp-util. Refactored wp-util dependencies to vanilla JS in Google Fonts checker to no longer rely on it.
* Improved: really long URLs in the Dashboard are now wrapped.
* Fixed: when OMGF Pro isn't installed, Fallback Font Stack shouldn't be enabled.

= 6.0.0 - **2000 IQ edition** | May 26th, 2025 =
* Added: Google Fonts checker, which will notify you when a plugin or theme has added Google Fonts OMGF can't process (and provide a solution!)
* Improved: the menu in the Top Admin Bar now has a stoplight, which notifies you if there are any Google Fonts and/or configurational issues. It'll show green if all is well.
* Improved: the Task Manager is superseded by a brand-new Dashboard, which will give you a quick overview of:
  - If OMGF was able to process all Google Fonts on your site, and if not; show you where they were found with possible solutions.
  - If there were any configurational issues (e.g., known conflicts with other plugins, etc.).
  - Cache status,
  - Simple cache management tasks: Empty and Refresh.
* Improved: The settings screen got a fresh new coat of paint, fully aligned with [Daan.dev's new look](https://daan.dev/blog/rants/daan-dev-2-0/).
* Improved: Settings were moved around to move logical places:
  - The Detection Settings tab has been removed,
  - The Local Fonts tab is now fully dedicated to informing you about your Local Google Fonts configuration.
  - All options, that're not directly related to Locally Hosting Google Fonts but are directed more at optimization, are now moved to the Advanced Settings tab.
* Improved: Optimize for (D)TAP is now renamed to the more appropriate Developer Mode.
* Improved: Mailerlite users are now made aware of the fact that it loads iframes loading Google Fonts.
* Improved: if Disable Admin Bar Menu (prev. Disable Quick Access Menu) is enabled, the Admin Bar Menu will still show if there are issues to notify the administrators.
* Removed a bunch of old upgrade/update notifications.
* Tons of bug fixes and code and security improvements.

= 5.9.3 | May 16th, 2025 - THE LAST PATCH RELEASE BEFORE **OMGF V6!** =
* Tested with WP 6.8
* Added: compatibility with OptimizePress 3 theme
* Fixed: Variable font weights weren't processed properly during optimization.
* Fixed: Update notices weren't displayed properly.
* Various small bugfixes.

[ Changelog shortened ... ]

= 5.0.0 - **The Better, Bigger, Faster, Stronger, Cooler, Awesome-er Edition** | March 4th, 2022 =
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
