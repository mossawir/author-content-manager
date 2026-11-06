<?php
/**
 * Uninstall routine.
 *
 * By default every piece of data created through the plugin is preserved:
 * settings, custom post type and taxonomy definitions, author profiles and all
 * content. Data is only removed when the administrator enabled the
 * "Remove Data on Uninstall" setting.
 *
 * @package AuthorContentManager
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove plugin data for the current site.
 *
 * @return void
 */
function acm_uninstall_site() {
	$settings    = get_option( 'acm_settings' );
	$delete_data = is_array( $settings ) && ! empty( $settings['delete_data'] );

	if ( ! $delete_data ) {
		flush_rewrite_rules( false );

		return;
	}

	global $wpdb;

	$author_ids = get_posts(
		array(
			'post_type'        => 'acm_author',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'no_found_rows'    => true,
		)
	);

	foreach ( (array) $author_ids as $author_id ) {
		wp_delete_post( (int) $author_id, true );
	}

	delete_option( 'acm_settings' );
	delete_option( 'acm_post_types' );
	delete_option( 'acm_taxonomies' );
	delete_option( 'acm_author_custom_fields' );
	delete_option( 'acm_rewrite_needed' );
	delete_option( 'acm_version' );

	// Remove queued admin notices and field validation messages.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup of plugin transients.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_acm_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_acm_' ) . '%'
		)
	);

	flush_rewrite_rules( false );
}

if ( is_multisite() && function_exists( 'get_sites' ) ) {
	$acm_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( (array) $acm_site_ids as $acm_site_id ) {
		switch_to_blog( (int) $acm_site_id );
		acm_uninstall_site();
		restore_current_blog();
	}
} else {
	acm_uninstall_site();
}
