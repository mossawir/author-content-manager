<?php
/**
 * Shortcode display for custom post types.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Queries and renders posts of a plugin post type in grid or list layouts.
 */
class ACM_Post_Display {

	/**
	 * Shortcode callback shared by [acm_posts] and every per-type shortcode.
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string       $content Enclosed content.
	 * @param string       $tag     Shortcode tag.
	 * @return string
	 */
	public static function shortcode( $atts, $content = '', $tag = '' ) {
		$atts = is_array( $atts ) ? $atts : array();
		$tag  = (string) $tag;

		if ( 0 === strpos( $tag, 'acm_posts_' ) ) {
			$post_type = sanitize_key( substr( $tag, strlen( 'acm_posts_' ) ) );
		} else {
			$post_type = isset( $atts['post_type'] ) ? sanitize_key( $atts['post_type'] ) : '';
		}

		if ( '' === $post_type && is_post_type_archive() ) {
			$queried = get_queried_object();

			if ( $queried && isset( $queried->name ) && ACM_Post_Type_Registry::get( $queried->name ) ) {
				$post_type = sanitize_key( $queried->name );
			}
		}

		if ( '' === $post_type && is_singular() ) {
			$post = get_post();

			if ( $post instanceof WP_Post && ACM_Post_Type_Registry::get( $post->post_type ) ) {
				$post_type = $post->post_type;
			}
		}

		if ( '' === $post_type ) {
			return '';
		}

		return self::render( $post_type, $atts );
	}

	/**
	 * Default shortcode arguments for a post type.
	 *
	 * @param string $post_type Post type key.
	 * @return array
	 */
	public static function defaults( $post_type ) {
		$config = ACM_Post_Type_Registry::get( $post_type );
		$config = wp_parse_args( is_array( $config ) ? $config : array(), ACM_Post_Type_Registry::defaults() );

		return array(
			'post_type'       => $post_type,
			'layout'          => $config['shortcode_layout'],
			'columns'         => $config['shortcode_columns'],
			'posts_per_page'  => $config['shortcode_per_page'],
			'orderby'         => $config['shortcode_orderby'],
			'order'           => $config['shortcode_order'],
			'category'        => '',
			'taxonomy'        => '',
			'term'            => '',
			'ids'             => '',
			'offset'          => 0,
			'image_size'      => $config['shortcode_image_size'],
			'show_image'      => $config['shortcode_show_image'],
			'show_excerpt'    => $config['shortcode_show_excerpt'],
			'show_date'       => $config['shortcode_show_date'],
			'show_terms'      => $config['shortcode_show_terms'],
			'show_read_more'  => $config['shortcode_show_read_more'],
			'excerpt_length'  => $config['shortcode_excerpt_length'],
			'title'           => '',
			'class'           => '',
		);
	}

	/**
	 * Render posts for a post type.
	 *
	 * @param string $post_type Post type key.
	 * @param array  $atts      Shortcode attributes.
	 * @return string
	 */
	public static function render( $post_type, $atts = array() ) {
		$config = ACM_Post_Type_Registry::get( $post_type );

		if ( ! is_array( $config ) || empty( $config['active'] ) || ! post_type_exists( $post_type ) ) {
			return '';
		}

		$config = wp_parse_args( $config, ACM_Post_Type_Registry::defaults() );
		$atts   = shortcode_atts( self::defaults( $post_type ), is_array( $atts ) ? $atts : array(), 'acm_posts' );

		$layout = 'list' === strtolower( (string) $atts['layout'] ) ? 'list' : 'grid';
		$columns = min( 6, max( 1, absint( $atts['columns'] ) ) );

		$show_image      = acm_to_bool( $atts['show_image'], true );
		$show_excerpt    = acm_to_bool( $atts['show_excerpt'], true );
		$show_date       = acm_to_bool( $atts['show_date'], true );
		$show_terms      = acm_to_bool( $atts['show_terms'], true );
		$show_read_more  = acm_to_bool( $atts['show_read_more'], true );
		$excerpt_length  = min( 100, absint( $atts['excerpt_length'] ) );

		$per_page = (int) $atts['posts_per_page'];

		if ( -1 !== $per_page ) {
			$per_page = min( 100, max( 1, absint( $per_page ) ) );
		}

		$orderby_map = array(
			'date'       => 'date',
			'title'      => 'title',
			'menu_order' => 'menu_order',
			'modified'   => 'modified',
			'rand'       => 'rand',
			'id'         => 'ID',
		);

		$orderby = strtolower( sanitize_key( $atts['orderby'] ) );
		$orderby = isset( $orderby_map[ $orderby ] ) ? $orderby_map[ $orderby ] : 'date';
		$order   = 'ASC' === strtoupper( (string) $atts['order'] ) ? 'ASC' : 'DESC';

		$taxonomy = sanitize_key( $atts['taxonomy'] );

		if ( '' === $taxonomy ) {
			$taxonomy = sanitize_key( $config['shortcode_category_taxonomy'] );
		}

		$filter_value = trim( (string) ( '' !== (string) $atts['term'] ? $atts['term'] : $atts['category'] ) );

		if ( '' === $taxonomy && '' !== $filter_value ) {
			$attached = get_object_taxonomies( $post_type );

			if ( in_array( 'category', $attached, true ) ) {
				$taxonomy = 'category';
			} elseif ( ! empty( $attached ) ) {
				$taxonomy = (string) reset( $attached );
			}
		}

		if ( '' !== $taxonomy && ! taxonomy_exists( $taxonomy ) ) {
			$taxonomy = '';
		}

		if ( '' !== $filter_value && '' === $taxonomy ) {
			return '';
		}

		$query_args = array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => $per_page,
			'orderby'                => $orderby,
			'order'                  => $order,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => $show_terms || '' !== $taxonomy,
			'update_post_meta_cache' => true,
		);

		$offset = absint( $atts['offset'] );

		if ( $offset > 0 ) {
			$query_args['offset'] = $offset;
		}

		$ids = self::parse_ids( $atts['ids'] );

		if ( '' !== trim( (string) $atts['ids'] ) && empty( $ids ) ) {
			return '';
		}

		if ( ! empty( $ids ) ) {
			$query_args['post__in'] = $ids;
		}

		if ( '' !== $filter_value && '' !== $taxonomy ) {
			$terms = self::resolve_terms( $taxonomy, $filter_value );

			if ( empty( $terms ) ) {
				return '';
			}

			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for the documented category filter.
				array(
					'taxonomy'         => $taxonomy,
					'field'            => 'term_id',
					'terms'            => $terms,
					'include_children' => true,
				),
			);
		}

		if ( '' === $taxonomy && $show_terms ) {
			$attached = get_object_taxonomies( $post_type );
			$attached = array_values( array_diff( $attached, array( 'post_format' ) ) );

			if ( ! empty( $attached ) ) {
				$taxonomy = (string) reset( $attached );
			}
		}

		/**
		 * Filter the WP_Query arguments used by post type shortcodes.
		 *
		 * @param array  $query_args Query arguments.
		 * @param string $post_type  Post type key.
		 * @param array  $atts       Shortcode attributes.
		 */
		$query_args = apply_filters( 'acm_post_shortcode_query_args', $query_args, $post_type, $atts );

		$query = new WP_Query( $query_args );

		if ( empty( $query->posts ) ) {
			return '';
		}

		$image_size = self::resolve_image_size( $atts['image_size'], $config );

		$template_args = array(
			'posts'          => $query->posts,
			'layout'         => $layout,
			'columns'        => $columns,
			'taxonomy'       => $taxonomy,
			'image_size'     => $image_size,
			'show_image'     => $show_image,
			'show_excerpt'   => $show_excerpt,
			'show_date'      => $show_date,
			'show_terms'     => $show_terms,
			'show_read_more' => $show_read_more,
			'excerpt_length' => $excerpt_length,
			'title'          => sanitize_text_field( (string) $atts['title'] ),
			'class'          => $atts['class'],
			'post_type'      => $post_type,
		);

		/**
		 * Filter the template arguments used by post type shortcodes.
		 *
		 * @param array  $template_args Template arguments.
		 * @param string $post_type     Post type key.
		 * @param array  $atts          Shortcode attributes.
		 */
		$template_args = apply_filters( 'acm_post_shortcode_template_args', $template_args, $post_type, $atts );

		ACM_Assets::enqueue_frontend_assets();

		return ACM_Templates::render( 'post-' . $layout . '.php', $template_args );
	}

	/**
	 * Parse a comma separated list of post IDs.
	 *
	 * @param mixed $ids Raw IDs.
	 * @return array
	 */
	private static function parse_ids( $ids ) {
		if ( is_array( $ids ) ) {
			$ids = implode( ',', $ids );
		}

		$parts  = preg_split( '/[\s,]+/', trim( (string) $ids ) );
		$output = array();

		foreach ( (array) $parts as $id ) {
			$id = absint( $id );

			if ( $id > 0 && ! in_array( $id, $output, true ) ) {
				$output[] = $id;
			}
		}

		return $output;
	}

	/**
	 * Resolve comma separated term slugs or IDs to term IDs.
	 *
	 * @param string $taxonomy Taxonomy key.
	 * @param string $value    Raw term list.
	 * @return array
	 */
	private static function resolve_terms( $taxonomy, $value ) {
		$parts = preg_split( '/[\s,]+/', trim( (string) $value ) );
		$terms = array();

		foreach ( (array) $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}

			$term = is_numeric( $part ) ? get_term_by( 'term_id', absint( $part ), $taxonomy ) : get_term_by( 'slug', sanitize_title( $part ), $taxonomy );

			if ( $term && ! is_wp_error( $term ) ) {
				$terms[] = (int) $term->term_id;
			}
		}

		return array_values( array_unique( $terms ) );
	}

	/**
	 * Resolve the image size for a shortcode.
	 *
	 * @param string $requested Requested size.
	 * @param array  $config    Post type configuration.
	 * @return string
	 */
	private static function resolve_image_size( $requested, $config ) {
		$allowed = ACM_Settings::available_image_sizes();

		foreach ( array( sanitize_key( (string) $requested ), sanitize_key( (string) $config['shortcode_image_size'] ), sanitize_key( (string) ACM_Settings::get( 'image_size', 'medium' ) ) ) as $size ) {
			if ( '' !== $size && in_array( $size, $allowed, true ) ) {
				return $size;
			}
		}

		return 'medium';
	}

	/**
	 * Shortcode parameter documentation shared by admin screens.
	 *
	 * @return array
	 */
	public static function attribute_docs() {
		return array(
			'post_type'      => __( 'Post type key. Required for [acm_posts]; pre-filled by every per-type shortcode.', 'author-content-manager' ),
			'layout'         => __( 'grid or list.', 'author-content-manager' ),
			'columns'        => __( 'Grid column count from 1 to 6. Ignored by the list layout.', 'author-content-manager' ),
			'posts_per_page' => __( 'Number of posts to show, or -1 for all. Defaults to the post type setting.', 'author-content-manager' ),
			'orderby'        => __( 'date, title, menu_order, modified, rand or ID.', 'author-content-manager' ),
			'order'          => __( 'ASC or DESC.', 'author-content-manager' ),
			'category'       => __( 'Comma separated category slugs or IDs using the selected category taxonomy.', 'author-content-manager' ),
			'taxonomy'       => __( 'Optional taxonomy override for the category/term filter and term display.', 'author-content-manager' ),
			'term'           => __( 'Comma separated term slugs or IDs. Takes precedence over category.', 'author-content-manager' ),
			'ids'            => __( 'Comma separated post IDs to display specific posts.', 'author-content-manager' ),
			'offset'         => __( 'Number of posts to skip.', 'author-content-manager' ),
			'image_size'     => __( 'A registered image size for featured images.', 'author-content-manager' ),
			'show_image'     => __( 'true or false.', 'author-content-manager' ),
			'show_excerpt'   => __( 'true or false.', 'author-content-manager' ),
			'show_date'      => __( 'true or false.', 'author-content-manager' ),
			'show_terms'     => __( 'true or false.', 'author-content-manager' ),
			'show_read_more' => __( 'true or false.', 'author-content-manager' ),
			'excerpt_length' => __( 'Excerpt length in words. Use 0 for the full excerpt.', 'author-content-manager' ),
			'title'          => __( 'Optional heading above the posts.', 'author-content-manager' ),
			'class'          => __( 'Extra CSS classes on the wrapper.', 'author-content-manager' ),
		);
	}
}
