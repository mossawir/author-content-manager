<?php
/**
 * Custom taxonomy registry.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores custom taxonomy definitions and registers them with WordPress.
 */
class ACM_Taxonomy_Registry {

	/**
	 * Option name.
	 */
	const OPTION = ACM_OPTION_TAXONOMIES;

	/**
	 * Maximum length of a taxonomy key.
	 */
	const MAX_KEY_LENGTH = 32;

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
			'slug'              => '',
			'plural'            => '',
			'singular'          => '',
			'description'       => '',
			'post_types'        => array( ACM_POST_TYPE ),
			'hierarchical'      => 1,
			'public'            => 1,
			'show_in_rest'      => 1,
			'show_admin_column' => 1,
			'show_in_nav_menus' => 1,
			'rewrite_slug'      => '',
			'active'            => 1,
			'created'           => 0,
		);
	}

	/**
	 * Every configured taxonomy.
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
	 * @param string $slug Taxonomy key.
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
	 * @return string|WP_Error Sanitized taxonomy key.
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

		if ( '' !== $original_slug && $original_slug !== $slug && isset( $all[ $original_slug ] ) ) {
			unset( $all[ $original_slug ] );
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
	 * Delete a definition. Terms are kept in the database.
	 *
	 * @param string $slug Taxonomy key.
	 * @return bool
	 */
	public static function delete( $slug ) {
		$slug = sanitize_key( $slug );
		$all  = self::get_all();

		if ( ! isset( $all[ $slug ] ) ) {
			return false;
		}

		unset( $all[ $slug ] );

		self::persist( $all );

		return true;
	}

	/**
	 * Activate or deactivate a definition.
	 *
	 * @param string $slug   Taxonomy key.
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
	 * Remove a post type from every taxonomy that references it.
	 *
	 * @param string $post_type Post type key.
	 * @return void
	 */
	public static function detach_post_type( $post_type ) {
		$post_type = sanitize_key( $post_type );
		$all       = self::get_all();
		$changed   = false;

		foreach ( $all as $slug => $config ) {
			if ( ! isset( $config['post_types'] ) || ! is_array( $config['post_types'] ) ) {
				continue;
			}

			if ( in_array( $post_type, $config['post_types'], true ) ) {
				$all[ $slug ]['post_types'] = array_values( array_diff( $config['post_types'], array( $post_type ) ) );
				$changed                    = true;
			}
		}

		if ( $changed ) {
			self::persist( $all );
		}
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
			return new WP_Error( 'acm_missing_plural', __( 'Please enter a plural taxonomy name.', 'author-content-manager' ) );
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
			return new WP_Error( 'acm_missing_slug', __( 'Please enter a valid taxonomy slug using letters, numbers, dashes or underscores.', 'author-content-manager' ) );
		}

		if ( in_array( $slug, self::reserved_slugs(), true ) ) {
			return new WP_Error(
				'acm_reserved_slug',
				sprintf(
					/* translators: %s: reserved taxonomy slug. */
					__( '"%s" is reserved by WordPress or by this plugin. Please choose another slug.', 'author-content-manager' ),
					$slug
				)
			);
		}

		$all = self::get_all();

		if ( $slug !== $original_slug && isset( $all[ $slug ] ) ) {
			return new WP_Error( 'acm_duplicate_slug', __( 'A custom taxonomy with this slug already exists. Please choose another slug.', 'author-content-manager' ) );
		}

		if ( post_type_exists( $slug ) ) {
			return new WP_Error( 'acm_post_type_conflict', __( 'This slug is already used by a post type. Please choose another slug.', 'author-content-manager' ) );
		}

		if ( taxonomy_exists( $slug ) && ( '' === $original_slug || $slug !== $original_slug ) ) {
			return new WP_Error( 'acm_taxonomy_conflict', __( 'This slug is already used by another taxonomy. Please choose another slug.', 'author-content-manager' ) );
		}

		$clean['slug']        = $slug;
		$clean['plural']      = $plural;
		$clean['singular']    = $singular;
		$clean['description'] = isset( $data['description'] ) ? self::limit_text( sanitize_textarea_field( $data['description'] ), 500 ) : '';

		$object_types = array();
		if ( isset( $data['post_types'] ) && is_array( $data['post_types'] ) ) {
			foreach ( $data['post_types'] as $post_type ) {
				$post_type = sanitize_key( $post_type );

				if ( '' !== $post_type ) {
					$object_types[] = $post_type;
				}
			}
		}
		$object_types = array_values( array_unique( $object_types ) );

		$valid_object_types = array();

		foreach ( $object_types as $object_type ) {
			if ( post_type_exists( $object_type ) || ACM_Post_Type_Registry::get( $object_type ) ) {
				$valid_object_types[] = $object_type;
			}
		}

		$object_types = $valid_object_types;

		if ( empty( $object_types ) ) {
			return new WP_Error( 'acm_missing_post_types', __( 'Please assign the taxonomy to at least one post type.', 'author-content-manager' ) );
		}

		$clean['post_types']        = $object_types;
		$clean['hierarchical']      = empty( $data['hierarchical'] ) ? 0 : 1;
		$clean['public']            = empty( $data['public'] ) ? 0 : 1;
		$clean['show_in_rest']      = empty( $data['show_in_rest'] ) ? 0 : 1;
		$clean['show_admin_column'] = empty( $data['show_admin_column'] ) ? 0 : 1;
		$clean['show_in_nav_menus'] = empty( $data['show_in_nav_menus'] ) ? 0 : 1;
		$clean['active']            = empty( $data['active'] ) ? 0 : 1;

		$rewrite               = isset( $data['rewrite_slug'] ) ? sanitize_title( $data['rewrite_slug'] ) : '';
		$clean['rewrite_slug'] = self::limit_text( $rewrite, 200 );

		/**
		 * Filter a sanitized custom taxonomy definition before it is stored.
		 *
		 * @param array  $clean         Sanitized definition.
		 * @param array  $data          Raw definition.
		 * @param string $original_slug Key being edited.
		 */
		return apply_filters( 'acm_sanitize_taxonomy', $clean, $data, $original_slug );
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

			if ( '' === $slug || empty( $config['active'] ) || taxonomy_exists( $slug ) ) {
				continue;
			}

			$config = wp_parse_args( $config, self::defaults() );

			$object_types = array();
			foreach ( (array) $config['post_types'] as $post_type ) {
				if ( post_type_exists( $post_type ) ) {
					$object_types[] = $post_type;
				}
			}

			$object_types = array_values( array_unique( $object_types ) );

			if ( empty( $object_types ) ) {
				continue;
			}

			register_taxonomy( $slug, $object_types, self::build_args( $slug, $config ) );
		}
	}

	/**
	 * Build the arguments passed to register_taxonomy().
	 *
	 * @param string $slug   Taxonomy key.
	 * @param array  $config Definition.
	 * @return array
	 */
	public static function build_args( $slug, $config ) {
		$config       = wp_parse_args( $config, self::defaults() );
		$public       = ! empty( $config['public'] );
		$hierarchical = ! empty( $config['hierarchical'] );
		$plural       = $config['plural'];
		$singular     = $config['singular'];
		$rewrite_slug = '' !== $config['rewrite_slug'] ? $config['rewrite_slug'] : $slug;

		$labels = array(
			'name'                       => $plural,
			'singular_name'              => $singular,
			'menu_name'                  => $plural,
			/* translators: %s: plural taxonomy name. */
			'all_items'                  => sprintf( __( 'All %s', 'author-content-manager' ), $plural ),
			/* translators: %s: singular taxonomy name. */
			'edit_item'                  => sprintf( __( 'Edit %s', 'author-content-manager' ), $singular ),
			/* translators: %s: singular taxonomy name. */
			'view_item'                  => sprintf( __( 'View %s', 'author-content-manager' ), $singular ),
			/* translators: %s: singular taxonomy name. */
			'update_item'                => sprintf( __( 'Update %s', 'author-content-manager' ), $singular ),
			/* translators: %s: singular taxonomy name. */
			'add_new_item'               => sprintf( __( 'Add New %s', 'author-content-manager' ), $singular ),
			/* translators: %s: singular taxonomy name. */
			'new_item_name'              => sprintf( __( 'New %s Name', 'author-content-manager' ), $singular ),
			/* translators: %s: singular taxonomy name. */
			'parent_item'                => sprintf( __( 'Parent %s', 'author-content-manager' ), $singular ),
			/* translators: %s: singular taxonomy name. */
			'parent_item_colon'          => sprintf( __( 'Parent %s:', 'author-content-manager' ), $singular ),
			/* translators: %s: plural taxonomy name. */
			'search_items'               => sprintf( __( 'Search %s', 'author-content-manager' ), $plural ),
			/* translators: %s: plural taxonomy name. */
			'popular_items'              => sprintf( __( 'Popular %s', 'author-content-manager' ), $plural ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'author-content-manager' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'author-content-manager' ),
			'choose_from_most_used'      => __( 'Choose from the most used items', 'author-content-manager' ),
			/* translators: %s: plural taxonomy name. */
			'not_found'                  => sprintf( __( 'No %s found.', 'author-content-manager' ), strtolower( $plural ) ),
			'no_terms'                   => __( 'No items', 'author-content-manager' ),
			/* translators: %s: plural taxonomy name. */
			'items_list_navigation'      => sprintf( __( '%s list navigation', 'author-content-manager' ), $plural ),
			/* translators: %s: plural taxonomy name. */
			'items_list'                 => sprintf( __( '%s list', 'author-content-manager' ), $plural ),
			'back_to_items'              => __( '&larr; Go to items', 'author-content-manager' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => (string) $config['description'],
			'public'             => $public,
			'publicly_queryable' => $public,
			'hierarchical'       => $hierarchical,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => $public && ! empty( $config['show_in_nav_menus'] ),
			'show_in_rest'       => ! empty( $config['show_in_rest'] ),
			'rest_base'          => $slug,
			'show_admin_column'  => ! empty( $config['show_admin_column'] ),
			'show_in_quick_edit' => true,
			'query_var'          => $public ? $slug : false,
			'rewrite'            => $public
				? array(
					'slug'         => $rewrite_slug,
					'with_front'   => false,
					'hierarchical' => $hierarchical,
				)
				: false,
		);

		/**
		 * Filter the arguments used to register a plugin custom taxonomy.
		 *
		 * @param array  $args   Registration arguments.
		 * @param string $slug   Taxonomy key.
		 * @param array  $config Stored definition.
		 */
		return apply_filters( 'acm_taxonomy_args', $args, $slug, $config );
	}

	/**
	 * Create the default author category taxonomy on first activation.
	 *
	 * @return void
	 */
	public static function maybe_create_defaults() {
		if ( false !== get_option( self::OPTION, false ) ) {
			return;
		}

		$defaults = array(
			'author_category' => array(
				'slug'              => 'author_category',
				'plural'            => __( 'Author Categories', 'author-content-manager' ),
				'singular'          => __( 'Author Category', 'author-content-manager' ),
				'description'       => __( 'Group author profiles, for example by expertise or team.', 'author-content-manager' ),
				'post_types'        => array( ACM_POST_TYPE ),
				'hierarchical'      => 1,
				'public'            => 1,
				'show_in_rest'      => 1,
				'show_admin_column' => 1,
				'show_in_nav_menus' => 1,
				'rewrite_slug'      => 'author-category',
				'active'            => 1,
				'created'           => time(),
			),
		);

		add_option( self::OPTION, $defaults );
		self::$cache = $defaults;
	}

	/**
	 * Count the terms attached to a definition, including inactive taxonomies.
	 *
	 * @param string $slug Taxonomy key.
	 * @return int
	 */
	public static function count_terms( $slug ) {
		global $wpdb;

		$slug = self::sanitize_key( $slug );

		if ( '' === $slug ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Inactive taxonomies are not registered, so wp_count_terms() cannot be used.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
				$slug
			)
		);

		return (int) $count;
	}

	/**
	 * Post types available for the "associated post types" field.
	 *
	 * @return array Post type key => label.
	 */
	public static function available_post_types() {
		$types = get_post_types( array( 'public' => true ), 'objects' );

		$options = array();
		foreach ( $types as $slug => $object ) {
			if ( 'attachment' === $slug ) {
				continue;
			}

			$options[ $slug ] = isset( $object->labels->singular_name ) ? $object->labels->singular_name : $slug;
		}

		foreach ( ACM_Post_Type_Registry::get_all() as $slug => $config ) {
			if ( ! isset( $options[ $slug ] ) ) {
				/* translators: %s: custom post type name. */
				$options[ $slug ] = sprintf( __( '%s (inactive)', 'author-content-manager' ), $config['plural'] );
			}
		}

		/**
		 * Filter the post types offered when editing a taxonomy.
		 *
		 * @param array $options Post type key => label.
		 */
		return apply_filters( 'acm_taxonomy_post_types', $options );
	}

	/**
	 * Keys that may not be used for a custom taxonomy.
	 *
	 * @return array
	 */
	public static function reserved_slugs() {
		$reserved = array(
			'category',
			'post_tag',
			'nav_menu',
			'link_category',
			'post_format',
			'wp_theme',
			'wp_template_part_area',
			'wp_pattern_category',
			'type',
			'taxonomy',
			'term',
			'author',
			'acm_author',
		);

		foreach ( array_keys( ACM_Post_Type_Registry::get_all() ) as $post_type ) {
			$reserved[] = $post_type;
		}

		$reserved[] = ACM_Settings::sanitize_author_base( ACM_Settings::get( 'author_base', 'authors' ) );

		/**
		 * Filter the reserved custom taxonomy keys.
		 *
		 * @param array $reserved Reserved keys.
		 */
		return array_values( array_unique( apply_filters( 'acm_reserved_taxonomy_slugs', $reserved ) ) );
	}

	/**
	 * Link to the native term management screen.
	 *
	 * @param string $slug Taxonomy key.
	 * @return string
	 */
	public static function admin_terms_url( $slug ) {
		$config      = self::get( $slug );
		$post_types  = $config && ! empty( $config['post_types'] ) ? (array) $config['post_types'] : array();
		$post_type   = ! empty( $post_types ) ? reset( $post_types ) : 'post';

		return add_query_arg(
			array(
				'taxonomy'  => $slug,
				'post_type' => $post_type,
			),
			admin_url( 'edit-tags.php' )
		);
	}

	/**
	 * Sanitize a taxonomy key.
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
