# Changelog

## 2.1.3 - 2026-04-06

- Replaced the settings-page source ordering logic with a native reorder flow.
- Added explicit Up/Down controls so source priority can always be changed, even if drag behavior varies by browser.

## 2.1.2 - 2026-04-06

- Fixed the settings-page source list so drag-and-drop reordering works more reliably in wp-admin.
- Simplified the sortable list layout and tightened the sortable helper behavior for admin compatibility.

## 2.1.1 - 2026-04-06

- Hardened the sortable feed-source settings field so source order is saved reliably.
- Improved Danish text normalization for the read-more label and reduced mojibake fallback issues.

## 2.1.0 - 2026-04-06

- Added drag-and-drop source prioritization to the settings page for feed URLs.
- Applied source priority to carousel ordering after the recent-news window and before keyword ranking for older items.

## 2.0.0 - 2026-04-06

- Started the new major release line for RSS News Carousel.
- Removed duplicate cron hook registration so scheduled cache refreshes only run once per event.
- Shortened cache lifetime for failed feed refreshes to recover faster from transient upstream outages.
- Added namespace-based cache invalidation so settings updates and uninstall work reliably with persistent object caches.
- Localized the mobile swipe hint through PHP and hardened frontend default text handling for Danish characters.
- Replaced the Tottenham-specific defaults with generic Danish carousel text.

## 1.1.2 - 2026-04-02

- Added a first-run mobile swipe hint so users can immediately see that the carousel supports swiping.
- Smoothed the mobile swipe motion with a gentler, more horizontal transition.

## 1.1.1 - 2026-04-02

- Removed the mobile navigation arrows so small-screen users rely on swipe and autoplay only.
- Restored full-width mobile cards after removing the mobile arrows.
- Tweaked mobile card spacing so the source and date line sits cleanly below the image.

## 1.1.0 - 2026-04-02

- Added customizable frontend text settings for the carousel eyebrow, heading, and read-more label.
- Reworked the carousel header, spacing system, footer alignment, and side navigation placement across mobile and desktop.
- Improved mobile swipe behavior with stronger gesture feedback and kept the read-more link as the only clickable card action.

## 1.0.8 - 2026-04-01

- Improved carousel arrow feedback so clicks show a short accent glow without leaving a stuck pressed state.

## 1.0.7 - 2026-04-01

- Refined the mobile swipe animation for a more fluid, app-like feel.

## 1.0.6 - 2026-04-01

- Improved mobile swipe feedback so the cards glide more naturally during touch drags.
- Reworked the mobile header into a cleaner two-row layout.

## 1.0.5 - 2026-04-01

- Repositioned mobile carousel arrows to keep them clear of the card footer.

## 1.0.4 - 2026-04-01

- Added subtle animated feedback during touch swipes on mobile.

## 1.0.3 - 2026-04-01

- Restored mobile swipe support when carousel cards are fully clickable.
- Bumped the plugin version and refreshed the release package.

## 1.0.2 - 2026-04-01

- Added a styled admin help/manual section to the settings page.
- Added the new preferred shortcode `[rss_carousel]`.
- Kept legacy shortcode aliases for backward compatibility.
- Refined release packaging workflow.
- Added a reusable release build script for correct WordPress zip packages.

## 1.0.1 - 2026-04-01

- Prepared the plugin for release packaging.
- Added uninstall cleanup for plugin options and cached transients.
- Improved plugin metadata, lifecycle cleanup, and cache invalidation.
- Removed temporary frontend spacing hacks and cleaned renderer output.
- Corrected frontend text encoding and refined asset registration for release.
- Added package-hardening files for plugin directories and repository line endings.
