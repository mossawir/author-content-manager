<?php
/**
 * Frontend integration.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the author profile to single author pages, provides the archive
 * template for classic themes and prints the custom CSS.
 */
class ACM_Frontend {

	/**
	 * Author profiles already printed in this request.
	 *
	 * @var array
	 */
	private static $rendered = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_filter( 'the_content', array( $this, 'append_author_profile' ), 20 );
		add_filter( 'template_include', array( $this, 'archive_template' ), 99 );
		add_action( 'wp_head', array( $this, 'print_custom_css' ), 99 );
	}

	/**
	 * Append the author profile to the single author content.
	 *
	 * Using the content filter keeps the theme header, footer, sidebar and
	 * block templates intact on every theme.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append_author_profile( $content ) {
		if ( is_admin() || ! is_singular( ACM_POST_TYPE ) ) {
			return $content;
		}

		$post_id = (int) get_the_ID();

		if ( $post_id <= 0 || $post_id !== (int) get_queried_object_id() || isset( self::$rendered[ $post_id ] ) ) {
			return $content;
		}

		$author = ACM_Author::get( $post_id );

		if ( ! $author ) {
			return $content;
		}

		$profile = ACM_Templates::profile(
			$author,
			array(
				'layout'       => 'vertical',
				'show_image'   => true,
				'show_bio'     => true,
				'show_contact' => true,
				'show_social'  => true,
				'show_name'    => true,
				'context'      => 'single',
			)
		);

		if ( '' === $profile ) {
			return $content;
		}

		self::$rendered[ $post_id ] = true;

		ACM_Assets::enqueue_frontend_assets();

		return $content . $profile;
	}

	/**
	 * Use the plugin archive template for classic themes.
	 *
	 * Block themes keep their own archive template. A theme can override the
	 * markup by creating archive-acm_author.php or
	 * author-content-manager/archive-author.php.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function archive_template( $template ) {
		if ( ! is_post_type_archive( ACM_POST_TYPE ) ) {
			return $template;
		}

		/**
		 * Filter whether the plugin archive template is used.
		 *
		 * @param bool   $use      Whether to use the plugin template.
		 * @param string $template Resolved template path.
		 */
		if ( ! apply_filters( 'acm_use_plugin_archive_template', true, $template ) ) {
			return $template;
		}

		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return $template;
		}

		$theme_template = locate_template( array( 'archive-' . ACM_POST_TYPE . '.php' ) );

		if ( '' !== $theme_template ) {
			return $theme_template;
		}

		$plugin_template = ACM_Templates::locate( 'archive-author.php' );

		return '' !== $plugin_template ? $plugin_template : $template;
	}

	/**
	 * Print the administrator's custom CSS.
	 *
	 * @return void
	 */
	public function print_custom_css() {
		$css = trim( (string) ACM_Settings::get( 'custom_css', '' ) );

		/**
		 * Filter the custom CSS printed on the frontend.
		 *
		 * @param string $css Custom CSS.
		 */
		$css = (string) apply_filters( 'acm_custom_css', $css );

		if ( '' === $css ) {
			return;
		}

		echo "\n<style id=\"acm-custom-css\">\n" . $css . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is sanitized when saved and must not be HTML escaped.
	}
}
