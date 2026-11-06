<?php
/**
 * Plugin settings.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads, validates and stores the plugin settings.
 */
class ACM_Settings {

	/**
	 * Option name.
	 */
	const OPTION = ACM_OPTION_SETTINGS;

	/**
	 * Cached settings for the current request.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Validation notices produced while sanitizing.
	 *
	 * @var array
	 */
	private static $notices = array();

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		$defaults = array(
			'enable_css'        => 1,
			'enable_social'     => 1,
			'enable_contact'    => 1,
			'image_size'        => 'medium',
			'custom_field_image_size' => 'medium',
			'grid_columns'      => 3,
			'default_layout'    => 'vertical',
			'bio_limit'         => 160,
			'author_archive'    => 1,
			'author_base'       => 'authors',
			'assign_post_types' => array( 'post' ),
			'custom_css'        => '',
			'delete_data'       => 0,
		);

		/**
		 * Filter the plugin default settings.
		 *
		 * @param array $defaults Default settings.
		 */
		return apply_filters( 'acm_default_settings', $defaults );
	}

	/**
	 * Return every setting merged with the defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$stored = get_option( self::OPTION, array() );
			$stored = is_array( $stored ) ? $stored : array();

			self::$cache = wp_parse_args( $stored, self::defaults() );
		}

		return self::$cache;
	}

	/**
	 * Return a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback used when the key does not exist.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$settings = self::all();

		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

	/**
	 * Persist a full settings array.
	 *
	 * @param array $settings Settings to store.
	 * @return array Stored settings.
	 */
	public static function update( $settings ) {
		$previous = self::all();
		$settings = self::sanitize( $settings );

		update_option( self::OPTION, $settings );
		self::$cache = null;

		$current = self::all();

		if ( (string) $previous['author_base'] !== (string) $current['author_base'] || (int) $previous['author_archive'] !== (int) $current['author_archive'] ) {
			ACM_Plugin::mark_rewrite_dirty();
		}

		return $current;
	}

	/**
	 * Restore the default settings.
	 *
	 * @return array
	 */
	public static function reset() {
		$previous = self::all();
		$defaults = self::defaults();

		update_option( self::OPTION, $defaults );
		self::$cache = null;

		if ( (string) $previous['author_base'] !== (string) $defaults['author_base'] || (int) $previous['author_archive'] !== (int) $defaults['author_archive'] ) {
			ACM_Plugin::mark_rewrite_dirty();
		}

		return $defaults;
	}

	/**
	 * Validate and sanitize a settings array.
	 *
	 * Unknown keys are dropped so that stale options cannot linger.
	 *
	 * @param array $input Raw settings.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$output   = array();

		self::$notices = array();

		$output['enable_css']     = empty( $input['enable_css'] ) ? 0 : 1;
		$output['enable_social']  = empty( $input['enable_social'] ) ? 0 : 1;
		$output['enable_contact'] = empty( $input['enable_contact'] ) ? 0 : 1;
		$output['author_archive'] = empty( $input['author_archive'] ) ? 0 : 1;
		$output['delete_data']    = empty( $input['delete_data'] ) ? 0 : 1;

		$sizes              = self::available_image_sizes();
		$requested_size     = isset( $input['image_size'] ) ? sanitize_key( $input['image_size'] ) : '';
		$output['image_size'] = in_array( $requested_size, $sizes, true ) ? $requested_size : $defaults['image_size'];

		if ( '' !== $requested_size && ! in_array( $requested_size, $sizes, true ) ) {
			self::$notices[] = __( 'The selected image size is not registered. The default size was restored.', 'author-content-manager' );
		}

		$custom_size                        = isset( $input['custom_field_image_size'] ) ? sanitize_key( $input['custom_field_image_size'] ) : '';
		$output['custom_field_image_size'] = in_array( $custom_size, $sizes, true ) ? $custom_size : $defaults['custom_field_image_size'];

		$columns                = isset( $input['grid_columns'] ) ? absint( $input['grid_columns'] ) : $defaults['grid_columns'];
		$output['grid_columns'] = min( 6, max( 1, $columns ) );

		$layout = isset( $input['default_layout'] ) ? sanitize_key( $input['default_layout'] ) : '';
		if ( ! in_array( $layout, array( 'vertical', 'horizontal' ), true ) ) {
			$layout = $defaults['default_layout'];
		}
		$output['default_layout'] = $layout;

		$bio_limit           = isset( $input['bio_limit'] ) ? absint( $input['bio_limit'] ) : $defaults['bio_limit'];
		$output['bio_limit'] = min( 2000, $bio_limit );

		$base                 = isset( $input['author_base'] ) ? $input['author_base'] : '';
		$output['author_base'] = self::sanitize_author_base( $base );

		if ( $output['author_base'] !== sanitize_title( (string) $base ) && '' !== trim( (string) $base ) ) {
			self::$notices[] = __( 'The author archive slug was invalid or reserved and has been reset to "authors".', 'author-content-manager' );
		}

		$assigned = array();
		if ( isset( $input['assign_post_types'] ) && is_array( $input['assign_post_types'] ) ) {
			foreach ( $input['assign_post_types'] as $post_type ) {
				$post_type = sanitize_key( $post_type );
				if ( '' !== $post_type ) {
					$assigned[] = $post_type;
				}
			}
		}
		$output['assign_post_types'] = array_values( array_intersect( array_unique( $assigned ), array_keys( self::assignable_post_types() ) ) );

		$output['custom_css'] = self::sanitize_css( isset( $input['custom_css'] ) ? $input['custom_css'] : '' );

		/**
		 * Filter sanitized settings.
		 *
		 * @param array $output Sanitized settings.
		 * @param array $input  Raw settings.
		 */
		return apply_filters( 'acm_sanitize_settings', $output, $input );
	}

	/**
	 * Notices produced by the most recent sanitize call.
	 *
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * Sanitize the author profile base slug.
	 *
	 * @param string $base Raw slug.
	 * @return string
	 */
	public static function sanitize_author_base( $base ) {
		$base = sanitize_title( (string) $base );
		$base = trim( $base, '-' );

		if ( '' === $base || strlen( $base ) > 60 ) {
			return 'authors';
		}

		$reserved = array(
			'author',
			'category',
			'tag',
			'search',
			'page',
			'feed',
			'embed',
			'comments',
			'attachment',
			'wp-admin',
			'wp-content',
			'wp-includes',
			'wp-json',
			'post',
			'type',
			'taxonomy',
			'term',
			'user',
			'users',
			'login',
			'logout',
			'admin',
			'register',
		);

		if ( in_array( $base, $reserved, true ) ) {
			return 'authors';
		}

		if ( post_type_exists( $base ) || taxonomy_exists( $base ) ) {
			return 'authors';
		}

		return $base;
	}

	/**
	 * Remove markup and style-closing sequences from custom CSS.
	 *
	 * @param string $css Raw CSS.
	 * @return string
	 */
	public static function sanitize_css( $css ) {
		$css = (string) $css;
		$css = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $css );
		$css = preg_replace( '#</?style\b[^>]*>#i', '', $css );
		$css = wp_strip_all_tags( $css );
		$css = str_replace( array( '<!--', '-->', '<style', '</style', '<script', '</script' ), '', $css );

		return trim( $css );
	}

	/**
	 * Image sizes available for author photos.
	 *
	 * @return array
	 */
	public static function available_image_sizes() {
		$sizes = get_intermediate_image_sizes();

		if ( ! in_array( 'full', $sizes, true ) ) {
			$sizes[] = 'full';
		}

		/**
		 * Filter the selectable author image sizes.
		 *
		 * @param array $sizes Registered image size names.
		 */
		return array_values( array_unique( apply_filters( 'acm_image_sizes', $sizes ) ) );
	}

	/**
	 * Post types that may be linked to an author profile.
	 *
	 * @return array Post type slug => label.
	 */
	public static function assignable_post_types() {
		$types  = get_post_types( array( 'public' => true ), 'objects' );
		$output = array();

		foreach ( $types as $slug => $object ) {
			if ( ACM_POST_TYPE === $slug || 'attachment' === $slug ) {
				continue;
			}

			$output[ $slug ] = isset( $object->labels->singular_name ) ? $object->labels->singular_name : $slug;
		}

		/**
		 * Filter the post types available for author assignment.
		 *
		 * @param array $output Post type slug => label.
		 */
		return apply_filters( 'acm_assignable_post_types', $output );
	}
}
