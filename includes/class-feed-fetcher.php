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
			$this->cache->set( $settings, $result, $settings['cache_minutes'] );

			return $result;
		}

		$this->load_feed_functions();

		$items  = array();
		$errors = array();

		foreach ( $feed_urls as $feed_url ) {
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

			$items = array_merge( $items, $this->item_normalizer->normalize_items( $feed_items, $feed ) );
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

		$this->cache->set( $settings, $result, $settings['cache_minutes'] );

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
	 * Sorts items by keyword relevance first, then by publication date descending.
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
	 * Compares two normalized items by keyword relevance and publication date.
	 *
	 * @param array $left  Left item.
	 * @param array $right Right item.
	 * @return int
	 */
	private function compare_items( array $left, array $right ) {
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

		$left_timestamp  = $this->get_item_timestamp( $left );
		$right_timestamp = $this->get_item_timestamp( $right );

		if ( $left_timestamp === $right_timestamp ) {
			return 0;
		}

		return ( $left_timestamp > $right_timestamp ) ? -1 : 1;
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
