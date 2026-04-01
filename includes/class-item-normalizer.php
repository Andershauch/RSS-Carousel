<?php
/**
 * Feed item normalization.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes SimplePie items into a consistent array shape.
 */
class NTC_Item_Normalizer {

	/**
	 * Normalizes a list of feed items.
	 *
	 * @param array           $items Feed items.
	 * @param SimplePie|false $feed  Feed object.
	 * @return array
	 */
	public function normalize_items( array $items, $feed = false ) {
		$normalized_items = array();
		$source_name      = $this->get_feed_source( $feed );

		foreach ( $items as $item ) {
			if ( ! $item instanceof SimplePie_Item ) {
				continue;
			}

			$normalized_items[] = $this->normalize_item( $item, $source_name );
		}

		return $normalized_items;
	}

	/**
	 * Normalizes a single feed item.
	 *
	 * @param SimplePie_Item $item        Feed item.
	 * @param string         $source_name Feed source fallback name.
	 * @return array
	 */
	public function normalize_item( SimplePie_Item $item, $source_name = '' ) {
		$title        = $this->sanitize_text_value( $item->get_title() );
		$url          = esc_url_raw( $item->get_link() );
		$guid         = $this->sanitize_text_value( $item->get_id() );
		$excerpt      = $this->sanitize_text_value( $this->get_excerpt( $item ) );
		$media_data   = $this->get_media_data( $item );
		$image        = $media_data['image'];
		$categories   = $this->get_categories( $item );
		$published_at = $this->get_published_at( $item );

		if ( empty( $source_name ) ) {
			$source_name = $this->get_feed_source( $item->get_feed() );
		}

		return array(
			'title'        => $title,
			'url'          => $url,
			'source'       => $source_name,
			'published_at' => $published_at,
			'excerpt'      => $excerpt,
			'image'        => $image,
			'media_type'   => $media_data['type'],
			'media_url'    => $media_data['url'],
			'categories'   => $categories,
			'guid'         => $guid,
		);
	}

	/**
	 * Returns a normalized publication date.
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return string
	 */
	private function get_published_at( SimplePie_Item $item ) {
		$timestamp = $item->get_date( 'U' );

		if ( false === $timestamp || ! is_numeric( $timestamp ) ) {
			return '';
		}

		$timestamp = (int) $timestamp;

		if ( $timestamp <= 0 ) {
			return '';
		}

		return gmdate( 'c', $timestamp );
	}

	/**
	 * Returns a feed source name.
	 *
	 * @param SimplePie|false $feed Feed object.
	 * @return string
	 */
	private function get_feed_source( $feed ) {
		if ( ! $feed || ! is_object( $feed ) || ! method_exists( $feed, 'get_title' ) ) {
			return '';
		}

		$source_name = $this->sanitize_text_value( $feed->get_title() );

		if ( ! empty( $source_name ) ) {
			return $source_name;
		}

		if ( method_exists( $feed, 'get_link' ) ) {
			$feed_url = esc_url_raw( $feed->get_link() );
			$host     = wp_parse_url( $feed_url, PHP_URL_HOST );

			if ( is_string( $host ) && '' !== $host ) {
				return sanitize_text_field( $host );
			}
		}

		return '';
	}

	/**
	 * Returns a normalized excerpt.
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return string
	 */
	private function get_excerpt( SimplePie_Item $item ) {
		$excerpt = $item->get_description();

		if ( empty( $excerpt ) ) {
			$excerpt = $item->get_content();
		}

		return $excerpt;
	}

	/**
	 * Returns normalized categories.
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return array
	 */
	private function get_categories( SimplePie_Item $item ) {
		$categories      = array();
		$item_categories = $item->get_categories();

		if ( empty( $item_categories ) ) {
			return $categories;
		}

		foreach ( $item_categories as $category ) {
			if ( ! is_object( $category ) || ! method_exists( $category, 'get_label' ) ) {
				continue;
			}

			$label = $this->sanitize_text_value( $category->get_label() );

			if ( '' !== $label ) {
				$categories[] = $label;
			}
		}

		return array_values( array_unique( $categories ) );
	}

	/**
	 * Returns normalized media data for the item.
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return array
	 */
	private function get_media_data( SimplePie_Item $item ) {
		$enclosures = $item->get_enclosures();
		$image_url  = '';
		$audio_url  = '';
		$video_url  = '';

		if ( ! empty( $enclosures ) ) {
			foreach ( $enclosures as $enclosure ) {
				if ( ! is_object( $enclosure ) || ! method_exists( $enclosure, 'get_link' ) ) {
					continue;
				}

				$enclosure_url = esc_url_raw( $enclosure->get_link() );
				$type          = method_exists( $enclosure, 'get_type' ) ? (string) $enclosure->get_type() : '';

				if ( empty( $image_url ) && $this->is_image_url( $enclosure_url, $type ) ) {
					$image_url = $enclosure_url;
					continue;
				}

				if ( empty( $audio_url ) && $this->is_audio_url( $enclosure_url, $type ) ) {
					$audio_url = $enclosure_url;
					continue;
				}

				if ( empty( $video_url ) && $this->is_video_url( $enclosure_url, $type ) ) {
					$video_url = $enclosure_url;
				}
			}
		}

		$content = (string) $item->get_content() . ' ' . (string) $item->get_description();

		if ( empty( $video_url ) ) {
			$video_url = $this->extract_media_source(
				$content,
				array(
					'/<video[^>]+src=[\'"]([^\'"]+)[\'"]/i',
					'/<source[^>]+src=[\'"]([^\'"]+)[\'"][^>]+type=[\'"]video\/[^\'"]+[\'"]/i',
					'/<source[^>]+type=[\'"]video\/[^\'"]+[\'"][^>]+src=[\'"]([^\'"]+)[\'"]/i',
				)
			);
		}

		if ( empty( $audio_url ) ) {
			$audio_url = $this->extract_media_source(
				$content,
				array(
					'/<audio[^>]+src=[\'"]([^\'"]+)[\'"]/i',
					'/<source[^>]+src=[\'"]([^\'"]+)[\'"][^>]+type=[\'"]audio\/[^\'"]+[\'"]/i',
					'/<source[^>]+type=[\'"]audio\/[^\'"]+[\'"][^>]+src=[\'"]([^\'"]+)[\'"]/i',
				)
			);
		}

		if ( empty( $image_url ) ) {
			$image_url = $this->extract_media_source(
				$content,
				array(
					'/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i',
				)
			);
		}

		if ( ! empty( $video_url ) ) {
			return array(
				'type'  => 'video',
				'url'   => $video_url,
				'image' => $image_url,
			);
		}

		if ( ! empty( $audio_url ) ) {
			return array(
				'type'  => 'audio',
				'url'   => $audio_url,
				'image' => $image_url,
			);
		}

		if ( ! empty( $image_url ) ) {
			return array(
				'type'  => 'image',
				'url'   => $image_url,
				'image' => $image_url,
			);
		}

		return array(
			'type'  => '',
			'url'   => '',
			'image' => '',
		);
	}

	/**
	 * Extracts the first matching media source from HTML content.
	 *
	 * @param string $content  HTML content.
	 * @param array  $patterns Regex patterns.
	 * @return string
	 */
	private function extract_media_source( $content, array $patterns ) {
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $content, $matches ) ) {
				$media_url = isset( $matches[1] ) ? esc_url_raw( $matches[1] ) : '';

				if ( ! empty( $media_url ) ) {
					return $media_url;
				}
			}
		}

		return '';
	}

	/**
	 * Determines whether the given URL/type looks like an image.
	 *
	 * @param string $url  Candidate URL.
	 * @param string $type Candidate mime type.
	 * @return bool
	 */
	private function is_image_url( $url, $type = '' ) {
		if ( empty( $url ) ) {
			return false;
		}

		if ( 0 === strpos( $type, 'image/' ) ) {
			return true;
		}

		return (bool) preg_match( '/\.(?:jpg|jpeg|png|gif|webp|avif|svg)(?:\?.*)?$/i', $url );
	}

	/**
	 * Determines whether the given URL/type looks like audio.
	 *
	 * @param string $url  Candidate URL.
	 * @param string $type Candidate mime type.
	 * @return bool
	 */
	private function is_audio_url( $url, $type = '' ) {
		if ( empty( $url ) ) {
			return false;
		}

		if ( 0 === strpos( $type, 'audio/' ) ) {
			return true;
		}

		return (bool) preg_match( '/\.(?:mp3|m4a|aac|ogg|wav)(?:\?.*)?$/i', $url );
	}

	/**
	 * Determines whether the given URL/type looks like video.
	 *
	 * @param string $url  Candidate URL.
	 * @param string $type Candidate mime type.
	 * @return bool
	 */
	private function is_video_url( $url, $type = '' ) {
		if ( empty( $url ) ) {
			return false;
		}

		if ( 0 === strpos( $type, 'video/' ) ) {
			return true;
		}

		return (bool) preg_match( '/\.(?:mp4|webm|mov|m4v|ogv)(?:\?.*)?$/i', $url );
	}

	/**
	 * Sanitizes text-like values from feed data.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function sanitize_text_value( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$value = wp_strip_all_tags( $value, true );

		return sanitize_text_field( $value );
	}
}
