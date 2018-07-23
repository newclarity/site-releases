<?php
/*
Plugin Name: Site Releases
Plugin URI: https://github.com/newclarity/site-releases
Description: Help agencies to track site releases
Version: 0.1.6-a
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

		require __DIR__ . '/includes/class-empty-only-terms.php';

		if ( defined( 'WPSEO_FILE' ) ) {
			require __DIR__ . '/includes/class-disable-wpseo-for-site-releases.php';
		}

		self::_register_data();
		add_action( 'edit_form_after_title', [ __CLASS__, '_edit_form_after_title' ] );

		/**
		 * Persist selected release name.
		 */
		add_action( 'wp_loaded', [ __CLASS__, '_wp_loaded' ] );

		/**
		 * Add an activation hook (though we may not need it...)
		 */
		register_activation_hook( __FILE__, [ __CLASS__, 'activate' ] );

		/**
		 *
		 */
		add_filter( 'manage_sr_site_release_posts_columns', [ __CLASS__, '_manage_sr_site_release_posts_columns' ] );

		add_filter('manage_sr_site_release_posts_custom_column', [ __CLASS__, '_manage_sr_site_release_posts_custom_column' ], 10, 2 );
	}

	static function activate() {

	}

	/**
	 * Move $_POST[release_name_id] into $_POST[tax_input] so WordPress core will save it.
	 */
	static function _wp_loaded() {
		do {
			if ( ! isset( $_POST[ 'post_type' ] ) || self::POST_TYPE !== $_POST[ 'post_type' ]) {
				break;
			}

			if ( $_POST[ 'release_name_id' ] ) {
				if ( '-1' === $_POST[ 'release_name_id' ] ) {
					$_POST[ 'release_name_id' ] = 0;
				}
				$_POST[ 'tax_input' ][ self::TAXONOMY ][ 0 ] = "{$_POST[ 'release_name_id' ]}";
			}

			if ( empty( $_POST[ 'release' ] ) ) {
				break;
			}

			$release_scope = $_POST[ 'release_scope' ];
			if( 'minor' === $release_scope || 'patch' === $release_scope){
				$release = $_POST['release'][ $release_scope ];
				if( !empty( $release['number'] ) && !empty( $release['name'])) {
					$_POST['post_title'] = $release['number'] . ' "'. $release['name'] . '"';
				}

				if( !empty( $release['term_id'])) {
					$_POST[ 'tax_input' ][ self::TAXONOMY ][ 0 ] = $release[ 'term_id' ];
				}

			} else {

				//@TODO maybe insert new term

			}

		} while ( false );
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
			'capabilities'       => self::_capabilities_required( 'term' ),
		);
		$args = apply_filters( 'site_releases_taxonomy_args', $args, self::TAXONOMY, self::POST_TYPE );
		register_taxonomy( self::TAXONOMY, self::POST_TYPE, $args );

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

		echo <<<STYLE
<style type="text/css">
#titlediv{
	display: none;
}
#edit-form-after-title {
	margin: 1em;
	font-size: 1.5em;
	line-height: 1.5;
}
#edit-form-after-title select {
	font-size:1em;
}
.release_number {
	min-width: 40px;
    display: inline-block;
    margin-left: 0.4em;
}
.release_number.previous {
	margin-left: .3em;
    min-width: 42px;	
}

.release_name {
	font-weight: bold;
    min-width: 9em;
    font-size: 1em;
    background-color: transparent;
    border: none;
   	margin-left: 0.25em;
}

.release_name {
    display: inline-block;	
	position: relative;
	quotes: "“" "”" "‘" "’";
}

.release_name::before {content:open-quote; left:0; top:0;}
.release_name::after {content:close-quote; right:0; bottom:0;}

.release_description {
    display: block;
    font-size: 0.7em;
    margin-left: 6.1em;
    line-height: 0.5em;
    margin-top: -8px;
}

.release_description::before {
    content: "\A";
    white-space: pre; 
}

#prev_rel_link,
.edit_release {
	text-decoration: none;
}

.release_scopes_section {
	margin-top: 1em;
}

.release_scopes_list {
	margin-top: 0.15em;
}

.release_scopes_list li {
	margin-bottom: 0.5em; 
}

</style>
STYLE;

		$is_new = ( 'auto-draft' == $post->post_status );

		$releases = self::get_latest_releases();
		$minor    = $releases[ 'minor' ];
		$patch    = $releases[ 'patch' ];

		$is_same_quarter = self::in_quarter( $minor[ 'datetime' ], 'current' );

		echo '<div id="edit-form-after-title">';
		if ( !$is_new ) {

			echo $patch
				? $patch['number'] ." &quot;" . $patch['name'] . "&quot;<br>"
				: $minor['number'] ." &quot;" . $minor['name'] . "&quot;<br>";

			if( $minor[ 'post_id' ] == $post->ID || isset( $patch[ 'post_id' ] ) && $patch[ 'post_id' ] == $post->ID ) {
				echo '<small>'.__( 'This release is currently the latest.', 'site-releases' ).'</small>';
			}

		} elseif ( $is_new && ! $is_same_quarter ) {
			//forcibly increment minor version, set patch to 0
			echo 'This release is in a new quarter. Minor release number will be incremented (i.e. y in x.y.z).';
		} elseif ( $is_new ) {

			$next_numbers = self::get_next_release_numbers( $releases );
			$next_terms   = self::get_next_release_terms( $releases );

			//echo '<pre> Next numbers: ' . print_r( $next_numbers, true ) . '</pre>';
			//echo '<pre> Next terms: ' . print_r( $next_terms, true ) . '</pre>';

			_e( 'Previous release: ', 'site-releases' );
			echo '<br>';
			if ( $patch ) {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;<span class='release_number previous'>{$patch['number']}</span><span class='release_name previous'>{$patch['name']}</span>";
			} else {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;<span class='release_number previous'>{$minor['number']}</span><span class='release_name previous'>{$minor['name']}</span>";
			}
			echo '&nbsp;<a title="'.__('Edit previous release', 'site-releases').'" id="prev_rel_link" href="'.get_edit_post_link( $patch ? $patch['post_id']: $minor['post_id'] ) .'"><span class="dashicons dashicons-external"></span></a>';

			$patch_name = esc_html( $next_terms['patch']->name );
			$minor_name = esc_html( $next_terms['minor']->name );

			echo '<div class="release_scopes_section">';
			echo '<div class="release_scopes_title">' . __( 'Suggested name:', 'site-releases' ) . '</div>';

			echo '<input type="hidden" name="release[patch][number]" value="'.$next_numbers['patch'].'">';
			echo '<input type="hidden" name="release[patch][term_id]" value="'.$next_terms['patch']->term_id.'">';
			//echo '<input type="hidden" name="release[patch][name]" value="'.$next_terms['patch']->name.'">';
			echo '<input type="hidden" name="release[minor][number]" value="'.$next_numbers['minor'].'">';
			echo '<input type="hidden" name="release[minor][term_id]" value="'.$next_terms['minor']->term_id.'">';
			//echo '<input type="hidden" name="release[minor][name]" value="'.$next_terms['minor']->name.'">';

			$patch_description = __('if this release includes <em>only minor improvements</em>.', 'site-releases');
			$minor_description = __('if this release includes <em>important new features</em>.', 'site-releases');

			echo "
<ul class='release_scopes_list'><li>
<label><input readonly='readonly' type='radio' name='release_scope' value='patch'/><span class='release_number patch'>{$next_numbers['patch']}</span> <input class='release_name patch' value='{$patch_name}' name='release[patch][name]'/><span class='release_description'>{$patch_description}</span></label>
</li><li>
<label><input readonly='readonly' type='radio' name='release_scope' value='minor' checked='checked' /><span class='release_number minor'>{$next_numbers['minor']}</span> <input class='release_name minor' value='{$minor_name}' name='release[minor][name]'/><span class='release_description'>{$minor_description}</span></label>
</li></ul>
";

//			echo '<div class="js-customize-release" style="display:none">';
//			wp_dropdown_categories( array(
//				'empty_only'       => true,
//				'hierarchical'     => true,
//				'taxonomy'         => self::TAXONOMY,
//				'show_option_none' => __( 'Select a Name', 'site-releases' ),
//				'include_selected' => true,
//				'selected'         => isset( $next_terms[ 'patch' ] ) ? $next_terms[ 'patch' ]->term_id : $next_terms[ 'minor' ]->term_id,
//				'name'             => 'release_name_id',
//				'orderby'          => 'name',
//
//			) );

			echo '</div>';
			echo '</div>';
			echo '</div>';
		}
	}

	/**
	 * @param int $post_id ID of a Site Release post.
	 *
	 * @return WP_Term|null
	 */
	static function get_release_term( $post_id ){
		$terms = wp_get_object_terms( $post_id, self::TAXONOMY );
		return count( $terms ) ? $terms[ 0 ] : null;
	}

	/**
	 * @param int $term_id
	 */
	static function get_release_post( $term_id ){

		$post  = null;

		$query = array(
			'post_type' => self::POST_TYPE,
			'tax_query' => array(
				array(
					'taxonomy' => self::TAXONOMY,
					'field' => 'term_id',
					'terms' => $term_id
				)
			),
			'posts_per_page' => 1
		);
		$posts = get_posts( $query );

		if( !empty( $posts )){
			$post  = reset( $posts );
		}

		return $post;
	}

	/**
	 *
	 */
	static function get_latest_releases(){

		$minor_release  = null;
		$patch_release  = null;

		$query = array(
			'post_type'      => self::POST_TYPE,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => 10,
			'post_status'    => 'publish'
		);
		$releases = get_posts( $query );

		do {

			if( empty( $releases )){
				break;
			}

			foreach ( $releases as $post ) {

				$parts = explode(' ', $post->post_title );
				$number = $parts[0];

				if( substr_count( $parts[0], '.' ) > 1){
					/**
					 * skip patch release
					 */
					continue;
				}

				$term = self::get_release_term( $post->ID );

				$minor_release = [
					'post_id'     => $post->ID,
					'term_id'     => $term ? $term->term_id : 0,
					'name'        => $term ? $term->name: '',
					'number'      => $number,
					'description' => $post->post_content,
					'datetime'    => $post->post_date_gmt
				];

				break;
			}

			if( !$minor_release ) {
				break;
			}

			foreach ( $releases as $post ) {

				$parts = explode(' ', $post->post_title );
				$number = $parts[0];
				if( substr_count( $parts[0], '.' ) < 2){
					/**
					 * skip minor release
					 */
					continue;
				}

				if( 0 !== strpos( $number, $minor_release['number'] )){
					//skip patch that does not belong to the currently selected minor release
					continue;
				}

				$term = self::get_release_term( $post->ID );

				$patch_release = [
					'post_id'     => $post->ID,
					'term_id'     => $term ? $term->term_id : 0,
					'name'        => $term ? $term->name : '',
					'number'      => $number,
					'description' => $post->post_content,
					'datetime'    => $post->post_date_gmt
				];

				break;
			}

		} while( false );

		$minor_release = $minor_release
			? $minor_release
			: [
				'post_id'     => 0,
				'term_id'     => 0,
				'name'        => __('Initial', 'site-releases'),
				'number'      => '1.0',
				'description' => __('Initial release', 'site-releases'),
				'datetime'    => gmdate('Y-m-d H:i:s')
			];

		return [
			'minor' => $minor_release,
			'patch' => $patch_release
		];
	}

	/**
	 * Each site release should be given a 2 or 3 part dotted version number in the title field of this edit form using the pattern X.Y.Z where:
	 *
	 * <ul>
	 * <li>Z (patch number) should increment by 1 for every release, unless the previous release occurred in the prior calendar quarter and then Z should be made empty (which means the same as .0.)</li>
	 * <li>Y (minor number) should increment by 1 when the previous release occurred in the prior for calendar quarter, unless Y equals 9 in which case X should change and Y should revert to 0 .</li>
	 * <li>And, finally, X (major number) should increment by 1 when Y of the previous release equals 9.</li>
	 * </ul>
	 *
	 * @param array $releases contains details about the latest releases @see self::get_latest_releases()
	 *
	 * @return array
	 */
	static function get_next_release_numbers( $releases ){

		$minor = $releases['minor'];
		$patch = $releases['patch'];

		$is_same_quarter = self::in_quarter( $minor['datetime'], 'current' );

		$next_minor_parts = explode( '.', $minor['number'] );

		if( $patch ) {
			$next_patch_parts = explode( '.', $patch[ 'number' ] );
		} else {
			/**
			 * define initial PATCH number
			 */
			$next_patch_parts = $next_minor_parts;
			$next_patch_parts[2] = 0;
		}

		/**
		 * resolve next MINOR number
		 */
		if( $next_minor_parts[1] < 9 && $is_same_quarter ) {
			$next_minor_parts[ 1 ]++;
		} else {
			/**
			 * increment MAJOR number
			 */
			$next_minor_parts[ 0 ] ++;
			/**
			 * set MINOR number to 0
			 */
			$next_minor_parts[ 1 ] = 0;
		}

		/**
		 * resolve next PATCH number
		 */
		if( $next_patch_parts[2] < 9 && $is_same_quarter ){
			/**
			 * no need to increment MINOR number
			 * increment PATCH number
			 */
			$next_patch_parts[2]++;
		} else {
			/**
			 * "forcibly" increment MINOR number
			 */
			$next_patch_parts[1]++;
			/**
			 * make PATCH number undefined
			 */
			$next_patch_parts = null;
		}

		return apply_filters('sr_get_next_release_numbers', array(
			'minor' => implode( '.', $next_minor_parts ),
			'patch' => implode( '.', $next_patch_parts ),
		), $releases );
	}

	/**
	 * Each site release should be assigned a name; a metro Atlanta county name when X is incremented, or a metro Atlanta city name when Y is incremented. For example 2.2 might be Decatur and 2.3 might be Doraville and 2.4 might be Duluth whereas 3.0 might be Henry or Clayton.
	 * Note, release name for PATCH releases remain the same, i.e. if 2.3 is named "Doraville", 2.3.1 will still be named "Doraville".
	 *
	 * @param array $releases
	 *
	 * @return array
	 */
	static function get_next_release_terms( $releases ){

		$minor = $releases['minor'];

		$in_same_quarter = self::in_quarter( $minor['datetime'], 'current' );

		$prev_minor_parts = array_map( 'absint', explode( '.', $minor[ 'number' ] ) );
		$next_minor_parts = $prev_minor_parts;

		/**
		 * resolve next MINOR number
		 */
		if( $next_minor_parts[1] < 9 && $in_same_quarter ) {
			$next_minor_parts[ 1 ]++;
		} else {
			/**
			 * increment MAJOR number
			 */
			$next_minor_parts[ 0 ] ++;
			/**
			 * set MINOR number to 0
			 */
			$next_minor_parts[ 1 ] = 0;
		}

		$term = get_term( $minor['term_id'], self::TAXONOMY );

		if( 0 === $next_minor_parts[ 1 ] && 0 === $prev_minor_parts[ 1 ]) {
			$terms = self::get_empty_sibling_terms( $term );
		} elseif( 0 == $next_minor_parts[ 1 ] && 0 !== $prev_minor_parts[ 1 ] ) {
			$terms = self::get_empty_sibling_terms( $term->parent );
		} elseif( $next_minor_parts[ 1 ] !== $prev_minor_parts[ 1 ] && 0 !== $prev_minor_parts[ 1 ]) {
			$terms = self::get_empty_sibling_terms( $term );
		} else {
			$terms = self::get_empty_children_terms( $term );
		}

		$next_term = empty( $terms ) ? $term : reset( $terms );

		/**
		 *
		 */
		return apply_filters('sr_get_next_release_terms', array( 'minor' => $next_term, 'patch' => $term ), $releases );
	}

	/**
	 * Get empty sibling terms
	 *
	 * @param WP_Term|int $term
	 * @param array $query
	 *
	 * @return array|null|int|WP_Error
	 */
	static function get_empty_sibling_terms( $term, $query = array() ){

		if( is_numeric( $term ) ){
			$term = get_term( $term, self::TAXONOMY );
		}

		return $term->parent
			? self::get_empty_children_terms( get_term( $term->parent, $term->taxonomy ), $query )
			: null;

	}

	/**
	 * Get empty sibling terms
	 *
	 * @param int|WP_Term $term
	 * @param array $query
	 *
	 * @return array|int|WP_Error
	 */
	static function get_empty_children_terms( $term, $query = array() ){

		if( is_numeric( $term ) ){
			$term = get_term( $term, self::TAXONOMY );
		}

		$query = wp_parse_args( $query, array(
			'parent'     => $term->term_id,
			'taxonomy'   => $term->taxonomy,
			'empty_only' => true
		));

		return get_terms( $query );

	}

	/**
	 * Render release names checkbox.
	 */
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
		echo '<p>' . __( 'You will need to reload this page after adding in order for them to appear in the dropdown to the left.', 'site-releases' ) . '</p>';

		echo '</div>';
	}

	/**
	 * Change the columns displayed on admin list
	 *
	 * @param string[] $columns
	 * @return string[]
	 *
	 */
	static function _manage_sr_site_release_posts_columns( $columns ){

//		unset( $columns['comments'] );
//		unset( $columns['tags'] );
//		/*
//		 * This column is added by NPR plugin. We will add this link to row actions instead.
//		 */
//		unset( $columns['update_story'] );
//
//		$cb = $columns['cb'];
//		unset( $columns['cb'] );
//
//		$columns = array(
//			           'cb'           => $cb,
//			           'story_source' => _x( '', 'story_source', 'wabe' ),
//		           ) + $columns;

		$columns['version'] = __('Release No.', 'site-releases');
		return $columns;
	}

	/**
	 * Render a column with the icons depicting the source (NPR vs WABE) and a warning icon when the target page does not exist.
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	static function _manage_sr_site_release_posts_custom_column( $column, $post_id ) {

		do {

			if ( 'version' !== $column ) {
				break;
			}

			if( $term = self::get_release_term( $post_id )){
				esc_html_e( $term->name );
			}

//			$story = new WABE_Story( $post_id );
//
//			$story->the_target_story_status_html();

		} while ( false );

	}

	/**
	 * Compute the start and end date of some fixed o relative quarter in a specific year.
	 *
	 * @credit: https://stackoverflow.com/questions/21185924/get-startdate-and-enddate-for-current-quarter-php
	 *
	 * @param mixed $quarter  Integer from 1 to 4 or relative string value:
	 *                        'this', 'current', 'previous', 'first' or 'last'.
	 *                        'this' is equivalent to 'current'. Any other value
	 *                        will be ignored and instead current quarter will be used.
	 *                        Default value 'current'. Particularly, 'previous' value
	 *                        only make sense with current year so if you use it with
	 *                        other year like: get_dates_of_quarter('previous', 1990)
	 *                        the year will be ignored and instead the current year
	 *                        will be used.
	 * @param int $year       Year of the quarter. Any wrong value will be ignored and
	 *                        instead the current year will be used.
	 *                        Default value null (current year).
	 * @param string $format  String to format returned dates
	 * @return array          Array with two elements (keys): start and end date.
	 */
	public static function get_dates_of_quarter( $quarter = 'current', $year = null, $format = null )
	{
		if ( !is_int($year) ) {
			$year = (new DateTime)->format('Y');
		}
		$current_quarter = ceil((new DateTime)->format('n') / 3);
		switch (  strtolower($quarter) ) {
			case 'this':
			case 'current':
				$quarter = ceil((new DateTime)->format('n') / 3);
				break;

			case 'previous':
				$year = (new DateTime)->format('Y');
				if ($current_quarter == 1) {
					$quarter = 4;
					$year--;
				} else {
					$quarter =  $current_quarter - 1;
				}
				break;

			case 'first':
				$quarter = 1;
				break;

			case 'last':
				$quarter = 4;
				break;

			default:
				$quarter = (!is_int($quarter) || $quarter < 1 || $quarter > 4) ? $current_quarter : $quarter;
				break;
		}
		if ( $quarter === 'this' ) {
			$quarter = ceil((new DateTime)->format('n') / 3);
		}
		$start = new DateTime($year.'-'.(3*$quarter-2).'-1 00:00:00');
		$end = new DateTime($year.'-'.(3*$quarter).'-'.($quarter == 1 || $quarter == 4 ? 31 : 30) .' 23:59:59');

		return array(
			'start' => $format ? $start->format($format) : $start,
			'end' => $format ? $end->format($format) : $end,
		);
	}

	/**
	 * @param string $date
	 * @param string $quarter
	 *
	 * @return bool
	 */
	static function in_quarter( $date, $quarter = 'current' ){

		$q = self::get_dates_of_quarter( $quarter );

		return $date >= $q['start']->format('Y-m-d H:i:s') && $date <= $q['end']->format('Y-m-d H:i:s');

	}
}