<?php
/**
 * Uninstall
 *
 * @package Bulk Generate Thumbnails
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/* For Single site */
if ( ! is_multisite() ) {
	$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
	foreach ( $blogusers as $user ) {
		delete_user_option( $user->ID, 'bgth_per_page', false );
		delete_user_option( $user->ID, 'bgth_mail_send', false );
		delete_user_option( $user->ID, 'bgth_filter_user', false );
		delete_user_option( $user->ID, 'bgth_filter_mime_type', false );
		delete_user_option( $user->ID, 'bgth_filter_monthly', false );
	}
} else {
	/* For Multisite */
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->prefix}blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blogid ) {
		switch_to_blog( $blogid );
		$blogusers = get_users(
			array(
				'blog_id' => $blogid,
				'fields' => array( 'ID' ),
			)
		);
		foreach ( $blogusers as $user ) {
			delete_user_option( $user->ID, 'bgth_per_page', false );
			delete_user_option( $user->ID, 'bgth_mail_send', false );
			delete_user_option( $user->ID, 'bgth_filter_user', false );
			delete_user_option( $user->ID, 'bgth_filter_mime_type', false );
			delete_user_option( $user->ID, 'bgth_filter_monthly', false );
		}
	}
	switch_to_blog( $original_blog_id );
}
