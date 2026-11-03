<?php
/**
 * Plugin Name:       Drift: Welcome
 * Plugin URI:        https://driftcreativesystems.co.uk/
 * Description:       Replaces the wp-admin dashboard with one welcome panel and a grid of quick links, so editors land somewhere useful. Optional white labelling of the admin bar, footer and login screen, and admin menus hidden from everyone except agency users.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Drift Creative Systems
 * Author URI:        https://driftcreativesystems.co.uk/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       drift-welcome
 *
 * Copyright © 2026 Drift Creative Systems Ltd
 *
 * @package Drift_Welcome
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Duplicate install guard
|--------------------------------------------------------------------------
| A second copy (e.g. a GitHub "Source code" zip unpacked alongside the real
| folder) would fatal on redeclared classes. Bail and tell the admin instead.
*/
if ( defined( 'DRIFT_WELCOME_VERSION' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Drift: Welcome is installed more than once. Please delete the duplicate plugin folder.', 'drift-welcome' ) . '</p></div>';
		}
	);
	return;
}

define( 'DRIFT_WELCOME_VERSION', '1.0.0' );
define( 'DRIFT_WELCOME_FILE', __FILE__ );
define( 'DRIFT_WELCOME_PATH', plugin_dir_path( __FILE__ ) );
define( 'DRIFT_WELCOME_URL', plugin_dir_url( __FILE__ ) );

require_once DRIFT_WELCOME_PATH . 'includes/class-drift-welcome-settings.php';
require_once DRIFT_WELCOME_PATH . 'includes/class-drift-welcome-admin.php';
require_once DRIFT_WELCOME_PATH . 'includes/class-drift-welcome-admin-access.php';
require_once DRIFT_WELCOME_PATH . 'includes/class-drift-welcome-white-label.php';
require_once DRIFT_WELCOME_PATH . 'includes/class-drift-welcome-dashboard.php';

add_action(
	'plugins_loaded',
	function () {
		Drift_Welcome_Admin::init();
		Drift_Welcome_Admin_Access::init();
		Drift_Welcome_White_Label::init();
		Drift_Welcome_Dashboard::init();
	}
);
