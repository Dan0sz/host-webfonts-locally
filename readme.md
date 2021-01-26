# OMGF | Host Google Fonts Locally

OMGF automagically caches the Google Fonts used by your theme/plugins locally. No configuration (or brains) required!

## Description

> How could using fonts via Google's service possibly run afoul of GDPR? The fact of the matter is that, when a font is requested by the user's browser, their IP is logged by Google and used for analytics.
> — Lifehacker

Leverage Browser Cache, Minimize DNS requests and serve your Google Fonts in a 100% GDPR compliant way with OMGF!

OMGF is written with performance and user-friendliness in mind. It uses the Google Fonts Helper API to automatically cache the fonts your theme and plugins use to **minimize DNS requests** and speed up your WordPress website.

### How Does It Work?

After installing the plugin, OMGF runs silently in the background and captures any requests made to fonts.googleapis.com or fonts.gstatic.com. When a webpage is first loaded, it reroutes these requests to its own Download API and copies the fonts over to your server. Then it generates a stylesheet for your fonts including EOT, TTF, WOFF and WOFF2 formats to guarantee maximum cross browser compatibility!

When the fonts are downloaded and the stylesheet is generated, it rewrites every URL pointing to fonts.googleapis.com or fonts.gstatic.com to the locally hosted variant.

Please keep in mind that, although I try to make the configuration of this plugin as easy as possible, the concept of locally hosting a file or optimizing Google Fonts for *Pagespeed Insights* or *GT Metrix* has proven to be confusing for some people. If you're not sure of what your doing, please consult a SEO expert or Webdeveloper to help you with the configuration of this plugin or [hire me to do it for you](https://ffw.press/wordpress/omgf-expert-configuration/).

### Features
- Automatically replace registered/enqueued Google Fonts in `wp_head()` with local copies,
- Automatically remove registered/enqueued Google Fonts from `wp_head()`,
- Manage Optimized Google Fonts,
  - Preload above the fold fonts,
  - Don't load certain fonts or entire stylesheets.
- Leverage the font-display (swap) option,
- Serve fonts from CDN,
- Use OMGF with *security through obscurity* plugins.

### Features in the PRO version
Everything in the free version, plus:
- Automatically remove/replace all Google Fonts throughout the entire HTML document,
- Combine all Google Fonts (made by your theme and/or plugins) requests into one file,
- Deduplicate Google Fonts requests,
- Reduce loading time and page size, by forcing the used subset(s) for all Google Fonts requests,
- Remove Resource Hints (preload, preconnect, dns-prefetch) pointing to fonts.googleapis.com or fonts.gstatic.com.

**[Documentation](https://ffw.press/docs/omgf-pro/) | [Purchase OMGF Pro](https://ffw.press/wordpress/omgf-pro/)**


## Installation

### Using GIT

1. From your terminal, `cd` to your plugins directory (usually `wp-content/plugins`)
1. Run the following command: `git clone https://github.com/Dan0sz/host-webfonts-locally.git host-webfonts-local`

### From the Wordpress Repository

1. From your WordPress administrator area, go to *Plugins > Add New*
1. Search for 'Daan van den Bergh'
1. Click the 'Install' button next to *OMGF | Host Google Fonts Locally*
1. Click 'Activate'

## Frequently Asked Questions

Visit the [FAQ at Wordpress.org](https://wordpress.org/plugins/host-webfonts-local/#faq)

## Support

For Support Queries, checkout the [Support Forum at Wordpress.org](https://wordpress.org/support/plugin/host-webfonts-local)

## Changelog

Visit the [Changelog at Wordpress.org](https://wordpress.org/plugins/host-webfonts-local/#developers)
