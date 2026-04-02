# Changelog

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
