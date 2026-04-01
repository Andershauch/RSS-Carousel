=== RSS News Carousel ===
Contributors: rss-news-carousel
Tags: rss, feed, carousel, news, shortcode
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display filtered RSS and Atom feed items in a configurable carousel with shortcode support.

== Description ==

RSS News Carousel lets you:

* Add one or more RSS or Atom feed URLs.
* Prioritize stories by configured keywords.
* Cache feed results with the Transients API.
* Render feed items with a responsive frontend carousel.
* Control theme, layout, typography, and key display options from wp-admin.

Use the shortcode below anywhere shortcodes are supported:

[news_topic_carousel]

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **RSS News Carousel** in WordPress admin.
3. Go to **Settings > RSS News Carousel**.
4. Add your feed URLs and display settings.
5. Insert `[news_topic_carousel]` into a page, post, or supported builder widget.

== Frequently Asked Questions ==

= Does it work with Elementor? =

Yes. Add Elementor's Shortcode widget and insert `[news_topic_carousel]`.

= Does it fetch feeds on every page load? =

No. Feed results are cached using the WordPress Transients API based on the plugin settings.

= Can I refresh the feed cache manually? =

Yes. The settings page includes a **Refresh Cache** button.

== Changelog ==

= 1.0.1 =

* Prepared the plugin for release packaging.
* Improved lifecycle cleanup and added uninstall handling.
* Tightened settings and cache management.
* Removed MVP-only frontend hacks and cleaned asset loading.
* Updated documentation and packaging metadata.

== Upgrade Notice ==

= 1.0.1 =

Recommended release-ready update with cleanup, uninstall handling, and packaging improvements.
