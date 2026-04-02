<?php
/**
 * Main plugin bootstrap class.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin components.
 */
class NTC_Plugin {

	/**
	 * Cron hook name used for scheduled cache refreshes.
	 *
	 * @var string
	 */
	const REFRESH_CRON_HOOK = 'ntc_refresh_cache_event';

	/**
	 * Cron schedule slug for the two-hour refresh interval.
	 *
	 * @var string
	 */
	const REFRESH_CRON_SCHEDULE = 'ntc_every_two_hours';

	/**
	 * Admin page handler.
	 *
	 * @var NTC_Admin
	 */
	private $admin;

	/**
	 * Settings handler.
	 *
	 * @var NTC_Settings
	 */
	private $settings;

	/**
	 * Feed fetcher handler.
	 *
	 * @var NTC_Feed_Fetcher
	 */
	private $feed_fetcher;

	/**
	 * Frontend renderer.
	 *
	 * @var NTC_Renderer
	 */
	private $renderer;

	/**
	 * Shortcode handler.
	 *
	 * @var NTC_Shortcode
	 */
	private $shortcode;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->settings     = new NTC_Settings();
		$this->feed_fetcher = new NTC_Feed_Fetcher(
			$this->settings,
			new NTC_Item_Normalizer(),
			new NTC_Keyword_Filter(),
			new NTC_Cache()
		);
		$this->admin        = new NTC_Admin( $this->settings, $this->feed_fetcher );
		$this->renderer     = new NTC_Renderer( $this->feed_fetcher, $this->settings );
		$this->shortcode    = new NTC_Shortcode( $this->renderer );
	}

	/**
	 * Registers plugin hooks.
	 *
	 * @return void
	 */
	public function run() {
		$this->register_cron_hooks();
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		$this->shortcode->register_hooks();
		$this->schedule_refresh_event();

		if ( is_admin() ) {
			$this->settings->register_hooks();
			$this->admin->register_hooks();
		}
	}

	/**
	 * Loads the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'rss-news-carousel',
			false,
			dirname( plugin_basename( NTC_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Returns the feed fetcher service.
	 *
	 * @return NTC_Feed_Fetcher
	 */
	public function get_feed_fetcher() {
		return $this->feed_fetcher;
	}

	/**
	 * Returns the frontend renderer service.
	 *
	 * @return NTC_Renderer
	 */
	public function get_renderer() {
		return $this->renderer;
	}

	/**
	 * Registers cron-related hooks for scheduled cache refreshes.
	 *
	 * @return void
	 */
	private function register_cron_hooks() {
		add_filter( 'cron_schedules', array( $this, 'add_refresh_cron_schedule' ) );
		add_action( self::REFRESH_CRON_HOOK, array( $this, 'handle_scheduled_refresh' ) );
	}

	/**
	 * Adds the custom every-two-hours cron interval.
	 *
	 * @param array $schedules Registered cron schedules.
	 * @return array
	 */
	public function add_refresh_cron_schedule( $schedules ) {
		if ( ! isset( $schedules[ self::REFRESH_CRON_SCHEDULE ] ) ) {
			$schedules[ self::REFRESH_CRON_SCHEDULE ] = array(
				'interval' => 2 * HOUR_IN_SECONDS,
				'display'  => __( 'Every two hours', 'rss-news-carousel' ),
			);
		}

		return $schedules;
	}

	/**
	 * Schedules the recurring cache refresh if it does not already exist.
	 *
	 * @return void
	 */
	public function schedule_refresh_event() {
		$this->register_cron_hooks();

		if ( wp_next_scheduled( self::REFRESH_CRON_HOOK ) ) {
			return;
		}

		wp_schedule_event( time() + HOUR_IN_SECONDS, self::REFRESH_CRON_SCHEDULE, self::REFRESH_CRON_HOOK );
	}

	/**
	 * Clears the recurring cache refresh event.
	 *
	 * @return void
	 */
	public function clear_refresh_event() {
		$timestamp = wp_next_scheduled( self::REFRESH_CRON_HOOK );

		while ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, self::REFRESH_CRON_HOOK );
			$timestamp = wp_next_scheduled( self::REFRESH_CRON_HOOK );
		}
	}

	/**
	 * Rebuilds the cached feed data when the scheduled event fires.
	 *
	 * @return void
	 */
	public function handle_scheduled_refresh() {
		$this->feed_fetcher->refresh_cache();
	}
}
