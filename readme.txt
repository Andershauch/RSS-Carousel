=== RSS News Carousel ===
Contributors: rss-news-carousel
Tags: rss, feed, carousel, news, shortcode
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.3
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

= 2.1.3 =

* Replaced the settings-page source ordering logic with a native reorder flow.
* Added explicit Up/Down controls so source priority can always be changed, even if drag behavior varies by browser.

= 2.1.2 =

* Fixed the settings-page source list so drag-and-drop reordering works more reliably in wp-admin.
* Simplified the sortable list layout and tightened the sortable helper behavior for admin compatibility.

= 2.1.1 =

* Hardened the sortable feed-source settings field so source order is saved reliably.
* Improved Danish text normalization for the read-more label and reduced mojibake fallback issues.

= 2.1.0 =

* Added drag-and-drop source prioritization in the settings screen for feed URLs.
* Applied source priority to carousel ordering after the recent-news window and before keyword ranking for older items.

= 2.0.0 =

* Starts the new major release line for RSS News Carousel.
* Removed duplicate scheduled refresh registration so cache rebuilds only run once per cron event.
* Reduced the cache lifetime for failed feed refreshes so the carousel recovers faster after feed outages.
* Improved cache invalidation for sites using persistent object cache backends.
* Localized the mobile swipe hint and hardened default frontend text handling for Danish characters.
* Replaced the Tottenham-specific defaults with generic Danish carousel copy.

= 1.1.2 =

* Added a first-run mobile swipe hint so users can immediately see that the carousel supports swiping.
* Smoothed the mobile swipe motion with a gentler, more horizontal transition.

= 1.1.1 =

* Removed mobile navigation arrows and let the carousel use swipe and autoplay only on small screens.
* Restored full-width mobile cards after removing the mobile arrows.
* Tweaked mobile card spacing so the source and date line sits cleanly below the image.

= 1.1.0 =

* Added customizable frontend text settings for the carousel header and read-more link.
* Reworked the mobile and desktop carousel layout, spacing, and navigation placement.
* Improved mobile swipe behavior and made the read-more link the only clickable card action.

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

= 2.1.3 =

Recommended update with native source reordering in settings and reliable Up/Down fallback controls.

= 2.1.2 =

Recommended update with a more reliable drag-and-drop source-priority list in settings.

= 2.1.1 =

Recommended update with a more robust sortable feed-source settings field and safer Danish text handling.

= 2.1.0 =

Recommended update with sortable feed-source priority in settings and improved source-aware item ordering.

= 2.0.0 =

Recommended major update with safer cache invalidation, faster recovery from feed outages, and improved Danish text handling.

= 1.1.2 =

Recommended update with clearer mobile swipe onboarding and smoother swipe motion.

= 1.1.1 =

Recommended update with the latest mobile carousel polish and full-width card layout improvements.

= 1.1.0 =

Recommended update with the new customizable text settings, carousel layout polish, and improved mobile swipe behavior.

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
