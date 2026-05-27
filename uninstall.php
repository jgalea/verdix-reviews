<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove plugin options.
delete_option( 'verdix_settings' );

// Optionally remove all review meta from posts.
// Uncomment the following if you want full cleanup on uninstall.
// global $wpdb;
// $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_verdix_review' ) );
