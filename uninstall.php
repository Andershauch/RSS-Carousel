<?php
/**
 * Uninstall routine for RSS News Carousel.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ntc_settings' );
delete_transient( 'ntc_invalid_feed_urls_notice' );

global $wpdb;

$patterns = array(
	'_transient_ntc_feed_data_%',
	'_transient_timeout_ntc_feed_data_%',
);

foreach ( $patterns as $pattern ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$pattern
		)
	);
}
