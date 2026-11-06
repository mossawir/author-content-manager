<?php
/**
 * Plugin Name:       Author & Content Manager
 * Description:       Create custom post types, taxonomies, author profiles and shortcode-driven post displays without writing code.
 * Version:           1.2.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mossawir Ahmed
 * Author URI:        https://mossawir.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       author-content-manager
 * Domain Path:       /languages
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

define( 'ACM_VERSION', '1.2.1' );
define( 'ACM_FILE', __FILE__ );
define( 'ACM_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACM_URL', plugin_dir_url( __FILE__ ) );
define( 'ACM_BASENAME', plugin_basename( __FILE__ ) );

/** Author post type identifier. */
define( 'ACM_POST_TYPE', 'acm_author' );

/** Option names. */
define( 'ACM_OPTION_POST_TYPES', 'acm_post_types' );
define( 'ACM_OPTION_TAXONOMIES', 'acm_taxonomies' );
define( 'ACM_OPTION_SETTINGS', 'acm_settings' );
define( 'ACM_OPTION_REWRITE', 'acm_rewrite_needed' );

/**
 * Load the plugin.
 *
 * All classes are loaded before the plugin boots so that every module can rely
 * on the others being available, regardless of the hook that triggers it.
 */
require_once ACM_DIR . 'includes/class-acm-settings.php';
require_once ACM_DIR . 'includes/class-acm-post-type-registry.php';
require_once ACM_DIR . 'includes/class-acm-taxonomy-registry.php';
require_once ACM_DIR . 'includes/class-acm-author.php';
require_once ACM_DIR . 'includes/class-acm-custom-fields.php';
require_once ACM_DIR . 'includes/class-acm-author-fields.php';
require_once ACM_DIR . 'includes/class-acm-author-manager.php';
require_once ACM_DIR . 'includes/class-acm-templates.php';
require_once ACM_DIR . 'includes/class-acm-post-display.php';
require_once ACM_DIR . 'includes/class-acm-shortcodes.php';
require_once ACM_DIR . 'includes/class-acm-frontend.php';
require_once ACM_DIR . 'includes/class-acm-assets.php';
require_once ACM_DIR . 'includes/class-acm-admin.php';
require_once ACM_DIR . 'includes/class-acm-plugin.php';
require_once ACM_DIR . 'includes/api.php';

register_activation_hook( __FILE__, array( 'ACM_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ACM_Plugin', 'deactivate' ) );

/**
 * Return the shared plugin instance.
 *
 * @return ACM_Plugin
 */
function acm_plugin() {
	return ACM_Plugin::instance();
}

acm_plugin()->run();
