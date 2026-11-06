<?php
/**
 * Asset loading.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues plugin styles and scripts only where they are needed.
 */
class ACM_Assets {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_frontend' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ), 10 );
	}

	/**
	 * Frontend asset detection.
	 *
	 * @return void
	 */
	public function maybe_enqueue_frontend() {
		if ( self::content_uses_shortcodes() || is_singular( ACM_POST_TYPE ) || is_post_type_archive( ACM_POST_TYPE ) ) {
			self::enqueue_frontend_assets();

			return;
		}

		$post = get_post();

		if ( $post instanceof WP_Post && is_singular() ) {
			$display = ACM_Custom_Fields::post_type_display( $post->post_type );

			if ( 'auto' === $display['display'] && ACM_Custom_Fields::post_type_has_fields( $post->post_type ) ) {
				self::enqueue_frontend_assets();
			}
		}
	}

	/**
	 * Whether the current singular content contains one of the plugin shortcodes.
	 *
	 * @return bool
	 */
	private static function content_uses_shortcodes() {
		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$tags = array(
			'custom_author',
			'custom_authors',
			'custom_author_name',
			'custom_author_image',
			'custom_author_email',
			'custom_author_field',
			'custom_author_fields',
			'acm_post_field',
			'acm_post_fields',
			'acm_post_image',
			'acm_posts',
		);

		foreach ( $tags as $tag ) {
			if ( has_shortcode( (string) $post->post_content, $tag ) ) {
				return true;
			}
		}

		if ( function_exists( 'has_block' ) && has_block( 'core/shortcode', $post ) ) {
			foreach ( $tags as $tag ) {
				if ( false !== strpos( (string) $post->post_content, '[' . $tag ) ) {
					return true;
				}
			}
		}

		return (bool) apply_filters( 'acm_needs_frontend_assets', false );
	}

	/**
	 * Enqueue the frontend stylesheet and script.
	 *
	 * Safe to call from inside a shortcode after wp_head has already run;
	 * WordPress prints late styles in the footer.
	 *
	 * @return void
	 */
	public static function enqueue_frontend_assets() {
		if ( ACM_Settings::get( 'enable_css', 1 ) && ! wp_style_is( 'acm-frontend', 'enqueued' ) ) {
			wp_enqueue_style( 'acm-frontend', ACM_URL . 'public/css/frontend.css', array(), ACM_VERSION );
		}

		/**
		 * Filter whether the small frontend enhancement script is loaded.
		 *
		 * @param bool $enqueue Whether to enqueue the script.
		 */
		if ( apply_filters( 'acm_enqueue_frontend_js', true ) && ! wp_script_is( 'acm-frontend', 'enqueued' ) ) {
			wp_enqueue_script( 'acm-frontend', ACM_URL . 'public/js/frontend.js', array(), ACM_VERSION, true );
		}
	}

	/**
	 * Enqueue admin assets on plugin screens and author editors.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$is_author_screen = ACM_POST_TYPE === $screen->post_type;
		$is_plugin_screen = ACM_Admin::is_plugin_screen( $hook );

		if ( ! $is_author_screen && ! $is_plugin_screen ) {
			return;
		}

		wp_enqueue_style( 'acm-admin', ACM_URL . 'admin/css/admin.css', array(), ACM_VERSION );
		wp_enqueue_script( 'acm-admin', ACM_URL . 'admin/js/admin.js', array( 'jquery' ), ACM_VERSION, true );

		wp_localize_script(
			'acm-admin',
			'acmAdmin',
			array(
				'mediaTitle'     => __( 'Select profile picture', 'author-content-manager' ),
				'mediaButton'    => __( 'Use this image', 'author-content-manager' ),
				'mediaEmpty'     => __( 'No image selected', 'author-content-manager' ),
				'copyLabel'      => __( 'Copy', 'author-content-manager' ),
				'copiedLabel'    => __( 'Copied!', 'author-content-manager' ),
				'copyErrorLabel' => __( 'Press Ctrl+C to copy', 'author-content-manager' ),
				'confirmDelete'  => __( 'Delete this configuration? Existing content will be kept.', 'author-content-manager' ),
				'confirmReset'   => __( 'Reset every plugin setting to its default value?', 'author-content-manager' ),
				'confirmTrash'   => __( 'Move this author profile to the Trash?', 'author-content-manager' ),
			)
		);

		if ( 'post' === $screen->base && ( $is_author_screen || ACM_Custom_Fields::post_type_has_fields( $screen->post_type ) ) ) {
			wp_enqueue_media();
		}
	}
}
