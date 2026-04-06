<?php
/**
 * Admin page functionality.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the WordPress admin UI.
 */
class NTC_Admin {

	/**
	 * Required admin capability.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Admin stylesheet handle.
	 *
	 * @var string
	 */
	const ADMIN_STYLE_HANDLE = 'ntc-admin';

	/**
	 * Admin script handle.
	 *
	 * @var string
	 */
	const ADMIN_SCRIPT_HANDLE = 'ntc-admin';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'ntc-settings';

	/**
	 * Admin action name for manual cache refresh.
	 *
	 * @var string
	 */
	const REFRESH_ACTION = 'ntc_refresh_cache';

	/**
	 * Settings handler.
	 *
	 * @var NTC_Settings
	 */
	private $settings;

	/**
	 * Feed fetcher service.
	 *
	 * @var NTC_Feed_Fetcher
	 */
	private $feed_fetcher;

	/**
	 * Class constructor.
	 *
	 * @param NTC_Settings     $settings     Settings handler.
	 * @param NTC_Feed_Fetcher $feed_fetcher Feed fetcher.
	 */
	public function __construct( NTC_Settings $settings, NTC_Feed_Fetcher $feed_fetcher ) {
		$this->settings     = $settings;
		$this->feed_fetcher = $feed_fetcher;
	}

	/**
	 * Registers admin hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'render_invalid_feed_urls_notice' ) );
		add_action( 'admin_notices', array( $this, 'render_cache_refresh_notice' ) );
		add_action( 'admin_post_' . self::REFRESH_ACTION, array( $this, 'handle_refresh_cache' ) );
	}

	/**
	 * Enqueues admin assets on the plugin settings page only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		$style_path    = NTC_PLUGIN_PATH . 'assets/css/admin.css';
		$script_path   = NTC_PLUGIN_PATH . 'assets/js/admin.js';
		$style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : NTC_VERSION;
		$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : NTC_VERSION;

		wp_enqueue_style(
			self::ADMIN_STYLE_HANDLE,
			NTC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$style_version
		);

		wp_enqueue_script(
			self::ADMIN_SCRIPT_HANDLE,
			NTC_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			$script_version,
			true
		);

		wp_localize_script(
			self::ADMIN_SCRIPT_HANDLE,
			'ntcAdminL10n',
			array(
				'addSource'    => __( 'Add source', 'rss-news-carousel' ),
				'removeSource' => __( 'Remove', 'rss-news-carousel' ),
				'dragSource'   => __( 'Drag to reorder', 'rss-news-carousel' ),
				'moveUp'       => __( 'Up', 'rss-news-carousel' ),
				'moveDown'     => __( 'Down', 'rss-news-carousel' ),
			)
		);
	}

	/**
	 * Adds the plugin settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'RSS News Carousel', 'rss-news-carousel' ),
			__( 'RSS News Carousel', 'rss-news-carousel' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! $this->current_user_can_manage() ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'rss-news-carousel' )
			);
		}
		?>
		<div class="wrap ntc-admin">
			<div class="ntc-admin__hero">
				<div class="ntc-admin__hero-content">
					<p class="ntc-admin__eyebrow"><?php echo esc_html__( 'Live feed', 'rss-news-carousel' ); ?></p>
					<h1><?php echo esc_html__( 'RSS News Carousel', 'rss-news-carousel' ); ?></h1>
					<p class="ntc-admin__intro"><?php echo esc_html__( 'Configure feed sources, keyword priorities, layout settings, and cache tools for the carousel.', 'rss-news-carousel' ); ?></p>
				</div>
			</div>

			<?php settings_errors( NTC_Settings::OPTION_NAME ); ?>

			<div class="ntc-admin__grid">
				<div class="ntc-admin__card ntc-admin__card--manual">
					<h2><?php echo esc_html__( 'How to use this plugin', 'rss-news-carousel' ); ?></h2>
					<ol class="ntc-admin__manual-list">
						<li><?php echo esc_html__( 'Add one or more RSS or Atom feed URLs and drag them, or use Up/Down, to set your preferred source priority.', 'rss-news-carousel' ); ?></li>
						<li><?php echo esc_html__( 'Add keywords separated by commas if you want older stories within each source to be prioritised by match strength.', 'rss-news-carousel' ); ?></li>
						<li><?php echo esc_html__( 'Choose how many items to show, how long they should be cached, and which parts of each card should be visible.', 'rss-news-carousel' ); ?></li>
						<li><?php echo esc_html__( 'Adjust theme, layout, fonts, and colors to match your site.', 'rss-news-carousel' ); ?></li>
						<li><?php echo esc_html__( 'Save the settings and place the shortcode on a page or in Elementor.', 'rss-news-carousel' ); ?></li>
					</ol>
					<div class="ntc-admin__shortcode-box">
						<span class="ntc-admin__shortcode-label"><?php echo esc_html__( 'Shortcode', 'rss-news-carousel' ); ?></span>
						<code>[rss_carousel]</code>
					</div>
				</div>

				<div class="ntc-admin__card">
					<form action="options.php" method="post">
						<?php
						settings_fields( NTC_Settings::OPTION_GROUP );
						do_settings_sections( self::PAGE_SLUG );
						submit_button( __( 'Save Settings', 'rss-news-carousel' ) );
						?>
					</form>
				</div>

				<div class="ntc-admin__card ntc-admin__card--cache">
					<h2><?php echo esc_html__( 'Cache Tools', 'rss-news-carousel' ); ?></h2>
					<p><?php echo esc_html__( 'Clear the current cached feed data and rebuild it immediately from the configured feeds.', 'rss-news-carousel' ); ?></p>

					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::REFRESH_ACTION ); ?>" />
						<?php wp_nonce_field( self::REFRESH_ACTION, 'ntc_refresh_cache_nonce' ); ?>
						<?php submit_button( __( 'Refresh Cache', 'rss-news-carousel' ), 'secondary', 'submit', false ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders an admin notice for invalid feed URLs after settings save.
	 *
	 * @return void
	 */
	public function render_invalid_feed_urls_notice() {
		if ( ! $this->current_user_can_manage() || ! $this->is_settings_page_request() ) {
			return;
		}

		$invalid_urls = get_transient( NTC_Settings::INVALID_FEEDS_NOTICE_KEY );

		if ( empty( $invalid_urls ) || ! is_array( $invalid_urls ) ) {
			return;
		}

		delete_transient( NTC_Settings::INVALID_FEEDS_NOTICE_KEY );
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php echo esc_html__( 'Some feed URLs were invalid and were not saved:', 'rss-news-carousel' ); ?>
			</p>
			<ul style="list-style: disc; margin-left: 1.5rem;">
				<?php foreach ( $invalid_urls as $invalid_url ) : ?>
					<li><code><?php echo esc_html( $invalid_url ); ?></code></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Handles manual cache refresh requests.
	 *
	 * @return void
	 */
	public function handle_refresh_cache() {
		if ( ! $this->current_user_can_manage() ) {
			wp_die(
				esc_html__( 'You do not have permission to perform this action.', 'rss-news-carousel' )
			);
		}

		check_admin_referer( self::REFRESH_ACTION, 'ntc_refresh_cache_nonce' );

		$data     = $this->feed_fetcher->refresh_cache();
		$settings = $this->settings->get_settings();
		$errors   = isset( $data['errors'] ) && is_array( $data['errors'] ) ? $data['errors'] : array();
		$items    = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
		$status   = 'success';

		if ( empty( $settings['rss_feeds'] ) ) {
			$status = 'empty';
		} elseif ( ! empty( $errors ) && empty( $items ) ) {
			$status = 'error';
		} elseif ( empty( $items ) ) {
			$status = 'warning';
		}

		$redirect_url = add_query_arg(
			array(
				'ntc_cache_refresh'  => $status,
				'ntc_cache_items'    => count( $items ),
			),
			$this->get_settings_page_url()
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Renders a notice after a manual cache refresh.
	 *
	 * @return void
	 */
	public function render_cache_refresh_notice() {
		if ( ! $this->current_user_can_manage() || ! $this->is_settings_page_request() ) {
			return;
		}
		$status = isset( $_GET['ntc_cache_refresh'] ) ? sanitize_key( wp_unslash( $_GET['ntc_cache_refresh'] ) ) : '';
		$count  = isset( $_GET['ntc_cache_items'] ) ? absint( wp_unslash( $_GET['ntc_cache_items'] ) ) : 0;

		if ( '' === $status ) {
			return;
		}

		$notice_class = 'notice notice-success is-dismissible';
		$message      = sprintf(
			/* translators: %d: item count */
			__( 'Cache refreshed successfully. %d items are ready.', 'rss-news-carousel' ),
			$count
		);

		if ( 'empty' === $status ) {
			$notice_class = 'notice notice-warning is-dismissible';
			$message      = __( 'Cache refresh skipped because no feed URLs are configured yet.', 'rss-news-carousel' );
		} elseif ( 'warning' === $status ) {
			$notice_class = 'notice notice-warning is-dismissible';
			$message      = __( 'Cache refreshed, but no items matched the current feed and filter settings.', 'rss-news-carousel' );
		} elseif ( 'error' === $status ) {
			$notice_class = 'notice notice-error is-dismissible';
			$message      = __( 'Cache refresh completed, but all configured feeds failed to load.', 'rss-news-carousel' );
		}
		?>
		<div class="<?php echo esc_attr( $notice_class ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Returns whether the current user may manage plugin settings.
	 *
	 * @return bool
	 */
	private function current_user_can_manage() {
		return current_user_can( self::CAPABILITY );
	}

	/**
	 * Returns whether the current request is for the plugin settings page.
	 *
	 * @return bool
	 */
	private function is_settings_page_request() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		return self::PAGE_SLUG === $page;
	}

	/**
	 * Returns the admin URL for the plugin settings page.
	 *
	 * @return string
	 */
	private function get_settings_page_url() {
		return add_query_arg(
			array(
				'page' => self::PAGE_SLUG,
			),
			admin_url( 'options-general.php' )
		);
	}
}
