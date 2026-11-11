<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'verdix_settings' );
delete_option( 'verdix_rating_sort_backfilled' );
delete_option( 'verdix_migration_stop_schema' );
delete_transient( 'verdix_migratable_count' );

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_verdix_rating_sort' ) );
