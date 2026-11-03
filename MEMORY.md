# MEMORY.md — Drift: Welcome

Decisions, gotchas and open items that aren't obvious from the code. Keep entries short and dated.

## Key decisions

- **2026-11-03: Forked from Bonsai Dashboard 1.4.1 as a free WordPress.org plugin.** Bonsai Dashboard stays on Bonsai client sites. No migration: every option, meta key and class has a new `drift_welcome_` name.
- **2026-11-03: Name "Welcome", not "Dashboard".** "Drift: Dashboard" is already the Electron desktop app (repo `drift-creative-systems/drift-dashboard`). Accent emerald `#10b981` / `#047857`; orange, sky, indigo, red and fuchsia were taken.
- **2026-11-03: Screen under Settings.** Every tab is `manage_options`. Without the Bonsai Hub there's no shared menu, and Settings is where admins look. Note `options-general.php` is in the default hidden menus, so non-agency admins lose it once an agency user exists, same as the Bonsai menu before.
- **2026-11-03: Bonsai-specific behaviour made conditional.** No default Support URL (was the Bonsai Zendesk). Theme Setup only when `theme-general-settings` is in the user's menu. Dropped the theme ACF `primary_colour` / `secondary_colour` read; the Colours tab covers it.
- **2026-11-03: Drift admin footer at priority 100**, after the White Label footer (99), so the company details stay on the plugin's own screen even when white labelling hides or replaces the footer elsewhere.
- **Carried over from Bonsai Dashboard:**
  - Widgets are removed from `$wp_meta_boxes`, not hidden with CSS.
  - One form per tab; a save only touches that tab's keys.
  - Agency user instead of "user ID 1": user 1 may be the client or not exist.

## Gotchas

- Bonsai Dashboard and Drift: Welcome together = empty dashboard (each sweep removes the other's widget). A notice shows on Dashboard, Plugins and the settings screen.
- The settings page relies on core's `options-head.php` for "Settings saved." (the redirect adds `settings-updated=true`). Don't also call `settings_errors()`, or it shows twice.
- `menu_url()` reads the built `$menu` / `$submenu`, so Theme Setup only appears for users who can open it. It only works after `admin_menu`, which is fine on the dashboard.
- Menu hiding blocks by URL on `current_screen`. AJAX and REST requests aren't blocked; it's a tidy-up for clients, not a security boundary.

## Open items / tech debt

- [ ] Screenshots for WordPress.org (`.wordpress-org/screenshot-1..4.png`).
- [ ] Run Plugin Check before submitting to WordPress.org. PHPCS (WordPress standard) and PHPCompatibilityWP 7.4+ were clean on 2026-11-03.
- [ ] Smoke test on a real install: dashboard, all four tabs save, login screen, agency user lockout cases.
- [ ] Possible: widget chrome (title bar, drag handle) is still core postbox markup with the toggle hidden. Revisit only if it looks distracting.
