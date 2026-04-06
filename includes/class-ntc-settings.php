<?php
/**
 * Settings registration and sanitization.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Settings API integration.
 */
class NTC_Settings {

	/**
	 * Registered option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'ntc_settings';

	/**
	 * Settings API option group.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'ntc_settings_group';

	/**
	 * Settings section identifier.
	 *
	 * @var string
	 */
	const SECTION_ID = 'ntc_general_section';

	/**
	 * Design settings section identifier.
	 *
	 * @var string
	 */
	const DESIGN_SECTION_ID = 'ntc_design_section';

	/**
	 * Transient key for invalid feed URL notices.
	 *
	 * @var string
	 */
	const INVALID_FEEDS_NOTICE_KEY = 'ntc_invalid_feed_urls_notice';

	/**
	 * Registers hooks related to plugin settings.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_upgrade_text_defaults' ) );
		add_action( 'update_option_' . self::OPTION_NAME, array( $this, 'handle_settings_updated' ), 10, 2 );
		add_filter( 'option_page_capability_' . self::OPTION_GROUP, array( $this, 'get_required_capability' ) );
	}

	/**
	 * Clears cached feed data after settings are updated.
	 *
	 * @param array $old_value Previous option value.
	 * @param array $new_value New option value.
	 * @return void
	 */
	public function handle_settings_updated( $old_value, $new_value ) {
		unset( $old_value, $new_value );

		$cache = new NTC_Cache();

		$cache->delete_all();
	}

	/**
	 * Upgrades legacy default frontend texts from older plugin versions.
	 *
	 * This keeps existing installs in sync when the old Tottenham-specific copy
	 * or mojibake fallback text was previously saved to the database.
	 *
	 * @return void
	 */
	public function maybe_upgrade_text_defaults() {
		$settings = get_option( self::OPTION_NAME, null );

		if ( ! is_array( $settings ) ) {
			return;
		}

		$updated = false;

		if ( isset( $settings['header_eyebrow_text'] ) && 'TOTTENHAM LIVE FEED' === $settings['header_eyebrow_text'] ) {
			$settings['header_eyebrow_text'] = 'LIVE NYHEDSFEED';
			$updated                         = true;
		}

		if ( isset( $settings['header_title_text'] ) && 'SENESTE NYHEDER OM DIT YNDLINGSHOLD' === $settings['header_title_text'] ) {
			$settings['header_title_text'] = 'SENESTE NYHEDER';
			$updated                       = true;
		}

		if ( isset( $settings['read_more_text'] ) && false !== strpos( (string) $settings['read_more_text'], 'Ãƒ' ) ) {
			$settings['read_more_text'] = 'LÃ†S MERE';
			$updated                    = true;
		}

		if ( isset( $settings['read_more_text'] ) && preg_match( '/\x{00C3}/u', (string) $settings['read_more_text'] ) ) {
			$settings['read_more_text'] = html_entity_decode( 'L&AElig;S MERE', ENT_QUOTES, 'UTF-8' );
			$updated                    = true;
		}

		if ( isset( $settings['read_more_text'] ) && $this->contains_mojibake_text( $settings['read_more_text'] ) ) {
			$settings['read_more_text'] = $this->get_default_read_more_text();
			$updated                    = true;
		}

		if ( ! $updated ) {
			return;
		}

		update_option( self::OPTION_NAME, $settings, false );
	}

	/**
	 * Returns the required capability for the settings page.
	 *
	 * @return string
	 */
	public function get_required_capability() {
		return 'manage_options';
	}

	/**
	 * Registers settings, section, and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'description'       => __( 'Settings for the RSS News Carousel plugin.', 'rss-news-carousel' ),
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);

		add_settings_section(
			self::SECTION_ID,
			__( 'Carousel Settings', 'rss-news-carousel' ),
			array( $this, 'render_section_description' ),
			NTC_Admin::PAGE_SLUG
		);

		add_settings_section(
			self::DESIGN_SECTION_ID,
			__( 'Design Settings', 'rss-news-carousel' ),
			array( $this, 'render_design_section_description' ),
			NTC_Admin::PAGE_SLUG
		);

		$this->register_field(
			'rss_feeds',
			__( 'RSS feed URLs', 'rss-news-carousel' ),
			array( $this, 'render_feed_sources_field' ),
			array(
				'description' => __( 'Add feed URLs, then drag them or use Up/Down to set source priority. Higher entries are shown before lower entries.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'keywords',
			__( 'Keywords', 'rss-news-carousel' ),
			array( $this, 'render_text_field' ),
			array(
				'description' => __( 'Enter comma-separated keywords used for future filtering.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'items_limit',
			__( 'Items limit', 'rss-news-carousel' ),
			array( $this, 'render_number_field' ),
			array(
				'min'         => 1,
				'max'         => 50,
				'description' => __( 'Maximum number of feed items to prepare for display.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'cache_minutes',
			__( 'Cache duration', 'rss-news-carousel' ),
			array( $this, 'render_number_field' ),
			array(
				'min'         => 1,
				'max'         => 1440,
				'description' => __( 'Cache lifetime in minutes for future feed retrieval.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'show_image',
			__( 'Show image', 'rss-news-carousel' ),
			array( $this, 'render_checkbox_field' ),
			array(
				'label' => __( 'Display featured media when available.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'show_date',
			__( 'Show date', 'rss-news-carousel' ),
			array( $this, 'render_checkbox_field' ),
			array(
				'label' => __( 'Display the publication date.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'show_source',
			__( 'Show source', 'rss-news-carousel' ),
			array( $this, 'render_checkbox_field' ),
			array(
				'label' => __( 'Display the feed source name.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'show_excerpt',
			__( 'Show excerpt', 'rss-news-carousel' ),
			array( $this, 'render_checkbox_field' ),
			array(
				'label' => __( 'Display a short excerpt for each item.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'autoplay',
			__( 'Autoplay', 'rss-news-carousel' ),
			array( $this, 'render_checkbox_field' ),
			array(
				'label' => __( 'Enable automatic carousel rotation.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'theme',
			__( 'Theme', 'rss-news-carousel' ),
			array( $this, 'render_select_field' ),
			array(
				'options'     => array(
					'light' => __( 'Light', 'rss-news-carousel' ),
					'dark'  => __( 'Dark', 'rss-news-carousel' ),
				),
				'description' => __( 'Choose the visual theme for the future carousel output.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'layout',
			__( 'Layout', 'rss-news-carousel' ),
			array( $this, 'render_select_field' ),
			array(
				'options'     => array(
					'compact' => __( 'Compact', 'rss-news-carousel' ),
					'cards'   => __( 'Cards', 'rss-news-carousel' ),
					'hero'    => __( 'Hero', 'rss-news-carousel' ),
				),
				'description' => __( 'Choose the future presentation layout.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'header_eyebrow_text',
			__( 'Eyebrow text', 'rss-news-carousel' ),
			array( $this, 'render_text_field' ),
			array(
				'description' => __( 'Text shown above the main carousel heading.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'header_title_text',
			__( 'Heading text', 'rss-news-carousel' ),
			array( $this, 'render_text_field' ),
			array(
				'description' => __( 'Main heading shown at the top of the carousel.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'read_more_text',
			__( 'Read more text', 'rss-news-carousel' ),
			array( $this, 'render_text_field' ),
			array(
				'description' => __( 'Label used for the read-more link in each card footer.', 'rss-news-carousel' ),
			)
		);

		$this->register_field(
			'heading_font',
			__( 'Heading font', 'rss-news-carousel' ),
			array( $this, 'render_select_field' ),
			array(
				'options'     => $this->get_heading_font_options(),
				'description' => __( 'Choose the font style used for carousel headings.', 'rss-news-carousel' ),
			),
			self::DESIGN_SECTION_ID
		);

		$this->register_field(
			'body_font',
			__( 'Body font', 'rss-news-carousel' ),
			array( $this, 'render_select_field' ),
			array(
				'options'     => $this->get_body_font_options(),
				'description' => __( 'Choose the font style used for source labels and excerpt text.', 'rss-news-carousel' ),
			),
			self::DESIGN_SECTION_ID
		);

		$this->register_field(
			'heading_color',
			__( 'Heading color', 'rss-news-carousel' ),
			array( $this, 'render_color_field' ),
			array(
				'description' => __( 'Choose the color used for headlines and important links.', 'rss-news-carousel' ),
			),
			self::DESIGN_SECTION_ID
		);

		$this->register_field(
			'body_color',
			__( 'Body text color', 'rss-news-carousel' ),
			array( $this, 'render_color_field' ),
			array(
				'description' => __( 'Choose the color used for excerpts, dates, and supporting text.', 'rss-news-carousel' ),
			),
			self::DESIGN_SECTION_ID
		);

		$this->register_field(
			'accent_color',
			__( 'Accent color', 'rss-news-carousel' ),
			array( $this, 'render_color_field' ),
			array(
				'description' => __( 'Choose the accent color used for highlights and interactive details.', 'rss-news-carousel' ),
			),
			self::DESIGN_SECTION_ID
		);

		$this->register_field(
			'background_color',
			__( 'Background color', 'rss-news-carousel' ),
			array( $this, 'render_color_field' ),
			array(
				'description' => __( 'Choose the main plugin background color.', 'rss-news-carousel' ),
			),
			self::DESIGN_SECTION_ID
		);

		$this->register_field(
			'header_background_color',
			__( 'Header background color', 'rss-news-carousel' ),
			array( $this, 'render_color_field' ),
			array(
				'description' => __( 'Choose the background color used behind the top headline area.', 'rss-news-carousel' ),
			),
			self::DESIGN_SECTION_ID
		);

		$this->register_field(
			'header_text_color',
			__( 'Header text color', 'rss-news-carousel' ),
			array( $this, 'render_color_field' ),
			array(
				'description' => __( 'Choose the text color used in the top headline area.', 'rss-news-carousel' ),
			),
			self::DESIGN_SECTION_ID
		);
	}

	/**
	 * Registers an individual settings field.
	 *
	 * @param string   $key      Field key inside the option array.
	 * @param string   $title    Field label.
	 * @param callable $callback Field renderer callback.
	 * @param array    $args     Additional renderer arguments.
	 *
	 * @return void
	 */
	private function register_field( $key, $title, $callback, array $args = array(), $section_id = self::SECTION_ID ) {
		$args['key'] = $key;

		add_settings_field(
			$key,
			$title,
			$callback,
			NTC_Admin::PAGE_SLUG,
			$section_id,
			$args
		);
	}

	/**
	 * Returns the default settings array.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'rss_feeds'               => '',
			'keywords'                => '',
			'items_limit'             => 10,
			'cache_minutes'           => 30,
			'show_image'              => 1,
			'show_date'               => 1,
			'show_source'             => 1,
			'show_excerpt'            => 1,
			'autoplay'                => 0,
			'theme'                   => 'light',
			'layout'                  => 'cards',
			'header_eyebrow_text'     => 'LIVE NYHEDSFEED',
			'header_title_text'       => 'SENESTE NYHEDER',
			'read_more_text'          => $this->get_default_read_more_text(),
			'heading_font'            => 'apex',
			'body_font'               => 'apex',
			'heading_color'           => '#0a1c54',
			'body_color'              => '#7a7a7a',
			'accent_color'            => '#f6fe08',
			'background_color'        => '#ffffff',
			'header_background_color' => '#0a1c54',
			'header_text_color'       => '#ffffff',
		);
	}

	/**
	 * Renders the section description.
	 *
	 * @return void
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'These settings prepare the plugin for future feed retrieval and front-end rendering.', 'rss-news-carousel' ) . '</p>';
	}

	/**
	 * Renders the design section description.
	 *
	 * @return void
	 */
	public function render_design_section_description() {
		echo '<p>' . esc_html__( 'Fine-tune the carousel typography and text colors without touching code.', 'rss-news-carousel' ) . '</p>';
	}

	/**
	 * Sanitizes the option array before it is saved.
	 *
	 * @param mixed $input Raw settings input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults = $this->get_defaults();
		$output   = $defaults;

		if ( ! is_array( $input ) ) {
			add_settings_error(
				self::OPTION_NAME,
				'ntc_invalid_payload',
				__( 'The submitted settings payload was invalid. Defaults were restored.', 'rss-news-carousel' ),
				'error'
			);

			return $defaults;
		}

		$rss_feeds = isset( $input['rss_feeds'] ) ? wp_unslash( $input['rss_feeds'] ) : '';
		$keywords  = isset( $input['keywords'] ) ? wp_unslash( $input['keywords'] ) : '';

		$output['rss_feeds']               = $this->sanitize_feed_urls( $rss_feeds );
		$output['keywords']                = $this->sanitize_keywords( $keywords );
		$output['items_limit']             = $this->sanitize_number( $input, 'items_limit', 1, 50, $defaults['items_limit'] );
		$output['cache_minutes']           = $this->sanitize_number( $input, 'cache_minutes', 1, 1440, $defaults['cache_minutes'] );
		$output['show_image']              = $this->sanitize_checkbox( $input, 'show_image' );
		$output['show_date']               = $this->sanitize_checkbox( $input, 'show_date' );
		$output['show_source']             = $this->sanitize_checkbox( $input, 'show_source' );
		$output['show_excerpt']            = $this->sanitize_checkbox( $input, 'show_excerpt' );
		$output['autoplay']                = $this->sanitize_checkbox( $input, 'autoplay' );
		$output['theme']                   = $this->sanitize_choice( $input, 'theme', array( 'light', 'dark' ), $defaults['theme'] );
		$output['layout']                  = $this->sanitize_choice( $input, 'layout', array( 'compact', 'cards', 'hero' ), $defaults['layout'] );
		$output['header_eyebrow_text']     = $this->sanitize_text_setting( $input, 'header_eyebrow_text', $defaults['header_eyebrow_text'] );
		$output['header_title_text']       = $this->sanitize_text_setting( $input, 'header_title_text', $defaults['header_title_text'] );
		$output['read_more_text']          = $this->sanitize_text_setting( $input, 'read_more_text', $defaults['read_more_text'] );
		$output['heading_font']            = $this->sanitize_choice( $input, 'heading_font', array_keys( $this->get_heading_font_options() ), $defaults['heading_font'] );
		$output['body_font']               = $this->sanitize_choice( $input, 'body_font', array_keys( $this->get_body_font_options() ), $defaults['body_font'] );
		$output['heading_color']           = $this->sanitize_color( $input, 'heading_color', $defaults['heading_color'] );
		$output['body_color']              = $this->sanitize_color( $input, 'body_color', $defaults['body_color'] );
		$output['accent_color']            = $this->sanitize_color( $input, 'accent_color', $defaults['accent_color'] );
		$output['background_color']        = $this->sanitize_color( $input, 'background_color', $defaults['background_color'] );
		$output['header_background_color'] = $this->sanitize_color( $input, 'header_background_color', $defaults['header_background_color'] );
		$output['header_text_color']       = $this->sanitize_color( $input, 'header_text_color', $defaults['header_text_color'] );

		return $output;
	}

	/**
	 * Sanitizes newline-separated feed URLs.
	 *
	 * @param string $value Raw textarea value.
	 * @return string
	 */
	private function sanitize_feed_urls( $value ) {
		$value        = is_string( $value ) ? $value : '';
		$lines        = preg_split( '/\r\n|\r|\n/', $value );
		$valid_urls   = array();
		$invalid_urls = array();

		if ( empty( $lines ) ) {
			return '';
		}

		foreach ( $lines as $line ) {
			$url = trim( $line );

			if ( '' === $url ) {
				continue;
			}

			$url = esc_url_raw( $url );

			if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
				$invalid_urls[] = trim( $line );
				continue;
			}

			$valid_urls[] = $url;
		}

		$valid_urls = array_values( array_unique( $valid_urls ) );

		if ( ! empty( $invalid_urls ) ) {
			$invalid_urls = array_map( 'sanitize_text_field', $invalid_urls );

			set_transient(
				self::INVALID_FEEDS_NOTICE_KEY,
				array_values( array_unique( array_filter( $invalid_urls ) ) ),
				MINUTE_IN_SECONDS
			);
		} else {
			delete_transient( self::INVALID_FEEDS_NOTICE_KEY );
		}

		return implode( "\n", $valid_urls );
	}

	/**
	 * Sanitizes a comma-separated keyword string.
	 *
	 * @param string $value Raw keyword input.
	 * @return string
	 */
	private function sanitize_keywords( $value ) {
		$value    = is_string( $value ) ? $value : '';
		$parts    = explode( ',', $value );
		$keywords = array();

		foreach ( $parts as $part ) {
			$keyword = sanitize_text_field( $part );
			$keyword = trim( $keyword );

			if ( '' !== $keyword ) {
				$keywords[] = $keyword;
			}
		}

		$keywords = array_values( array_unique( $keywords ) );

		return implode( ', ', $keywords );
	}

	/**
	 * Sanitizes a numeric setting within a defined range.
	 *
	 * @param array  $input   Raw option input.
	 * @param string $key     Array key.
	 * @param int    $min     Minimum allowed value.
	 * @param int    $max     Maximum allowed value.
	 * @param int    $default Default fallback value.
	 * @return int
	 */
	private function sanitize_number( array $input, $key, $min, $max, $default ) {
		if ( ! isset( $input[ $key ] ) ) {
			return $default;
		}

		$raw_value = wp_unslash( $input[ $key ] );
		$value     = filter_var(
			$raw_value,
			FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'min_range' => $min,
					'max_range' => $max,
				),
			)
		);

		if ( false === $value ) {
			add_settings_error(
				self::OPTION_NAME,
				'ntc_invalid_' . $key,
				sprintf(
					/* translators: 1: field label, 2: minimum number, 3: maximum number */
					__( '%1$s must be between %2$d and %3$d.', 'rss-news-carousel' ),
					esc_html( $this->get_setting_label( $key ) ),
					$min,
					$max
				),
				'error'
			);

			return $default;
		}

		return $value;
	}

	/**
	 * Sanitizes a checkbox field.
	 *
	 * @param array  $input Raw option input.
	 * @param string $key   Array key.
	 * @return int
	 */
	private function sanitize_checkbox( array $input, $key ) {
		return isset( $input[ $key ] ) ? 1 : 0;
	}

	/**
	 * Sanitizes a select field choice.
	 *
	 * @param array  $input   Raw option input.
	 * @param string $key     Array key.
	 * @param array  $allowed Allowed values.
	 * @param string $default Default fallback value.
	 * @return string
	 */
	private function sanitize_choice( array $input, $key, array $allowed, $default ) {
		$value = isset( $input[ $key ] ) ? sanitize_key( wp_unslash( $input[ $key ] ) ) : $default;

		if ( ! in_array( $value, $allowed, true ) ) {
			add_settings_error(
				self::OPTION_NAME,
				'ntc_invalid_choice_' . $key,
				sprintf(
					/* translators: %s: field label */
					__( 'An invalid value was provided for %s.', 'rss-news-carousel' ),
					esc_html( $this->get_setting_label( $key ) )
				),
				'error'
			);

			return $default;
		}

		return $value;
	}

	/**
	 * Sanitizes a single-line text setting.
	 *
	 * @param array  $input   Raw option input.
	 * @param string $key     Array key.
	 * @param string $default Default fallback value.
	 * @return string
	 */
	private function sanitize_text_setting( array $input, $key, $default ) {
		if ( ! isset( $input[ $key ] ) ) {
			return $default;
		}

		$value = sanitize_text_field( wp_unslash( $input[ $key ] ) );

		if ( '' === $value ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Sanitizes a hex color field.
	 *
	 * @param array  $input   Raw option input.
	 * @param string $key     Array key.
	 * @param string $default Default fallback value.
	 * @return string
	 */
	private function sanitize_color( array $input, $key, $default ) {
		$value = isset( $input[ $key ] ) ? sanitize_hex_color( wp_unslash( $input[ $key ] ) ) : $default;

		if ( empty( $value ) ) {
			add_settings_error(
				self::OPTION_NAME,
				'ntc_invalid_color_' . $key,
				sprintf(
					/* translators: %s: field label */
					__( 'An invalid color was provided for %s.', 'rss-news-carousel' ),
					esc_html( $this->get_setting_label( $key ) )
				),
				'error'
			);

			return $default;
		}

		return $value;
	}

	/**
	 * Returns a human-readable label for a setting key.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	private function get_setting_label( $key ) {
		$labels = array(
			'rss_feeds'     => __( 'RSS feed URLs', 'rss-news-carousel' ),
			'keywords'      => __( 'Keywords', 'rss-news-carousel' ),
			'items_limit'   => __( 'Items limit', 'rss-news-carousel' ),
			'cache_minutes' => __( 'Cache duration', 'rss-news-carousel' ),
			'theme'         => __( 'Theme', 'rss-news-carousel' ),
			'layout'        => __( 'Layout', 'rss-news-carousel' ),
			'header_eyebrow_text' => __( 'Eyebrow text', 'rss-news-carousel' ),
			'header_title_text'   => __( 'Heading text', 'rss-news-carousel' ),
			'read_more_text'      => __( 'Read more text', 'rss-news-carousel' ),
			'heading_font'  => __( 'Heading font', 'rss-news-carousel' ),
			'body_font'     => __( 'Body font', 'rss-news-carousel' ),
			'heading_color' => __( 'Heading color', 'rss-news-carousel' ),
			'body_color'    => __( 'Body text color', 'rss-news-carousel' ),
			'accent_color'  => __( 'Accent color', 'rss-news-carousel' ),
			'background_color'        => __( 'Background color', 'rss-news-carousel' ),
			'header_background_color' => __( 'Header background color', 'rss-news-carousel' ),
			'header_text_color'       => __( 'Header text color', 'rss-news-carousel' ),
		);

		return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
	}

	/**
	 * Renders a textarea settings field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_textarea_field( array $args ) {
		$key         = $args['key'];
		$settings    = $this->get_settings();
		$value       = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		$rows        = isset( $args['rows'] ) ? absint( $args['rows'] ) : 5;
		$description = isset( $args['description'] ) ? $args['description'] : '';
		?>
		<textarea
			class="large-text code"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"
			id="<?php echo esc_attr( $key ); ?>"
			rows="<?php echo esc_attr( $rows ); ?>"
		><?php echo esc_textarea( $value ); ?></textarea>
		<?php if ( ! empty( $description ) ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the sortable feed-source field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_feed_sources_field( array $args ) {
		$key         = $args['key'];
		$settings    = $this->get_settings();
		$value       = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
		$description = isset( $args['description'] ) ? $args['description'] : '';
		$field_name  = self::OPTION_NAME . '[' . $key . ']';
		$feed_urls   = $this->split_feed_lines( $value );
		?>
		<div class="ntc-feed-sources" data-ntc-feed-sources="true">
			<textarea
				class="ntc-feed-sources__storage"
				data-role="feed-source-storage"
				name="<?php echo esc_attr( $field_name ); ?>"
				id="<?php echo esc_attr( $key ); ?>"
				rows="1"
				hidden
			><?php echo esc_textarea( $value ); ?></textarea>

			<ul class="ntc-feed-sources__list" data-role="feed-source-list">
				<?php foreach ( $feed_urls as $feed_url ) : ?>
					<li class="ntc-feed-sources__item">
						<button type="button" class="ntc-feed-sources__handle button-link" aria-label="<?php echo esc_attr__( 'Drag to reorder', 'rss-news-carousel' ); ?>">::</button>
						<input class="regular-text ntc-feed-sources__input" type="url" value="<?php echo esc_attr( $feed_url ); ?>" placeholder="<?php echo esc_attr__( 'https://example.com/feed/', 'rss-news-carousel' ); ?>" />
						<div class="ntc-feed-sources__actions">
							<button type="button" class="button-secondary ntc-feed-sources__move" data-action="move-feed-source-up"><?php echo esc_html__( 'Up', 'rss-news-carousel' ); ?></button>
							<button type="button" class="button-secondary ntc-feed-sources__move" data-action="move-feed-source-down"><?php echo esc_html__( 'Down', 'rss-news-carousel' ); ?></button>
							<button type="button" class="button-link-delete ntc-feed-sources__remove" data-action="remove-feed-source"><?php echo esc_html__( 'Remove', 'rss-news-carousel' ); ?></button>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<button type="button" class="button-secondary ntc-feed-sources__add" data-action="add-feed-source"><?php echo esc_html__( 'Add source', 'rss-news-carousel' ); ?></button>

			<template>
				<li class="ntc-feed-sources__item">
					<button type="button" class="ntc-feed-sources__handle button-link" aria-label="<?php echo esc_attr__( 'Drag to reorder', 'rss-news-carousel' ); ?>">::</button>
					<input class="regular-text ntc-feed-sources__input" type="url" value="" placeholder="<?php echo esc_attr__( 'https://example.com/feed/', 'rss-news-carousel' ); ?>" />
					<div class="ntc-feed-sources__actions">
						<button type="button" class="button-secondary ntc-feed-sources__move" data-action="move-feed-source-up"><?php echo esc_html__( 'Up', 'rss-news-carousel' ); ?></button>
						<button type="button" class="button-secondary ntc-feed-sources__move" data-action="move-feed-source-down"><?php echo esc_html__( 'Down', 'rss-news-carousel' ); ?></button>
						<button type="button" class="button-link-delete ntc-feed-sources__remove" data-action="remove-feed-source"><?php echo esc_html__( 'Remove', 'rss-news-carousel' ); ?></button>
					</div>
				</li>
			</template>

			<noscript>
				<textarea
					class="large-text code"
					name="<?php echo esc_attr( $field_name ); ?>"
					rows="8"
				><?php echo esc_textarea( $value ); ?></textarea>
			</noscript>
		</div>
		<?php if ( ! empty( $description ) ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders a text input field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_text_field( array $args ) {
		$key         = $args['key'];
		$settings    = $this->get_settings();
		$value       = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		$description = isset( $args['description'] ) ? $args['description'] : '';
		?>
		<input
			class="regular-text"
			type="text"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"
			id="<?php echo esc_attr( $key ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<?php if ( ! empty( $description ) ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders a number input field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_number_field( array $args ) {
		$key         = $args['key'];
		$settings    = $this->get_settings();
		$value       = isset( $settings[ $key ] ) ? absint( $settings[ $key ] ) : 0;
		$min         = isset( $args['min'] ) ? absint( $args['min'] ) : 0;
		$max         = isset( $args['max'] ) ? absint( $args['max'] ) : 0;
		$description = isset( $args['description'] ) ? $args['description'] : '';
		?>
		<input
			class="small-text"
			type="number"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"
			id="<?php echo esc_attr( $key ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			min="<?php echo esc_attr( $min ); ?>"
			max="<?php echo esc_attr( $max ); ?>"
			step="1"
		/>
		<?php if ( ! empty( $description ) ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders a checkbox field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_checkbox_field( array $args ) {
		$key      = $args['key'];
		$settings = $this->get_settings();
		$checked  = ! empty( $settings[ $key ] );
		$label    = isset( $args['label'] ) ? $args['label'] : '';
		?>
		<label for="<?php echo esc_attr( $key ); ?>">
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"
				id="<?php echo esc_attr( $key ); ?>"
				value="1"
				<?php checked( $checked ); ?>
			/>
			<?php echo esc_html( $label ); ?>
		</label>
		<?php
	}

	/**
	 * Renders a select field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_select_field( array $args ) {
		$key         = $args['key'];
		$settings    = $this->get_settings();
		$value       = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		$options     = isset( $args['options'] ) ? $args['options'] : array();
		$description = isset( $args['description'] ) ? $args['description'] : '';
		?>
		<select
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"
			id="<?php echo esc_attr( $key ); ?>"
		>
			<?php foreach ( $options as $option_value => $option_label ) : ?>
				<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
					<?php echo esc_html( $option_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! empty( $description ) ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders a color input field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_color_field( array $args ) {
		$key         = $args['key'];
		$settings    = $this->get_settings();
		$value       = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		$description = isset( $args['description'] ) ? $args['description'] : '';
		?>
		<input
			class="ntc-color-field"
			type="color"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>"
			id="<?php echo esc_attr( $key ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<?php if ( ! empty( $description ) ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Returns available heading font options.
	 *
	 * @return array
	 */
	private function get_heading_font_options() {
		return array(
			'apex'      => __( 'Apex New', 'rss-news-carousel' ),
			'editorial' => __( 'Editorial serif', 'rss-news-carousel' ),
			'display'   => __( 'Display sans', 'rss-news-carousel' ),
			'modern'    => __( 'Modern sans', 'rss-news-carousel' ),
			'classic'   => __( 'Classic serif', 'rss-news-carousel' ),
		);
	}

	/**
	 * Returns available body font options.
	 *
	 * @return array
	 */
	private function get_body_font_options() {
		return array(
			'apex'    => __( 'Apex New', 'rss-news-carousel' ),
			'modern'  => __( 'Modern sans', 'rss-news-carousel' ),
			'neutral' => __( 'Neutral sans', 'rss-news-carousel' ),
			'classic' => __( 'Classic serif', 'rss-news-carousel' ),
		);
	}

	/**
	 * Returns stored settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return $this->normalize_text_settings(
			wp_parse_args( $settings, $this->get_defaults() )
		);
	}

	/**
	 * Normalizes legacy or mojibake frontend text settings.
	 *
	 * @param array $settings Settings payload.
	 * @return array
	 */
	private function normalize_text_settings( array $settings ) {
		if ( isset( $settings['header_eyebrow_text'] ) && 'TOTTENHAM LIVE FEED' === $settings['header_eyebrow_text'] ) {
			$settings['header_eyebrow_text'] = 'LIVE NYHEDSFEED';
		}

		if ( isset( $settings['header_title_text'] ) && 'SENESTE NYHEDER OM DIT YNDLINGSHOLD' === $settings['header_title_text'] ) {
			$settings['header_title_text'] = 'SENESTE NYHEDER';
		}

		if (
			! isset( $settings['read_more_text'] ) ||
			'' === $settings['read_more_text'] ||
			preg_match( '/\x{00C3}/u', (string) $settings['read_more_text'] )
		) {
			$settings['read_more_text'] = html_entity_decode( 'L&AElig;S MERE', ENT_QUOTES, 'UTF-8' );
		}

		if ( isset( $settings['read_more_text'] ) && $this->contains_mojibake_text( $settings['read_more_text'] ) ) {
			$settings['read_more_text'] = $this->get_default_read_more_text();
		}

		return $settings;
	}

	/**
	 * Splits a newline-separated feed list into trimmed entries.
	 *
	 * @param string $value Raw feed list.
	 * @return array
	 */
	private function split_feed_lines( $value ) {
		$value = is_string( $value ) ? $value : '';
		$lines = preg_split( '/\r\n|\r|\n/', $value );

		if ( empty( $lines ) ) {
			return array();
		}

		$lines = array_map( 'trim', $lines );
		$lines = array_filter(
			$lines,
			static function ( $line ) {
				return '' !== $line;
			}
		);

		return array_values( $lines );
	}

	/**
	 * Returns the default read-more label with proper Danish encoding.
	 *
	 * @return string
	 */
	private function get_default_read_more_text() {
		return html_entity_decode( 'L&AElig;S MERE', ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Returns whether a text value appears to contain mojibake.
	 *
	 * @param string $value Text value.
	 * @return bool
	 */
	private function contains_mojibake_text( $value ) {
		$value = (string) $value;

		return '' !== $value && false !== strpos( $value, 'Ã' );
	}
}

