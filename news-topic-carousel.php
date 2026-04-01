<?php
/**
 * Plugin Name: RSS News Carousel
 * Description: Displays filtered RSS and Atom feed items in a configurable news carousel.
 * Version:     1.0.8
 * Author:      RSS News Carousel
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: rss-news-carousel
 * Domain Path: /languages
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NTC_VERSION', '1.0.8' );
define( 'NTC_PLUGIN_FILE', __FILE__ );
define( 'NTC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'NTC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once NTC_PLUGIN_PATH . 'includes/class-ntc-plugin.php';
require_once NTC_PLUGIN_PATH . 'includes/class-ntc-admin.php';
require_once NTC_PLUGIN_PATH . 'includes/class-cache.php';
require_once NTC_PLUGIN_PATH . 'includes/class-item-normalizer.php';
require_once NTC_PLUGIN_PATH . 'includes/class-keyword-filter.php';
require_once NTC_PLUGIN_PATH . 'includes/class-feed-fetcher.php';
require_once NTC_PLUGIN_PATH . 'includes/class-renderer.php';
require_once NTC_PLUGIN_PATH . 'includes/class-shortcode.php';
require_once NTC_PLUGIN_PATH . 'includes/class-ntc-settings.php';

register_activation_hook( NTC_PLUGIN_FILE, 'ntc_activate_plugin' );
register_deactivation_hook( NTC_PLUGIN_FILE, 'ntc_deactivate_plugin' );

/**
 * Returns the main plugin instance.
 *
 * @return NTC_Plugin
 */
function ntc_get_plugin() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new NTC_Plugin();
	}

	return $plugin;
}

/**
 * Starts the plugin.
 *
 * @return void
 */
function ntc_run_plugin() {
	ntc_get_plugin()->run();
}

/**
 * Handles plugin activation tasks.
 *
 * @return void
 */
function ntc_activate_plugin() {
	$settings = new NTC_Settings();

	if ( false === get_option( NTC_Settings::OPTION_NAME, false ) ) {
		add_option( NTC_Settings::OPTION_NAME, $settings->get_defaults() );
	}
}

/**
 * Handles plugin deactivation tasks.
 *
 * @return void
 */
function ntc_deactivate_plugin() {
	$settings = new NTC_Settings();
	$cache    = new NTC_Cache();

	$cache->delete_all();
	delete_transient( NTC_Settings::INVALID_FEEDS_NOTICE_KEY );
}

ntc_run_plugin();
