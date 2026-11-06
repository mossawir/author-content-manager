<?php
/**
 * Plugin bootstrap and hook wiring.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin controller.
 *
 * A single instance wires every module to the appropriate WordPress hook.
 * Modules themselves stay stateless where possible so that they can be reused
 * from templates, shortcodes and REST callbacks.
 */
final class ACM_Plugin {

	/**
	 * Shared instance.
	 *
	 * @var ACM_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin module.
	 *
	 * @var ACM_Admin|null
	 */
	public $admin = null;

	/**
	 * Frontend module.
	 *
	 * @var ACM_Frontend|null
	 */
	public $frontend = null;

	/**
	 * Assets module.
	 *
	 * @var ACM_Assets|null
	 */
	public $assets = null;

	/**
	 * Shortcodes module.
	 *
	 * @var ACM_Shortcodes|null
	 */
	public $shortcodes = null;

	/**
	 * Retrieve the shared instance.
	 *
	 * @return ACM_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Whether the plugin has already booted.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Constructor. Private to enforce the singleton.
	 */
	private function __construct() {}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function run() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$this->admin      = new ACM_Admin();
		$this->assets     = new ACM_Assets();
		$this->shortcodes = new ACM_Shortcodes();
		$this->frontend   = new ACM_Frontend();

		$this->admin->hooks();
		$this->assets->hooks();
		$this->shortcodes->hooks();
		$this->frontend->hooks();

		ACM_Author_Manager::hooks();
		ACM_Author_Fields::hooks();
		ACM_Custom_Fields::hooks();

		add_action( 'init', array( $this, 'load_textdomain' ), 1 );
		add_action( 'init', array( $this, 'register_content_types' ), 9 );
		add_action( 'init', array( $this, 'flush_rewrites_if_needed' ), 99 );

		add_filter( 'plugin_action_links_' . ACM_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'author-content-manager',
			false,
			dirname( ACM_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register every content type owned by the plugin.
	 *
	 * Custom post types are registered first so custom taxonomies can attach to
	 * them. Both run on `init` so that other plugins and themes can filter them.
	 *
	 * @return void
	 */
	public function register_content_types() {
		ACM_Author_Manager::register_post_type();
		ACM_Post_Type_Registry::register();
		ACM_Taxonomy_Registry::register();
	}

	/**
	 * Flush rewrite rules once after activation or a configuration change.
	 *
	 * Deferring the flush to a later `init` guarantees that newly created post
	 * types and taxonomies are part of the generated rule set.
	 *
	 * @return void
	 */
	public function flush_rewrites_if_needed() {
		$version = get_option( 'acm_version' );

		if ( $version !== ACM_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'acm_version', ACM_VERSION, false );
			delete_option( ACM_OPTION_REWRITE );

			return;
		}

		if ( ! get_option( ACM_OPTION_REWRITE ) ) {
			return;
		}

		flush_rewrite_rules( false );
		delete_option( ACM_OPTION_REWRITE );
	}

	/**
	 * Flag the rewrite rules for regeneration on the next request.
	 *
	 * @return void
	 */
	public static function mark_rewrite_dirty() {
		update_option( ACM_OPTION_REWRITE, 1, false );
	}

	/**
	 * Add a settings shortcut to the plugin row.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( ACM_Admin::page_url( 'acm-settings' ) ),
			esc_html__( 'Settings', 'author-content-manager' )
		);

		array_unshift( $links, $settings );

		return $links;
	}

	/**
	 * Activation routine.
	 *
	 * Settings are merged with defaults on demand, so activation only has to
	 * make sure rewrite rules are regenerated and the version is recorded.
	 *
	 * @return void
	 */
	public static function activate() {
		ACM_Taxonomy_Registry::maybe_create_defaults();

		if ( false === get_option( ACM_OPTION_SETTINGS ) ) {
			add_option( ACM_OPTION_SETTINGS, ACM_Settings::defaults() );
		}

		if ( false === get_option( ACM_OPTION_POST_TYPES ) ) {
			add_option( ACM_OPTION_POST_TYPES, array() );
		}

		if ( false === get_option( ACM_OPTION_TAXONOMIES ) ) {
			add_option( ACM_OPTION_TAXONOMIES, array() );
		}

		update_option( 'acm_version', ACM_VERSION, false );
		self::mark_rewrite_dirty();
	}

	/**
	 * Deactivation routine.
	 *
	 * Content is never touched on deactivation. Rewrite rules are flushed so the
	 * permalinks of the no longer registered content types stop resolving.
	 *
	 * @return void
	 */
	public static function deactivate() {
		foreach ( array_keys( ACM_Taxonomy_Registry::get_all() ) as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) && function_exists( 'unregister_taxonomy' ) ) {
				unregister_taxonomy( $taxonomy );
			}
		}

		foreach ( array_keys( ACM_Post_Type_Registry::get_all() ) as $post_type ) {
			if ( post_type_exists( $post_type ) && function_exists( 'unregister_post_type' ) ) {
				unregister_post_type( $post_type );
			}
		}

		if ( post_type_exists( ACM_POST_TYPE ) && function_exists( 'unregister_post_type' ) ) {
			unregister_post_type( ACM_POST_TYPE );
		}

		delete_option( ACM_OPTION_REWRITE );
		flush_rewrite_rules( false );
	}

	/**
	 * Prevent cloning of the singleton.
	 *
	 * @return void
	 */
	private function __clone() {}
}
