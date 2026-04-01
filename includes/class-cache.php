<?php
/**
 * Cache handling for normalized feed results.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps the Transients API for feed result caching.
 */
class NTC_Cache {

	/**
	 * Transient key prefix.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'ntc_feed_data_';

	/**
	 * Retrieves cached feed data for a settings payload.
	 *
	 * @param array $settings Plugin settings.
	 * @return array|false
	 */
	public function get( array $settings ) {
		$cached = get_transient( $this->get_key( $settings ) );

		return is_array( $cached ) ? $cached : false;
	}

	/**
	 * Stores feed data in a transient.
	 *
	 * @param array $settings Plugin settings.
	 * @param array $data     Cached feed payload.
	 * @param int   $minutes  Cache lifetime in minutes.
	 * @return bool
	 */
	public function set( array $settings, array $data, $minutes ) {
		$expiration = max( 1, absint( $minutes ) ) * MINUTE_IN_SECONDS;

		return set_transient( $this->get_key( $settings ), $data, $expiration );
	}

	/**
	 * Deletes cached feed data for a settings payload.
	 *
	 * @param array $settings Plugin settings.
	 * @return bool
	 */
	public function delete( array $settings ) {
		return delete_transient( $this->get_key( $settings ) );
	}

	/**
	 * Deletes all plugin transients stored in the options table.
	 *
	 * @return void
	 */
	public function delete_all() {
		global $wpdb;

		$option_names = array(
			'_transient_' . self::TRANSIENT_PREFIX . '%',
			'_transient_timeout_' . self::TRANSIENT_PREFIX . '%',
		);

		foreach ( $option_names as $option_name ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$option_name
				)
			);
		}
	}

	/**
	 * Builds a stable transient key from plugin settings.
	 *
	 * @param array $settings Plugin settings.
	 * @return string
	 */
	private function get_key( array $settings ) {
		$normalized_settings = $this->sort_settings( $settings );

		return self::TRANSIENT_PREFIX . md5( NTC_VERSION . '|' . wp_json_encode( $normalized_settings ) );
	}

	/**
	 * Recursively sorts settings so the cache key stays stable.
	 *
	 * @param array $settings Plugin settings.
	 * @return array
	 */
	private function sort_settings( array $settings ) {
		foreach ( $settings as $key => $value ) {
			if ( is_array( $value ) ) {
				$settings[ $key ] = $this->sort_settings( $value );
			}
		}

		ksort( $settings );

		return $settings;
	}
}
