# OMGF | GDPR Compliant, Faster Google Fonts. Easy.

OMGF automagically caches the Google Fonts used by your theme/plugins locally. No configuration (or brains) required!

## Description

> How could using fonts via Google's service possibly run afoul of GDPR? The fact of the matter is that, when a font is requested by the user's browser, their IP is logged by Google and used for analytics.
> — Lifehacker

**Leverage Browser Cache**, **reduce DNS lookups/requests**, **reduce Cumulative Layout Shift** and make your Google Fonts **100% GDPR compliant** with OMGF!

OMGF is written with performance and user-friendliness in mind. It uses the Google Fonts API to automatically cache the fonts your theme and plugins use to **minimize DNS requests** and speed up your WordPress website.

## How Does It Work?

After installing the plugin, OMGF will automatically start looking for Google Fonts whenever a page is requested on your website.

All Google Fonts are listed in the **Optimize Local Fonts** section of OMGF's settings screen. There, you can choose to:

- _Preload_ fonts to **reduce Cumulative Layout Shift** above the fold,
- _Unload_ fonts that're not used by you, your theme and/or plugins,
- Set a _Fallback Font Stack_ (OMGF Pro required), to further **reduce Cumulative Layout Shift**, or
- _Replace_ (OMGF Pro required) font-families with system fonts to **speed up page loading times**!

### Other Features include

- **Variable Fonts** support,
- **Remove unused subsets** to reduce the size of the CSS stylesheet,
- **Remove Resource Hints** (preload, preconnect, dns-prefetch) pointing to `fonts.googleapis.com` or `fonts.gstatic.com`,
- **Ensure text remains visible during webfont load** by forcing the _font-display_ attribute to your Google Fonts,
- **Ensure text remains visible during webfont load** by forcing the _font-display_ attribute to all your other fonts! (OMGF Pro required),

### Additional Features in OMGF Pro

- **Multisite** support,
- "Dig deeper" to find Google Fonts and optimize further. OMGF Pro supports:
  - `@font-face` and `@import` statements inside **inline `<style>` blocks**,
  - `@font-face` and `@import` statements inside **local stylesheets** loaded by e.g. your theme and/or plugins,
  - Web Font Loader (`webfont.js`),
  - Early Access Google Fonts.
  - Material Icons support.
- Modify your fonts' `src: url()` attribute to fully integrate with your configuration,
  - Use this to serve fonts and the stylesheets from your CDN, or
  - To serve fonts from an alternative path (e.g. when you're using Security through Obscurity plugins like WP Hide, etc.), or
  - Set a relative path to easily migrate from development/staging areas to production/live, or
  - Anything you like!

_[Purchase OMGF Pro](https://daan.dev/wordpress/omgf-pro/) | [Documentation](https://daan.dev/docs/omgf-pro/) | [Tested Plugins & Themes](https://daan.dev/docs/omgf-pro/tested-themes-plugins/)_

## Installation

### Using GIT

1. From your terminal, `cd` to your plugins directory (usually `wp-content/plugins`)
1. Run the following command: `git clone https://github.com/Dan0sz/host-webfonts-locally.git host-webfonts-local`

### From the Wordpress Repository

1. From your WordPress administrator area, go to _Plugins > Add New_
1. Search for 'Daan van den Bergh'
1. Click the 'Install' button next to _OMGF | Host Google Fonts Locally_
1. Click 'Activate'

## Frequently Asked Questions

For the FAQ, [click here](https://daan.dev/docs/omgf-pro-faq).

## Support

For Support Queries, checkout the [Support Forum at Wordpress.org](https://wordpress.org/support/plugin/host-webfonts-local)

## Changelog

Visit the [Changelog at Wordpress.org](https://wordpress.org/plugins/host-webfonts-local/#developers)
