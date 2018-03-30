<?php

/**
 * Adds an 'empty-only' argument to get_terms()
 *
 * Class _Site_Releases_Empty_Only_Terms
 */
class _Site_Releases_Empty_Only_Terms {

	static function on_load() {

		add_filter( 'pre_get_terms', [ __CLASS__, '_pre_get_terms' ] );
		add_filter( 'get_terms_defaults', [ __CLASS__, '_get_terms_defaults' ], 10, 2 );
		add_filter( 'get_terms', [ __CLASS__, '_get_terms' ], 10, 4 );
		add_filter( 'get_terms_args', [ __CLASS__, '_get_terms_args_999' ], 999, 2 );

	}

	/**
	 * Ensures $args[ 'hide_empty' ] is false if $args[ 'empty_only' ] is true
	 *
	 * @param array $args       An array of get_terms() arguments.
	 * @param array $taxonomies An array of taxonomies.
	 * @return array
	 */
	static function _get_terms_args_999( $args, $taxonomies ) {
		do {
			if ( empty( $args[ 'empty_only' ] ) ) {
				break;
			}
			if ( ! in_array( Site_Releases::TAXONOMY, $taxonomies ) ) {
				break;
			}
			$args[ 'hide_empty' ] = false;
		} while ( false );
		return $args;
	}

	/**
	 * Force 'hide_empty' to false if 'empty_only' is true.
	 * @param WP_Term_Query $term_query
	 */
	static function _pre_get_terms( $term_query ) {

		do {

			if ( empty( $term_query->query_vars[ 'empty_only' ] ) ) {
				break;
			}

			if ( ! isset( $term_query->query_var_defaults[ 'empty_only' ] ) ) {
				/**
				 * Not sure if this is needed, but it can't hurt
				 */
				$term_query->query_var_defaults[ 'empty_only' ] = false;
			}

			/**
			 * 'empty_only' means must have 'hide_empty' == false
			 */
			$term_query->query_vars[ 'hide_empty' ] = false;

			if ( ! is_null( $term_query->query_vars[ 'pad_counts' ] ) ) {
				break;
			}

			/**
			 * If still set to null (Twas set to null in self::_get_terms_defaults())
			 */
			$term_query->query_vars[ 'pad_counts' ] = true;

		} while ( false );

	}

	/**
	 * Set 'pad_counts' to null so if actually set we can detect that.
	 *
	 * @param string[] $query_defaults
	 * @param string[] $taxonomies
	 * @return string[]
	 */
	static function _get_terms_defaults( $query_defaults, $taxonomies ) {

		do {

			if ( is_null( $taxonomies ) ) {
				$taxonomies = [];
			}

			if ( ! is_array( $taxonomies ) ) {
				$taxonomies = [ $taxonomies ];
			}

			if ( ! in_array( Site_Releases::TAXONOMY, $taxonomies ) ) {
				break;
			}

			if ( empty( $query_defaults[ 'pad_counts' ] )  ) {
				$query_defaults[ 'pad_counts' ] = null;
			}

			if ( ! isset( $query_defaults[ 'include_selected' ] )  ) {
				$query_defaults[ 'include_selected' ] = false;
			}


		} while ( false );

		return $query_defaults;

	}

	/**
	 * Remove non-empty when 'empty_only' $arg passed
	 *
	 * @param array         $terms      Array of found terms.
	 * @param array         $taxonomies An array of taxonomies.
	 * @param array         $query_vars An array of get_terms() arguments.
	 * @param WP_Term_Query $term_query The WP_Term_Query object.
	 * @return array
	 */
	static function _get_terms( $terms, $taxonomies, $query_vars, $term_query  ) {

		do {

			if ( empty( $term_query->query_vars[ 'empty_only' ] ) ) {
				break;
			}

			if ( ! in_array( Site_Releases::TAXONOMY, $taxonomies ) ) {
				break;
			}

			$also_include = array();

			do {

				/**
				 * Handle when 'include_selected' is specified
				 */
				if ( empty( $term_query->query_vars[ 'include_selected' ] ) ) {
					break;
				}

				if ( empty( $term_query->query_vars[ 'selected' ] ) ) {
					break;
				}

				if ( ! $selected_id = intval( $term_query->query_vars[ 'selected' ] ) ) {
					break;
				}

				if ( empty( $term_query->query_vars[ 'hierarchical' ] ) ) {
					$also_include = [ $selected_id ];
					break;
				}

				$also_include = self::_get_family_terms( $selected_id );

			} while ( false );

			$children_terms = array();

			foreach ( $terms as $term ) {
				if ( 0 === $term->parent ) {
					continue;
				}
				$children_terms[ $term->parent ] = true;
			}

			foreach ( $terms as $index => $term ) {
				if ( 0 === $term->count ) {
					continue;
				}
				if ( ! empty( $term_query->query_vars[ 'hierarchical' ] ) ) {
					if ( in_array( $term->term_id, $also_include ) ) {
						continue;
					}
					if ( isset( $children_terms[ $term->term_id ] ) ) {
						continue;
					}
				}
				unset( $terms[ $index ] );
			}

			if ( false === strpos( $term_query->query_vars['fields'], '=>' ) ) {
				/**
				 * Not one of `id=>parent`, `id=>name` or id=>slug`; these are indexed by an id.
				 * When '=>' is not in 'fields' then caller expects  array index to start with
				 * zero and be sequential.
				 */
				$terms = array_values( $terms );
			}

		} while ( false );

		return $terms;

	}

	/**
	 * Return array of term_ids in ancestor order, e.g. $term_id will be last element
	 *
	 * @param int $term_id
	 * @return int[]
	 */
	static function _get_family_terms( $term_id ) {
		do {

			$family = array( $term_id );

			if ( ! $term = get_term( $term_id ) ) {
				break;
			}

			$parent_id = $term->parent;

			while ( $parent_id ) {

				array_unshift( $family, $parent_id );

				if ( ! $parent = get_term( $parent_id, Site_Releases::TAXONOMY ) ) {
					break;
				}

				$parent_id = $parent->parent;

			}

		} while ( false );

		return $family;

	}

}
_Site_Releases_Empty_Only_Terms::on_load();