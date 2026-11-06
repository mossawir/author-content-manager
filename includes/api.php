<?php
/**
 * Public helper functions.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize a shortcode or setting boolean.
 *
 * @param mixed $value   Raw value.
 * @param bool  $default Value used when the input is not recognized.
 * @return bool
 */
function acm_to_bool( $value, $default = false ) {
	if ( is_bool( $value ) ) {
		return $value;
	}

	if ( is_int( $value ) || is_float( $value ) ) {
		return 0 !== (int) $value;
	}

	if ( ! is_string( $value ) ) {
		return $default;
	}

	$value = strtolower( trim( $value ) );

	if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
		return true;
	}

	if ( in_array( $value, array( '0', 'false', 'no', 'off', '' ), true ) ) {
		return false;
	}

	return $default;
}

/**
 * Sanitize a whitespace separated list of CSS classes.
 *
 * @param string|array $classes Raw classes.
 * @return string
 */
function acm_sanitize_classes( $classes ) {
	if ( is_array( $classes ) ) {
		$classes = implode( ' ', $classes );
	}

	$classes = preg_split( '/\s+/', trim( (string) $classes ) );
	$classes = is_array( $classes ) ? $classes : array();
	$output  = array();

	foreach ( $classes as $class ) {
		$class = sanitize_html_class( $class );

		if ( '' !== $class && ! in_array( $class, $output, true ) ) {
			$output[] = $class;
		}
	}

	return implode( ' ', $output );
}

/**
 * Retrieve an author profile object.
 *
 * @param int|WP_Post $author Author ID or post object.
 * @return ACM_Author|null
 */
function acm_get_author( $author ) {
	return ACM_Author::get( $author );
}

/**
 * Retrieve a stored author field.
 *
 * @param int    $author_id Author ID.
 * @param string $field     Field key, for example "email" or "linkedin".
 * @return string
 */
function acm_get_author_field( $author_id, $field ) {
	$author = ACM_Author::get( $author_id );

	return $author ? $author->get_field( $field ) : '';
}

/**
 * Retrieve author profile picture markup.
 *
 * @param int    $author_id Author ID.
 * @param string $size      Image size.
 * @param array  $attr      Extra image attributes.
 * @return string
 */
function acm_get_author_photo( $author_id, $size = '', $attr = array() ) {
	$author = ACM_Author::get( $author_id );

	return $author ? $author->get_photo_html( $size, $attr ) : '';
}

/**
 * Authors assigned to a post through the author assignment meta box.
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return ACM_Author[]
 */
function acm_get_assigned_authors( $post_id = 0 ) {
	$post_id = $post_id > 0 ? (int) $post_id : (int) get_the_ID();

	if ( $post_id <= 0 ) {
		return array();
	}

	return ACM_Author_Manager::get_assigned_authors( $post_id );
}

/**
 * Query author profiles.
 *
 * Supported arguments: ids, taxonomy, term, post_id, posts_per_page, orderby,
 * order and no_found_rows.
 *
 * @param array $args Query arguments.
 * @return ACM_Author[]
 */
function acm_get_authors( $args = array() ) {
	return ACM_Shortcodes::get_authors_from_args( is_array( $args ) ? $args : array() );
}

/**
 * Custom fields of an author profile.
 *
 * @param int  $author_id    Author ID.
 * @param bool $only_visible Whether to only include fields shown on the profile.
 * @return array List of arrays with definition and value keys.
 */
function acm_get_author_custom_fields( $author_id, $only_visible = true ) {
	return ACM_Custom_Fields::author_items( (int) $author_id, (bool) $only_visible );
}

/**
 * One custom field value of an author profile.
 *
 * @param int    $author_id Author ID.
 * @param string $key       Field key.
 * @return string
 */
function acm_get_author_custom_field( $author_id, $key ) {
	return ACM_Custom_Fields::get_value( 'author', (int) $author_id, $key );
}

/**
 * Custom fields of a post.
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return array List of arrays with definition and value keys.
 */
function acm_get_post_fields( $post_id = 0 ) {
	$post_id = $post_id > 0 ? (int) $post_id : (int) get_the_ID();

	return $post_id > 0 ? ACM_Custom_Fields::post_items( $post_id ) : array();
}

/**
 * One custom field value of a post.
 *
 * @param int    $post_id Post ID. Defaults to the current post.
 * @param string $key     Field key.
 * @return string
 */
function acm_get_post_field( $post_id = 0, $key = '' ) {
	$post_id = $post_id > 0 ? (int) $post_id : (int) get_the_ID();

	return $post_id > 0 ? ACM_Custom_Fields::get_value( 'post', $post_id, $key ) : '';
}
