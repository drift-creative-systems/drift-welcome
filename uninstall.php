<?php
/**
 * Removes the plugin's options, on every site in a network, and the
 * "Agency user" flag from every user. The dashboard replacement itself is
 * runtime only, so there's nothing else to clean up.
 *
 * @package Drift_Welcome
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Removes everything the plugin stored on the current site.
 *
 * @return void
 */
function drift_welcome_uninstall_site() {
	delete_option( 'drift_welcome_settings' );
	delete_option( 'drift_welcome_white_label' );
	delete_option( 'drift_welcome_hidden_menus' );
}

if ( is_multisite() ) {
	$drift_welcome_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $drift_welcome_site_ids as $drift_welcome_site_id ) {
		switch_to_blog( $drift_welcome_site_id );
		drift_welcome_uninstall_site();
		restore_current_blog();
	}
} else {
	drift_welcome_uninstall_site();
}

// User meta is shared across a network, so it only needs removing once.
delete_metadata( 'user', 0, 'drift_welcome_agency_user', '', true );
