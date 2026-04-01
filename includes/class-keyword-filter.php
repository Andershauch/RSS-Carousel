<?php
/**
 * Keyword filtering and prioritization for normalized feed items.
 *
 * @package RSS_News_Carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters and prioritizes normalized items against configured keywords.
 */
class NTC_Keyword_Filter {

	/**
	 * Filters a list of normalized items to only keyword matches.
	 *
	 * @param array        $items    Normalized items.
	 * @param string|array $keywords Keyword list.
	 * @return array
	 */
	public function filter_items( array $items, $keywords ) {
		$normalized_keywords = $this->normalize_keywords( $keywords );

		if ( empty( $normalized_keywords ) ) {
			return $items;
		}

		$filtered_items = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( $this->get_item_keyword_score( $item, $normalized_keywords ) > 0 ) {
				$filtered_items[] = $item;
			}
		}

		return $filtered_items;
	}

	/**
	 * Adds keyword priority metadata so matching items can be sorted first.
	 *
	 * @param array        $items    Normalized items.
	 * @param string|array $keywords Keyword list.
	 * @return array
	 */
	public function prioritize_items( array $items, $keywords ) {
		$normalized_keywords = $this->normalize_keywords( $keywords );

		if ( empty( $normalized_keywords ) ) {
			return $this->add_default_priority( $items );
		}

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				unset( $items[ $index ] );
				continue;
			}

			$matched_keywords = $this->get_item_matched_keywords( $item, $normalized_keywords );

			$items[ $index ]['ntc_keyword_score']    = $this->get_item_keyword_score( $item, $normalized_keywords );
			$items[ $index ]['ntc_keyword_hits']     = count( $matched_keywords );
			$items[ $index ]['ntc_matched_keywords'] = $matched_keywords;
		}

		return array_values( $items );
	}

	/**
	 * Normalizes keywords to a lowercase array.
	 *
	 * @param string|array $keywords Raw keywords.
	 * @return array
	 */
	private function normalize_keywords( $keywords ) {
		if ( is_string( $keywords ) ) {
			$keywords = explode( ',', $keywords );
		}

		if ( ! is_array( $keywords ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $keywords as $keyword ) {
			$keyword = sanitize_text_field( $keyword );
			$keyword = trim( $keyword );

			if ( '' !== $keyword ) {
				$normalized[] = $this->lowercase( $keyword );
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Adds zeroed priority metadata when no keywords are configured.
	 *
	 * @param array $items Normalized items.
	 * @return array
	 */
	private function add_default_priority( array $items ) {
		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				unset( $items[ $index ] );
				continue;
			}

			$items[ $index ]['ntc_keyword_score']    = 0;
			$items[ $index ]['ntc_keyword_hits']     = 0;
			$items[ $index ]['ntc_matched_keywords'] = array();
		}

		return array_values( $items );
	}

	/**
	 * Returns a weighted keyword score for an item.
	 *
	 * @param array $item     Normalized item.
	 * @param array $keywords Normalized keywords.
	 * @return int
	 */
	private function get_item_keyword_score( array $item, array $keywords ) {
		$title      = $this->normalize_text( isset( $item['title'] ) ? $item['title'] : '' );
		$excerpt    = $this->normalize_text( isset( $item['excerpt'] ) ? $item['excerpt'] : '' );
		$categories = $this->normalize_text(
			isset( $item['categories'] ) && is_array( $item['categories'] )
				? implode( ' ', $item['categories'] )
				: ''
		);
		$score      = 0;

		foreach ( $keywords as $keyword ) {
			if ( false !== strpos( $title, $keyword ) ) {
				$score += 5;
			}

			if ( false !== strpos( $categories, $keyword ) ) {
				$score += 3;
			}

			if ( false !== strpos( $excerpt, $keyword ) ) {
				$score += 1;
			}
		}

		return $score;
	}

	/**
	 * Returns the matched keywords for an item.
	 *
	 * @param array $item     Normalized item.
	 * @param array $keywords Normalized keywords.
	 * @return array
	 */
	private function get_item_matched_keywords( array $item, array $keywords ) {
		$title      = $this->normalize_text( isset( $item['title'] ) ? $item['title'] : '' );
		$excerpt    = $this->normalize_text( isset( $item['excerpt'] ) ? $item['excerpt'] : '' );
		$categories = $this->normalize_text(
			isset( $item['categories'] ) && is_array( $item['categories'] )
				? implode( ' ', $item['categories'] )
				: ''
		);
		$matches    = array();

		foreach ( $keywords as $keyword ) {
			if (
				false !== strpos( $title, $keyword ) ||
				false !== strpos( $excerpt, $keyword ) ||
				false !== strpos( $categories, $keyword )
			) {
				$matches[] = $keyword;
			}
		}

		return array_values( array_unique( $matches ) );
	}

	/**
	 * Normalizes text for keyword matching.
	 *
	 * @param string $value Input text.
	 * @return string
	 */
	private function normalize_text( $value ) {
		return $this->lowercase( wp_strip_all_tags( (string) $value, true ) );
	}

	/**
	 * Converts text to lowercase, using mbstring when available.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	private function lowercase( $value ) {
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $value );
		}

		return strtolower( $value );
	}
}
