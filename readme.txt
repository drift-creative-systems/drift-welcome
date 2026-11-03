=== Drift: Welcome ===
Contributors: driftcreativesystems
Tags: dashboard, admin dashboard, white label, client dashboard, login screen
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace the wp-admin dashboard with one welcome panel and a grid of quick links, so editors land somewhere useful.

== Description ==

The standard WordPress dashboard is full of things site editors don't need: At a Glance, Activity, Quick Draft, WordPress news and whatever widgets other plugins have added. Drift: Welcome removes all of them and puts a single panel in their place: your logo, a welcome message and large quick-link cards to the parts of the site people actually edit.

Features:

* **Welcome panel** with an optional logo, a heading (or "Welcome back" and the user's name) and a short message with links, bold text and lists.
* **Quick links** to Pages and Posts, plus Team, Theme Setup, Analytics and Support when they apply. Each card only shows when it leads somewhere the user can go.
* **Custom cards** for anything else: a client portal, a booking system, a shared drive. Pick any Dashicon and choose whether it opens in a new tab.
* **Colours**: set the panel and card colours to match the site, or keep the built-in ones.
* **Every other dashboard widget is removed**, including ones added by other plugins, so nothing creeps back in later.
* **White Label** (optional): hide the WordPress logo, footer credit and version number, add your own admin bar logo and footer text, and restyle the login screen with your logo, background and colours.
* **Admin menus** (optional): mark yourself as an "Agency user" and hide menus such as Plugins, Appearance and Settings from everyone else, whatever their role. Hidden pages are blocked by URL too.
* Everything is removed when you delete the plugin.

= Privacy =

Drift: Welcome doesn't connect to any external service, track you or collect data. It adds nothing to the front end of your site, apart from the admin bar logo for logged-in users if you set one.

= For developers =

* `drift_welcome_quick_links` (filter, `$links`, `$settings`): the cards on the dashboard. Each card is an array with `label`, `url`, `icon` (a Dashicons class) and optional `external` (bool) and `sub` (a second line of text).

Example, to add a card for WooCommerce orders:

`add_filter( 'drift_welcome_quick_links', function ( $links ) { $links[] = array( 'label' => 'Orders', 'url' => admin_url( 'edit.php?post_type=shop_order' ), 'icon' => 'dashicons-cart' ); return $links; } );`

Source code, issues and pull requests: [github.com/drift-creative-systems/drift-welcome](https://github.com/drift-creative-systems/drift-welcome).

= Copyright =

Copyright © 2026 Drift Creative Systems Ltd

Drift Creative Systems Ltd. Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF

== Installation ==

1. In your WordPress admin, go to Plugins → Add New, search for "Drift: Welcome", then install and activate it.
2. Go to Settings → Drift: Welcome to add your logo and message, and the Analytics and Support links.
3. Open the Dashboard to see the result.

== Frequently Asked Questions ==

= Where have the other dashboard widgets gone? =

They're removed while the plugin is active, including widgets added by other plugins. Deactivate Drift: Welcome and they come back.

= Why isn't the Support (or Analytics) card showing? =

Those cards only appear once you've entered a URL under Settings → Drift: Welcome → Quick links.

= What are the Team and Theme Setup cards? =

Team links to a "team" post type, if your theme has one. If it uses a different name, such as "staff", enter it on the Quick links tab. Theme Setup links to a theme options page with the slug `theme-general-settings` (common with ACF Options Pages). Each card only shows if that screen exists and the user can open it.

= How does menu hiding work? Can I lock myself out? =

Tick "Agency user" on your own profile. From then on, the menus ticked on the White Label tab are hidden from everyone who isn't an agency user, and only agency users can change the White Label settings. Until at least one agency user exists, nothing is hidden. The Dashboard and each user's own Profile are never hidden, so the checkbox is always reachable.

= Does it work with White Label CMS? =

Use one or the other. Both change the login screen and admin bar. The White Label tab warns you while White Label CMS is active.

== Screenshots ==

1. The dashboard with the welcome panel and quick links.
2. The Welcome tab: logo, heading and message.
3. The Quick links tab, with custom cards.
4. The White Label tab: branding, login screen and admin menus.

== Changelog ==

= 1.0.0 =
* First public release: welcome panel, quick links, custom cards, colours, white labelling of the admin and login screen, and admin menu hiding for non-agency users.

== Upgrade Notice ==

= 1.0.0 =
First public release.
