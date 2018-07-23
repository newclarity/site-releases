<?php

/**
 * Class _Site_Releases_Disable_WPSEO
 */
class _Site_Releases_Disable_WPSEO {

	static function on_load() {

		add_filter( 'add_meta_boxes', [ __CLASS__, '_add_meta_boxes_11' ], 11, 2 );
		add_filter( 'option_wpseo_titles', [ __CLASS__, '_option_wpseo_titles' ] );
		add_filter( 'admin_init', [ __CLASS__, '_admin_init_11' ], 11 );

	}

	/**
	 * Disable Yoast SEO stuff in publish box for Releases
	 */
	static function _option_wpseo_titles( $value ) {
		$value[ 'hideeditbox-' . Site_Releases::POST_TYPE ] = true;
		return $value;
	}

	/**
	 * Remove Yoast SEO metabox for Release
	 */
	static function _add_meta_boxes_11() {
		remove_meta_box( 'wpseo_meta', Site_Releases::POST_TYPE, 'normal' );
		remove_meta_box( 'yoast_internal_linking', Site_Releases::POST_TYPE, 'side' );
	}

	/**
	 * Disable Yoast SEO stuff in term edit page for Release Names
	 */
	static function _admin_init_11() {
		global $taxnow, $pagenow;
		do {
			if ( ! isset( $taxnow ) ) {
				break;
			}
			if ( ! isset( $pagenow ) ) {
				break;
			}
			if ( Site_Releases::TAXONOMY !== $taxnow ) {
				break;
			}
			if ( 'term.php' !== $pagenow ) {
				break;
			}
			$hook = "{$taxnow}_edit_form";
			$callable =  self::_get_hook_instance_method_callable( $hook, 'WPSEO_Taxonomy', 'term_metabox', 90 );
			if ( is_null( $callable ) ) {
				break;
			}
			remove_action( $hook, $callable, 90 );
		} while ( false );
	}

	/**
	 * Find the callable for and a hook which callable calls an instance method
	 */
	static function _get_hook_instance_method_callable( $hook, $class_name, $method_name, $priority = 10 ) {
		global $wp_filter;
		do {
			$callable = null;
			if ( ! isset( $wp_filter[ $hook ] ) ) {
				break;
			}
			$hook_obj = $wp_filter[ $hook ];
			if ( ! isset( $hook_obj->callbacks ) ) {
				break;
			}
			if ( ! is_array( $hook_obj->callbacks ) ) {
				break;
			}
			if ( ! isset( $hook_obj->callbacks[ $priority ] ) ) {
				break;
			}
			$callbacks = $hook_obj->callbacks[ $priority ];
			if ( ! is_array( $callbacks ) ) {
				break;
			}
			foreach( $callbacks as $index => $callback ) {
				if ( ! isset( $callback[ 'function' ] ) ) {
					continue;
				}
				if ( ! is_array( $callback[ 'function' ] ) ) {
					continue;
				}
				if ( 2 !== count( $callback[ 'function' ] ) ) {
					continue;
				}
				$callback = $callback[ 'function' ];
				if ( ! isset( $callback[ 0 ] ) ) {
					continue;
				}
				if ( ! isset( $callback[ 1 ] ) ) {
					continue;
				}
				if ( ! is_object( $callback[ 0 ] ) ) {
					continue;
				}
				if ( ! is_string( $callback[ 1 ] ) ) {
					continue;
				}
				if ( ! is_a( $callback[ 0 ], $class_name ) ) {
					continue;
				}
				if ( $method_name !== $callback[ 1 ] ) {
					continue;
				}
				$callable = $callback;
				break;
			}

		} while ( false );

		return $callable;

	}

}
_Site_Releases_Disable_WPSEO::on_load();