<?php
/**
 * Custom post type registry.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores custom post type definitions and registers them with WordPress.
 *
 * Definitions live in a single option so they can be read, cached and filtered
 * without extra database tables. Deleting a definition never deletes posts.
 */
class ACM_Post_Type_Registry {

	/**
	 * Option name.
	 */
	const OPTION = ACM_OPTION_POST_TYPES;

	/**
	 * Maximum length of a post type key.
	 */
	const MAX_KEY_LENGTH = 20;

	/**
	 * Cached definitions.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default definition.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'slug'                => '',
			'plural'              => '',
			'singular'            => '',
			'description'         => '',
			'menu_icon'           => 'dashicons-admin-post',
			'menu_position'       => '',
			'public'              => 1,
			'publicly_queryable'  => 1,
			'show_in_rest'        => 1,
			'show_in_admin_menu'  => 1,
			'has_archive'         => 1,
			'rewrite_slug'        => '',
			'hierarchical'        => 0,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'fields'              => array(),
			'field_display'       => 'shortcode',
			'field_show_labels'   => 1,
			'field_image_size'    => '',
			'enable_categories'   => 0,
			'enable_tags'         => 0,
			'shortcode_layout'    => 'grid',
			'shortcode_columns'   => 3,
			'shortcode_per_page'  => 6,
			'shortcode_image_size' => '',
			'shortcode_show_image' => 1,
			'shortcode_show_excerpt' => 1,
			'shortcode_show_date' => 1,
			'shortcode_show_terms' => 1,
			'shortcode_show_read_more' => 1,
			'shortcode_excerpt_length' => 20,
			'shortcode_orderby'   => 'date',
			'shortcode_order'     => 'DESC',
			'shortcode_category_taxonomy' => '',
			'active'              => 1,
			'created'             => 0,
		);
	}

	/**
	 * Every configured post type.
	 *
	 * @return array
	 */
	public static function get_all() {
		if ( null === self::$cache ) {
			$stored = get_option( self::OPTION, array() );
			self::$cache = is_array( $stored ) ? $stored : array();
		}

		return self::$cache;
	}

	/**
	 * Retrieve one definition.
	 *
	 * @param string $slug Post type key.
	 * @return array|null
	 */
	public static function get( $slug ) {
		$all = self::get_all();

		return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
	}

	/**
	 * Create or update a definition.
	 *
	 * @param array  $data          Raw definition.
	 * @param string $original_slug Key being edited, empty when creating.
	 * @return string|WP_Error Sanitized post type key.
	 */
	public static function save( $data, $original_slug = '' ) {
		$data          = is_array( $data ) ? $data : array();
		$original_slug = sanitize_key( $original_slug );
		$clean         = self::sanitize( $data, $original_slug );

		if ( is_wp_error( $clean ) ) {
			return $clean;
		}

		$all  = self::get_all();
		$slug = $clean['slug'];

		if ( '' !== $original_slug && $original_slug !== $slug ) {
			if ( isset( $all[ $original_slug ] ) ) {
				unset( $all[ $original_slug ] );
			}

			ACM_Taxonomy_Registry::detach_post_type( $original_slug );
		}

		if ( isset( $all[ $slug ]['created'] ) ) {
			$clean['created'] = (int) $all[ $slug ]['created'];
		} elseif ( empty( $clean['created'] ) ) {
			$clean['created'] = time();
		}

		$all[ $slug ] = $clean;

		self::persist( $all );

		return $slug;
	}

	/**
	 * Delete a definition while keeping every post that belongs to it.
	 *
	 * @param string $slug Post type key.
	 * @return bool
	 */
	public static function delete( $slug ) {
		$slug = sanitize_key( $slug );
		$all  = self::get_all();

		if ( ! isset( $all[ $slug ] ) ) {
			return false;
		}

		unset( $all[ $slug ] );

		ACM_Taxonomy_Registry::detach_post_type( $slug );
		self::persist( $all );

		return true;
	}

	/**
	 * Activate or deactivate a definition.
	 *
	 * @param string $slug   Post type key.
	 * @param bool   $active New state.
	 * @return bool
	 */
	public static function set_active( $slug, $active ) {
		$slug = sanitize_key( $slug );
		$all  = self::get_all();

		if ( ! isset( $all[ $slug ] ) ) {
			return false;
		}

		$all[ $slug ]['active'] = $active ? 1 : 0;

		self::persist( $all );

		return true;
	}

	/**
	 * Persist the definitions and flag rewrite rules for regeneration.
	 *
	 * @param array $all Definitions.
	 * @return void
	 */
	private static function persist( $all ) {
		self::$cache = $all;

		update_option( self::OPTION, $all );
		ACM_Plugin::mark_rewrite_dirty();
	}

	/**
	 * Validate and sanitize a definition.
	 *
	 * @param array  $data          Raw definition.
	 * @param string $original_slug Key being edited.
	 * @return array|WP_Error
	 */
	public static function sanitize( $data, $original_slug = '' ) {
		$defaults = self::defaults();
		$clean    = $defaults;

		$plural   = isset( $data['plural'] ) ? sanitize_text_field( $data['plural'] ) : '';
		$singular = isset( $data['singular'] ) ? sanitize_text_field( $data['singular'] ) : '';
		$slug     = isset( $data['slug'] ) ? $data['slug'] : '';

		if ( '' === $plural ) {
			return new WP_Error( 'acm_missing_plural', __( 'Please enter a plural post type name.', 'author-content-manager' ) );
		}

		$plural = self::limit_text( $plural, 100 );

		if ( '' === $singular ) {
			$singular = $plural;
		}

		$singular = self::limit_text( $singular, 100 );

		$slug = self::sanitize_key( $slug );

		if ( '' === $slug ) {
			$slug = self::sanitize_key( str_replace( '-', '_', sanitize_title( $plural ) ) );
		}

		if ( '' === $slug ) {
			return new WP_Error( 'acm_missing_slug', __( 'Please enter a valid post type slug using letters, numbers, dashes or underscores.', 'author-content-manager' ) );
		}

		$reserved = self::reserved_slugs();

		if ( in_array( $slug, $reserved, true ) ) {
			return new WP_Error(
				'acm_reserved_slug',
				sprintf(
					/* translators: %s: reserved post type slug. */
					__( '"%s" is reserved by WordPress or by this plugin. Please choose another slug.', 'author-content-manager' ),
					$slug
				)
			);
		}

		$all = self::get_all();

		if ( $slug !== $original_slug && isset( $all[ $slug ] ) ) {
			return new WP_Error( 'acm_duplicate_slug', __( 'A custom post type with this slug already exists. Please choose another slug.', 'author-content-manager' ) );
		}

		if ( taxonomy_exists( $slug ) ) {
			return new WP_Error( 'acm_taxonomy_conflict', __( 'This slug is already used by a taxonomy. Please choose another slug.', 'author-content-manager' ) );
		}

		if ( post_type_exists( $slug ) && ( '' === $original_slug || $slug !== $original_slug ) ) {
			return new WP_Error( 'acm_post_type_conflict', __( 'This slug is already used by another post type. Please choose another slug.', 'author-content-manager' ) );
		}

		$clean['slug']        = $slug;
		$clean['plural']      = $plural;
		$clean['singular']    = $singular;
		$clean['description'] = isset( $data['description'] ) ? self::limit_text( sanitize_textarea_field( $data['description'] ), 500 ) : '';

		$icon = isset( $data['menu_icon'] ) ? sanitize_html_class( $data['menu_icon'] ) : '';

		if ( '' === $icon || 0 !== strpos( $icon, 'dashicons-' ) ) {
			$icon = $defaults['menu_icon'];
		}

		$clean['menu_icon'] = $icon;

		$position = '';
		if ( isset( $data['menu_position'] ) && '' !== $data['menu_position'] ) {
			$position = (int) $data['menu_position'];
			$position = max( -100, min( 100, $position ) );
		}
		$clean['menu_position'] = '' === $position ? '' : $position;

		$clean['public']             = empty( $data['public'] ) ? 0 : 1;
		$clean['publicly_queryable'] = empty( $data['publicly_queryable'] ) ? 0 : 1;
		$clean['show_in_rest']       = empty( $data['show_in_rest'] ) ? 0 : 1;
		$clean['show_in_admin_menu'] = empty( $data['show_in_admin_menu'] ) ? 0 : 1;
		$clean['has_archive']        = empty( $data['has_archive'] ) ? 0 : 1;
		$clean['hierarchical']       = empty( $data['hierarchical'] ) ? 0 : 1;
		$clean['active']             = empty( $data['active'] ) ? 0 : 1;

		$rewrite              = isset( $data['rewrite_slug'] ) ? sanitize_title( $data['rewrite_slug'] ) : '';
		$clean['rewrite_slug'] = self::limit_text( $rewrite, 200 );

		$allowed          = array_keys( self::supported_features() );
		$supports         = isset( $data['supports'] ) && is_array( $data['supports'] ) ? array_map( 'sanitize_key', $data['supports'] ) : array();
		$supports         = array_values( array_intersect( $supports, $allowed ) );
		$clean['supports'] = $supports;

		$existing = ( '' !== $original_slug && isset( $all[ $original_slug ] ) ) ? $all[ $original_slug ] : null;

		if ( ! empty( $data['fields_present'] ) || array_key_exists( 'fields', $data ) ) {
			$raw_fields = isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array();
		} elseif ( is_array( $existing ) && isset( $existing['fields'] ) ) {
			$raw_fields = (array) $existing['fields'];
		} else {
			$raw_fields = array();
		}

		$fields = ACM_Custom_Fields::sanitize_definitions( $raw_fields, 'post' );

		if ( is_wp_error( $fields ) ) {
			return $fields;
		}

		$clean['fields'] = $fields;

		if ( array_key_exists( 'field_display', $data ) ) {
			$display = sanitize_key( $data['field_display'] );
		} elseif ( is_array( $existing ) && isset( $existing['field_display'] ) ) {
			$display = sanitize_key( $existing['field_display'] );
		} else {
			$display = 'shortcode';
		}

		$clean['field_display'] = 'auto' === $display ? 'auto' : 'shortcode';

		if ( array_key_exists( 'field_show_labels', $data ) ) {
			$clean['field_show_labels'] = empty( $data['field_show_labels'] ) ? 0 : 1;
		} elseif ( is_array( $existing ) && isset( $existing['field_show_labels'] ) ) {
			$clean['field_show_labels'] = empty( $existing['field_show_labels'] ) ? 0 : 1;
		} else {
			$clean['field_show_labels'] = 1;
		}

		$image_size = '';

		if ( array_key_exists( 'field_image_size', $data ) ) {
			$image_size = sanitize_key( $data['field_image_size'] );
		} elseif ( is_array( $existing ) && isset( $existing['field_image_size'] ) ) {
			$image_size = sanitize_key( $existing['field_image_size'] );
		}

		$clean['field_image_size'] = in_array( $image_size, ACM_Settings::available_image_sizes(), true ) ? $image_size : '';

		foreach ( array( 'enable_categories', 'enable_tags', 'shortcode_show_image', 'shortcode_show_excerpt', 'shortcode_show_date', 'shortcode_show_terms', 'shortcode_show_read_more' ) as $boolean_key ) {
			if ( array_key_exists( $boolean_key, $data ) ) {
				$clean[ $boolean_key ] = empty( $data[ $boolean_key ] ) ? 0 : 1;
			} elseif ( is_array( $existing ) && isset( $existing[ $boolean_key ] ) ) {
				$clean[ $boolean_key ] = empty( $existing[ $boolean_key ] ) ? 0 : 1;
			}
		}

		if ( array_key_exists( 'shortcode_layout', $data ) ) {
			$layout = sanitize_key( $data['shortcode_layout'] );
		} elseif ( is_array( $existing ) && isset( $existing['shortcode_layout'] ) ) {
			$layout = sanitize_key( $existing['shortcode_layout'] );
		} else {
			$layout = 'grid';
		}

		$clean['shortcode_layout'] = 'list' === $layout ? 'list' : 'grid';

		if ( array_key_exists( 'shortcode_columns', $data ) ) {
			$clean['shortcode_columns'] = min( 6, max( 1, absint( $data['shortcode_columns'] ) ) );
		} elseif ( is_array( $existing ) && isset( $existing['shortcode_columns'] ) ) {
			$clean['shortcode_columns'] = min( 6, max( 1, absint( $existing['shortcode_columns'] ) ) );
		}

		if ( array_key_exists( 'shortcode_per_page', $data ) ) {
			$per_page = absint( $data['shortcode_per_page'] );
		} elseif ( is_array( $existing ) && isset( $existing['shortcode_per_page'] ) ) {
			$per_page = absint( $existing['shortcode_per_page'] );
		} else {
			$per_page = 6;
		}

		$clean['shortcode_per_page'] = min( 50, max( 1, $per_page ) );

		if ( array_key_exists( 'shortcode_image_size', $data ) ) {
			$shortcode_image_size = sanitize_key( $data['shortcode_image_size'] );
		} elseif ( is_array( $existing ) && isset( $existing['shortcode_image_size'] ) ) {
			$shortcode_image_size = sanitize_key( $existing['shortcode_image_size'] );
		} else {
			$shortcode_image_size = '';
		}

		$clean['shortcode_image_size'] = in_array( $shortcode_image_size, ACM_Settings::available_image_sizes(), true ) ? $shortcode_image_size : '';

		if ( array_key_exists( 'shortcode_excerpt_length', $data ) ) {
			$excerpt_length = absint( $data['shortcode_excerpt_length'] );
		} elseif ( is_array( $existing ) && isset( $existing['shortcode_excerpt_length'] ) ) {
			$excerpt_length = absint( $existing['shortcode_excerpt_length'] );
		} else {
			$excerpt_length = 20;
		}

		$clean['shortcode_excerpt_length'] = min( 100, $excerpt_length );

		$orderby_map = array(
			'date'       => 'date',
			'title'      => 'title',
			'menu_order' => 'menu_order',
			'modified'   => 'modified',
			'rand'       => 'rand',
			'id'         => 'ID',
		);

		if ( array_key_exists( 'shortcode_orderby', $data ) ) {
			$orderby = strtolower( sanitize_key( $data['shortcode_orderby'] ) );
		} elseif ( is_array( $existing ) && isset( $existing['shortcode_orderby'] ) ) {
			$orderby = strtolower( sanitize_key( $existing['shortcode_orderby'] ) );
		} else {
			$orderby = 'date';
		}

		$clean['shortcode_orderby'] = isset( $orderby_map[ $orderby ] ) ? $orderby_map[ $orderby ] : 'date';

		if ( array_key_exists( 'shortcode_order', $data ) ) {
			$order = sanitize_key( $data['shortcode_order'] );
		} elseif ( is_array( $existing ) && isset( $existing['shortcode_order'] ) ) {
			$order = sanitize_key( $existing['shortcode_order'] );
		} else {
			$order = 'DESC';
		}

		$clean['shortcode_order'] = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';

		if ( array_key_exists( 'shortcode_category_taxonomy', $data ) ) {
			$category_taxonomy = sanitize_key( $data['shortcode_category_taxonomy'] );
		} elseif ( is_array( $existing ) && isset( $existing['shortcode_category_taxonomy'] ) ) {
			$category_taxonomy = sanitize_key( $existing['shortcode_category_taxonomy'] );
		} else {
			$category_taxonomy = '';
		}

		if ( '' !== $category_taxonomy && ! taxonomy_exists( $category_taxonomy ) && ! ACM_Taxonomy_Registry::get( $category_taxonomy ) ) {
			$category_taxonomy = '';
		}

		if ( 'category' === $category_taxonomy ) {
			$clean['enable_categories'] = 1;
		}

		if ( 'post_tag' === $category_taxonomy ) {
			$clean['enable_tags'] = 1;
		}

		if ( '' === $category_taxonomy && ! empty( $clean['enable_categories'] ) ) {
			$category_taxonomy = 'category';
		} elseif ( '' === $category_taxonomy && ! empty( $clean['enable_tags'] ) ) {
			$category_taxonomy = 'post_tag';
		}

		$clean['shortcode_category_taxonomy'] = $category_taxonomy;

		/**
		 * Filter a sanitized custom post type definition before it is stored.
		 *
		 * @param array  $clean         Sanitized definition.
		 * @param array  $data          Raw definition.
		 * @param string $original_slug Key being edited.
		 */
		return apply_filters( 'acm_sanitize_post_type', $clean, $data, $original_slug );
	}

	/**
	 * Register every active definition with WordPress.
	 *
	 * @return void
	 */
	public static function register() {
		$all = self::get_all();

		if ( empty( $all ) ) {
			return;
		}

		foreach ( $all as $slug => $config ) {
			$slug = self::sanitize_key( $slug );

			if ( '' === $slug || empty( $config['active'] ) || post_type_exists( $slug ) ) {
				continue;
			}

			$config = wp_parse_args( $config, self::defaults() );

			register_post_type( $slug, self::build_args( $slug, $config ) );

			if ( ! empty( $config['enable_categories'] ) && taxonomy_exists( 'category' ) ) {
				register_taxonomy_for_object_type( 'category', $slug );
			}

			if ( ! empty( $config['enable_tags'] ) && taxonomy_exists( 'post_tag' ) ) {
				register_taxonomy_for_object_type( 'post_tag', $slug );
			}
		}
	}

	/**
	 * Taxonomies that can be used for the shortcode category filter.
	 *
	 * @return array Taxonomy key => label.
	 */
	public static function taxonomy_options() {
		$excluded = array( 'post_format', 'nav_menu', 'link_category', 'wp_theme', 'wp_template_part_area', 'wp_pattern_category' );
		$options  = array();

		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
			if ( in_array( $taxonomy->name, $excluded, true ) ) {
				continue;
			}

			$options[ $taxonomy->name ] = isset( $taxonomy->labels->singular_name ) && '' !== $taxonomy->labels->singular_name ? $taxonomy->labels->singular_name : $taxonomy->name;
		}

		foreach ( ACM_Taxonomy_Registry::get_all() as $taxonomy_slug => $config ) {
			if ( isset( $options[ $taxonomy_slug ] ) ) {
				continue;
			}

			$options[ $taxonomy_slug ] = isset( $config['singular'] ) ? $config['singular'] : $taxonomy_slug;
		}

		asort( $options );

		/**
		 * Filter the taxonomies offered for the shortcode category setting.
		 *
		 * @param array $options Taxonomy key => label.
		 */
		return apply_filters( 'acm_post_type_taxonomy_options', $options );
	}

	/**
	 * Build the arguments passed to register_post_type().
	 *
	 * @param string $slug   Post type key.
	 * @param array  $config Definition.
	 * @return array
	 */
	public static function build_args( $slug, $config ) {
		$config   = wp_parse_args( $config, self::defaults() );
		$public   = ! empty( $config['public'] );
		$show_ui  = ! empty( $config['show_in_admin_menu'] );
		$plural   = $config['plural'];
		$singular = $config['singular'];
		$supports = array_values( array_intersect( (array) $config['supports'], array_keys( self::supported_features() ) ) );

		if ( ! empty( $config['hierarchical'] ) && ! in_array( 'page-attributes', $supports, true ) ) {
			$supports[] = 'page-attributes';
		}

		$rewrite_slug = '' !== $config['rewrite_slug'] ? $config['rewrite_slug'] : $slug;
		$menu_icon    = '' !== $config['menu_icon'] ? $config['menu_icon'] : 'dashicons-admin-post';

		$labels = array(
			'name'                  => $plural,
			'singular_name'         => $singular,
			'menu_name'             => $plural,
			'name_admin_bar'        => $singular,
			'add_new'               => __( 'Add New', 'author-content-manager' ),
			/* translators: %s: singular post type name. */
			'add_new_item'          => sprintf( __( 'Add New %s', 'author-content-manager' ), $singular ),
			/* translators: %s: singular post type name. */
			'new_item'              => sprintf( __( 'New %s', 'author-content-manager' ), $singular ),
			/* translators: %s: singular post type name. */
			'edit_item'             => sprintf( __( 'Edit %s', 'author-content-manager' ), $singular ),
			/* translators: %s: singular post type name. */
			'view_item'             => sprintf( __( 'View %s', 'author-content-manager' ), $singular ),
			/* translators: %s: plural post type name. */
			'all_items'             => sprintf( __( 'All %s', 'author-content-manager' ), $plural ),
			/* translators: %s: plural post type name. */
			'search_items'          => sprintf( __( 'Search %s', 'author-content-manager' ), $plural ),
			/* translators: %s: singular post type name. */
			'parent_item_colon'     => sprintf( __( 'Parent %s:', 'author-content-manager' ), $singular ),
			/* translators: %s: plural post type name. */
			'not_found'             => sprintf( __( 'No %s found.', 'author-content-manager' ), strtolower( $plural ) ),
			/* translators: %s: plural post type name. */
			'not_found_in_trash'    => sprintf( __( 'No %s found in Trash.', 'author-content-manager' ), strtolower( $plural ) ),
			/* translators: %s: singular post type name. */
			'archives'              => sprintf( __( '%s Archives', 'author-content-manager' ), $singular ),
			'attributes'            => __( 'Attributes', 'author-content-manager' ),
			'featured_image'        => __( 'Featured image', 'author-content-manager' ),
			'set_featured_image'    => __( 'Set featured image', 'author-content-manager' ),
			'remove_featured_image' => __( 'Remove featured image', 'author-content-manager' ),
			'use_featured_image'    => __( 'Use as featured image', 'author-content-manager' ),
			/* translators: %s: singular post type name. */
			'insert_into_item'      => sprintf( __( 'Insert into %s', 'author-content-manager' ), strtolower( $singular ) ),
			/* translators: %s: singular post type name. */
			'uploaded_to_this_item' => sprintf( __( 'Uploaded to this %s', 'author-content-manager' ), strtolower( $singular ) ),
			/* translators: %s: plural post type name. */
			'items_list'            => sprintf( __( '%s list', 'author-content-manager' ), $plural ),
			'items_list_navigation' => __( 'Items list navigation', 'author-content-manager' ),
			/* translators: %s: plural post type name. */
			'filter_items_list'     => sprintf( __( 'Filter %s list', 'author-content-manager' ), strtolower( $plural ) ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => (string) $config['description'],
			'public'              => $public,
			'publicly_queryable'  => ! empty( $config['publicly_queryable'] ),
			'exclude_from_search' => ! $public || empty( $config['publicly_queryable'] ),
			'show_ui'             => $show_ui,
			'show_in_menu'        => $show_ui,
			'show_in_nav_menus'   => $public && $show_ui,
			'show_in_admin_bar'   => $show_ui,
			'show_in_rest'        => ! empty( $config['show_in_rest'] ),
			'rest_base'           => $slug,
			'menu_icon'           => $menu_icon,
			'menu_position'       => is_int( $config['menu_position'] ) ? $config['menu_position'] : null,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'hierarchical'        => ! empty( $config['hierarchical'] ),
			'supports'            => $supports,
			'has_archive'         => $public && ! empty( $config['has_archive'] ),
			'rewrite'             => $public
				? array(
					'slug'       => $rewrite_slug,
					'with_front' => true,
					'feeds'      => true,
					'pages'      => ! empty( $config['hierarchical'] ),
				)
				: false,
			'query_var'           => $public ? $slug : false,
			'can_export'          => true,
			'delete_with_user'    => false,
		);

		/**
		 * Filter the arguments used to register a plugin custom post type.
		 *
		 * @param array  $args   Registration arguments.
		 * @param string $slug   Post type key.
		 * @param array  $config Stored definition.
		 */
		return apply_filters( 'acm_post_type_args', $args, $slug, $config );
	}

	/**
	 * Features that may be enabled for a custom post type.
	 *
	 * @return array
	 */
	public static function supported_features() {
		return array(
			'title'           => __( 'Title', 'author-content-manager' ),
			'editor'          => __( 'Editor', 'author-content-manager' ),
			'thumbnail'       => __( 'Featured Image', 'author-content-manager' ),
			'excerpt'         => __( 'Excerpt', 'author-content-manager' ),
			'comments'        => __( 'Comments', 'author-content-manager' ),
			'revisions'       => __( 'Revisions', 'author-content-manager' ),
			'author'          => __( 'Author', 'author-content-manager' ),
			'custom-fields'   => __( 'Custom Fields', 'author-content-manager' ),
			'page-attributes' => __( 'Page Attributes', 'author-content-manager' ),
		);
	}

	/**
	 * Curated Dashicons for the menu icon picker.
	 *
	 * @return array
	 */
	public static function icon_choices() {
		$icons = array(
			'dashicons-admin-post',
			'dashicons-admin-page',
			'dashicons-admin-generic',
			'dashicons-admin-users',
			'dashicons-admin-comments',
			'dashicons-admin-media',
			'dashicons-admin-appearance',
			'dashicons-admin-plugins',
			'dashicons-admin-tools',
			'dashicons-admin-settings',
			'dashicons-admin-network',
			'dashicons-admin-home',
			'dashicons-admin-links',
			'dashicons-admin-multisite',
			'dashicons-format-aside',
			'dashicons-format-gallery',
			'dashicons-format-image',
			'dashicons-format-quote',
			'dashicons-format-status',
			'dashicons-format-video',
			'dashicons-format-audio',
			'dashicons-format-chat',
			'dashicons-groups',
			'dashicons-businessman',
			'dashicons-businesswoman',
			'dashicons-portfolio',
			'dashicons-products',
			'dashicons-store',
			'dashicons-cart',
			'dashicons-tag',
			'dashicons-category',
			'dashicons-images-alt2',
			'dashicons-camera',
			'dashicons-video-alt2',
			'dashicons-microphone',
			'dashicons-media-default',
			'dashicons-edit',
			'dashicons-post-status',
			'dashicons-calendar-alt',
			'dashicons-location',
			'dashicons-phone',
			'dashicons-email-alt',
			'dashicons-star-filled',
			'dashicons-heart',
			'dashicons-awards',
			'dashicons-shield',
			'dashicons-lock',
			'dashicons-megaphone',
			'dashicons-lightbulb',
			'dashicons-book',
			'dashicons-book-alt',
			'dashicons-clipboard',
			'dashicons-download',
			'dashicons-chart-bar',
			'dashicons-analytics',
			'dashicons-testimonial',
			'dashicons-universal-access',
		);

		$choices = array();
		foreach ( $icons as $icon ) {
			$choices[ $icon ] = str_replace( array( 'dashicons-', '-' ), array( '', ' ' ), $icon );
		}

		/**
		 * Filter the Dashicon choices shown in the post type editor.
		 *
		 * @param array $choices Icon class => label.
		 */
		return apply_filters( 'acm_dashicon_choices', $choices );
	}

	/**
	 * Keys that may not be used for a custom post type.
	 *
	 * @return array
	 */
	public static function reserved_slugs() {
		$reserved = array(
			'post',
			'page',
			'attachment',
			'revision',
			'nav_menu_item',
			'custom_css',
			'customize_changeset',
			'oembed_cache',
			'user_request',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_global_styles',
			'wp_navigation',
			'wp_font_family',
			'wp_font_face',
			'action',
			'author',
			'order',
			'theme',
			'type',
			'taxonomy',
			'term',
			'fields',
			'acm_author',
		);

		foreach ( array_keys( ACM_Taxonomy_Registry::get_all() ) as $taxonomy ) {
			$reserved[] = $taxonomy;
		}

		$reserved[] = ACM_Settings::sanitize_author_base( ACM_Settings::get( 'author_base', 'authors' ) );

		/**
		 * Filter the reserved custom post type keys.
		 *
		 * @param array $reserved Reserved keys.
		 */
		return array_values( array_unique( apply_filters( 'acm_reserved_post_type_slugs', $reserved ) ) );
	}

	/**
	 * Post counts for a definition, including inactive ones.
	 *
	 * @param string $slug Post type key.
	 * @return array{total:int,published:int}
	 */
	public static function count_posts( $slug ) {
		global $wpdb;

		$slug   = self::sanitize_key( $slug );
		$counts = array(
			'total'     => 0,
			'published' => 0,
		);

		if ( '' === $slug ) {
			return $counts;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Configurations may reference post types that are no longer registered, so the posts table is queried directly.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_status, COUNT(*) AS num FROM {$wpdb->posts} WHERE post_type = %s GROUP BY post_status",
				$slug
			)
		);

		if ( ! is_array( $rows ) ) {
			return $counts;
		}

		foreach ( $rows as $row ) {
			$status = isset( $row->post_status ) ? $row->post_status : '';
			$num    = isset( $row->num ) ? (int) $row->num : 0;

			if ( in_array( $status, array( 'auto-draft', 'trash', 'inherit' ), true ) ) {
				continue;
			}

			$counts['total'] += $num;

			if ( 'publish' === $status ) {
				$counts['published'] += $num;
			}
		}

		return $counts;
	}

	/**
	 * Sanitize a post type key.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	public static function sanitize_key( $key ) {
		$key = sanitize_key( (string) $key );
		$key = preg_replace( '/[^a-z0-9_\-]/', '', $key );

		return substr( (string) $key, 0, self::MAX_KEY_LENGTH );
	}

	/**
	 * Limit the length of a plain text value.
	 *
	 * @param string $text  Text.
	 * @param int    $limit Maximum characters.
	 * @return string
	 */
	private static function limit_text( $text, $limit ) {
		$text = trim( (string) $text );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, $limit );
		}

		if ( preg_match( '/^.{0,' . (int) $limit . '}/us', $text, $matches ) ) {
			return $matches[0];
		}

		return substr( $text, 0, $limit );
	}
}
