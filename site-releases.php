<?php
/*
Plugin Name: Site Releases
Plugin URI: https://github.com/newclarity/site-releases
Description: Help agencies to track site releases
Version: 0.2.0
Author: The NewClarity Team
Author URI: http://newclarity.net
Text Domain: site-releases
*/

add_action( 'init', [ 'Site_Releases', 'on_load' ], 11 );

class Site_Releases {

	const POST_TYPE =  'sr_site_release';

	const _BELOW_SETTINGS = 80;

	const VERSION_STEP     = 0.1;
	const VERSION_DECIMALS = 1;

	const _CAPABILITIES_REQUIRED = 'manage_options';

	static function on_load() {

		//require __DIR__ . '/includes/class-empty-only-terms.php';

		if ( defined( 'WPSEO_FILE' ) ) {
			require __DIR__ . '/includes/class-disable-wpseo-for-site-releases.php';
		}

		self::_register_data();
		add_action( 'edit_form_after_title', [ __CLASS__, '_edit_form_after_title' ] );

		/**
		 * Add an activation hook (though we may not need it...)
		 */
		register_activation_hook( __FILE__, [ __CLASS__, 'activate' ] );

		/**
		 *
		 */
		add_filter( 'manage_sr_site_release_posts_columns', [ __CLASS__, '_manage_sr_site_release_posts_columns' ] );

		add_filter('manage_sr_site_release_posts_custom_column', [ __CLASS__, '_manage_sr_site_release_posts_custom_column' ], 10, 2 );

		add_action('admin_enqueue_scripts', [ __CLASS__, '_admin_enqueue_scripts' ]);
	}

	static function activate() {

	}

	static function _admin_enqueue_scripts( $hook ){
		$screen = get_current_screen();
		if( 'add' == $screen->action && self::POST_TYPE == $screen->post_type ){
			wp_enqueue_script( [ 'jquery', 'jquery-ui-spinner' ] );

			$wp_scripts = wp_scripts();
			wp_enqueue_style(
				'jquery-ui-theme-smoothness',
				sprintf(
					'//ajax.googleapis.com/ajax/libs/jqueryui/%s/themes/smoothness/jquery-ui.css', // working for https as well now
					$wp_scripts->registered['jquery-ui-core']->ver
				)
			);
		}

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
			'capabilities'        => self::_capabilities_required( 'post' ),
			'has_archive'         => true,
			'delete_with_user'    => false,
			'show_in_rest'        => true,
			'rest_base'           => 'site-releases',
			'show_in_quick_edit'  => false,
			'show_tagcloud'       => false,
			'show_admin_column'   => true,
			'taxonomies'          => [ ],
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

	}

	/**
	 * Register the Site Releases POST TYPE and Release Name TAXONOMY to WordPress.
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	private static function _capabilities_required( $type ) {
		$capabilities = array(
			"edit_{$type}"    => self::_CAPABILITIES_REQUIRED,
			"edit_{$type}s"   => self::_CAPABILITIES_REQUIRED,
			"delete_{$type}"  => self::_CAPABILITIES_REQUIRED,
			"delete_{$type}s" => self::_CAPABILITIES_REQUIRED,
		);
		switch ( $type ) {
			case 'term':
				$capabilities[ 'assign_term' ]  = self::_CAPABILITIES_REQUIRED;
				$capabilities[ 'assign_terms' ] = self::_CAPABILITIES_REQUIRED;
				$capabilities[ 'manage_terms' ] = self::_CAPABILITIES_REQUIRED;
				break;
			case 'post':
				$capabilities[ 'read_post' ]          = self::_CAPABILITIES_REQUIRED;
				$capabilities[ 'publish_posts' ]      = self::_CAPABILITIES_REQUIRED;
				$capabilities[ 'create_posts' ]       = self::_CAPABILITIES_REQUIRED;
				$capabilities[ 'edit_others_posts' ]  = self::_CAPABILITIES_REQUIRED;
				$capabilities[ 'read_private_posts' ] = self::_CAPABILITIES_REQUIRED;
				break;
		}
		return $capabilities;
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

		$decimals = self::VERSION_DECIMALS;

		echo <<<STYLE
<style type="text/css">
#titlediv{
	display: none;
}
#edit-form-after-title {
	margin: 1em 1em 0.25em 0.5em;
	font-size: 1.5em;
	line-height: 1.5;
}
#edit-form-after-title select {
	font-size:1em;
}
.release_number {
    display: inline-block;
    width: 2.5em;
    min-width: auto;

	font-weight: bold;
  
    background-color: transparent;
    border: none;

	quotes: "“" "”" "‘" "’";
    margin-left: 5px;
    
    outline: none;
    font-size: 18px;
}

.ui-widget .release_number {
	outline: none;
    font-size: 18px;    
    margin-top: 0;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;    
}

.release_number.previous {
	margin-left: .3em;
    min-width: 0;
    width: auto;	
}

#prev_rel_link,
.edit_release {
	text-decoration: none;
}

.release_section {
	margin-top: 1em;
}
.release_section .ui-widget-content{
	background: #fafafa;
}

.release_section .ui-spinner{
	margin-left: 5px;
}

.release_title {
	display: inline-block;
	margin-right: 10px;	
}
</style>
<script> 
jQuery(document).ready(function($){
  var ctrl = $( '.jq-spinner' );
  ctrl.spinner({ step: ctrl.data('step'), numberFormat: 'n', min: ctrl.data('min'), max: ctrl.data('max'),  stop: function (event, ui) {
    if ($(this).val().indexOf(".") < 0) {
        $(this).val($(this).val() + '.0');
    }
  }, create: function(event, ui){
	if ($(this).val().indexOf(".") < 0) {
        $(this).val($(this).val() + '.0');
    }      
  }});
});
</script>
STYLE;


		$is_new = ( 'auto-draft' == $post->post_status );

		$release = self::get_latest_release();

		echo '<div id="edit-form-after-title">';

		if ( !$is_new ) {

			echo sprintf( 'Release %s', number_format( $release['number'], 1 ));

			if( $release[ 'post_id' ] == $post->ID || isset( $release[ 'post_id' ] ) && $release[ 'post_id' ] == $post->ID ) {
				echo '<br><small>'.__( 'This release is currently the latest.', 'site-releases' ).'</small>';
			}

		} else {

			$current_number = number_format( $release['number'], 1 );
			_e( 'Previous release: ', 'site-releases' );
			echo "<span class='release_number previous'>{$current_number}</span>";
			echo '&nbsp;<a title="'.__('Edit previous release', 'site-releases').'" id="prev_rel_link" href="'.get_edit_post_link( $release['post_id'] ) .'"><span class="dashicons dashicons-external"></span></a>';

			$next_number = self::get_next_release_number( $release );
			$step = self::VERSION_STEP;
			$max  = ceil( $next_number + $step );
			$formatted = number_format( $next_number, 1 );

			echo '<div class="release_section">';
				echo '<div class="release_title">' . __( 'This release:', 'site-releases' ) . '</div>';
				echo "<input data-step='{$step}' data-min='{$formatted}' data-max='{$max}' class='release_number jq-spinner' value='{$formatted}' name='post_title'/>";
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * @param string $param
	 * @param mixed  $value
	 */
	static function get_site_release_by( $param, $value ){

		$post = null;

		do {

			switch ( $param ){
				case 'number':
					if ( ! is_numeric( $value ) ) {
						break;
					}

					$posts = get_page_by_title( round( $value, 1 ), OBJECT, self::POST_TYPE );
					break;
			}

			if ( ! empty( $posts ) ) {
				$post = reset( $posts );
			}

		} while ( false );

		return $post;
	}

	/**
	 * @return array|null
	 */
	static function get_latest_release(){

		$release  = null;

		$query = array(
			'post_type'      => self::POST_TYPE,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => 1,
			'post_status'    => 'publish'
		);
		$releases = get_posts( $query );

		do {

			if( empty( $releases )){
				break;
			}

			$post = reset( $releases );

			$release = [
				'post_id'     => $post->ID,
				'post'        => $post,
				'number'      => round( $post->post_title, 1 ),
				'description' => $post->post_content,
				'datetime'    => $post->post_date_gmt
			];

		} while( false );

		return $release;
	}

	/**
	 * Each site release should be given a 2-part dotted version number in the title field of this edit form using the pattern X.Y where:
	 *
	 * <ul>
	 * <li>Y (minor number) should increment by 1 for every release</li>
	 * <li>X (major number) should increment by 1 when Y equals 9 (in which case X should change and Y should revert to 0).</li>
	 * </ul>
	 *
	 * @param array $release contains details about the latest releases @see self::get_latest_release()
	 *
	 * @return float
	 */
	static function get_next_release_number( $release ){
		return apply_filters('sr_get_next_release_number', $release['number'] + self::VERSION_STEP, self::VERSION_DECIMALS, $release );
	}

	/**
	 * Change the columns displayed on admin list
	 *
	 * @param string[] $columns
	 * @return string[]
	 *
	 */
	static function _manage_sr_site_release_posts_columns( $columns ){
		$columns['changes'] = __('Changes', 'site-releases');
		return $columns;
	}

	/**
	 * Render a column with a  name of the release.
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	static function _manage_sr_site_release_posts_custom_column( $column, $post_id ) {

		do {

			if ( 'changes' !== $column ) {
				break;
			}

			echo apply_filters( 'the_excerpt', get_the_excerpt( $post_id ));

		} while ( false );

	}

}