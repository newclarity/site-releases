<?php
/*
Plugin Name: Site Releases
Plugin URI: https://github.com/newclarity/site-releases
Description: Help agencies to track site releases
Version: 0.1.4
Author: The NewClarity Team
Author URI: http://newclarity.net
Text Domain: site-releases
*/

add_action( 'init', [ 'Site_Releases', 'on_load' ], 11 );

class Site_Releases {

	const POST_TYPE =  'sr_site_release';

	const TAXONOMY =  'sr_site_release_name';

	const _BELOW_SETTINGS = 80;

	const _CAPABILITIES_REQUIRED = 'manage_options';

	static function on_load() {
		require __DIR__ . '/includes/class-wpseo-integration.php';
		require __DIR__ . '/includes/class-empty-only-terms.php';

		register_activation_hook( __FILE__, [ __CLASS__, 'activate' ] );

		self::_register_data();
		add_action( 'edit_form_after_title', [ __CLASS__, '_edit_form_after_title' ] );

		/**
		 * Persist selected release name.
		 */
		add_action( 'wp_loaded', [ __CLASS__, '_wp_loaded' ] );

	}

	static function activate() {

	}

	/**
	 * Move $_POST[release_name_id] into $_POST[tax_input] so WordPress core will save it.
	 */
	static function _wp_loaded() {
		do {
			if ( ! isset( $_POST[ 'release_name_id' ] ) ) {
				break;
			}
			if ( '-1' === $_POST[ 'release_name_id' ] ) {
				$_POST[ 'release_name_id' ] = 0;
			}
			$_POST[ 'tax_input' ][ self::TAXONOMY ][ 0 ] = "{$_POST[ 'release_name_id' ]}";
		} while ( false );
	}

	/**
	 * @param WP_Post $post
	 */
	static function _edit_form_after_title( $post ) {
		if ( $post->post_type === self::POST_TYPE ) {
			self::edit_form( $post );
		}
	}

	/**
	 * @param WP_Post $post
	 */
	static function edit_form( $post ) {

			echo<<<STYLE
<style type="text/css">
#edit-form-after-title {
	margin: 1em;
	font-size:1.5em;
}
#edit-form-after-title select {
	font-size:1em;
}
</style>
STYLE;
		echo '<div id="edit-form-after-title">';
		_e( 'Release Name', 'site-releases' );
		echo ': ';
		$terms = wp_get_object_terms( $post->ID, self::TAXONOMY );
		$term_id = isset( $terms[ 0 ]->term_id )
			? $terms[ 0 ]->term_id
			: null;

		wp_dropdown_categories( array(
			'empty_only'       => true,
			'hierarchical'     => true,
			'taxonomy'         => self::TAXONOMY,
			'show_option_none' => __( 'Select a Name', 'site-releases' ),
			'include_selected' => true,
			'selected'         => $term_id,
			'name'             => 'release_name_id',
			'orderby'          => 'name',

		));
		echo '</div>';
	}

	/**
	 * Register the Site Releases POST TYPE and Release Name TAXONOMY to WordPress.
	 */
	private static function _register_data() {
		$args = array(
			'label'               => __( 'Site Releases', 'site-releases' ),
			'public'              => true,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => false,
			'publicly_queryable'  => false,
			'menu_position'       => self::_BELOW_SETTINGS,
			'menu_icon'           => 'dashicons-images-alt',
			'capabilities'       => self::_capabilities_required(),
			'has_archive'         => true,
			'delete_with_user'    => false,
			'show_in_rest'        => true,
			'rest_base'           => 'site-releases',
			'show_in_quick_edit'  => false,
			'show_tagcloud'       => false,
			'show_admin_column'   => true,
			'taxonomies'          => [ Site_Releases::TAXONOMY ],
			'rewrite'             => array(
				'slug'       => 'site-releases',
				'with_front' => false,
			),
			'labels'              => array(
				'name'                  => _x( 'Site Releases', 'post type general name', 'site-releases' ),
				'singular_name'         => _x( 'Site Release', 'post type singular name', 'site-releases' ),
				'add_new'               => _x( 'Add New', 'release', 'site-releases' ),
				'add_new_item'          => __( 'Add New Site Release', 'site-releases' ),
				'edit_item'             => __( 'Edit Site Release', 'site-releases' ),
				'new_item'              => __( 'New Site Release', 'site-releases' ),
				'view_item'             => __( 'View Site Release', 'site-releases' ),
				'view_items'            => __( 'View Site Releases', 'site-releases' ),
				'search_items'          => __( 'Search Site Releases', 'site-releases' ),
				'not_found'             => __( 'No site releases found.', 'site-releases' ),
				'not_found_in_trash'    => __( 'No site releases found in Trash.', 'site-releases' ),
				'all_items'             => __( 'All Site Releases', 'site-releases' ),
				'archives'              => __( 'Site Release Archives', 'site-releases' ),
				'attributes'            => __( 'Site Release Attributes', 'site-releases' ),
				'filter_items_list'     => __( 'Filter site releases list', 'site-releases' ),
				'items_list_navigation' => __( 'Site Releases list navigation', 'site-releases' ),
				'items_list'            => __( 'Site Releases list', 'site-releases' ),
			),
		);
		$args = apply_filters( 'site_releases_post_type_args', $args, self::POST_TYPE );
		register_post_type( self::POST_TYPE, $args );

		$args = array(
			'label'              => __( 'Release Names', 'site-releases' ),
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'rewrite'            => false,
			'hierarchical'       => true,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'meta_box_cb'        => [ __CLASS__, '_manage_release_names' ],
			'rest_base'          => 'site-release-names',
			'capabilities'       => self::_capabilities_required(),
		);
		$args = apply_filters( 'site_releases_taxonomy_args', $args, self::TAXONOMY, self::POST_TYPE );
		register_taxonomy( self::TAXONOMY, self::POST_TYPE, $args );

	}

	/**
	 * Register the Site Releases POST TYPE and Release Name TAXONOMY to WordPress.
	 */
	private static function _capabilities_required() {
		return array(
			'edit_post'          => self::_CAPABILITIES_REQUIRED,
			'read_post'          => self::_CAPABILITIES_REQUIRED,
			'delete_post'        => self::_CAPABILITIES_REQUIRED,
			'edit_posts'         => self::_CAPABILITIES_REQUIRED,
			'edit_others_posts'  => self::_CAPABILITIES_REQUIRED,
			'publish_posts'      => self::_CAPABILITIES_REQUIRED,
			'read_private_posts' => self::_CAPABILITIES_REQUIRED,
			'create_posts'       => self::_CAPABILITIES_REQUIRED,
			'delete_posts'       => self::_CAPABILITIES_REQUIRED,
		);
	}


	static function _manage_release_names() {
		$url = admin_url( 'edit-tags.php?taxonomy=' . self::TAXONOMY . '&post_type=' . self::POST_TYPE );
		echo <<<STYLE
<style type="text/css">
#site-release-names-help {
	margin: 1em;
}
</style>
STYLE;
		echo '<div id="site-release-names-help">';
		$message = __( 'Click %shere%s to add Release Names.', 'site-releases' );
		echo '<p>' . sprintf( $message, '<a target="_blank" href="' .$url . '">', '</a>' ) . '</p>';
		echo '<p>' . __( 'You will need to reload this page after adding in order for them to appear in the dropdown to the left.' ) . '</p>';

		echo '</div>';
	}
}