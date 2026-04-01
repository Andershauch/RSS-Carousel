<?php
/**
 * Shortcode registration and asset management.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin shortcode and frontend assets.
 */
class NTC_Shortcode {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const SHORTCODE_TAG = 'news_topic_carousel';

	/**
	 * Renderer instance.
	 *
	 * @var NTC_Renderer
	 */
	private $renderer;

	/**
	 * Class constructor.
	 *
	 * @param NTC_Renderer $renderer Frontend renderer.
	 */
	public function __construct( NTC_Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Registers frontend hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'init', array( $this, 'register_shortcode' ) );
	}

	/**
	 * Registers frontend script and style handles.
	 *
	 * @return void
	 */
	public function register_assets() {
		$style_path     = NTC_PLUGIN_PATH . 'assets/css/frontend.css';
		$script_path    = NTC_PLUGIN_PATH . 'assets/js/carousel.js';
		$style_version  = file_exists( $style_path ) ? (string) filemtime( $style_path ) : NTC_VERSION;
		$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : NTC_VERSION;

		wp_register_style(
			'ntc-frontend',
			NTC_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			$style_version
		);

		wp_register_script(
			'ntc-carousel',
			NTC_PLUGIN_URL . 'assets/js/carousel.js',
			array( 'wp-i18n' ),
			$script_version,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'ntc-carousel',
				'rss-news-carousel',
				NTC_PLUGIN_PATH . 'languages'
			);
		}
	}

	/**
	 * Registers the carousel shortcode.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( self::SHORTCODE_TAG, array( $this, 'render_shortcode' ) );
	}

	/**
	 * Renders the carousel shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		wp_enqueue_style( 'ntc-frontend' );
		wp_enqueue_script( 'ntc-carousel' );

		return $this->renderer->render( is_array( $atts ) ? $atts : array() );
	}
}
