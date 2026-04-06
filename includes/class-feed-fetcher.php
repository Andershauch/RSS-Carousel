<?php
/**
 * Feed fetching and aggregation.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches, normalizes, filters, and caches feed items.
 */
class NTC_Feed_Fetcher {

	/**
	 * Maximum number of items to read per feed.
	 *
	 * @var int
	 */
	const MAX_ITEMS_PER_FEED = 15;

	/**
	 * Recent-news window in seconds.
	 *
	 * Items published within this rolling window are always shown first.
	 *
	 * @var int
	 */
	const RECENT_ITEMS_WINDOW = 2 * DAY_IN_SECONDS;

	/**
	 * Settings handler.
	 *
	 * @var NTC_Settings
	 */
	private $settings;

	/**
	 * Item normalizer.
	 *
	 * @var NTC_Item_Normalizer
	 */
	private $item_normalizer;

	/**
	 * Keyword filter.
	 *
	 * @var NTC_Keyword_Filter
	 */
	private $keyword_filter;

	/**
	 * Cache handler.
	 *
	 * @var NTC_Cache
	 */
	private $cache;

	/**
	 * Class constructor.
	 *
	 * @param NTC_Settings        $settings        Settings handler.
	 * @param NTC_Item_Normalizer $item_normalizer Item normalizer.
	 * @param NTC_Keyword_Filter  $keyword_filter  Keyword filter.
	 * @param NTC_Cache           $cache           Cache handler.
	 */
	public function __construct( NTC_Settings $settings, NTC_Item_Normalizer $item_normalizer, NTC_Keyword_Filter $keyword_filter, NTC_Cache $cache ) {
		$this->settings        = $settings;
		$this->item_normalizer = $item_normalizer;
		$this->keyword_filter  = $keyword_filter;
		$this->cache           = $cache;
	}

	/**
	 * Returns aggregated feed data.
	 *
	 * @return array
	 */
	public function get_feed_data() {
		$settings = $this->settings->get_settings();
		$cached   = $this->cache->get( $settings );

		if ( false !== $cached ) {
			return $cached;
		}

		$result = array(
			'items'   => array(),
			'message' => '',
			'errors'  => array(),
		);

		$feed_urls = $this->get_feed_urls( $settings );

		if ( empty( $feed_urls ) ) {
			$result['message'] = __( 'Add one or more RSS feed URLs in the plugin settings.', 'rss-news-carousel' );
			$this->cache_result( $settings, $result );

			return $result;
		}

		$this->load_feed_functions();

		$items  = array();
		$errors = array();

		foreach ( $feed_urls as $source_priority => $feed_url ) {
			$feed = fetch_feed( $feed_url );

			if ( is_wp_error( $feed ) ) {
				$errors[] = sanitize_text_field( $feed->get_error_message() );
				continue;
			}

			if ( ! is_object( $feed ) || ! method_exists( $feed, 'get_items' ) ) {
				$errors[] = __( 'A feed could not be read.', 'rss-news-carousel' );
				continue;
			}

			$feed_items = $feed->get_items( 0, self::MAX_ITEMS_PER_FEED );

			if ( empty( $feed_items ) ) {
				continue;
			}

			$items = array_merge(
				$items,
				$this->apply_source_priority(
					$this->item_normalizer->normalize_items( $feed_items, $feed ),
					$feed_url,
					(int) $source_priority
				)
			);
		}

		$items = $this->keyword_filter->prioritize_items( $items, $settings['keywords'] );
		$items = $this->remove_duplicates( $items );
		$items = $this->sort_items( $items );

		if ( ! empty( $settings['items_limit'] ) ) {
			$items = array_slice( $items, 0, absint( $settings['items_limit'] ) );
		}

		$result['items']  = $items;
		$result['errors'] = array_values( array_unique( array_filter( $errors ) ) );

		if ( empty( $items ) ) {
			$result['message'] = $this->get_empty_message( $settings, $result['errors'] );
		}

		$this->cache_result( $settings, $result );

		return $result;
	}

	/**
	 * Clears the cached feed data for the current settings.
	 *
	 * @return bool
	 */
	private function clear_cache() {
		return $this->cache->delete( $this->settings->get_settings() );
	}

	/**
	 * Rebuilds the cached feed data immediately.
	 *
	 * @return array
	 */
	public function refresh_cache() {
		$this->clear_cache();

		return $this->get_feed_data();
	}

	/**
	 * Stores a feed result with a production-safe cache lifetime.
	 *
	 * Failed refreshes are cached briefly so transient upstream outages do not
	 * blank the carousel for the full normal TTL.
	 *
	 * @param array $settings Plugin settings.
	 * @param array $result   Feed result payload.
	 * @return bool
	 */
	private function cache_result( array $settings, array $result ) {
		$cache_minutes = isset( $settings['cache_minutes'] ) ? absint( $settings['cache_minutes'] ) : 30;

		if ( $this->is_failed_result( $result ) ) {
			$cache_minutes = min( $cache_minutes, 5 );
		}

		return $this->cache->set( $settings, $result, $cache_minutes );
	}

	/**
	 * Returns whether a feed result represents a failed refresh.
	 *
	 * @param array $result Feed result payload.
	 * @return bool
	 */
	private function is_failed_result( array $result ) {
		$items  = isset( $result['items'] ) && is_array( $result['items'] ) ? $result['items'] : array();
		$errors = isset( $result['errors'] ) && is_array( $result['errors'] ) ? $result['errors'] : array();

		return ! empty( $errors ) && empty( $items );
	}

	/**
	 * Returns escaped fallback markup for empty states.
	 *
	 * @param string $message Optional fallback message.
	 * @return string
	 */
	public function get_fallback_markup( $message = '' ) {
		if ( '' === $message ) {
			$message = __( 'No news items are available right now.', 'rss-news-carousel' );
		}

		return sprintf(
			'<p class="ntc-feed-fallback">%s</p>',
			esc_html( $message )
		);
	}

	/**
	 * Loads WordPress feed functions when needed.
	 *
	 * @return void
	 */
	private function load_feed_functions() {
		if ( ! function_exists( 'fetch_feed' ) ) {
			require_once ABSPATH . WPINC . '/feed.php';
		}
	}

	/**
	 * Returns normalized feed URLs from settings.
	 *
	 * @param array $settings Plugin settings.
	 * @return array
	 */
	private function get_feed_urls( array $settings ) {
		$rss_feeds = isset( $settings['rss_feeds'] ) ? (string) $settings['rss_feeds'] : '';
		$feed_urls = preg_split( '/\r\n|\r|\n/', $rss_feeds );
		$valid     = array();

		if ( empty( $feed_urls ) ) {
			return $valid;
		}

		foreach ( $feed_urls as $feed_url ) {
			$feed_url = trim( $feed_url );
			$feed_url = esc_url_raw( $feed_url );

			if ( empty( $feed_url ) || ! wp_http_validate_url( $feed_url ) ) {
				continue;
			}

			$valid[] = $feed_url;
		}

		return array_values( array_unique( $valid ) );
	}

	/**
	 * Applies source metadata used for downstream prioritization.
	 *
	 * @param array  $items           Normalized feed items.
	 * @param string $feed_url        Feed URL.
	 * @param int    $source_priority Source priority index.
	 * @return array
	 */
	private function apply_source_priority( array $items, $feed_url, $source_priority ) {
		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				unset( $items[ $index ] );
				continue;
			}

			$items[ $index ]['ntc_source_feed_url'] = (string) $feed_url;
			$items[ $index ]['ntc_source_priority'] = (int) $source_priority;
		}

		return array_values( $items );
	}

	/**
	 * Removes duplicate items by URL or GUID.
	 *
	 * @param array $items Normalized items.
	 * @return array
	 */
	private function remove_duplicates( array $items ) {
		$unique_items = array();
		$seen_urls    = array();
		$seen_guids   = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$url  = isset( $item['url'] ) ? (string) $item['url'] : '';
			$guid = isset( $item['guid'] ) ? (string) $item['guid'] : '';

			if ( '' !== $url && isset( $seen_urls[ $url ] ) ) {
				continue;
			}

			if ( '' !== $guid && isset( $seen_guids[ $guid ] ) ) {
				continue;
			}

			if ( '' !== $url ) {
				$seen_urls[ $url ] = true;
			}

			if ( '' !== $guid ) {
				$seen_guids[ $guid ] = true;
			}

			$unique_items[] = $item;
		}

		return $unique_items;
	}

	/**
	 * Sorts items so fresh stories come first, then older stories by keywords and date.
	 *
	 * @param array $items Normalized items.
	 * @return array
	 */
	private function sort_items( array $items ) {
		usort(
			$items,
			array( $this, 'compare_items' )
		);

		return $items;
	}

	/**
	 * Compares two normalized items for carousel ordering.
	 *
	 * @param array $left  Left item.
	 * @param array $right Right item.
	 * @return int
	 */
	private function compare_items( array $left, array $right ) {
		$left_is_recent  = $this->is_recent_item( $left );
		$right_is_recent = $this->is_recent_item( $right );
		$left_timestamp  = $this->get_item_timestamp( $left );
		$right_timestamp = $this->get_item_timestamp( $right );

		if ( $left_is_recent !== $right_is_recent ) {
			return $left_is_recent ? -1 : 1;
		}

		if ( $left_is_recent && $right_is_recent ) {
			if ( $left_timestamp === $right_timestamp ) {
				return $this->compare_source_priority( $left, $right );
			}

			return ( $left_timestamp > $right_timestamp ) ? -1 : 1;
		}

		$source_priority_comparison = $this->compare_source_priority( $left, $right );

		if ( 0 !== $source_priority_comparison ) {
			return $source_priority_comparison;
		}

		$left_score  = isset( $left['ntc_keyword_score'] ) ? (int) $left['ntc_keyword_score'] : 0;
		$right_score = isset( $right['ntc_keyword_score'] ) ? (int) $right['ntc_keyword_score'] : 0;

		if ( $left_score !== $right_score ) {
			return ( $left_score > $right_score ) ? -1 : 1;
		}

		$left_hits  = isset( $left['ntc_keyword_hits'] ) ? (int) $left['ntc_keyword_hits'] : 0;
		$right_hits = isset( $right['ntc_keyword_hits'] ) ? (int) $right['ntc_keyword_hits'] : 0;

		if ( $left_hits !== $right_hits ) {
			return ( $left_hits > $right_hits ) ? -1 : 1;
		}

		if ( $left_timestamp === $right_timestamp ) {
			return 0;
		}

		return ( $left_timestamp > $right_timestamp ) ? -1 : 1;
	}

	/**
	 * Compares two items by source priority.
	 *
	 * Lower source indexes are shown first.
	 *
	 * @param array $left  Left item.
	 * @param array $right Right item.
	 * @return int
	 */
	private function compare_source_priority( array $left, array $right ) {
		$left_priority  = isset( $left['ntc_source_priority'] ) ? (int) $left['ntc_source_priority'] : PHP_INT_MAX;
		$right_priority = isset( $right['ntc_source_priority'] ) ? (int) $right['ntc_source_priority'] : PHP_INT_MAX;

		if ( $left_priority === $right_priority ) {
			return 0;
		}

		return ( $left_priority < $right_priority ) ? -1 : 1;
	}

	/**
	 * Returns the Unix timestamp for an item publication date.
	 *
	 * @param array $item Normalized item.
	 * @return int
	 */
	private function get_item_timestamp( array $item ) {
		if ( empty( $item['published_at'] ) ) {
			return 0;
		}

		$timestamp = strtotime( $item['published_at'] );

		return false === $timestamp ? 0 : (int) $timestamp;
	}

	/**
	 * Returns whether an item was published within the recent-news window.
	 *
	 * @param array $item Normalized item.
	 * @return bool
	 */
	private function is_recent_item( array $item ) {
		$timestamp = $this->get_item_timestamp( $item );

		if ( $timestamp <= 0 ) {
			return false;
		}

		return $timestamp >= $this->get_recent_threshold();
	}

	/**
	 * Returns the Unix timestamp threshold for recent stories.
	 *
	 * @return int
	 */
	private function get_recent_threshold() {
		$current_timestamp = function_exists( 'current_time' )
			? (int) current_time( 'timestamp', true )
			: time();

		return $current_timestamp - self::RECENT_ITEMS_WINDOW;
	}

	/**
	 * Returns a friendly empty-state message.
	 *
	 * @param array $settings Plugin settings.
	 * @param array $errors   Feed errors.
	 * @return string
	 */
	private function get_empty_message( array $settings, array $errors ) {
		if ( ! empty( $errors ) ) {
			return __( 'No news items are available right now. Please try again later.', 'rss-news-carousel' );
		}

		if ( ! empty( $settings['keywords'] ) ) {
			return __( 'No feed items matched the configured keywords.', 'rss-news-carousel' );
		}

		return __( 'No news items are available right now.', 'rss-news-carousel' );
	}
}
