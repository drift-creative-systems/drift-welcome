![Drift Creative Systems](.github/banner.jpg)

# Drift: Welcome

Free WordPress plugin by **Drift Creative Systems**. Replaces the wp-admin dashboard with one welcome panel (logo, heading, message) and a grid of quick-link cards, so editors land somewhere useful. Optional white labelling of the admin bar, footer and login screen, and admin menus hidden from everyone except agency users. Settings are under **Settings → Drift: Welcome**.

- WordPress.org slug: `drift-welcome` (submission pending)
- Licence: GPL-2.0-or-later
- Requires WordPress 6.0+, PHP 7.4+

The user-facing documentation is [readme.txt](readme.txt), which WordPress.org displays. This file is for developers.

## Structure

```
drift-welcome.php                              Bootstrap: duplicate-install guard, constants, requires, init on plugins_loaded
includes/
  class-drift-welcome-settings.php             The drift_welcome_settings option: defaults, per-tab save (SECTIONS), sanitising
  class-drift-welcome-admin.php                Settings → Drift: Welcome: tabs, Welcome/Quick links/Colours forms, shared media_field()/color_field(), admin footer, Bonsai Dashboard conflict notice
  class-drift-welcome-white-label.php          White Label tab (drift_welcome_white_label): WordPress branding, admin bar logo, footer text, login screen
  class-drift-welcome-admin-access.php         "Agency user" profile checkbox and menu hiding/blocking for everyone else (drift_welcome_hidden_menus)
  class-drift-welcome-dashboard.php            Removes every other dashboard widget, registers the panel, builds the quick links
assets/
  dashboard.css                                The panel. Dashboard only
  admin.css                                    Settings screen. Settings screen only
  admin.js                                     jQuery: image pickers, colour pickers, custom cards repeater
uninstall.php                                  Removes the options on every site in a network, and the agency user meta
```

## How the dashboard replacement works

`wp_dashboard_setup` runs `Drift_Welcome_Dashboard::add_widget()` at priority 10 (registers `drift_welcome_panel`), then `strip_other_widgets()` at 999: explicit `remove_meta_box()` calls for the core widgets, then a sweep of `$wp_meta_boxes['dashboard']` that removes anything else. Our widget has to be registered before the sweep, or it would remove itself. `wp_welcome_panel` is removed outright.

Widgets are removed from `$wp_meta_boxes`, not hidden with CSS, so a plugin added later is swept up without this plugin knowing its widget ID.

**Bonsai Dashboard** does the same job. If both are active, each sweep removes the other's widget and the dashboard is empty. A notice on the Dashboard, Plugins and settings screens says so.

## Quick links

Built in `Drift_Welcome_Dashboard::get_quick_links()`, in this order. Each card only shows when it leads somewhere the user can go:

| Card | Shows when |
|---|---|
| Pages / Posts | User has `edit_pages` / `edit_posts` |
| Theme Setup | `theme-general-settings` (ACF Options Page convention) is in the user's admin menu |
| Team | The configured post type (default `team`) exists and the user can edit it |
| Analytics | A URL is set |
| Custom cards | Always, in save order. Rows without a label or URL are dropped at save time |
| Support | A URL is set |

Then `drift_welcome_quick_links` filters the list. Cards missing a label, URL or icon are dropped after the filter.

The custom cards repeater (`assets/admin.js`) clones a `<script type="text/template">` built from the same `render_card_row()` that renders saved rows, so they can't drift apart. Row indices only increment, so a removed-then-added row can't collide with one still on the page.

## Stored data

| Key | Where | What |
|---|---|---|
| `drift_welcome_settings` | option (not autoloaded) | Welcome, Quick links and Colours tabs. One form per tab; a save only touches that tab's keys (`Drift_Welcome_Settings::SECTIONS`) |
| `drift_welcome_white_label` | option (not autoloaded) | White Label tab. Every field blank = WordPress unchanged |
| `drift_welcome_hidden_menus` | option (not autoloaded) | Top-level menu slugs hidden from non-agency users. Unset = `default_hidden_menus()` |
| `drift_welcome_agency_user` | user meta | `'1'` on agency users |

Images are stored as attachment IDs, so they survive a domain change. All of it is removed in `uninstall.php`. Never rename these keys once released: sites have live data under them.

## Hooks

| Hook | Type | Purpose |
|---|---|---|
| `drift_welcome_quick_links` | filter | `$links`, `$settings`. The dashboard cards: `label`, `url`, `icon` (Dashicons class), optional `external`, `sub` |

```php
// Add a card for WooCommerce orders.
add_filter( 'drift_welcome_quick_links', function ( $links ) {
	$links[] = array(
		'label' => 'Orders',
		'url'   => admin_url( 'edit.php?post_type=shop_order' ),
		'icon'  => 'dashicons-cart',
	);
	return $links;
} );
```

## Development

- No build step. Edit `assets/*.js` / `*.css` directly.
- Coding standard: `phpcs.xml.dist` (WordPress, prefixes `drift_welcome` / `DRIFT_WELCOME`, PHP 7.4+).
- Run **Plugin Check** before every release. The PHPCS-based checks don't work in WordPress Playground (its filesystem can't `flock()`), so use a real install, e.g. WP-CLI with the SQLite drop-in:
  ```sh
  wp plugin install plugin-check --activate
  wp plugin check drift-welcome --include-low-severity-errors --include-low-severity-warnings \
    --require=./wp-content/plugins/plugin-check/cli.php
  ```
  Check a copy built from `.distignore`, not the repo, so dev files don't trip the `file_type` check.
- Menu hiding can lock people out of admin screens. Test any change to `Drift_Welcome_Admin_Access` with no agency users, one agency user, and a non-agency administrator.
- Directory guidelines review: the `wp-plugin-directory-guidelines` Claude Code skill.

## Releasing

1. Bump the version in **three** places: the `Version:` header, `DRIFT_WELCOME_VERSION` and `Stable tag:` in `readme.txt`. Add a changelog entry to both `readme.txt` and `CHANGELOG.md`.
2. Merge `develop` → `main`, then publish a GitHub release tagged `vX.Y.Z`.
3. `.github/workflows/release.yml` checks the three versions match, builds `drift-welcome.zip` from `.distignore` and attaches it to the release.
4. Once approved on WordPress.org, set the repo variable `WPORG_DEPLOY=true` and the secrets `SVN_USERNAME` / `SVN_PASSWORD`. Releases then deploy to SVN automatically, and readme/asset changes on `main` sync on their own (`wporg-assets.yml`).

WordPress.org assets (icon, banners, screenshots) live in `.wordpress-org/`. Screenshots still need adding as `screenshot-1.png` to `screenshot-4.png` to match `readme.txt`.

## Licence

GPL-2.0-or-later. See [LICENSE](LICENSE).

Copyright © 2026 Drift Creative Systems Ltd

Drift Creative Systems Ltd. Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF
