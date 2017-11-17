<?php

/**
 * Class _Site_Releases_WPSEO_Integration
 */
class _Site_Releases_WPSEO_Integration {

	static function on_load() {

		add_filter( 'add_meta_boxes', [ __CLASS__, '_add_meta_boxes_11' ], 11, 2 );
		add_filter( 'option_wpseo_titles', [ __CLASS__, '_option_wpseo_titles' ] );

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
	}

}
_Site_Releases_WPSEO_Integration::on_load();