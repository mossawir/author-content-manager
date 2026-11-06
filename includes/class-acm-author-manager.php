<?php
/**
 * Author post type management.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the author post type and improves its admin experience.
 */
class ACM_Author_Manager {

	/**
	 * Meta key holding assigned author IDs for a post.
	 */
	const ASSIGNMENT_META = '_acm_related_authors';

	/**
	 * Assignment nonce action.
	 */
	const ASSIGNMENT_NONCE = 'acm_save_assignment';

	/**
	 * Register the author post type.
	 *
	 * The slug of both the single profiles and the archive is configurable, but
	 * it is registered here rather than in the post type registry so authors
	 * can never be deleted by mistake.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$settings = ACM_Settings::all();
		$base     = isset( $settings['author_base'] ) ? $settings['author_base'] : 'authors';
		$base     = ACM_Settings::sanitize_author_base( $base );
		$archive  = ! empty( $settings['author_archive'] );

		$labels = array(
			'name'                  => __( 'Authors', 'author-content-manager' ),
			'singular_name'         => __( 'Author', 'author-content-manager' ),
			'menu_name'             => __( 'Authors', 'author-content-manager' ),
			'name_admin_bar'        => __( 'Author', 'author-content-manager' ),
			'add_new'               => __( 'Add New Author', 'author-content-manager' ),
			'add_new_item'          => __( 'Add New Author', 'author-content-manager' ),
			'new_item'              => __( 'New Author', 'author-content-manager' ),
			'edit_item'             => __( 'Edit Author', 'author-content-manager' ),
			'view_item'             => __( 'View Author Profile', 'author-content-manager' ),
			'all_items'             => __( 'Authors', 'author-content-manager' ),
			'search_items'          => __( 'Search Authors', 'author-content-manager' ),
			'not_found'             => __( 'No authors found.', 'author-content-manager' ),
			'not_found_in_trash'    => __( 'No authors found in Trash.', 'author-content-manager' ),
			'archives'              => __( 'Author Archives', 'author-content-manager' ),
			'attributes'            => __( 'Author Attributes', 'author-content-manager' ),
			'featured_image'        => __( 'Profile Picture', 'author-content-manager' ),
			'set_featured_image'    => __( 'Set profile picture', 'author-content-manager' ),
			'remove_featured_image' => __( 'Remove profile picture', 'author-content-manager' ),
			'use_featured_image'    => __( 'Use as profile picture', 'author-content-manager' ),
			'insert_into_item'      => __( 'Insert into author profile', 'author-content-manager' ),
			'uploaded_to_this_item' => __( 'Uploaded to this author', 'author-content-manager' ),
			'items_list'            => __( 'Authors list', 'author-content-manager' ),
			'items_list_navigation' => __( 'Authors list navigation', 'author-content-manager' ),
			'filter_items_list'     => __( 'Filter authors list', 'author-content-manager' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Author profiles managed by Author & Content Manager.', 'author-content-manager' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => true,
			'rest_base'           => 'authors',
			'menu_icon'           => 'dashicons-groups',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'excerpt', 'revisions', 'author' ),
			'has_archive'         => $archive ? $base : false,
			'rewrite'             => array(
				'slug'       => $base,
				'with_front' => false,
				'feeds'      => true,
			),
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
		);

		/**
		 * Filter the arguments used to register the author post type.
		 *
		 * @param array $args Registration arguments.
		 */
		$args = apply_filters( 'acm_author_post_type_args', $args );

		register_post_type( ACM_POST_TYPE, $args );
	}

	/**
	 * Register the hooks owned by this module.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_assignment_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_assignment' ), 10, 2 );
		add_filter( 'manage_' . ACM_POST_TYPE . '_posts_columns', array( __CLASS__, 'author_columns' ) );
		add_action( 'manage_' . ACM_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_author_column' ), 10, 2 );
		add_filter( 'posts_search', array( __CLASS__, 'search_where' ), 10, 2 );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
		add_filter( 'post_updated_messages', array( __CLASS__, 'updated_messages' ) );

		$enabled = (array) ACM_Settings::get( 'assign_post_types', array() );

		foreach ( $enabled as $post_type ) {
			$post_type = sanitize_key( $post_type );

			if ( '' === $post_type || ACM_POST_TYPE === $post_type ) {
				continue;
			}

			add_filter(
				'manage_' . $post_type . '_posts_columns',
				static function ( $columns ) use ( $post_type ) {
					return ACM_Author_Manager::assignment_columns( $columns, $post_type );
				}
			);

			add_action(
				'manage_' . $post_type . '_posts_custom_column',
				array( __CLASS__, 'render_assignment_column' ),
				10,
				2
			);
		}
	}

	/**
	 * Replace the title placeholder on the author editor.
	 *
	 * @param string  $text Placeholder text.
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public static function title_placeholder( $text, $post ) {
		if ( $post instanceof WP_Post && ACM_POST_TYPE === $post->post_type ) {
			return __( 'Author name', 'author-content-manager' );
		}

		return $text;
	}

	/**
	 * Human readable update messages for author profiles.
	 *
	 * @param array $messages Post type messages.
	 * @return array
	 */
	public static function updated_messages( $messages ) {
		$messages[ ACM_POST_TYPE ] = array(
			0  => '',
			1  => __( 'Author profile updated.', 'author-content-manager' ),
			2  => __( 'Custom field updated.', 'author-content-manager' ),
			3  => __( 'Custom field deleted.', 'author-content-manager' ),
			4  => __( 'Author profile updated.', 'author-content-manager' ),
			/* translators: %s: revision date. */
			5  => sprintf( __( 'Author profile restored to revision from %s.', 'author-content-manager' ), '%s' ),
			6  => __( 'Author profile published.', 'author-content-manager' ),
			7  => __( 'Author profile saved.', 'author-content-manager' ),
			8  => __( 'Author profile submitted.', 'author-content-manager' ),
			/* translators: %s: scheduled date. */
			9  => sprintf( __( 'Author profile scheduled for: %s.', 'author-content-manager' ), '%s' ),
			10 => __( 'Author profile draft updated.', 'author-content-manager' ),
		);

		return $messages;
	}

	/**
	 * Get the public base slug used for author profiles.
	 *
	 * @return string
	 */
	public static function get_base() {
		return ACM_Settings::sanitize_author_base( ACM_Settings::get( 'author_base', 'authors' ) );
	}

	/**
	 * Archive URL for author profiles.
	 *
	 * @return string Empty string when archives are disabled.
	 */
	public static function get_archive_url() {
		if ( ! ACM_Settings::get( 'author_archive', 1 ) ) {
			return '';
		}

		return (string) get_post_type_archive_link( ACM_POST_TYPE );
	}

	/**
	 * Add the author assignment meta box to the configured post types.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function add_assignment_meta_boxes( $post_type ) {
		if ( ACM_POST_TYPE === $post_type ) {
			return;
		}

		$enabled = (array) ACM_Settings::get( 'assign_post_types', array() );

		if ( ! in_array( $post_type, $enabled, true ) ) {
			return;
		}

		add_meta_box(
			'acm-related-authors',
			__( 'Author Profile', 'author-content-manager' ),
			array( __CLASS__, 'render_assignment_box' ),
			$post_type,
			'side',
			'default',
			array( '__block_editor_compatible_meta_box' => true )
		);
	}

	/**
	 * Render the author assignment box.
	 *
	 * @param WP_Post $post Post being edited.
	 * @return void
	 */
	public static function render_assignment_box( $post ) {
		wp_nonce_field( self::ASSIGNMENT_NONCE, 'acm_assignment_nonce' );

		$authors = get_posts(
			array(
				'post_type'              => ACM_POST_TYPE,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => 200,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $authors ) ) {
			echo '<p>' . esc_html__( 'No author profiles exist yet.', 'author-content-manager' ) . '</p>';
			printf(
				'<p><a href="%1$s" class="button button-secondary">%2$s</a></p>',
				esc_url( admin_url( 'post-new.php?post_type=' . ACM_POST_TYPE ) ),
				esc_html__( 'Add New Author', 'author-content-manager' )
			);

			return;
		}

		$selected = self::get_assigned_ids( $post->ID );

		echo '<p class="description">' . esc_html__( 'Select one or more author profiles to display with this content.', 'author-content-manager' ) . '</p>';
		echo '<select name="acm_related_authors[]" id="acm_related_authors" multiple="multiple" size="6" class="widefat">';

		foreach ( $authors as $author ) {
			echo '<option value="' . esc_attr( (string) $author->ID ) . '"' . selected( in_array( (int) $author->ID, $selected, true ), true, false ) . '>';
			echo esc_html( get_the_title( $author ) );
			echo '</option>';
		}

		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Hold Ctrl (Windows) or Command (macOS) to select several authors.', 'author-content-manager' ) . '</p>';
	}

	/**
	 * Save the author assignment.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save_assignment( $post_id, $post ) {
		if ( ! $post instanceof WP_Post || ! isset( $_POST['acm_assignment_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['acm_assignment_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, self::ASSIGNMENT_NONCE ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$enabled = (array) ACM_Settings::get( 'assign_post_types', array() );

		if ( ! in_array( $post->post_type, $enabled, true ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$ids = array();

		if ( isset( $_POST['acm_related_authors'] ) && is_array( $_POST['acm_related_authors'] ) ) {
			$submitted = wp_unslash( $_POST['acm_related_authors'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with absint() below.

			foreach ( (array) $submitted as $id ) {
				$id = absint( $id );

				if ( $id > 0 ) {
					$ids[] = $id;
				}
			}
		}

		$ids = array_values( array_unique( $ids ) );

		if ( empty( $ids ) ) {
			delete_post_meta( $post_id, self::ASSIGNMENT_META );

			return;
		}

		update_post_meta( $post_id, self::ASSIGNMENT_META, $ids );
	}

	/**
	 * Assigned author IDs for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_assigned_ids( $post_id ) {
		$ids = get_post_meta( (int) $post_id, self::ASSIGNMENT_META, true );

		if ( ! is_array( $ids ) ) {
			$ids = '' === $ids ? array() : array( $ids );
		}

		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}

	/**
	 * Assigned author objects for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return ACM_Author[]
	 */
	public static function get_assigned_authors( $post_id ) {
		$authors = array();

		foreach ( self::get_assigned_ids( $post_id ) as $id ) {
			$author = ACM_Author::get( $id );

			if ( $author ) {
				$authors[] = $author;
			}
		}

		return $authors;
	}

	/**
	 * Add author assignment columns to enabled post types.
	 *
	 * @param array  $columns   Existing columns.
	 * @param string $post_type Post type.
	 * @return array
	 */
	public static function assignment_columns( $columns, $post_type ) {
		$enabled = (array) ACM_Settings::get( 'assign_post_types', array() );

		if ( ! in_array( $post_type, $enabled, true ) ) {
			return $columns;
		}

		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['acm_assigned_authors'] = __( 'Authors', 'author-content-manager' );
			}
		}

		return $new;
	}

	/**
	 * Render the author assignment column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_assignment_column( $column, $post_id ) {
		if ( 'acm_assigned_authors' !== $column ) {
			return;
		}

		$authors = self::get_assigned_authors( $post_id );

		if ( empty( $authors ) ) {
			echo '&mdash;';

			return;
		}

		$names = array();

		foreach ( $authors as $author ) {
			$name = $author->get_name();

			if ( '' === $name ) {
				continue;
			}

			$url = $author->get_profile_url();

			if ( '' !== $url ) {
				$names[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
			} else {
				$names[] = esc_html( $name );
			}
		}

		echo wp_kses_post( implode( ', ', $names ) );
	}

	/**
	 * Custom author list table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function author_columns( $columns ) {
		$new = array();

		if ( isset( $columns['cb'] ) ) {
			$new['cb'] = $columns['cb'];
		}

		$new['acm_photo']    = __( 'Photo', 'author-content-manager' );
		$new['title']        = isset( $columns['title'] ) ? $columns['title'] : __( 'Name', 'author-content-manager' );
		$new['acm_email']    = __( 'Email', 'author-content-manager' );
		$new['acm_location'] = __( 'Location', 'author-content-manager' );
		$new['acm_linkedin'] = __( 'LinkedIn', 'author-content-manager' );

		foreach ( $columns as $key => $label ) {
			if ( in_array( $key, array( 'cb', 'title', 'author', 'comments' ), true ) ) {
				continue;
			}

			$new[ $key ] = $label;
		}

		return $new;
	}

	/**
	 * Render custom author columns.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_author_column( $column, $post_id ) {
		$author = ACM_Author::get( $post_id );

		if ( ! $author ) {
			return;
		}

		switch ( $column ) {
			case 'acm_photo':
				$photo = $author->get_photo_html(
					'thumbnail',
					array(
						'class' => 'acm-admin-thumb',
						'alt'   => '',
					)
				);

				echo $photo ? wp_kses_post( $photo ) : '&mdash;';
				break;

			case 'acm_email':
				$email = $author->get_email();

				if ( '' === $email ) {
					echo '&mdash;';
					break;
				}

				echo '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>';
				break;

			case 'acm_location':
				$parts = array_filter( array( $author->get_city(), $author->get_country() ) );

				echo $parts ? esc_html( implode( ', ', $parts ) ) : '&mdash;';
				break;

			case 'acm_linkedin':
				$linkedin = $author->get_linkedin();

				if ( '' === $linkedin ) {
					echo '&mdash;';
					break;
				}

				printf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
					esc_url( $linkedin ),
					esc_html__( 'View profile', 'author-content-manager' )
				);
				break;
		}
	}

	/**
	 * Extend author admin searches to profile meta fields.
	 *
	 * @param string   $search Search SQL.
	 * @param WP_Query $query  Query object.
	 * @return string
	 */
	public static function search_where( $search, $query ) {
		if ( ! is_admin() || ! $query instanceof WP_Query ) {
			return $search;
		}

		$post_type = $query->get( 'post_type' );

		if ( ACM_POST_TYPE !== $post_type || ! $query->is_search() ) {
			return $search;
		}

		$term = trim( (string) $query->get( 's' ) );

		if ( '' === $term || '' === trim( (string) $search ) ) {
			return $search;
		}

		global $wpdb;

		$keys = array(
			ACM_Author_Fields::meta_key( 'name' ),
			ACM_Author_Fields::meta_key( 'email' ),
			ACM_Author_Fields::meta_key( 'city' ),
			ACM_Author_Fields::meta_key( 'country' ),
			ACM_Author_Fields::meta_key( 'phone' ),
			ACM_Author_Fields::meta_key( 'mobile' ),
			ACM_Author_Fields::meta_key( 'address' ),
		);

		$placeholders = implode( ', ', array_fill( 0, count( $keys ), '%s' ) );
		$params       = array_merge( $keys, array( '%' . $wpdb->esc_like( $term ) . '%' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders are built from a fixed key list.
		$meta_sql = $wpdb->prepare(
			" OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} AS acm_pm WHERE acm_pm.post_id = {$wpdb->posts}.ID AND acm_pm.meta_key IN ( $placeholders ) AND acm_pm.meta_value LIKE %s )",
			$params
		);
		// phpcs:enable

		if ( is_numeric( $term ) ) {
			$meta_sql .= $wpdb->prepare( " OR {$wpdb->posts}.ID = %d", (int) $term );
		}

		$search = preg_replace( '/^\s*AND\s*/i', '', (string) $search );

		return ' AND ( (' . $search . ')' . $meta_sql . ' ) ';
	}
}
