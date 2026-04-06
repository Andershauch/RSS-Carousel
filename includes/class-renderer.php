<?php
/**
 * Frontend carousel rendering.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders cached feed items into semantic carousel markup.
 */
class NTC_Renderer {

	/**
	 * Feed fetcher.
	 *
	 * @var NTC_Feed_Fetcher
	 */
	private $feed_fetcher;

	/**
	 * Settings handler.
	 *
	 * @var NTC_Settings
	 */
	private $settings;

	/**
	 * Class constructor.
	 *
	 * @param NTC_Feed_Fetcher $feed_fetcher Feed fetcher.
	 * @param NTC_Settings     $settings     Settings handler.
	 */
	public function __construct( NTC_Feed_Fetcher $feed_fetcher, NTC_Settings $settings ) {
		$this->feed_fetcher = $feed_fetcher;
		$this->settings     = $settings;
	}

	/**
	 * Renders the carousel HTML.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( array $atts = array() ) {
		$settings        = $this->settings->get_settings();
		$data            = $this->feed_fetcher->get_feed_data();
		$items           = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
		$item_count      = count( $items );
		$theme           = $this->sanitize_theme( $settings );
		$layout          = $this->sanitize_layout( $settings );
		$autoplay        = ! empty( $settings['autoplay'] );
		$block_id        = $this->generate_block_id();
		$inline_style    = $this->get_inline_style( $settings );
		$eyebrow_text    = $this->get_display_text( $settings, 'header_eyebrow_text', 'LIVE NYHEDSFEED' );
		$heading_text    = $this->get_display_text( $settings, 'header_title_text', 'SENESTE NYHEDER' );
		$section_classes = array(
			'ntc-carousel',
			'ntc-carousel--theme-' . $theme,
			'ntc-carousel--layout-' . $layout,
		);

		if ( empty( $items ) ) {
			$message = isset( $data['message'] ) ? (string) $data['message'] : '';

			return $this->wrap_fallback( $message, $theme, $layout, $inline_style );
		}

		ob_start();
		?>
		<section
			id="<?php echo esc_attr( $block_id ); ?>"
			class="<?php echo esc_attr( implode( ' ', $section_classes ) ); ?>"
			style="<?php echo esc_attr( $inline_style ); ?>"
			data-ntc-carousel="true"
			data-autoplay="<?php echo esc_attr( $autoplay ? 'true' : 'false' ); ?>"
			aria-roledescription="<?php echo esc_attr__( 'carousel', 'rss-news-carousel' ); ?>"
			aria-label="<?php echo esc_attr__( 'News topic carousel', 'rss-news-carousel' ); ?>"
		>
			<div class="ntc-carousel__header">
				<div class="ntc-carousel__heading-group">
					<p class="ntc-carousel__eyebrow"><?php echo esc_html( $eyebrow_text ); ?></p>
					<h2 class="ntc-carousel__title"><?php echo esc_html( $heading_text ); ?></h2>
				</div>
			</div>

			<div class="ntc-carousel__body">
				<button
					type="button"
					class="ntc-carousel__nav ntc-carousel__nav--prev"
					data-action="prev"
					aria-controls="<?php echo esc_attr( $block_id . '-track' ); ?>"
				>
					<span class="screen-reader-text"><?php echo esc_html__( 'Show previous stories', 'rss-news-carousel' ); ?></span>
					<span class="ntc-carousel__nav-icon" aria-hidden="true">&#10094;</span>
				</button>

				<button
					type="button"
					class="ntc-carousel__nav ntc-carousel__nav--next"
					data-action="next"
					aria-controls="<?php echo esc_attr( $block_id . '-track' ); ?>"
				>
					<span class="screen-reader-text"><?php echo esc_html__( 'Show next stories', 'rss-news-carousel' ); ?></span>
					<span class="ntc-carousel__nav-icon" aria-hidden="true">&#10095;</span>
				</button>

				<div class="ntc-carousel__viewport" tabindex="0" data-role="viewport">
					<ul id="<?php echo esc_attr( $block_id . '-track' ); ?>" class="ntc-carousel__track" role="list">
						<?php foreach ( $items as $index => $item ) : ?>
							<?php echo $this->render_item( $item, $settings, $index + 1, $item_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Renders a single carousel item.
	 *
	 * @param array $item     Normalized item.
	 * @param array $settings Plugin settings.
	 * @param int   $position Slide position.
	 * @param int   $total    Total number of slides.
	 * @return string
	 */
	private function render_item( array $item, array $settings, $position, $total ) {
		$title            = isset( $item['title'] ) ? (string) $item['title'] : '';
		$url              = isset( $item['url'] ) ? (string) $item['url'] : '';
		$source           = isset( $item['source'] ) ? (string) $item['source'] : '';
		$published_at     = isset( $item['published_at'] ) ? (string) $item['published_at'] : '';
		$excerpt          = isset( $item['excerpt'] ) ? (string) $item['excerpt'] : '';
		$image            = isset( $item['image'] ) ? (string) $item['image'] : '';
		$media_type       = isset( $item['media_type'] ) ? (string) $item['media_type'] : '';
		$media_url        = isset( $item['media_url'] ) ? (string) $item['media_url'] : '';
		$matched_keywords = isset( $item['ntc_matched_keywords'] ) && is_array( $item['ntc_matched_keywords'] )
			? $item['ntc_matched_keywords']
			: array();

		$show_media       = ! empty( $settings['show_image'] );
		$show_date        = ! empty( $settings['show_date'] ) && ! empty( $published_at );
		$show_source      = ! empty( $settings['show_source'] ) && '' !== $source;
		$show_excerpt     = ! empty( $settings['show_excerpt'] ) && '' !== $excerpt;
		$display_title    = '' !== $title ? $title : __( 'Untitled news item', 'rss-news-carousel' );
		$display_excerpt  = $show_excerpt ? wp_trim_words( $excerpt, 18, '&hellip;' ) : '';
		$read_more_fallback = 'LÃ†S MERE';
		$read_more_text   = $this->get_display_text( $settings, 'read_more_text', 'LÃ†S MERE' );
		$date_markup      = $show_date ? $this->get_date_markup( $published_at ) : '';

		if ( '' === $read_more_text || false !== strpos( $read_more_text, 'Ãƒ' ) ) {
			$read_more_text = $read_more_fallback;
		}

		$media_markup     = $show_media ? $this->get_media_markup( $media_type, $media_url, $image, $display_title ) : '';

		if ( '' === $read_more_text || preg_match( '/\x{00C3}/u', $read_more_text ) ) {
			$read_more_text = html_entity_decode( 'L&AElig;S MERE', ENT_QUOTES, 'UTF-8' );
		}

		$read_more_text = $this->normalize_read_more_text( $read_more_text );

		$keyword_markup   = $this->get_matched_keywords_markup( $matched_keywords );
		$card_classes     = array( 'ntc-card' );

		ob_start();
		?>
		<li
			class="ntc-carousel__slide"
			data-slide-index="<?php echo esc_attr( $position - 1 ); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'Slide %1$d of %2$d', 'rss-news-carousel' ), $position, $total ) ); ?>"
			aria-roledescription="<?php echo esc_attr__( 'slide', 'rss-news-carousel' ); ?>"
		>
			<article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>">
				<?php echo $media_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<div class="ntc-card__content">
					<?php if ( $show_date || $show_source ) : ?>
						<p class="ntc-card__meta">
							<?php if ( $show_source ) : ?>
								<span class="ntc-card__source"><?php echo esc_html( $source ); ?></span>
							<?php endif; ?>
							<?php if ( $show_date ) : ?>
								<?php echo $date_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<h3 class="ntc-card__title"><?php echo esc_html( $display_title ); ?></h3>

					<?php if ( $show_excerpt ) : ?>
						<p class="ntc-card__excerpt"><?php echo esc_html( $display_excerpt ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $url ) || '' !== $keyword_markup ) : ?>
						<div class="ntc-card__footer">
							<?php if ( ! empty( $url ) ) : ?>
								<a class="ntc-card__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( $read_more_text ); ?>
								</a>
							<?php endif; ?>
							<?php echo $keyword_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>
				</div>
			</article>
		</li>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Returns rendered media markup for an item.
	 *
	 * @param string $media_type    Media type.
	 * @param string $media_url     Media URL.
	 * @param string $image_url     Image URL.
	 * @param string $display_title Accessible title text.
	 * @return string
	 */
	private function get_media_markup( $media_type, $media_url, $image_url, $display_title ) {
		if ( 'video' === $media_type && ! empty( $media_url ) ) {
			$poster_attribute = ! empty( $image_url ) ? sprintf( ' poster="%s"', esc_url( $image_url ) ) : '';

			ob_start();
			?>
			<div class="ntc-card__media ntc-card__media--video">
				<video controls preload="metadata" playsinline<?php echo $poster_attribute; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<source src="<?php echo esc_url( $media_url ); ?>" />
					<?php echo esc_html( $display_title ); ?>
				</video>
			</div>
			<?php

			return (string) ob_get_clean();
		}

		if ( 'audio' === $media_type && ! empty( $media_url ) ) {
			ob_start();
			?>
			<div class="ntc-card__media ntc-card__media--audio">
				<?php if ( ! empty( $image_url ) ) : ?>
					<div class="ntc-card__cover">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy" />
					</div>
				<?php endif; ?>
				<audio controls preload="none">
					<source src="<?php echo esc_url( $media_url ); ?>" />
					<?php echo esc_html( $display_title ); ?>
				</audio>
			</div>
			<?php

			return (string) ob_get_clean();
		}

		if ( ! empty( $image_url ) ) {
			ob_start();
			?>
			<div class="ntc-card__media ntc-card__media--image">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy" />
			</div>
			<?php

			return (string) ob_get_clean();
		}

		return '';
	}

	/**
	 * Returns formatted date markup.
	 *
	 * @param string $published_at ISO 8601 date string.
	 * @return string
	 */
	private function get_date_markup( $published_at ) {
		$timestamp = strtotime( $published_at );

		if ( false === $timestamp ) {
			return '';
		}

		return sprintf(
			'<time class="ntc-card__date" datetime="%1$s">%2$s</time>',
			esc_attr( gmdate( 'c', $timestamp ) ),
			esc_html( wp_date( get_option( 'date_format' ), $timestamp ) )
		);
	}

	/**
	 * Returns rendered matched keyword tags.
	 *
	 * @param array $matched_keywords Matched keyword list.
	 * @return string
	 */
	private function get_matched_keywords_markup( array $matched_keywords ) {
		if ( empty( $matched_keywords ) ) {
			return '';
		}

		$tags = array();

		foreach ( $matched_keywords as $keyword ) {
			$tag = $this->format_keyword_tag( $keyword );

			if ( '' !== $tag ) {
				$tags[] = $tag;
			}
		}

		if ( empty( $tags ) ) {
			return '';
		}

		return sprintf(
			'<span class="ntc-card__keywords">%s</span>',
			esc_html( implode( ', ', $tags ) )
		);
	}

	/**
	 * Formats a keyword as a hashtag label.
	 *
	 * @param string $keyword Raw keyword.
	 * @return string
	 */
	private function format_keyword_tag( $keyword ) {
		$keyword = sanitize_text_field( (string) $keyword );
		$keyword = trim( $keyword );
		$keyword = ltrim( $keyword, '#' );

		if ( '' === $keyword ) {
			return '';
		}

		$keyword = preg_replace( '/\s+/', '-', $keyword );

		if ( ! is_string( $keyword ) || '' === $keyword ) {
			return '';
		}

		return '#' . $keyword;
	}

	/**
	 * Returns a settings-based text value with a fallback.
	 *
	 * @param array  $settings Plugin settings.
	 * @param string $key      Setting key.
	 * @param string $fallback Fallback text.
	 * @return string
	 */
	private function get_display_text( array $settings, $key, $fallback ) {
		$value = isset( $settings[ $key ] ) ? sanitize_text_field( (string) $settings[ $key ] ) : '';

		if ( '' === $value ) {
			return $fallback;
		}

		return $value;
	}

	/**
	 * Normalizes the read-more label to keep Danish characters intact.
	 *
	 * @param string $text Read-more label.
	 * @return string
	 */
	private function normalize_read_more_text( $text ) {
		$text = sanitize_text_field( (string) $text );

		if ( '' === $text || false !== strpos( $text, 'Ã' ) ) {
			return html_entity_decode( 'L&AElig;S MERE', ENT_QUOTES, 'UTF-8' );
		}

		return $text;
	}

	/**
	 * Wraps a fallback message in frontend container classes.
	 *
	 * @param string $message      Message text.
	 * @param string $theme        Theme slug.
	 * @param string $layout       Layout slug.
	 * @param string $inline_style Inline style string.
	 * @return string
	 */
	private function wrap_fallback( $message, $theme, $layout, $inline_style ) {
		$container_classes = array(
			'ntc-carousel',
			'ntc-carousel--empty',
			'ntc-carousel--theme-' . $theme,
			'ntc-carousel--layout-' . $layout,
		);

		return sprintf(
			'<section class="%1$s" style="%2$s" aria-label="%3$s">%4$s</section>',
			esc_attr( implode( ' ', $container_classes ) ),
			esc_attr( $inline_style ),
			esc_attr__( 'News topic carousel', 'rss-news-carousel' ),
			$this->feed_fetcher->get_fallback_markup( $message )
		);
	}

	/**
	 * Returns inline CSS custom properties from settings.
	 *
	 * @param array $settings Plugin settings.
	 * @return string
	 */
	private function get_inline_style( array $settings ) {
		$declarations = array(
			'--ntc-heading-font'      => $this->get_font_stack( isset( $settings['heading_font'] ) ? $settings['heading_font'] : 'apex', 'heading' ),
			'--ntc-body-font'         => $this->get_font_stack( isset( $settings['body_font'] ) ? $settings['body_font'] : 'apex', 'body' ),
			'--ntc-heading-color'     => isset( $settings['heading_color'] ) ? sanitize_hex_color( $settings['heading_color'] ) : '#0a1c54',
			'--ntc-body-color'        => isset( $settings['body_color'] ) ? sanitize_hex_color( $settings['body_color'] ) : '#7a7a7a',
			'--ntc-accent'            => isset( $settings['accent_color'] ) ? sanitize_hex_color( $settings['accent_color'] ) : '#f6fe08',
			'--ntc-bg'                => isset( $settings['background_color'] ) ? sanitize_hex_color( $settings['background_color'] ) : '#ffffff',
			'--ntc-surface'           => isset( $settings['background_color'] ) ? sanitize_hex_color( $settings['background_color'] ) : '#ffffff',
			'--ntc-surface-strong'    => isset( $settings['background_color'] ) ? sanitize_hex_color( $settings['background_color'] ) : '#ffffff',
			'--ntc-header-bg'         => isset( $settings['header_background_color'] ) ? sanitize_hex_color( $settings['header_background_color'] ) : '#0a1c54',
			'--ntc-header-text-color' => isset( $settings['header_text_color'] ) ? sanitize_hex_color( $settings['header_text_color'] ) : '#ffffff',
		);
		$style        = '';

		foreach ( $declarations as $property => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			$style .= $property . ':' . $value . ';';
		}

		return $style;
	}

	/**
	 * Returns a font stack for the selected font token.
	 *
	 * @param string $font_token Font token.
	 * @param string $context    Context type.
	 * @return string
	 */
	private function get_font_stack( $font_token, $context ) {
		$font_token = sanitize_key( $font_token );

		$heading_fonts = array(
			'apex'      => '"Apex New", "Avenir Next", "Segoe UI", "Helvetica Neue", sans-serif',
			'editorial' => '"Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif',
			'display'   => '"Trebuchet MS", "Avenir Next", "Segoe UI", sans-serif',
			'modern'    => '"Avenir Next", "Segoe UI", "Helvetica Neue", sans-serif',
			'classic'   => 'Baskerville, "Times New Roman", serif',
		);
		$body_fonts    = array(
			'apex'    => '"Apex New", "Avenir Next", "Segoe UI", "Helvetica Neue", sans-serif',
			'modern'  => '"Avenir Next", "Segoe UI", "Helvetica Neue", sans-serif',
			'neutral' => '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif',
			'classic' => 'Georgia, "Times New Roman", serif',
		);
		$fonts         = ( 'heading' === $context ) ? $heading_fonts : $body_fonts;

		if ( isset( $fonts[ $font_token ] ) ) {
			return $fonts[ $font_token ];
		}

		return ( 'heading' === $context ) ? $heading_fonts['apex'] : $body_fonts['apex'];
	}

	/**
	 * Sanitizes the configured theme.
	 *
	 * @param array $settings Plugin settings.
	 * @return string
	 */
	private function sanitize_theme( array $settings ) {
		$theme = isset( $settings['theme'] ) ? sanitize_key( $settings['theme'] ) : 'light';

		return in_array( $theme, array( 'light', 'dark' ), true ) ? $theme : 'light';
	}

	/**
	 * Sanitizes the configured layout.
	 *
	 * @param array $settings Plugin settings.
	 * @return string
	 */
	private function sanitize_layout( array $settings ) {
		$layout = isset( $settings['layout'] ) ? sanitize_key( $settings['layout'] ) : 'cards';

		return in_array( $layout, array( 'compact', 'cards', 'hero' ), true ) ? $layout : 'cards';
	}

	/**
	 * Generates a unique DOM id for the carousel instance.
	 *
	 * @return string
	 */
	private function generate_block_id() {
		if ( function_exists( 'wp_unique_id' ) ) {
			return wp_unique_id( 'ntc-carousel-' );
		}

		return uniqid( 'ntc-carousel-', false );
	}
}

