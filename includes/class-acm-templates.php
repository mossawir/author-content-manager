<?php
/**
 * Template loading and rendering.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Locates and renders frontend templates, allowing theme overrides.
 */
class ACM_Templates {

	/**
	 * Theme sub directory used for overrides.
	 */
	const THEME_DIR = 'author-content-manager';

	/**
	 * Default arguments for an individual profile.
	 *
	 * @return array
	 */
	public static function profile_defaults() {
		return array(
			'layout'       => 'vertical',
			'show_image'   => true,
			'show_bio'     => true,
			'show_contact' => true,
			'show_social'  => true,
			'show_name'    => true,
			'show_custom'  => true,
			'image_size'   => '',
			'class'        => '',
			'context'      => 'shortcode',
		);
	}

	/**
	 * Default arguments for author cards and grids.
	 *
	 * @return array
	 */
	public static function card_defaults() {
		return array(
			'show_image'   => true,
			'show_bio'     => true,
			'show_contact' => false,
			'show_social'  => true,
			'show_link'    => true,
			'image_size'   => '',
			'bio_limit'    => null,
			'class'        => '',
		);
	}

	/**
	 * Normalize profile arguments.
	 *
	 * @param array $args Raw arguments.
	 * @return array
	 */
	public static function profile_args( $args ) {
		$args = wp_parse_args( is_array( $args ) ? $args : array(), self::profile_defaults() );

		$args['layout']       = in_array( $args['layout'], array( 'vertical', 'horizontal' ), true ) ? $args['layout'] : 'vertical';
		$args['show_image']   = acm_to_bool( $args['show_image'], true );
		$args['show_bio']     = acm_to_bool( $args['show_bio'], true );
		$args['show_contact'] = acm_to_bool( $args['show_contact'], true );
		$args['show_social']  = acm_to_bool( $args['show_social'], true );
		$args['show_name']    = acm_to_bool( $args['show_name'], true );
		$args['show_custom']  = acm_to_bool( $args['show_custom'], true );
		$args['image_size']   = sanitize_key( (string) $args['image_size'] );
		$args['class']        = acm_sanitize_classes( $args['class'] );
		$args['context']      = sanitize_key( (string) $args['context'] );

		if ( '' === $args['image_size'] ) {
			$args['image_size'] = sanitize_key( (string) ACM_Settings::get( 'image_size', 'medium' ) );
		}

		/**
		 * Filter the arguments used by the individual author template.
		 *
		 * @param array $args Template arguments.
		 */
		return apply_filters( 'acm_profile_template_args', $args );
	}

	/**
	 * Normalize card arguments.
	 *
	 * @param array $args Raw arguments.
	 * @return array
	 */
	public static function card_args( $args ) {
		$args = wp_parse_args( is_array( $args ) ? $args : array(), self::card_defaults() );

		$args['show_image']   = acm_to_bool( $args['show_image'], true );
		$args['show_bio']     = acm_to_bool( $args['show_bio'], true );
		$args['show_contact'] = acm_to_bool( $args['show_contact'], false );
		$args['show_social']  = acm_to_bool( $args['show_social'], true );
		$args['show_link']    = acm_to_bool( $args['show_link'], true );
		$args['image_size']   = sanitize_key( (string) $args['image_size'] );
		$args['class']        = acm_sanitize_classes( $args['class'] );

		if ( '' === $args['image_size'] ) {
			$args['image_size'] = sanitize_key( (string) ACM_Settings::get( 'image_size', 'medium' ) );
		}

		if ( null !== $args['bio_limit'] ) {
			$args['bio_limit'] = (int) $args['bio_limit'];
		}

		/**
		 * Filter the arguments used by the author card template.
		 *
		 * @param array $args Template arguments.
		 */
		return apply_filters( 'acm_card_template_args', $args );
	}

	/**
	 * Render an individual author profile.
	 *
	 * @param ACM_Author $author Author object.
	 * @param array      $args   Template arguments.
	 * @return string
	 */
	public static function profile( $author, $args = array() ) {
		if ( ! $author instanceof ACM_Author || ! $author->exists() ) {
			return '';
		}

		$args           = self::profile_args( $args );
		$args['author'] = $author;

		return self::render( 'author-profile.php', $args );
	}

	/**
	 * Render a grid of author cards.
	 *
	 * @param ACM_Author[] $authors Author objects.
	 * @param array        $args    Template arguments.
	 * @return string Empty string when there is nothing to display.
	 */
	public static function grid( $authors, $args = array() ) {
		if ( empty( $authors ) || ! is_array( $authors ) ) {
			return '';
		}

		$args            = self::card_args( $args );
		$args['authors'] = array_values( array_filter( $authors, array( __CLASS__, 'is_author' ) ) );
		$args['columns'] = isset( $args['columns'] ) ? (int) $args['columns'] : 0;

		if ( empty( $args['authors'] ) ) {
			return '';
		}

		if ( $args['columns'] < 1 || $args['columns'] > 6 ) {
			$args['columns'] = (int) ACM_Settings::get( 'grid_columns', 3 );
			$args['columns'] = min( 6, max( 1, $args['columns'] ) );
		}

		return self::render( 'author-grid.php', $args );
	}

	/**
	 * Filter callback that keeps only valid author objects.
	 *
	 * @param mixed $author Value to check.
	 * @return bool
	 */
	public static function is_author( $author ) {
		return $author instanceof ACM_Author && $author->exists();
	}

	/**
	 * Locate a template, preferring a theme override.
	 *
	 * @param string $template Template file name.
	 * @return string Absolute path or empty string when missing.
	 */
	public static function locate( $template ) {
		$template = basename( (string) $template );

		if ( ! preg_match( '/^[a-z0-9\-]+\.php$/', $template ) ) {
			return '';
		}

		$theme = locate_template( array( self::THEME_DIR . '/' . $template ) );
		$path  = '' !== $theme ? $theme : ACM_DIR . 'public/templates/' . $template;

		if ( ! is_readable( $path ) ) {
			return '';
		}

		/**
		 * Filter the resolved template path.
		 *
		 * @param string $path     Absolute template path.
		 * @param string $template Template file name.
		 */
		return (string) apply_filters( 'acm_template_path', $path, $template );
	}

	/**
	 * Render a template and return its markup.
	 *
	 * @param string $template Template file name.
	 * @param array  $args     Variables exposed to the template as $args.
	 * @return string
	 */
	public static function render( $template, $args = array() ) {
		$path = self::locate( $template );

		if ( '' === $path ) {
			return '';
		}

		$args = is_array( $args ) ? $args : array();

		ob_start();
		require $path;

		return (string) ob_get_clean();
	}

	/**
	 * Inline SVG icon used by the templates.
	 *
	 * Markup is static and contains no user data.
	 *
	 * @param string $name Icon name.
	 * @return string
	 */
	public static function icon( $name ) {
		$icons = array(
			'linkedin' => '<svg class="acm-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.95v5.66H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>',
			'email'    => '<svg class="acm-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5z"/></svg>',
			'phone'    => '<svg class="acm-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.2.4 2.4.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.4 0 .8-.2 1z"/></svg>',
			'website'  => '<svg class="acm-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm6.9 6h-2.6a15 15 0 0 0-1.3-3.4A8 8 0 0 1 18.9 8zM12 4.1c.7 1 1.3 2.4 1.7 3.9h-3.4c.4-1.5 1-2.9 1.7-3.9zM4.3 14a8 8 0 0 1 0-4h3a19 19 0 0 0 0 4zm1 2h2.5c.3 1.2.7 2.4 1.3 3.4A8 8 0 0 1 5.3 16zm2.5-8H5.3a8 8 0 0 1 3.8-3.4A15 15 0 0 0 7.8 8zM12 19.9c-.7-1-1.3-2.4-1.7-3.9h3.4c-.4 1.5-1 2.9-1.7 3.9zm2-5.9h-4a17 17 0 0 1 0-4h4a17 17 0 0 1 0 4zm.9 5.4c.6-1 1-2.2 1.3-3.4h2.5a8 8 0 0 1-3.8 3.4zm1.7-5.4a19 19 0 0 0 0-4h3a8 8 0 0 1 0 4z"/></svg>',
			'location' => '<svg class="acm-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5.3 7 13 7 13s7-7.7 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg>',
		);

		/**
		 * Filter the available template icons.
		 *
		 * @param array $icons Icon name => SVG markup.
		 */
		$icons = apply_filters( 'acm_template_icons', $icons );

		return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
	}

	/**
	 * Build the markup for a contact row value.
	 *
	 * @param array $item Contact item with type and value.
	 * @return string Escaped HTML.
	 */
	public static function contact_value_html( $item ) {
		$type  = isset( $item['type'] ) ? $item['type'] : 'text';
		$value = isset( $item['value'] ) ? (string) $item['value'] : '';

		if ( '' === $value ) {
			return '';
		}

		switch ( $type ) {
			case 'email':
				return '<a href="' . esc_url( 'mailto:' . $value ) . '">' . esc_html( $value ) . '</a>';

			case 'url':
				return '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener noreferrer nofollow">' . esc_html( $value ) . '</a>';

			case 'tel':
				$tel = preg_replace( '/[^0-9+]/', '', $value );

				if ( '' === $tel ) {
					return esc_html( $value );
				}

				return '<a href="' . esc_url( 'tel:' . $tel ) . '">' . esc_html( $value ) . '</a>';

			case 'address':
				return nl2br( esc_html( $value ) );

			default:
				return esc_html( $value );
		}
	}

	/**
	 * Icon name for a contact row.
	 *
	 * @param string $key Contact key.
	 * @return string
	 */
	public static function contact_icon( $key ) {
		$map = array(
			'email'   => 'email',
			'phone'   => 'phone',
			'mobile'  => 'phone',
			'website' => 'website',
			'address' => 'location',
			'city'    => 'location',
			'country' => 'location',
		);

		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}
}
