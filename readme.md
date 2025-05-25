# OMGF | GDPR Compliant, Faster Google Fonts. Easy.

[![Github CI](https://github.com/Dan0sz/host-webfonts-locally/actions/workflows/tests.yml/badge.svg)](https://github.com/Dan0sz/host-webfonts-locally/actions/workflows/tests.yml) [![codecov](https://codecov.io/gh/Dan0sz/host-webfonts-locally/graph/badge.svg?token=VNS22P8ZH2)](https://codecov.io/gh/Dan0sz/host-webfonts-locally) ![WordPress version](https://img.shields.io/wordpress/plugin/v/host-webfonts-local.svg) ![WordPress Rating](https://img.shields.io/wordpress/plugin/r/host-webfonts-local.svg) ![WordPress Downloads](https://img.shields.io/wordpress/plugin/dt/host-webfonts-local.svg)

**OMGF can be downloaded for free without any paid subscription
from [the official WordPress repository](https://wordpress.org/plugins/host-webfonts-local/).**

OMGF automagically caches the Google Fonts used by your theme/plugins locally. No configuration (or brains) required!

## Description

> How could using fonts via Google's service possibly run afoul of GDPR? The fact of the matter is that, when a font is
> requested by the user's
> browser, their IP is logged by Google and used for analytics.
> — Lifehacker

**Leverage Browser Cache**, **reduce DNS lookups/requests**, **reduce Cumulative Layout Shift** and make your Google
Fonts **100% GDPR compliant**
with OMGF!

OMGF is written with performance and user-friendliness in mind. It uses the Google Fonts API to automatically cache the
fonts your theme and plugins
use to **minimize DNS requests** and speed up your WordPress website.

## How Does It Work?

After installing and configuring the plugin, OMGF will automatically start looking for Google Fonts whenever a page is
requested on your website.

All Google Fonts are listed in the **Optimize Local Fonts** section of OMGF's settings screen. There, you can choose to:

- _Preload_ fonts to **reduce Cumulative Layout Shift** above the fold,
- _Unload_ fonts that're not used by you, your theme and/or plugins,
- Set a _Fallback Font Stack_ (OMGF Pro required), to further **reduce Cumulative Layout Shift**, or
- _Replace_ (OMGF Pro required) font-families with system fonts to **speed up page loading times**!

### Other Features include

- The **integrated Google Fonts checker** sniffs through the network requests on your website on each pageload. If it
  still finds externally hosted Google Fonts after optimization, it will notify you and provide solutions where
  possible.
- **Variable Fonts** support,
- Automatically **Remove unused subsets** to reduce the size of the CSS stylesheet up to 90%!
- **Remove Resource Hints** (preload, preconnect, dns-prefetch) pointing to `fonts.googleapis.com` or
  `fonts.gstatic.com`,
- **Ensure text remains visible during webfont load** by forcing the _font-display_ attribute to your Google Fonts,
- **Ensure text remains visible during webfont load** by forcing the _font-display_ attribute to all your other fonts! (
  OMGF Pro required),

### Additional Features in OMGF Pro

- Run the Google Fonts checker in the frontend (for all users) to organically check for present external Google Fonts
  throughout your site.
- Automatically configures itself to make sure all externally hosted Google Fonts on your site are hosted locally. OMGF
  Pro supports:
	- `@font-face` and `@import` statements inside **inline `<style>` blocks**,
	- `@font-face` and `@import` statements inside **local stylesheets** loaded by e.g. your theme and/or plugins,
	- `@font-face` and `@import` statements inside **externally hosted stylesheets** loaded by your theme and/or
	  plugins,
	- Web Font Loader (`webfont.js`),
	- Async Google Fonts (loaded using JS)
	- Material Icons.
- **Multisite** and **WPML** support.
- Whitelabel stylesheets, which removes branding and comments from the stylesheets to further reduce the size.
- Modify your fonts' `src: url()` attribute to fully integrate with your configuration,
	- Use this to serve fonts and the stylesheets from your CDN, or
	- To serve fonts from an alternative path (e.g. when you're using Security through Obscurity plugins like WP Hide,
	  etc.), or
	- Anything you like!
- Dev Mode, which allows you to easily migrate between Development, Staging/Testing, Acceptance and Production
  environments.

_[Purchase OMGF Pro](https://daan.dev/wordpress/omgf-pro/) | [Documentation](https://daan.dev/docs/omgf-pro/) | [Tested Plugins & Themes](https://daan.dev/docs/omgf-pro/tested-themes-plugins/)_

## Installation

### Manually

1. Download
   the [latest release](https://github.com/Dan0sz/host-webfonts-locally/releases/latest/download/host-webfonts-local.zip)
2. From your WordPress administrator area, go to _Plugins > Add New_
3. Click _Upload Plugin_ and select the ZIP file you downloaded in step 1
4. Activate the plugin

### From the Wordpress Repository

1. From your WordPress administrator area, go to _Plugins > Add New_
2. Search for 'Daan van den Bergh'
3. Click the 'Install' button next to _OMGF | Host Google Fonts Locally_
4. Click 'Activate'

## Frequently Asked Questions

For the FAQ, [click here](https://daan.dev/docs/omgf-pro-faq).

## Support

For Support Queries, checkout
the [Support Forum at Wordpress.org](https://wordpress.org/support/plugin/host-webfonts-local)

## Changelog

Visit the [Changelog at Wordpress.org](https://wordpress.org/plugins/host-webfonts-local/#developers)
