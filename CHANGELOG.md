# Changelog

All notable changes to this project are documented here. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-11-03

First release as **Drift: Welcome**, a free WordPress.org plugin by Drift Creative Systems. Based on Bonsai Dashboard 1.4.1, which carries on separately for Bonsai client sites. There is no data migration between the two.

### Added
- Settings → Drift: Welcome screen with **Welcome**, **Quick links**, **Colours** and **White Label** tabs, the Drift monogram in emerald (`#10b981`), a "View dashboard" button and the copyright and company details in the admin footer.
- `drift_welcome_quick_links` filter for adding, removing or reordering dashboard cards.
- Notice on the Dashboard, Plugins and settings screens when Bonsai Dashboard is also active (both would remove each other's panel).
- "Settings" link on the Plugins screen.
- Copyright and company details in the README, readme.txt and the main file header.
- `readme.txt`, `LICENSE` (GPLv2), WordPress.org icon and banners, release workflow with optional SVN deploy, `phpcs.xml.dist`.

### Changed
- Renamed to Drift: Welcome: slug and text domain `drift-welcome`, prefix `drift_welcome_` / `DRIFT_WELCOME_` / `Drift_Welcome_`.
- Stored data renamed: options `drift_welcome_settings`, `drift_welcome_white_label` and `drift_welcome_hidden_menus`, user meta `drift_welcome_agency_user`.
- Support card only shows once a Support URL is set. There's no default support desk.
- Theme Setup card only shows when the `theme-general-settings` page is in the user's admin menu.
- Pages, Posts and Team cards only show to users who can edit them.
- Default welcome heading is now "Welcome back, {name}", and the default message is a short generic line. Default colours are Drift dark and emerald.
- Dashboard widget title is "Welcome". The profile section is "Admin access".
- Colour overrides are added with `wp_add_inline_style()` rather than printed in `admin_head`.
- Requires PHP 7.4 (was 8.0).

### Removed
- Plugin Update Checker, Composer and `vendor/`. Updates come from WordPress.org.
- Bundled Bonsai Hub menu, header and suite installer, and the 1.4.0 Bonsai menu migration.
- Reading brand colours from the theme's ACF options fields. Use the Colours tab.
- Vision Website conflict notice.
