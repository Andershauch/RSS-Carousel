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
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		$this->shortcode->register_hooks();

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
}
