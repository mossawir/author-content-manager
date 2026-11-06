<?php
/**
 * Shortcodes.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the author shortcodes.
 */
class ACM_Shortcodes {

	/**
	 * Register the shortcodes.
	 *
	 * @return void
	 */
	public function hooks() {
		add_shortcode( 'custom_author', array( $this, 'render_author' ) );
		add_shortcode( 'custom_authors', array( $this, 'render_authors' ) );
		add_shortcode( 'custom_author_name', array( $this, 'render_author_name' ) );
		add_shortcode( 'custom_author_image', array( $this, 'render_author_image' ) );
		add_shortcode( 'custom_author_email', array( $this, 'render_author_email' ) );
		add_shortcode( 'custom_author_field', array( $this, 'render_author_field' ) );
		add_shortcode( 'custom_author_fields', array( $this, 'render_author_fields' ) );
		add_shortcode( 'acm_post_field', array( $this, 'render_post_field' ) );
		add_shortcode( 'acm_post_fields', array( $this, 'render_post_fields' ) );
		add_shortcode( 'acm_post_image', array( $this, 'render_post_image' ) );
		add_shortcode( 'acm_posts', array( $this, 'render_post_type_posts' ) );

		foreach ( array_keys( ACM_Post_Type_Registry::get_all() ) as $post_type ) {
			$tag = 'acm_posts_' . sanitize_key( $post_type );

			if ( ! shortcode_exists( $tag ) ) {
				add_shortcode( $tag, array( $this, 'render_post_type_posts' ) );
			}
		}
	}

	/**
	 * Render [acm_posts] and every per-post-type alias.
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string       $content Enclosed content.
	 * @param string       $tag     Shortcode tag.
	 * @return string
	 */
	public function render_post_type_posts( $atts, $content = '', $tag = '' ) {
		return ACM_Post_Display::shortcode( $atts, $content, $tag );
	}

	/**
	 * Resolve the author referenced by a shortcode.
	 *
	 * Without an explicit ID or slug the shortcode falls back to the current
	 * author profile, then to the author assigned to the current post.
	 *
	 * @param string $id   Author ID.
	 * @param string $slug Author slug.
	 * @return ACM_Author|null
	 */
	public static function resolve_author( $id = '', $slug = '' ) {
		$author = null;

		$id = absint( $id );

		if ( $id > 0 ) {
			$author = ACM_Author::get( $id );
		}

		if ( ! $author ) {
			$slug = sanitize_title( (string) $slug );

			if ( '' !== $slug ) {
				$post = get_page_by_path( $slug, OBJECT, ACM_POST_TYPE );

				if ( $post instanceof WP_Post ) {
					$author = ACM_Author::get( $post );
				}
			}
		}

		if ( ! $author && is_singular( ACM_POST_TYPE ) ) {
			$author = ACM_Author::get( get_the_ID() );
		}

		if ( ! $author && is_singular() ) {
			$assigned = acm_get_assigned_authors( get_the_ID() );

			if ( ! empty( $assigned ) ) {
				$author = $assigned[0];
			}
		}

		if ( ! $author ) {
			return null;
		}

		if ( ! $author->is_published() && ! current_user_can( 'edit_post', $author->get_id() ) ) {
			return null;
		}

		return $author;
	}

	/**
	 * Render [custom_author].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_author( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'           => '',
				'slug'         => '',
				'layout'       => (string) ACM_Settings::get( 'default_layout', 'vertical' ),
				'show_image'   => 'true',
				'show_bio'     => 'true',
				'show_contact' => 'true',
				'show_social'  => 'true',
				'show_name'    => 'true',
				'show_custom'  => 'true',
				'image_size'   => (string) ACM_Settings::get( 'image_size', 'medium' ),
				'class'        => '',
			),
			$atts,
			'custom_author'
		);

		$author = self::resolve_author( $atts['id'], $atts['slug'] );

		if ( ! $author ) {
			return '';
		}

		$layout = in_array( $atts['layout'], array( 'vertical', 'horizontal' ), true ) ? $atts['layout'] : 'vertical';

		ACM_Assets::enqueue_frontend_assets();

		return ACM_Templates::profile(
			$author,
			array(
				'layout'       => $layout,
				'show_image'   => acm_to_bool( $atts['show_image'], true ),
				'show_bio'     => acm_to_bool( $atts['show_bio'], true ),
				'show_contact' => acm_to_bool( $atts['show_contact'], true ),
				'show_social'  => acm_to_bool( $atts['show_social'], true ),
				'show_name'    => acm_to_bool( $atts['show_name'], true ),
				'show_custom'  => acm_to_bool( $atts['show_custom'], true ),
				'image_size'   => $atts['image_size'],
				'class'        => $atts['class'],
				'context'      => 'shortcode',
			)
		);
	}

	/**
	 * Render [custom_authors].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_authors( $atts ) {
		$atts = shortcode_atts(
			array(
				'ids'            => '',
				'taxonomy'       => '',
				'term'           => '',
				'post_id'        => '',
				'columns'        => (string) ACM_Settings::get( 'grid_columns', 3 ),
				'posts_per_page' => '-1',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'show_image'     => 'true',
				'show_bio'       => 'true',
				'show_contact'   => 'false',
				'show_social'    => 'true',
				'show_link'      => 'true',
				'image_size'     => (string) ACM_Settings::get( 'image_size', 'medium' ),
				'bio_limit'      => (string) ACM_Settings::get( 'bio_limit', 160 ),
				'class'          => '',
			),
			$atts,
			'custom_authors'
		);

		$authors = self::get_authors_from_args(
			array(
				'ids'            => $atts['ids'],
				'taxonomy'       => $atts['taxonomy'],
				'term'           => $atts['term'],
				'post_id'        => $atts['post_id'],
				'posts_per_page' => $atts['posts_per_page'],
				'orderby'        => $atts['orderby'],
				'order'          => $atts['order'],
			)
		);

		if ( empty( $authors ) ) {
			return '';
		}

		ACM_Assets::enqueue_frontend_assets();

		return ACM_Templates::grid(
			$authors,
			array(
				'columns'      => absint( $atts['columns'] ),
				'show_image'   => acm_to_bool( $atts['show_image'], true ),
				'show_bio'     => acm_to_bool( $atts['show_bio'], true ),
				'show_contact' => acm_to_bool( $atts['show_contact'], false ),
				'show_social'  => acm_to_bool( $atts['show_social'], true ),
				'show_link'    => acm_to_bool( $atts['show_link'], true ),
				'image_size'   => $atts['image_size'],
				'bio_limit'    => absint( $atts['bio_limit'] ),
				'class'        => $atts['class'],
			)
		);
	}

	/**
	 * Render [custom_author_name].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_author_name( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => '',
				'slug'  => '',
				'link'  => 'false',
				'class' => '',
			),
			$atts,
			'custom_author_name'
		);

		$author = self::resolve_author( $atts['id'], $atts['slug'] );

		if ( ! $author ) {
			return '';
		}

		$name = $author->get_name();

		if ( '' === $name ) {
			return '';
		}

		$classes = array( 'acm-author-name' );
		$extra   = acm_sanitize_classes( $atts['class'] );

		if ( '' !== $extra ) {
			$classes[] = $extra;
		}

		$url  = $author->get_profile_url();
		$html = esc_html( $name );

		if ( acm_to_bool( $atts['link'], false ) && '' !== $url ) {
			$html = '<a class="acm-author-name__link" href="' . esc_url( $url ) . '">' . $html . '</a>';
		}

		return '<span class="' . esc_attr( implode( ' ', $classes ) ) . '">' . $html . '</span>';
	}

	/**
	 * Render [custom_author_image].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_author_image( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => '',
				'slug'  => '',
				'size'  => '',
				'class' => '',
			),
			$atts,
			'custom_author_image'
		);

		$author = self::resolve_author( $atts['id'], $atts['slug'] );

		if ( ! $author ) {
			return '';
		}

		$size  = sanitize_key( (string) $atts['size'] );
		$photo = $author->get_photo_html(
			$size,
			array(
				'class' => 'acm-author-image__img',
				'alt'   => $author->get_name(),
			)
		);

		if ( '' === $photo ) {
			return '';
		}

		$classes = array( 'acm-author-image', 'acm-author-image--' . ( '' !== $size ? $size : 'default' ) );
		$extra   = acm_sanitize_classes( $atts['class'] );

		if ( '' !== $extra ) {
			$classes[] = $extra;
		}

		$url   = $author->get_profile_url();
		$inner = $photo;

		if ( '' !== $url ) {
			$inner = '<a class="acm-author-image__link" href="' . esc_url( $url ) . '">' . $photo . '</a>';
		}

		return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . $inner . '</div>';
	}

	/**
	 * Render [custom_author_email].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_author_email( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => '',
				'slug'  => '',
				'link'  => 'true',
				'class' => '',
			),
			$atts,
			'custom_author_email'
		);

		$author = self::resolve_author( $atts['id'], $atts['slug'] );

		if ( ! $author ) {
			return '';
		}

		$email = $author->get_email();

		if ( '' === $email ) {
			return '';
		}

		$classes = array( 'acm-author-email' );
		$extra   = acm_sanitize_classes( $atts['class'] );

		if ( '' !== $extra ) {
			$classes[] = $extra;
		}

		$html = acm_to_bool( $atts['link'], true )
			? '<a class="acm-author-email__link" href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>'
			: esc_html( $email );

		return '<span class="' . esc_attr( implode( ' ', $classes ) ) . '">' . $html . '</span>';
	}

	/**
	 * Render [custom_author_field].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_author_field( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'         => '',
				'slug'       => '',
				'field'      => '',
				'show_label' => 'false',
				'class'      => '',
			),
			$atts,
			'custom_author_field'
		);

		$author = self::resolve_author( $atts['id'], $atts['slug'] );

		if ( ! $author ) {
			return '';
		}

		$definition = ACM_Custom_Fields::find_definition( ACM_Custom_Fields::author_definitions(), $atts['field'] );

		if ( ! $definition ) {
			return '';
		}

		$value = ACM_Custom_Fields::get_value( 'author', $author->get_id(), $definition['key'] );

		if ( ! ACM_Custom_Fields::has_value( $definition, $value ) ) {
			return '';
		}

		ACM_Assets::enqueue_frontend_assets();

		return ACM_Custom_Fields::render_single(
			array(
				'definition' => $definition,
				'value'      => $value,
			),
			array(
				'show_label' => acm_to_bool( $atts['show_label'], false ),
				'class'      => $atts['class'],
			)
		);
	}

	/**
	 * Render [custom_author_fields].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_author_fields( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'          => '',
				'slug'        => '',
				'show_labels' => 'true',
				'class'       => '',
			),
			$atts,
			'custom_author_fields'
		);

		$author = self::resolve_author( $atts['id'], $atts['slug'] );

		if ( ! $author ) {
			return '';
		}

		$items = ACM_Custom_Fields::author_items( $author->get_id(), false );

		if ( empty( $items ) ) {
			return '';
		}

		ACM_Assets::enqueue_frontend_assets();

		return ACM_Custom_Fields::render_list(
			$items,
			array(
				'context'     => 'author',
				'show_labels' => acm_to_bool( $atts['show_labels'], true ),
				'class'       => $atts['class'],
			)
		);
	}

	/**
	 * Resolve the post used by the post field shortcodes.
	 *
	 * @param string $post_id Post ID attribute.
	 * @return WP_Post|null
	 */
	private static function resolve_field_post( $post_id = '' ) {
		$post_id = absint( $post_id );

		if ( 0 === $post_id ) {
			$post_id = (int) get_the_ID();
		}

		if ( $post_id <= 0 ) {
			return null;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post_id ) ) {
			return null;
		}

		if ( ! ACM_Custom_Fields::post_type_has_fields( $post->post_type ) ) {
			return null;
		}

		return $post;
	}

	/**
	 * Render [acm_post_field].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_post_field( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id'    => '',
				'field'      => '',
				'show_label' => 'false',
				'image_size' => '',
				'link'       => 'true',
				'class'      => '',
			),
			$atts,
			'acm_post_field'
		);

		$post = self::resolve_field_post( $atts['post_id'] );

		if ( ! $post ) {
			return '';
		}

		$definition = ACM_Custom_Fields::find_definition( ACM_Custom_Fields::post_type_fields( $post->post_type ), $atts['field'] );

		if ( ! $definition ) {
			return '';
		}

		$value = ACM_Custom_Fields::get_value( 'post', $post->ID, $definition['key'] );

		if ( ! ACM_Custom_Fields::has_value( $definition, $value ) ) {
			return '';
		}

		ACM_Assets::enqueue_frontend_assets();

		return ACM_Custom_Fields::render_single(
			array(
				'definition' => $definition,
				'value'      => $value,
			),
			array(
				'show_label' => acm_to_bool( $atts['show_label'], false ),
				'image_size' => $atts['image_size'],
				'link'       => acm_to_bool( $atts['link'], true ),
				'post_type'  => $post->post_type,
				'class'      => $atts['class'],
			)
		);
	}

	/**
	 * Render [acm_post_fields].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_post_fields( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id'     => '',
				'show_labels' => 'true',
				'image_size'  => '',
				'title'       => '',
				'exclude'     => '',
				'class'       => '',
			),
			$atts,
			'acm_post_fields'
		);

		$post = self::resolve_field_post( $atts['post_id'] );

		if ( ! $post ) {
			return '';
		}

		$items = ACM_Custom_Fields::post_items( $post->ID );

		if ( '' !== trim( (string) $atts['exclude'] ) ) {
			$excluded = array_filter( array_map( 'sanitize_key', preg_split( '/[\s,]+/', (string) $atts['exclude'] ) ) );

			$items = array_values(
				array_filter(
					$items,
					static function ( $item ) use ( $excluded ) {
						return ! in_array( $item['definition']['key'], $excluded, true );
					}
				)
			);
		}

		if ( empty( $items ) ) {
			return '';
		}

		ACM_Assets::enqueue_frontend_assets();

		return ACM_Custom_Fields::render_list(
			$items,
			array(
				'show_labels' => acm_to_bool( $atts['show_labels'], true ),
				'title'       => $atts['title'],
				'image_size'  => $atts['image_size'],
				'post_type'   => $post->post_type,
				'context'     => 'post',
				'class'       => $atts['class'],
			)
		);
	}

	/**
	 * Render [acm_post_image].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_post_image( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => '',
				'field'   => '',
				'size'    => '',
				'link'    => 'true',
				'class'   => '',
			),
			$atts,
			'acm_post_image'
		);

		$post = self::resolve_field_post( $atts['post_id'] );

		if ( ! $post ) {
			return '';
		}

		$definitions = ACM_Custom_Fields::post_type_fields( $post->post_type );
		$definition  = ACM_Custom_Fields::find_definition( $definitions, $atts['field'] );

		if ( ! $definition ) {
			foreach ( $definitions as $candidate ) {
				if ( isset( $candidate['type'] ) && 'image' === $candidate['type'] ) {
					$definition = $candidate;
					break;
				}
			}
		}

		if ( ! $definition || 'image' !== $definition['type'] ) {
			return '';
		}

		$value = ACM_Custom_Fields::get_value( 'post', $post->ID, $definition['key'] );

		if ( ! ACM_Custom_Fields::has_value( $definition, $value ) ) {
			return '';
		}

		ACM_Assets::enqueue_frontend_assets();

		return ACM_Custom_Fields::render_single(
			array(
				'definition' => $definition,
				'value'      => $value,
			),
			array(
				'image_size' => $atts['size'],
				'link'       => acm_to_bool( $atts['link'], true ),
				'post_type'  => $post->post_type,
				'class'      => $atts['class'],
			)
		);
	}

	/**
	 * Query author profiles from shortcode style arguments.
	 *
	 * @param array $args Raw arguments.
	 * @return ACM_Author[]
	 */
	public static function get_authors_from_args( $args ) {
		$args = wp_parse_args(
			is_array( $args ) ? $args : array(),
			array(
				'ids'            => '',
				'taxonomy'       => '',
				'term'           => '',
				'post_id'        => 0,
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$ids = self::parse_ids( $args['ids'] );

		if ( '' !== trim( (string) ( is_array( $args['ids'] ) ? implode( ',', $args['ids'] ) : $args['ids'] ) ) && empty( $ids ) ) {
			return array();
		}

		$post_id = absint( $args['post_id'] );

		if ( $post_id > 0 ) {
			$assigned = ACM_Author_Manager::get_assigned_ids( $post_id );

			$ids = empty( $ids ) ? $assigned : array_values( array_intersect( $ids, $assigned ) );

			if ( empty( $ids ) ) {
				return array();
			}
		}

		$orderby = strtolower( sanitize_key( (string) $args['orderby'] ) );
		$allowed = array(
			'title'      => 'title',
			'name'       => 'title',
			'date'       => 'date',
			'modified'   => 'modified',
			'id'         => 'ID',
			'menu_order' => 'menu_order',
			'rand'       => 'rand',
		);

		$orderby = isset( $allowed[ $orderby ] ) ? $allowed[ $orderby ] : 'title';

		$order = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';

		$per_page = $args['posts_per_page'];
		if ( '-1' === (string) $per_page || -1 === (int) $per_page ) {
			$per_page = -1;
		} else {
			$per_page = max( 1, absint( $per_page ) );
		}

		$query_args = array(
			'post_type'              => ACM_POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => $per_page,
			'orderby'                => $orderby,
			'order'                  => $order,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => ! empty( $args['no_found_rows'] ),
			'update_post_term_cache' => false,
			'update_post_meta_cache' => true,
		);

		if ( ! empty( $ids ) ) {
			$query_args['post__in'] = $ids;
		}

		$taxonomy = sanitize_key( (string) $args['taxonomy'] );
		$term     = trim( (string) $args['term'] );

		if ( '' !== $taxonomy || '' !== $term ) {
			if ( '' === $taxonomy || '' === $term || ! taxonomy_exists( $taxonomy ) ) {
				return array();
			}

			$field       = is_numeric( $term ) ? 'term_id' : 'slug';
			$term_value  = is_numeric( $term ) ? absint( $term ) : sanitize_title( $term );
			$term_object = get_term_by( $field, $term_value, $taxonomy );

			if ( ! $term_object ) {
				return array();
			}

			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for the documented taxonomy filter.
				array(
					'taxonomy'         => $taxonomy,
					'field'            => $field,
					'terms'            => $term_value,
					'include_children' => true,
				),
			);

			$query_args['update_post_term_cache'] = true;
		}

		/**
		 * Filter the WP_Query arguments used to list authors.
		 *
		 * @param array $query_args Query arguments.
		 * @param array $args       Raw shortcode arguments.
		 */
		$query_args = apply_filters( 'acm_authors_query_args', $query_args, $args );

		$query   = new WP_Query( $query_args );
		$authors = array();

		foreach ( (array) $query->posts as $post ) {
			$author = ACM_Author::get( $post );

			if ( $author && $author->is_published() ) {
				$authors[] = $author;
			}
		}

		return $authors;
	}

	/**
	 * Parse a comma or space separated list of author IDs.
	 *
	 * @param mixed $ids Raw list.
	 * @return array
	 */
	private static function parse_ids( $ids ) {
		if ( is_array( $ids ) ) {
			$ids = implode( ',', $ids );
		}

		$ids = preg_split( '/[\s,]+/', trim( (string) $ids ) );
		$ids = is_array( $ids ) ? $ids : array();

		$output = array();

		foreach ( $ids as $id ) {
			$id = absint( $id );

			if ( $id > 0 && ! in_array( $id, $output, true ) ) {
				$output[] = $id;
			}
		}

		return $output;
	}
}
