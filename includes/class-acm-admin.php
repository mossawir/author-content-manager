<?php
/**
 * Admin interface.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the admin menu, handles form submissions and renders the views.
 */
class ACM_Admin {

	/**
	 * Capability required to manage plugin configuration.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Menu slug of the top level page.
	 */
	const MENU_SLUG = 'acm-dashboard';

	/**
	 * Registered page hooks keyed by page slug.
	 *
	 * @var array
	 */
	public static $page_hooks = array();

	/**
	 * Validation errors from the current request.
	 *
	 * @var array
	 */
	private $form_errors = array();

	/**
	 * Submitted values kept when validation fails.
	 *
	 * @var array
	 */
	private $form_data = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
		add_filter( 'parent_file', array( $this, 'fix_parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'fix_submenu_file' ), 10, 2 );

		add_action( 'admin_post_acm_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_acm_reset_settings', array( $this, 'handle_reset_settings' ) );
		add_action( 'admin_post_acm_delete_post_type', array( $this, 'handle_delete_post_type' ) );
		add_action( 'admin_post_acm_toggle_post_type', array( $this, 'handle_toggle_post_type' ) );
		add_action( 'admin_post_acm_delete_taxonomy', array( $this, 'handle_delete_taxonomy' ) );
		add_action( 'admin_post_acm_toggle_taxonomy', array( $this, 'handle_toggle_taxonomy' ) );
		add_action( 'admin_post_acm_delete_author_field', array( $this, 'handle_delete_author_field' ) );
	}

	/**
	 * Capability required for plugin screens.
	 *
	 * @return string
	 */
	public static function capability() {
		/**
		 * Filter the capability required to manage the plugin.
		 *
		 * @param string $capability Capability name.
		 */
		return (string) apply_filters( 'acm_admin_capability', self::CAPABILITY );
	}

	/**
	 * Build the admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$capability = self::capability();

		$hook = add_menu_page(
			__( 'Author & Content Manager', 'author-content-manager' ),
			__( 'Author & Content', 'author-content-manager' ),
			$capability,
			self::MENU_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-groups',
			26
		);

		if ( is_string( $hook ) ) {
			self::$page_hooks[ self::MENU_SLUG ] = $hook;
		}

		$this->add_page( __( 'Dashboard', 'author-content-manager' ), __( 'Dashboard', 'author-content-manager' ), self::MENU_SLUG, $capability, array( $this, 'render_dashboard' ) );

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Authors', 'author-content-manager' ),
			__( 'Authors', 'author-content-manager' ),
			'edit_posts',
			'edit.php?post_type=' . ACM_POST_TYPE
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Add New Author', 'author-content-manager' ),
			__( 'Add New Author', 'author-content-manager' ),
			'edit_posts',
			'post-new.php?post_type=' . ACM_POST_TYPE
		);

		$this->add_page( __( 'Author Custom Fields', 'author-content-manager' ), __( 'Author Fields', 'author-content-manager' ), 'acm-author-fields', $capability, array( $this, 'render_author_fields' ) );
		$this->add_page( __( 'Add New Author Field', 'author-content-manager' ), __( 'Add New Field', 'author-content-manager' ), 'acm-author-field-edit', $capability, array( $this, 'render_author_field_edit' ) );

		$this->add_page( __( 'Post Types', 'author-content-manager' ), __( 'Post Types', 'author-content-manager' ), 'acm-post-types', $capability, array( $this, 'render_post_types' ) );
		$this->add_page( __( 'Add New Post Type', 'author-content-manager' ), __( 'Add New Post Type', 'author-content-manager' ), 'acm-post-type-edit', $capability, array( $this, 'render_post_type_edit' ) );
		$this->add_page( __( 'Taxonomies', 'author-content-manager' ), __( 'Taxonomies', 'author-content-manager' ), 'acm-taxonomies', $capability, array( $this, 'render_taxonomies' ) );
		$this->add_page( __( 'Add New Taxonomy', 'author-content-manager' ), __( 'Add New Taxonomy', 'author-content-manager' ), 'acm-taxonomy-edit', $capability, array( $this, 'render_taxonomy_edit' ) );
		$this->add_page( __( 'Settings', 'author-content-manager' ), __( 'Settings', 'author-content-manager' ), 'acm-settings', $capability, array( $this, 'render_settings' ) );
		$this->add_page( __( 'Documentation', 'author-content-manager' ), __( 'Documentation', 'author-content-manager' ), 'acm-documentation', $capability, array( $this, 'render_documentation' ) );
		$this->add_page( __( 'About', 'author-content-manager' ), __( 'About', 'author-content-manager' ), 'acm-about', $capability, array( $this, 'render_about' ) );

		foreach ( array( 'acm-post-type-edit' => 'handle_save_post_type', 'acm-taxonomy-edit' => 'handle_save_taxonomy', 'acm-author-field-edit' => 'handle_save_author_field' ) as $page => $handler ) {
			if ( isset( self::$page_hooks[ $page ] ) ) {
				add_action( 'load-' . self::$page_hooks[ $page ], array( $this, $handler ) );
			}
		}
	}

	/**
	 * Register a submenu page and remember its hook suffix.
	 *
	 * @param string   $page_title Page title.
	 * @param string   $menu_title Menu title.
	 * @param string   $slug       Menu slug.
	 * @param string   $capability Capability.
	 * @param callable $callback   Render callback.
	 * @return void
	 */
	private function add_page( $page_title, $menu_title, $slug, $capability, $callback ) {
		$hook = add_submenu_page( self::MENU_SLUG, $page_title, $menu_title, $capability, $slug, $callback );

		if ( is_string( $hook ) ) {
			self::$page_hooks[ $slug ] = $hook;
		}
	}

	/**
	 * Whether the current admin screen belongs to the plugin.
	 *
	 * @param string $hook Optional hook suffix.
	 * @return bool
	 */
	public static function is_plugin_screen( $hook = '' ) {
		if ( '' !== $hook && in_array( $hook, self::$page_hooks, true ) ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used to decide whether to load admin assets.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		return 0 === strpos( $page, 'acm-' );
	}

	/**
	 * Highlight the plugin menu while editing author profiles.
	 *
	 * @param string $parent_file Parent file.
	 * @return string
	 */
	public function fix_parent_file( $parent_file ) {
		$screen = get_current_screen();

		if ( $screen && ACM_POST_TYPE === $screen->post_type && in_array( $screen->base, array( 'edit', 'post' ), true ) ) {
			return self::MENU_SLUG;
		}

		return $parent_file;
	}

	/**
	 * Highlight the correct submenu entry.
	 *
	 * @param string $submenu_file Current submenu file.
	 * @param string $parent_file  Parent file.
	 * @return string
	 */
	public function fix_submenu_file( $submenu_file, $parent_file ) {
		if ( self::MENU_SLUG !== $parent_file ) {
			return $submenu_file;
		}

		$screen = get_current_screen();

		if ( $screen && ACM_POST_TYPE === $screen->post_type ) {
			return 'post' === $screen->base ? 'post-new.php?post_type=' . ACM_POST_TYPE : 'edit.php?post_type=' . ACM_POST_TYPE;
		}

		return $submenu_file;
	}

	/**
	 * Build an admin page URL.
	 *
	 * @param string $page Page slug.
	 * @param array  $args Extra query arguments.
	 * @return string
	 */
	public static function page_url( $page, $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $page ), is_array( $args ) ? $args : array() ), admin_url( 'admin.php' ) );
	}

	/**
	 * Add an admin notice that survives the redirect.
	 *
	 * @param string $type    Notice type: success, warning or error.
	 * @param string $message Message.
	 * @return void
	 */
	public static function add_notice( $type, $message ) {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$key     = 'acm_admin_notices_' . $user_id;
		$notices = get_transient( $key );

		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		$notices[] = array(
			'type'    => in_array( $type, array( 'success', 'warning', 'error', 'info' ), true ) ? $type : 'info',
			'message' => (string) $message,
		);

		set_transient( $key, $notices, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Print queued admin notices.
	 *
	 * @return void
	 */
	public function render_notices() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		if ( ! self::is_plugin_screen( (string) $screen->id ) && ACM_POST_TYPE !== $screen->post_type ) {
			return;
		}

		$key     = 'acm_admin_notices_' . get_current_user_id();
		$notices = get_transient( $key );

		if ( ! is_array( $notices ) || empty( $notices ) ) {
			return;
		}

		delete_transient( $key );

		foreach ( $notices as $notice ) {
			$type = isset( $notice['type'] ) ? $notice['type'] : 'info';
			$type = in_array( $type, array( 'success', 'warning', 'error', 'info' ), true ) ? $type : 'info';

			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $type ),
				esc_html( isset( $notice['message'] ) ? $notice['message'] : '' )
			);
		}
	}

	/**
	 * Redirect back to a plugin page.
	 *
	 * @param string $page Page slug.
	 * @param array  $args Extra query arguments.
	 * @return void
	 */
	private function redirect( $page, $args = array() ) {
		wp_safe_redirect( self::page_url( $page, $args ) );
		exit;
	}

	/**
	 * Render a view file.
	 *
	 * @param string $view View name without extension.
	 * @param array  $args Variables exposed to the view as $args.
	 * @return void
	 */
	private function render_view( $view, $args = array() ) {
		$view = str_replace( '\\', '/', (string) $view );

		if ( ! preg_match( '#^[a-z0-9_\-/]+$#i', $view ) || false !== strpos( $view, '..' ) ) {
			return;
		}

		$path = ACM_DIR . 'admin/views/' . $view . '.php';

		if ( ! is_readable( $path ) ) {
			return;
		}

		$args = is_array( $args ) ? $args : array();

		require $path;
	}

	/**
	 * Render an admin error list.
	 *
	 * @return string
	 */
	private function error_html() {
		if ( empty( $this->form_errors ) ) {
			return '';
		}

		$html = '<div class="notice notice-error"><ul class="ul-disc">';

		foreach ( $this->form_errors as $error ) {
			$html .= '<li>' . esc_html( $error ) . '</li>';
		}

		return $html . '</ul></div>';
	}

	/**
	 * Dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		$this->guard();

		$counts = wp_count_posts( ACM_POST_TYPE );
		$total  = 0;

		foreach ( (array) $counts as $status => $count ) {
			if ( in_array( $status, array( 'auto-draft', 'trash', 'inherit' ), true ) ) {
				continue;
			}

			$total += (int) $count;
		}

		$this->render_view(
			'dashboard',
			array(
				'total_authors'     => $total,
				'published_authors' => isset( $counts->publish ) ? (int) $counts->publish : 0,
				'post_type_count'   => count( ACM_Post_Type_Registry::get_all() ),
				'taxonomy_count'    => count( ACM_Taxonomy_Registry::get_all() ),
				'recent_authors'    => get_posts(
					array(
						'post_type'      => ACM_POST_TYPE,
						'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
						'posts_per_page' => 5,
						'orderby'        => 'date',
						'order'          => 'DESC',
					)
				),
				'post_types'        => ACM_Post_Type_Registry::get_all(),
				'taxonomies'        => ACM_Taxonomy_Registry::get_all(),
			)
		);
	}

	/**
	 * Author custom fields list page.
	 *
	 * @return void
	 */
	public function render_author_fields() {
		$this->guard();

		$this->render_view(
			'author-fields',
			array(
				'fields' => ACM_Custom_Fields::author_definitions(),
			)
		);
	}

	/**
	 * Author custom field add/edit page.
	 *
	 * @return void
	 */
	public function render_author_field_edit() {
		$this->guard();

		$key   = isset( $_GET['field'] ) ? sanitize_key( wp_unslash( $_GET['field'] ) ) : '';
		$field = null;

		foreach ( ACM_Custom_Fields::author_definitions() as $definition ) {
			if ( $definition['key'] === $key ) {
				$field = $definition;
				break;
			}
		}

		if ( '' !== $key && null === $field ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'That author field could not be found.', 'author-content-manager' ) . '</p></div></div>';

			return;
		}

		$is_new = null === $field;

		$field = wp_parse_args(
			is_array( $field ) ? $field : array(),
			array(
				'key'             => '',
				'label'           => '',
				'type'            => 'text',
				'description'     => '',
				'required'        => 0,
				'show_in_profile' => 1,
			)
		);

		if ( ! empty( $this->form_data ) ) {
			$field = array_merge( $field, $this->form_data );
		}

		$this->render_view(
			'author-field-edit',
			array(
				'field'         => $field,
				'original_key'  => $key,
				'is_new'        => $is_new,
				'error_html'    => $this->error_html(),
			)
		);
	}

	/**
	 * Save an author custom field.
	 *
	 * @return void
	 */
	public function handle_save_author_field() {
		if ( ! isset( $_POST['acm_action'] ) || 'save_author_field' !== $_POST['acm_action'] ) {
			return;
		}

		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'author-content-manager' ), 403 );
		}

		check_admin_referer( 'acm_save_author_field' );

		$raw      = isset( $_POST['acm_field'] ) && is_array( $_POST['acm_field'] ) ? wp_unslash( $_POST['acm_field'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by the field engine.
		$original = isset( $_POST['acm_original_field'] ) ? sanitize_key( wp_unslash( $_POST['acm_original_field'] ) ) : '';
		$existing = array();

		foreach ( ACM_Custom_Fields::author_definitions() as $definition ) {
			if ( $definition['key'] !== $original ) {
				$existing[] = $definition['key'];
			}
		}

		$result = ACM_Custom_Fields::sanitize_definition( $raw, 'author', $existing );

		if ( is_wp_error( $result ) ) {
			$this->form_errors = array( $result->get_error_message() );
			$this->form_data   = $this->normalize_author_field_form( $raw );

			return;
		}

		$definitions = ACM_Custom_Fields::author_definitions();
		$replaced    = false;

		foreach ( $definitions as $index => $definition ) {
			if ( $definition['key'] === $original ) {
				$definitions[ $index ] = $result;
				$replaced              = true;
				break;
			}
		}

		if ( ! $replaced ) {
			$definitions[] = $result;
		}

		ACM_Custom_Fields::set_author_definitions( $definitions );

		self::add_notice( 'success', __( 'Author custom field saved.', 'author-content-manager' ) );

		$this->redirect( 'acm-author-fields' );
	}

	/**
	 * Normalize submitted author field values for redisplay.
	 *
	 * @param array $raw Raw values.
	 * @return array
	 */
	private function normalize_author_field_form( $raw ) {
		return array(
			'label'           => isset( $raw['label'] ) && is_scalar( $raw['label'] ) ? (string) $raw['label'] : '',
			'key'             => isset( $raw['key'] ) && is_scalar( $raw['key'] ) ? (string) $raw['key'] : '',
			'type'            => isset( $raw['type'] ) && is_scalar( $raw['type'] ) ? (string) $raw['type'] : 'text',
			'description'     => isset( $raw['description'] ) && is_scalar( $raw['description'] ) ? (string) $raw['description'] : '',
			'required'        => empty( $raw['required'] ) ? 0 : 1,
			'show_in_profile' => empty( $raw['show_in_profile'] ) ? 0 : 1,
		);
	}

	/**
	 * Delete an author custom field definition.
	 *
	 * @return void
	 */
	public function handle_delete_author_field() {
		$this->guard_action( 'acm_delete_author_field' );

		$key = isset( $_GET['field'] ) ? sanitize_key( wp_unslash( $_GET['field'] ) ) : '';

		if ( '' === $key ) {
			self::add_notice( 'error', __( 'The author field could not be found.', 'author-content-manager' ) );
			$this->redirect( 'acm-author-fields' );
		}

		$definitions = ACM_Custom_Fields::author_definitions();
		$remaining   = array_values(
			array_filter(
				$definitions,
				static function ( $definition ) use ( $key ) {
					return $definition['key'] !== $key;
				}
			)
		);

		if ( count( $remaining ) === count( $definitions ) ) {
			self::add_notice( 'error', __( 'The author field could not be found.', 'author-content-manager' ) );
		} else {
			ACM_Custom_Fields::set_author_definitions( $remaining );
			self::add_notice( 'success', __( 'Author custom field deleted. Stored values were kept.', 'author-content-manager' ) );
		}

		$this->redirect( 'acm-author-fields' );
	}

	/**
	 * Post type list page.
	 *
	 * @return void
	 */
	public function render_post_types() {
		$this->guard();

		$items = array();

		foreach ( ACM_Post_Type_Registry::get_all() as $slug => $config ) {
			$items[ $slug ] = array(
				'config' => wp_parse_args( $config, ACM_Post_Type_Registry::defaults() ),
				'counts' => ACM_Post_Type_Registry::count_posts( $slug ),
			);
		}

		$this->render_view( 'post-types', array( 'items' => $items ) );
	}

	/**
	 * Post type add/edit page.
	 *
	 * @return void
	 */
	public function render_post_type_edit() {
		$this->guard();

		$slug   = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';
		$config = null;

		if ( '' !== $slug ) {
			$config = ACM_Post_Type_Registry::get( $slug );
		}

		if ( '' !== $slug && null === $config ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'That custom post type could not be found.', 'author-content-manager' ) . '</p></div></div>';

			return;
		}

		$is_new = null === $config;
		$config = wp_parse_args( is_array( $config ) ? $config : array(), ACM_Post_Type_Registry::defaults() );

		if ( ! empty( $this->form_data ) ) {
			$config = array_merge( $config, $this->form_data );
		}

		if ( $is_new && empty( $this->form_data ) ) {
			$config['active']   = 1;
			$config['supports'] = array( 'title', 'editor', 'thumbnail', 'excerpt' );
		}

		$this->render_view(
			'post-type-edit',
			array(
				'config'        => $config,
				'original_slug' => $slug,
				'is_new'        => $is_new,
				'error_html'    => $this->error_html(),
			)
		);
	}

	/**
	 * Taxonomy list page.
	 *
	 * @return void
	 */
	public function render_taxonomies() {
		$this->guard();

		$items = array();

		foreach ( ACM_Taxonomy_Registry::get_all() as $slug => $config ) {
			$items[ $slug ] = array(
				'config' => wp_parse_args( $config, ACM_Taxonomy_Registry::defaults() ),
				'count'  => ACM_Taxonomy_Registry::count_terms( $slug ),
			);
		}

		$this->render_view( 'taxonomies', array( 'items' => $items ) );
	}

	/**
	 * Taxonomy add/edit page.
	 *
	 * @return void
	 */
	public function render_taxonomy_edit() {
		$this->guard();

		$slug   = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';
		$config = null;

		if ( '' !== $slug ) {
			$config = ACM_Taxonomy_Registry::get( $slug );
		}

		if ( '' !== $slug && null === $config ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'That custom taxonomy could not be found.', 'author-content-manager' ) . '</p></div></div>';

			return;
		}

		$is_new = null === $config;
		$config = wp_parse_args( is_array( $config ) ? $config : array(), ACM_Taxonomy_Registry::defaults() );

		if ( ! empty( $this->form_data ) ) {
			$config = array_merge( $config, $this->form_data );
		} elseif ( $is_new ) {
			$available = array_keys( ACM_Taxonomy_Registry::available_post_types() );

			$config['post_types'] = array_values( array_intersect( array( 'post', ACM_POST_TYPE ), $available ) );
		}

		$this->render_view(
			'taxonomy-edit',
			array(
				'config'        => $config,
				'original_slug' => $slug,
				'is_new'        => $is_new,
				'error_html'    => $this->error_html(),
			)
		);
	}

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		$this->guard();

		$this->render_view(
			'settings',
			array(
				'settings' => ACM_Settings::all(),
				'sizes'    => ACM_Settings::available_image_sizes(),
				'post_types' => ACM_Settings::assignable_post_types(),
			)
		);
	}

	/**
	 * Documentation page.
	 *
	 * @return void
	 */
	public function render_documentation() {
		$this->guard();

		$this->render_view(
			'documentation',
			array(
				'shortcodes' => self::shortcode_docs(),
				'settings'   => ACM_Settings::all(),
			)
		);
	}

	/**
	 * About developer page.
	 *
	 * @return void
	 */
	public function render_about() {
		$this->guard();

		$this->render_view(
			'about',
			array(
				'version' => ACM_VERSION,
			)
		);
	}

	/**
	 * Abort when the current user cannot manage the plugin.
	 *
	 * @return void
	 */
	private function guard() {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'author-content-manager' ), 403 );
		}
	}

	/**
	 * Save a post type from the editor form.
	 *
	 * Runs on `load-{page}` so a redirect is still possible on success.
	 *
	 * @return void
	 */
	public function handle_save_post_type() {
		if ( ! isset( $_POST['acm_action'] ) || 'save_post_type' !== $_POST['acm_action'] ) {
			return;
		}

		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'author-content-manager' ), 403 );
		}

		check_admin_referer( 'acm_save_post_type' );

		$raw           = isset( $_POST['acm_post_type'] ) && is_array( $_POST['acm_post_type'] ) ? wp_unslash( $_POST['acm_post_type'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by the registry.
		$original_slug = isset( $_POST['acm_original_slug'] ) ? sanitize_key( wp_unslash( $_POST['acm_original_slug'] ) ) : '';

		$result = ACM_Post_Type_Registry::save( $raw, $original_slug );

		if ( is_wp_error( $result ) ) {
			$this->form_errors = array( $result->get_error_message() );
			$this->form_data   = $this->normalize_post_type_form( $raw );

			return;
		}

		if ( '' !== $original_slug && $original_slug !== $result ) {
			self::add_notice( 'warning', __( 'The post type key changed. Existing posts still use the old key, so re-create the post type with the old key if you need them listed again.', 'author-content-manager' ) );
		}

		self::add_notice( 'success', __( 'Custom post type saved.', 'author-content-manager' ) );

		$this->redirect( 'acm-post-types' );
	}

	/**
	 * Normalize submitted post type values for redisplay.
	 *
	 * @param array $raw Raw submitted values.
	 * @return array
	 */
	private function normalize_post_type_form( $raw ) {
		$defaults = ACM_Post_Type_Registry::defaults();
		$data     = array();

		foreach ( array( 'slug', 'plural', 'singular', 'description', 'menu_icon', 'menu_position', 'rewrite_slug' ) as $key ) {
			$data[ $key ] = isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? (string) $raw[ $key ] : '';
		}

		foreach ( array( 'public', 'publicly_queryable', 'show_in_rest', 'show_in_admin_menu', 'has_archive', 'hierarchical', 'active' ) as $key ) {
			$data[ $key ] = empty( $raw[ $key ] ) ? 0 : 1;
		}

		$data['supports'] = isset( $raw['supports'] ) && is_array( $raw['supports'] ) ? array_map( 'sanitize_key', $raw['supports'] ) : array();

		$data['fields'] = array();

		if ( isset( $raw['fields'] ) && is_array( $raw['fields'] ) ) {
			foreach ( $raw['fields'] as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				$data['fields'][] = array(
					'label'       => isset( $field['label'] ) && is_scalar( $field['label'] ) ? (string) $field['label'] : '',
					'key'         => isset( $field['key'] ) && is_scalar( $field['key'] ) ? (string) $field['key'] : '',
					'type'        => isset( $field['type'] ) && is_scalar( $field['type'] ) ? (string) $field['type'] : 'text',
					'description' => isset( $field['description'] ) && is_scalar( $field['description'] ) ? (string) $field['description'] : '',
					'required'    => empty( $field['required'] ) ? 0 : 1,
				);
			}
		}

		$data['field_display']     = isset( $raw['field_display'] ) && is_scalar( $raw['field_display'] ) ? (string) $raw['field_display'] : 'shortcode';
		$data['field_show_labels'] = array_key_exists( 'field_show_labels', $raw ) && empty( $raw['field_show_labels'] ) ? 0 : 1;
		$data['field_image_size']  = isset( $raw['field_image_size'] ) && is_scalar( $raw['field_image_size'] ) ? (string) $raw['field_image_size'] : '';

		foreach ( array( 'enable_categories', 'enable_tags', 'shortcode_show_image', 'shortcode_show_excerpt', 'shortcode_show_date', 'shortcode_show_terms', 'shortcode_show_read_more' ) as $boolean_key ) {
			$data[ $boolean_key ] = empty( $raw[ $boolean_key ] ) ? 0 : 1;
		}

		foreach ( array( 'shortcode_layout', 'shortcode_image_size', 'shortcode_orderby', 'shortcode_order', 'shortcode_category_taxonomy' ) as $text_key ) {
			$data[ $text_key ] = isset( $raw[ $text_key ] ) && is_scalar( $raw[ $text_key ] ) ? (string) $raw[ $text_key ] : '';
		}

		foreach ( array( 'shortcode_columns', 'shortcode_per_page', 'shortcode_excerpt_length' ) as $number_key ) {
			$data[ $number_key ] = isset( $raw[ $number_key ] ) ? absint( $raw[ $number_key ] ) : 0;
		}

		if ( '' === $data['menu_icon'] ) {
			$data['menu_icon'] = $defaults['menu_icon'];
		}

		return $data;
	}

	/**
	 * Save a taxonomy from the editor form.
	 *
	 * @return void
	 */
	public function handle_save_taxonomy() {
		if ( ! isset( $_POST['acm_action'] ) || 'save_taxonomy' !== $_POST['acm_action'] ) {
			return;
		}

		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'author-content-manager' ), 403 );
		}

		check_admin_referer( 'acm_save_taxonomy' );

		$raw           = isset( $_POST['acm_taxonomy'] ) && is_array( $_POST['acm_taxonomy'] ) ? wp_unslash( $_POST['acm_taxonomy'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by the registry.
		$original_slug = isset( $_POST['acm_original_slug'] ) ? sanitize_key( wp_unslash( $_POST['acm_original_slug'] ) ) : '';

		$result = ACM_Taxonomy_Registry::save( $raw, $original_slug );

		if ( is_wp_error( $result ) ) {
			$this->form_errors = array( $result->get_error_message() );
			$this->form_data   = $this->normalize_taxonomy_form( $raw );

			return;
		}

		self::add_notice( 'success', __( 'Custom taxonomy saved.', 'author-content-manager' ) );

		$this->redirect( 'acm-taxonomies' );
	}

	/**
	 * Normalize submitted taxonomy values for redisplay.
	 *
	 * @param array $raw Raw submitted values.
	 * @return array
	 */
	private function normalize_taxonomy_form( $raw ) {
		$data = array();

		foreach ( array( 'slug', 'plural', 'singular', 'description', 'rewrite_slug' ) as $key ) {
			$data[ $key ] = isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? (string) $raw[ $key ] : '';
		}

		foreach ( array( 'hierarchical', 'public', 'show_in_rest', 'show_admin_column', 'show_in_nav_menus', 'active' ) as $key ) {
			$data[ $key ] = empty( $raw[ $key ] ) ? 0 : 1;
		}

		$data['post_types'] = isset( $raw['post_types'] ) && is_array( $raw['post_types'] ) ? array_map( 'sanitize_key', $raw['post_types'] ) : array();

		return $data;
	}

	/**
	 * Delete a post type configuration.
	 *
	 * @return void
	 */
	public function handle_delete_post_type() {
		$this->guard_action( 'acm_delete_post_type' );

		$slug = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';

		if ( '' === $slug || ! ACM_Post_Type_Registry::get( $slug ) ) {
			self::add_notice( 'error', __( 'The custom post type could not be found.', 'author-content-manager' ) );
			$this->redirect( 'acm-post-types' );
		}

		ACM_Post_Type_Registry::delete( $slug );

		self::add_notice( 'success', __( 'Custom post type deleted. Its posts were not deleted.', 'author-content-manager' ) );

		$this->redirect( 'acm-post-types' );
	}

	/**
	 * Activate or deactivate a post type.
	 *
	 * @return void
	 */
	public function handle_toggle_post_type() {
		$this->guard_action( 'acm_toggle_post_type' );

		$slug   = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';
		$active = isset( $_GET['active'] ) ? absint( $_GET['active'] ) : 0;

		if ( '' === $slug || ! ACM_Post_Type_Registry::get( $slug ) ) {
			self::add_notice( 'error', __( 'The custom post type could not be found.', 'author-content-manager' ) );
			$this->redirect( 'acm-post-types' );
		}

		ACM_Post_Type_Registry::set_active( $slug, 1 === $active );

		self::add_notice(
			'success',
			1 === $active ? __( 'Custom post type activated.', 'author-content-manager' ) : __( 'Custom post type deactivated. Its content is still stored.', 'author-content-manager' )
		);

		$this->redirect( 'acm-post-types' );
	}

	/**
	 * Delete a taxonomy configuration.
	 *
	 * @return void
	 */
	public function handle_delete_taxonomy() {
		$this->guard_action( 'acm_delete_taxonomy' );

		$slug = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';

		if ( '' === $slug || ! ACM_Taxonomy_Registry::get( $slug ) ) {
			self::add_notice( 'error', __( 'The custom taxonomy could not be found.', 'author-content-manager' ) );
			$this->redirect( 'acm-taxonomies' );
		}

		ACM_Taxonomy_Registry::delete( $slug );

		self::add_notice( 'success', __( 'Custom taxonomy deleted. Its terms were not deleted.', 'author-content-manager' ) );

		$this->redirect( 'acm-taxonomies' );
	}

	/**
	 * Activate or deactivate a taxonomy.
	 *
	 * @return void
	 */
	public function handle_toggle_taxonomy() {
		$this->guard_action( 'acm_toggle_taxonomy' );

		$slug   = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';
		$active = isset( $_GET['active'] ) ? absint( $_GET['active'] ) : 0;

		if ( '' === $slug || ! ACM_Taxonomy_Registry::get( $slug ) ) {
			self::add_notice( 'error', __( 'The custom taxonomy could not be found.', 'author-content-manager' ) );
			$this->redirect( 'acm-taxonomies' );
		}

		ACM_Taxonomy_Registry::set_active( $slug, 1 === $active );

		self::add_notice(
			'success',
			1 === $active ? __( 'Custom taxonomy activated.', 'author-content-manager' ) : __( 'Custom taxonomy deactivated. Its terms are still stored.', 'author-content-manager' )
		);

		$this->redirect( 'acm-taxonomies' );
	}

	/**
	 * Save the settings form.
	 *
	 * @return void
	 */
	public function handle_save_settings() {
		$this->guard_action( 'acm_save_settings' );

		$raw = isset( $_POST['acm_settings'] ) && is_array( $_POST['acm_settings'] ) ? wp_unslash( $_POST['acm_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by ACM_Settings.

		ACM_Settings::update( $raw );

		foreach ( ACM_Settings::get_notices() as $notice ) {
			self::add_notice( 'warning', $notice );
		}

		self::add_notice( 'success', __( 'Settings saved.', 'author-content-manager' ) );

		$this->redirect( 'acm-settings' );
	}

	/**
	 * Reset the settings.
	 *
	 * @return void
	 */
	public function handle_reset_settings() {
		$this->guard_action( 'acm_reset_settings' );

		ACM_Settings::reset();

		self::add_notice( 'success', __( 'All plugin settings were reset to their defaults.', 'author-content-manager' ) );

		$this->redirect( 'acm-settings' );
	}

	/**
	 * Verify capability and nonce for an admin-post action.
	 *
	 * @param string $action Nonce action.
	 * @return void
	 */
	private function guard_action( $action ) {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'author-content-manager' ), 403 );
		}

		check_admin_referer( $action );
	}

	/**
	 * Nonce URL for an admin-post action.
	 *
	 * @param string $action Action name.
	 * @param array  $args   Extra query arguments.
	 * @return string
	 */
	public static function action_url( $action, $args = array() ) {
		$args = array_merge(
			array(
				'action' => $action,
			),
			is_array( $args ) ? $args : array()
		);

		return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), $action );
	}

	/**
	 * Shortcode reference data.
	 *
	 * @return array
	 */
	public static function shortcode_docs() {
		return array(
			array(
				'code'        => '[custom_author]',
				'title'       => __( 'Individual author profile', 'author-content-manager' ),
				'description' => __( 'Displays a single author profile. Only fields that contain a value are rendered.', 'author-content-manager' ),
				'attributes'  => array(
					'id'           => __( 'Author profile ID.', 'author-content-manager' ),
					'slug'         => __( 'Author slug, used when the ID is unknown.', 'author-content-manager' ),
					'layout'       => __( 'vertical or horizontal.', 'author-content-manager' ),
					'show_image'   => __( 'true or false.', 'author-content-manager' ),
					'show_bio'     => __( 'true or false.', 'author-content-manager' ),
					'show_contact' => __( 'true or false.', 'author-content-manager' ),
					'show_social'  => __( 'true or false.', 'author-content-manager' ),
					'show_name'    => __( 'true or false.', 'author-content-manager' ),
					'show_custom'  => __( 'true or false. Show custom profile fields.', 'author-content-manager' ),
					'image_size'   => __( 'A registered image size, for example thumbnail, medium or large.', 'author-content-manager' ),
					'class'        => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[custom_author id="123" layout="horizontal" show_image="true" show_bio="true"]',
			),
			array(
				'code'        => '[custom_authors]',
				'title'       => __( 'Author grid', 'author-content-manager' ),
				'description' => __( 'Displays a responsive grid of published author profiles.', 'author-content-manager' ),
				'attributes'  => array(
					'columns'        => __( 'Number of columns, 1 to 6.', 'author-content-manager' ),
					'posts_per_page' => __( 'Number of authors to show, or -1 for all.', 'author-content-manager' ),
					'orderby'        => __( 'title, date, modified, ID, menu_order or rand.', 'author-content-manager' ),
					'order'          => __( 'ASC or DESC.', 'author-content-manager' ),
					'ids'            => __( 'Comma separated author IDs.', 'author-content-manager' ),
					'taxonomy'       => __( 'Taxonomy slug used with term.', 'author-content-manager' ),
					'term'           => __( 'Term slug or ID.', 'author-content-manager' ),
					'post_id'        => __( 'Show the authors assigned to this post.', 'author-content-manager' ),
					'show_image'     => __( 'true or false.', 'author-content-manager' ),
					'show_bio'       => __( 'true or false.', 'author-content-manager' ),
					'show_contact'   => __( 'true or false.', 'author-content-manager' ),
					'show_social'    => __( 'true or false.', 'author-content-manager' ),
					'show_link'      => __( 'true or false. Show the view profile link.', 'author-content-manager' ),
					'image_size'     => __( 'A registered image size.', 'author-content-manager' ),
					'bio_limit'      => __( 'Biography character limit, 0 for the full text.', 'author-content-manager' ),
					'class'          => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[custom_authors columns="3" posts_per_page="12" orderby="title" order="ASC"]',
			),
			array(
				'code'        => '[custom_authors ids="12,15,18"]',
				'title'       => __( 'Selected authors', 'author-content-manager' ),
				'description' => __( 'Displays only the authors whose IDs are listed.', 'author-content-manager' ),
				'attributes'  => array(
					'ids' => __( 'Comma separated author IDs.', 'author-content-manager' ),
				),
				'example'     => '[custom_authors ids="12,15,18" columns="3"]',
			),
			array(
				'code'        => '[custom_authors taxonomy="author_category" term="technology"]',
				'title'       => __( 'Authors by taxonomy', 'author-content-manager' ),
				'description' => __( 'Displays authors assigned to a term of any taxonomy registered for author profiles.', 'author-content-manager' ),
				'attributes'  => array(
					'taxonomy' => __( 'Taxonomy slug.', 'author-content-manager' ),
					'term'     => __( 'Term slug or ID.', 'author-content-manager' ),
				),
				'example'     => '[custom_authors taxonomy="author_category" term="technology"]',
			),
			array(
				'code'        => '[custom_author_name]',
				'title'       => __( 'Author name only', 'author-content-manager' ),
				'description' => __( 'Displays the author name as plain text, optionally linked to the profile.', 'author-content-manager' ),
				'attributes'  => array(
					'id'    => __( 'Author profile ID.', 'author-content-manager' ),
					'link'  => __( 'true to link to the profile.', 'author-content-manager' ),
					'class' => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[custom_author_name id="123" link="true"]',
			),
			array(
				'code'        => '[custom_author_image]',
				'title'       => __( 'Author image only', 'author-content-manager' ),
				'description' => __( 'Displays only the profile picture. Nothing is rendered when the author has no picture.', 'author-content-manager' ),
				'attributes'  => array(
					'id'    => __( 'Author profile ID.', 'author-content-manager' ),
					'size'  => __( 'Image size, for example thumbnail.', 'author-content-manager' ),
					'class' => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[custom_author_image id="123" size="thumbnail"]',
			),
			array(
				'code'        => '[custom_author_email]',
				'title'       => __( 'Author email only', 'author-content-manager' ),
				'description' => __( 'Displays the author email address. Nothing is rendered when no email is stored.', 'author-content-manager' ),
				'attributes'  => array(
					'id'    => __( 'Author profile ID.', 'author-content-manager' ),
					'link'  => __( 'true to create a mailto link.', 'author-content-manager' ),
					'class' => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[custom_author_email id="123"]',
			),
			array(
				'code'        => '[custom_author_field]',
				'title'       => __( 'Author custom field', 'author-content-manager' ),
				'description' => __( 'Displays one custom author field. Nothing is rendered when the author has no value for the field.', 'author-content-manager' ),
				'attributes'  => array(
					'id'         => __( 'Author profile ID.', 'author-content-manager' ),
					'field'      => __( 'Field key from Author Fields.', 'author-content-manager' ),
					'show_label' => __( 'true to print the field label.', 'author-content-manager' ),
					'class'      => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[custom_author_field id="123" field="department" show_label="true"]',
			),
			array(
				'code'        => '[custom_author_fields]',
				'title'       => __( 'All author custom fields', 'author-content-manager' ),
				'description' => __( 'Displays every non-empty custom field of an author, including fields hidden from the automatic profile.', 'author-content-manager' ),
				'attributes'  => array(
					'id'          => __( 'Author profile ID.', 'author-content-manager' ),
					'show_labels' => __( 'true or false.', 'author-content-manager' ),
					'class'       => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[custom_author_fields id="123" show_labels="true"]',
			),
			array(
				'code'        => '[acm_post_field]',
				'title'       => __( 'Post custom field', 'author-content-manager' ),
				'description' => __( 'Displays one custom field of a post type. Nothing is rendered when the field is empty or the post type has no such field.', 'author-content-manager' ),
				'attributes'  => array(
					'post_id'    => __( 'Post ID. Defaults to the current post.', 'author-content-manager' ),
					'field'      => __( 'Field key configured on the post type.', 'author-content-manager' ),
					'show_label' => __( 'true to print the field label.', 'author-content-manager' ),
					'image_size' => __( 'Image size for image fields.', 'author-content-manager' ),
					'link'       => __( 'true to link images to the full file.', 'author-content-manager' ),
					'class'      => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[acm_post_field post_id="123" field="price" show_label="true"]',
			),
			array(
				'code'        => '[acm_post_fields]',
				'title'       => __( 'All post custom fields', 'author-content-manager' ),
				'description' => __( 'Displays every non-empty custom field of a post type as a definition list.', 'author-content-manager' ),
				'attributes'  => array(
					'post_id'     => __( 'Post ID. Defaults to the current post.', 'author-content-manager' ),
					'show_labels' => __( 'true or false.', 'author-content-manager' ),
					'image_size'  => __( 'Image size for image fields.', 'author-content-manager' ),
					'title'       => __( 'Optional heading above the list.', 'author-content-manager' ),
					'exclude'     => __( 'Comma separated field keys to skip.', 'author-content-manager' ),
					'class'       => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[acm_post_fields post_id="123" title="Details"]',
			),
			array(
				'code'        => '[acm_post_image]',
				'title'       => __( 'Post field image', 'author-content-manager' ),
				'description' => __( 'Displays an image stored in a post type custom field. Nothing is rendered when the image is missing.', 'author-content-manager' ),
				'attributes'  => array(
					'post_id' => __( 'Post ID. Defaults to the current post.', 'author-content-manager' ),
					'field'   => __( 'Image field key. Defaults to the first image field.', 'author-content-manager' ),
					'size'    => __( 'Image size, for example medium or large.', 'author-content-manager' ),
					'link'    => __( 'true to link to the full image.', 'author-content-manager' ),
					'class'   => __( 'Extra CSS classes.', 'author-content-manager' ),
				),
				'example'     => '[acm_post_image post_id="123" field="photo" size="medium"]',
			),
			array(
				'code'        => '[acm_posts]',
				'title'       => __( 'Posts of a custom post type', 'author-content-manager' ),
				'description' => __( 'Displays posts of a plugin post type in a grid or list. Every post type also has its own shortcode and its own display settings. Empty fields are never printed.', 'author-content-manager' ),
				'attributes'  => ACM_Post_Display::attribute_docs(),
				'example'     => '[acm_posts post_type="portfolio" layout="grid" columns="3" posts_per_page="6"]',
			),
		);
	}
}
