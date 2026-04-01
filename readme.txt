=== RSS News Carousel ===
Contributors: rss-news-carousel
Tags: rss, feed, carousel, news, shortcode
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.8
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

[rss_carousel]

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **RSS News Carousel** in WordPress admin.
3. Go to **Settings > RSS News Carousel**.
4. Add your feed URLs and display settings.
5. Insert `[rss_carousel]` into a page, post, or supported builder widget.

== Frequently Asked Questions ==

= Does it work with Elementor? =

Yes. Add Elementor's Shortcode widget and insert `[rss_carousel]`.

= Does it fetch feeds on every page load? =

No. Feed results are cached using the WordPress Transients API based on the plugin settings.

= Can I refresh the feed cache manually? =

Yes. The settings page includes a **Refresh Cache** button.

== Changelog ==

= 1.0.8 =

* Improved carousel arrow feedback so clicks show a short accent glow without leaving a stuck pressed state.

= 1.0.7 =

* Refined the mobile swipe animation for a more fluid, app-like feel.

= 1.0.6 =

* Improved mobile swipe feedback so cards glide more naturally during touch drags.
* Refined the mobile header layout into a cleaner two-row structure.

= 1.0.5 =

* Repositioned mobile carousel arrows so they no longer overlap the card footer.

= 1.0.4 =

* Added subtle touch-swipe feedback on mobile for a smoother carousel interaction.

= 1.0.3 =

* Restored swipe support on mobile when cards are fully clickable.
* Updated release packaging and versioning for the latest stable build.

= 1.0.2 =

* Added a styled admin help/manual section on the settings page.
* Introduced the new preferred shortcode `[rss_carousel]`.
* Kept legacy shortcode aliases for backward compatibility.
* Refined plugin packaging workflow for cleaner release zips.

= 1.0.1 =

* Prepared the plugin for release packaging.
* Improved lifecycle cleanup and added uninstall handling.
* Tightened settings and cache management.
* Removed MVP-only frontend hacks and cleaned asset loading.
* Updated documentation and packaging metadata.

== Upgrade Notice ==

= 1.0.8 =

Recommended update with cleaner carousel arrow interaction states.

= 1.0.7 =

Recommended update with more natural mobile swipe feedback.

= 1.0.6 =

Recommended update with improved mobile swipe feel and cleaner mobile header layout.

= 1.0.5 =

Recommended update with improved mobile carousel controls.

= 1.0.4 =

Recommended update with improved mobile swipe feedback.

= 1.0.3 =

Recommended update with restored mobile swipe support and refreshed release packaging.

= 1.0.2 =

Recommended update with improved shortcode naming, admin guidance, and refreshed packaging.

= 1.0.1 =

Recommended release-ready update with cleanup, uninstall handling, and packaging improvements.
