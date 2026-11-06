=== Author & Content Manager ===
Developer: Mossawir Ahmed
Developer URI: https://mossawir.com
Tags: custom post types, custom taxonomy, authors, author profiles, shortcodes
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create custom post types, taxonomies, author profiles and shortcode-driven post displays without writing code.

== Description ==

Author & Content Manager is a lightweight plugin for sites that need more than the default WordPress content model. It combines a complete content toolkit in one clean admin interface:

**Custom post types**

* Unlimited post types with plural and singular labels, key, description, Dashicon, menu position and archive.
* Visibility controls for public, publicly queryable, REST API, admin menu and archives.
* Hierarchical (parent/child) support.
* Supports title, editor, featured image, excerpt, comments, revisions, author, custom fields and page attributes.
* Optional WordPress categories and tags for each post type, or attach any custom taxonomy.
* Activate, deactivate, edit and delete configurations at any time. Posts are never deleted.
* Per-post-type display settings and a dedicated posts shortcode with grid or list layouts.

**Post type display shortcodes**

* Every custom post type gets a dedicated shortcode such as [acm_posts_portfolio], plus the generic [acm_posts post_type="portfolio"].
* Choose a responsive grid (1 to 6 columns) or a list layout.
* Control posts per page, ordering, image size, featured image, excerpt, date, category/term links and read more links.
* Filter by category or any associated taxonomy term.
* Select the category taxonomy per post type and enable WordPress categories or tags when needed.
* Empty fields are never printed, and nothing is output when no posts match.

**Author profiles**

* A dedicated Authors post type with twelve profile fields: name, email, profile picture, biography, LinkedIn, phone, mobile, website, address, city, country and additional contact information.
* Native WordPress Media Library picker with live preview, remove option and image validation.
* Email and URL validation, sanitization and capability checks on every save.
* Public profile pages with SEO friendly URLs such as /authors/jane-doe/.
* Optional author archives and a configurable base slug.
* Author search in the admin that also matches profile fields.
* Assign author profiles to any post type and display them with a shortcode.
* Unlimited custom author fields (text, text area, email, URL, phone, number, date, image) with profile display and shortcodes.

**Custom fields for post types**

* Add unlimited custom fields to every custom post type directly from the post type editor.
* Image fields use the native WordPress Media Library with live preview and removal.
* Text, text area, email, URL, phone, number, date and image field types.
* Per-field labels, descriptions, keys and required flags.
* Show fields with shortcodes or automatically below the single post content.
* Empty fields are never printed, and every value is validated and escaped.

**Custom taxonomies**

* Unlimited category-like or tag-like taxonomies.
* Attach each taxonomy to one or several post types, including plugin post types.
* REST API, admin column and navigation menu support.
* Native term management screens.

**Shortcodes**

* [custom_author] for a single profile.
* [custom_authors] for a responsive grid, selected IDs, post IDs or a taxonomy term.
* [custom_author_name], [custom_author_image] and [custom_author_email] for single values.
* [custom_author_field] and [custom_author_fields] for custom author fields.
* [acm_post_field], [acm_post_fields] and [acm_post_image] for post type custom fields.
* [acm_posts] for any custom post type, plus a dedicated [acm_posts_{type}] shortcode for each one.
* Every field is checked before it is printed, so empty values never produce empty markup.

= Privacy =

The plugin does not send data anywhere and does not create custom database tables. Author profiles are stored as a custom post type and settings are stored in WordPress options.

== Installation ==

1. Upload the plugin ZIP through Plugins → Add New → Upload Plugin, or copy the author-content-manager folder to wp-content/plugins.
2. Activate the plugin.
3. Open Author & Content in the admin menu.
4. Create author profiles and optionally add custom post types or taxonomies.
5. Add a shortcode such as [custom_authors] to any page.

== Frequently Asked Questions ==

= Does deactivating the plugin delete my content? =

No. Deactivation only unregisters the post types and taxonomies. Posts, terms and author profiles stay in the database.

= What happens when I delete a custom post type configuration? =

Only the definition is removed. Posts that use the post type key are kept in the database. Recreate a definition with the same key to show them again.

= Can I use the shortcodes in Elementor or Gutenberg? =

Yes. Use a Shortcode block in Gutenberg or the Shortcode widget in Elementor. Any builder that runs do_shortcode() works.

= Are empty author fields hidden? =

Yes. Templates check every value before printing the label, icon or wrapper. The same rule applies to author and post type custom fields.

= How do I add custom fields to author profiles? =

Open Author Fields in the plugin menu, add a field, choose its type and enable "Show this field automatically on the author profile". Then fill the field in any author profile. Use [custom_author_field] or [custom_author_fields] to print it anywhere.

= How do I add custom fields to a custom post type? =

Edit the post type and use the Custom Fields builder to add fields. Choose whether values appear only through shortcodes or automatically below the single post content, and pick the image size. Fill the fields in the "Custom Fields" box on each post.

= How do I display posts of a custom post type? =

Every post type has its own shortcode, shown with copy buttons and full parameter documentation on its settings page. For a post type with the key portfolio, use [acm_posts_portfolio] or [acm_posts post_type="portfolio"]. The same page controls grid or list layout, columns, posts per page, image size, visible card elements, ordering and the category taxonomy.

= Is the profile picture stored twice? =

Yes. The Media Library attachment is the source of truth and its attachment ID is stored in post meta. No files are duplicated.

= Can I style the output? =

Yes. Every class is prefixed with acm-. Disable the bundled CSS in Settings and add your own, or use the class attribute of each shortcode.

== Developer ==

**Mossawir Ahmed** — Frontend & UI/UX Developer

Mossawir Ahmed is an experienced Frontend & UI/UX Developer with over 15 years of experience in web development, specializing in creating high-performance, user-friendly, and scalable web solutions.

With extensive experience in WordPress development, custom theme and plugin development, WooCommerce, Shopify, React, and modern JavaScript technologies, Mossawir combines technical expertise with a strong understanding of design and user experience.

His approach to development focuses on building practical, lightweight, reliable, and easy-to-use solutions that help developers, businesses, and website owners achieve more with less complexity.

Through his work, he aims to contribute to the WordPress ecosystem by developing useful tools and plugins that prioritize performance, flexibility, accessibility, and ease of use.

**Connect with the Developer**

* Website: https://mossawir.com
* LinkedIn: https://linkedin.com/in/mossawirahmed/
* Email: me@mossawir.com

== Changelog ==

= 1.2.1 =
* Added an About screen with the plugin developer profile and contact links.
* Updated the plugin author to Mossawir Ahmed.

= 1.2.0 =
* Added a dedicated posts shortcode and a generic [acm_posts] shortcode for every custom post type.
* Added grid and list display settings, columns, posts per page, ordering, image size and visible card elements.
* Added optional WordPress categories and tags for custom post types.
* Added a category taxonomy setting and category/term filtering in post shortcodes.
* Added complete shortcode documentation to each post type settings page and the documentation screen.

= 1.1.0 =
* Added custom fields for author profiles with profile display and shortcodes.
* Added a custom field and photo builder to every custom post type.
* Added post field shortcodes and automatic single-post display.
* Added per-post-type display, label and image size settings.
* Added a global custom field image size setting.
* Added Author Fields admin screens.

= 1.0.0 =
* Initial release.
* Custom post type manager.
* Author profiles with media library support.
* Custom taxonomy manager.
* Five shortcodes and responsive templates.
* Settings, documentation and safe uninstall.
