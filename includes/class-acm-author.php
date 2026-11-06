<?php
/**
 * Author profile data model.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read-only view over a single author profile.
 *
 * Every getter returns an empty string when no value is stored so that
 * templates can decide whether to render a field or skip it entirely.
 */
class ACM_Author {

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	private $id = 0;

	/**
	 * Post object.
	 *
	 * @var WP_Post|null
	 */
	private $post = null;

	/**
	 * Meta cache for this author.
	 *
	 * @var array
	 */
	private $meta = array();

	/**
	 * Constructor.
	 *
	 * @param int|WP_Post $author Author post ID or object.
	 */
	public function __construct( $author ) {
		$post = get_post( $author );

		if ( $post instanceof WP_Post ) {
			$this->post = $post;
			$this->id   = (int) $post->ID;
		} elseif ( is_numeric( $author ) ) {
			$this->id = (int) $author;
		}
	}

	/**
	 * Build an author object when the post really is an author profile.
	 *
	 * @param int|WP_Post $author Author post ID or object.
	 * @return ACM_Author|null
	 */
	public static function get( $author ) {
		$instance = new self( $author );

		if ( ! $instance->exists() || ACM_POST_TYPE !== $instance->post->post_type ) {
			return null;
		}

		return $instance;
	}

	/**
	 * Whether the underlying post exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return $this->post instanceof WP_Post && $this->id > 0;
	}

	/**
	 * Author post ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Underlying post object.
	 *
	 * @return WP_Post|null
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Whether the profile is published.
	 *
	 * @return bool
	 */
	public function is_published() {
		return $this->post instanceof WP_Post && 'publish' === $this->post->post_status;
	}

	/**
	 * Read a stored field.
	 *
	 * @param string $key Field key without the meta prefix.
	 * @return string
	 */
	public function get_field( $key ) {
		$key = sanitize_key( $key );

		if ( '' === $key || ! $this->exists() ) {
			return '';
		}

		if ( ! array_key_exists( $key, $this->meta ) ) {
			$value = get_post_meta( $this->id, ACM_Author_Fields::meta_key( $key ), true );

			$this->meta[ $key ] = is_scalar( $value ) ? trim( (string) $value ) : '';
		}

		return $this->meta[ $key ];
	}

	/**
	 * Display name.
	 *
	 * @return string
	 */
	public function get_name() {
		$name = $this->get_field( 'name' );

		if ( '' !== $name ) {
			return $name;
		}

		return $this->post instanceof WP_Post ? trim( (string) $this->post->post_title ) : '';
	}

	/**
	 * Post title, always present even when a display name is set.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->post instanceof WP_Post ? trim( (string) $this->post->post_title ) : '';
	}

	/**
	 * Email address.
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->get_field( 'email' );
	}

	/**
	 * Profile picture attachment ID.
	 *
	 * @return int
	 */
	public function get_photo_id() {
		return absint( $this->get_field( 'photo_id' ) );
	}

	/**
	 * Whether a usable profile picture is attached.
	 *
	 * @return bool
	 */
	public function has_photo() {
		$id = $this->get_photo_id();

		return $id > 0 && wp_attachment_is_image( $id );
	}

	/**
	 * Responsive image markup for the profile picture.
	 *
	 * @param string $size Requested image size.
	 * @param array  $attr Additional image attributes.
	 * @return string Empty string when no image is available.
	 */
	public function get_photo_html( $size = '', $attr = array() ) {
		if ( ! $this->has_photo() ) {
			return '';
		}

		$size = $this->normalize_size( $size );

		$attr = wp_parse_args(
			is_array( $attr ) ? $attr : array(),
			array(
				'class'    => 'acm-author__photo-img',
				'alt'      => $this->get_name(),
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);

		if ( '' === $attr['alt'] ) {
			$attr['alt'] = $this->get_name();
		}

		$html = wp_get_attachment_image( $this->get_photo_id(), $size, false, $attr );

		/**
		 * Filter the author photo markup.
		 *
		 * @param string     $html   Image markup.
		 * @param ACM_Author $author Author object.
		 * @param string     $size   Image size.
		 */
		return (string) apply_filters( 'acm_author_photo_html', $html, $this, $size );
	}

	/**
	 * Resolve an image size to a registered one.
	 *
	 * @param string $size Requested size.
	 * @return string
	 */
	private function normalize_size( $size ) {
		$size    = sanitize_key( (string) $size );
		$allowed = ACM_Settings::available_image_sizes();

		if ( '' !== $size && in_array( $size, $allowed, true ) ) {
			return $size;
		}

		$default = sanitize_key( (string) ACM_Settings::get( 'image_size', 'medium' ) );

		return in_array( $default, $allowed, true ) ? $default : 'medium';
	}

	/**
	 * Full biography as safe HTML.
	 *
	 * Falls back to the excerpt when the biography field is empty.
	 *
	 * @return string
	 */
	public function get_bio_html() {
		if ( ! $this->exists() ) {
			return '';
		}

		$bio = $this->get_field( 'bio' );

		if ( '' !== $bio ) {
			return wpautop( wp_kses_post( $bio ) );
		}

		$excerpt = $this->post instanceof WP_Post ? trim( (string) $this->post->post_excerpt ) : '';

		if ( '' !== $excerpt ) {
			return wpautop( esc_html( $excerpt ) );
		}

		return '';
	}

	/**
	 * Short biography used by cards and grids.
	 *
	 * @param int|null $limit Character limit. Defaults to the plugin setting.
	 * @return string Plain text, never empty HTML.
	 */
	public function get_bio_short( $limit = null ) {
		$source = $this->get_field( 'bio' );

		if ( '' === $source ) {
			$source = $this->post instanceof WP_Post ? (string) $this->post->post_excerpt : '';
		}

		if ( '' === $source ) {
			return '';
		}

		if ( null === $limit ) {
			$limit = (int) ACM_Settings::get( 'bio_limit', 160 );
		}

		$source = wp_strip_all_tags( $source );
		$source = wp_specialchars_decode( $source, ENT_QUOTES );

		$normalized = preg_replace( '/\s+/u', ' ', $source );
		$source     = null === $normalized ? preg_replace( '/\s+/', ' ', $source ) : $normalized;
		$source     = trim( (string) $source );

		return self::truncate( $source, (int) $limit );
	}

	/**
	 * Truncate plain text at a word boundary.
	 *
	 * @param string $text  Text.
	 * @param int    $limit Maximum characters. Zero or less disables the limit.
	 * @return string
	 */
	public static function truncate( $text, $limit ) {
		$text = (string) $text;

		if ( $limit <= 0 || '' === $text ) {
			return $text;
		}

		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $text ) <= $limit ) {
				return $text;
			}

			$cut = mb_substr( $text, 0, $limit );
		} elseif ( preg_match( '/^.{0,' . (int) $limit . '}/us', $text, $matches ) ) {
			if ( strlen( $matches[0] ) >= strlen( $text ) ) {
				return $text;
			}

			$cut = $matches[0];
		} else {
			if ( strlen( $text ) <= $limit ) {
				return $text;
			}

			$cut = substr( $text, 0, $limit );
		}

		$pos = strrpos( $cut, ' ' );

		if ( false !== $pos && $pos > 0 ) {
			$cut = substr( $cut, 0, $pos );
		}

		return rtrim( $cut ) . '…';
	}

	/**
	 * LinkedIn profile URL.
	 *
	 * @return string
	 */
	public function get_linkedin() {
		return $this->get_field( 'linkedin' );
	}

	/**
	 * Phone number.
	 *
	 * @return string
	 */
	public function get_phone() {
		return $this->get_field( 'phone' );
	}

	/**
	 * Mobile number.
	 *
	 * @return string
	 */
	public function get_mobile() {
		return $this->get_field( 'mobile' );
	}

	/**
	 * Website URL.
	 *
	 * @return string
	 */
	public function get_website() {
		return $this->get_field( 'website' );
	}

	/**
	 * Street address.
	 *
	 * @return string
	 */
	public function get_address() {
		return $this->get_field( 'address' );
	}

	/**
	 * City.
	 *
	 * @return string
	 */
	public function get_city() {
		return $this->get_field( 'city' );
	}

	/**
	 * Country.
	 *
	 * @return string
	 */
	public function get_country() {
		return $this->get_field( 'country' );
	}

	/**
	 * Additional contact information.
	 *
	 * @return string
	 */
	public function get_contact_extra() {
		return $this->get_field( 'contact_extra' );
	}

	/**
	 * Custom profile fields that contain a value.
	 *
	 * @param bool $only_visible Whether to only return fields shown on the profile.
	 * @return array
	 */
	public function get_custom_fields( $only_visible = true ) {
		if ( ! $this->exists() ) {
			return array();
		}

		return ACM_Custom_Fields::author_items( $this->id, (bool) $only_visible );
	}

	/**
	 * Read a single custom profile field value.
	 *
	 * @param string $key Field key.
	 * @return string
	 */
	public function get_custom_field( $key ) {
		if ( ! $this->exists() ) {
			return '';
		}

		return ACM_Custom_Fields::get_value( 'author', $this->id, $key );
	}

	/**
	 * Contact fields that actually contain a value.
	 *
	 * @return array List of field arrays with key, label, value and type.
	 */
	public function get_contact_items() {
		if ( ! $this->exists() || ! ACM_Settings::get( 'enable_contact', 1 ) ) {
			return array();
		}

		$items = array(
			array(
				'key'   => 'email',
				'label' => __( 'Email', 'author-content-manager' ),
				'value' => $this->get_email(),
				'type'  => 'email',
			),
			array(
				'key'   => 'phone',
				'label' => __( 'Phone', 'author-content-manager' ),
				'value' => $this->get_phone(),
				'type'  => 'tel',
			),
			array(
				'key'   => 'mobile',
				'label' => __( 'Mobile', 'author-content-manager' ),
				'value' => $this->get_mobile(),
				'type'  => 'tel',
			),
			array(
				'key'   => 'website',
				'label' => __( 'Website', 'author-content-manager' ),
				'value' => $this->get_website(),
				'type'  => 'url',
			),
			array(
				'key'   => 'address',
				'label' => __( 'Address', 'author-content-manager' ),
				'value' => $this->get_address(),
				'type'  => 'address',
			),
			array(
				'key'   => 'city',
				'label' => __( 'City', 'author-content-manager' ),
				'value' => $this->get_city(),
				'type'  => 'text',
			),
			array(
				'key'   => 'country',
				'label' => __( 'Country', 'author-content-manager' ),
				'value' => $this->get_country(),
				'type'  => 'text',
			),
			array(
				'key'   => 'contact_extra',
				'label' => __( 'Additional Information', 'author-content-manager' ),
				'value' => $this->get_contact_extra(),
				'type'  => 'address',
			),
		);

		$items = array_values(
			array_filter(
				$items,
				static function ( $item ) {
					return isset( $item['value'] ) && '' !== trim( (string) $item['value'] );
				}
			)
		);

		/**
		 * Filter the contact rows displayed for an author.
		 *
		 * @param array      $items  Contact rows.
		 * @param ACM_Author $author Author object.
		 */
		return apply_filters( 'acm_author_contact_items', $items, $this );
	}

	/**
	 * Social profiles that actually contain a value.
	 *
	 * @return array List of link arrays with key, label and url.
	 */
	public function get_social_links() {
		if ( ! $this->exists() || ! ACM_Settings::get( 'enable_social', 1 ) ) {
			return array();
		}

		$links = array(
			array(
				'key'   => 'linkedin',
				'label' => __( 'LinkedIn', 'author-content-manager' ),
				'url'   => $this->get_linkedin(),
			),
		);

		$links = array_values(
			array_filter(
				$links,
				static function ( $link ) {
					return isset( $link['url'] ) && '' !== trim( (string) $link['url'] );
				}
			)
		);

		/**
		 * Filter the social links displayed for an author.
		 *
		 * @param array      $links  Social links.
		 * @param ACM_Author $author Author object.
		 */
		return apply_filters( 'acm_author_social_links', $links, $this );
	}

	/**
	 * Public profile permalink.
	 *
	 * @return string Empty string for unpublished profiles.
	 */
	public function get_profile_url() {
		if ( ! $this->exists() || ! $this->is_published() ) {
			return '';
		}

		return (string) get_permalink( $this->id );
	}
}
