<?php
/**
 * Documentation view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_shortcodes = isset( $args['shortcodes'] ) ? $args['shortcodes'] : ACM_Admin::shortcode_docs();
$acm_base       = ACM_Author_Manager::get_base();
?>
<div class="wrap acm-wrap acm-docs">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Documentation', 'author-content-manager' ); ?></h1>
	<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings', 'author-content-manager' ); ?></a>
	<hr class="wp-header-end" />

	<nav class="nav-tab-wrapper acm-docs__nav" aria-label="<?php esc_attr_e( 'Documentation sections', 'author-content-manager' ); ?>">
		<a class="nav-tab" href="#acm-doc-post-types"><?php esc_html_e( 'Post Types', 'author-content-manager' ); ?></a>
		<a class="nav-tab" href="#acm-doc-authors"><?php esc_html_e( 'Authors', 'author-content-manager' ); ?></a>
		<a class="nav-tab" href="#acm-doc-fields"><?php esc_html_e( 'Custom Fields', 'author-content-manager' ); ?></a>
		<a class="nav-tab" href="#acm-doc-taxonomies"><?php esc_html_e( 'Taxonomies', 'author-content-manager' ); ?></a>
		<a class="nav-tab" href="#acm-doc-shortcodes"><?php esc_html_e( 'Shortcodes', 'author-content-manager' ); ?></a>
		<a class="nav-tab" href="#acm-doc-post-shortcodes"><?php esc_html_e( 'Post Shortcodes', 'author-content-manager' ); ?></a>
		<a class="nav-tab" href="#acm-doc-styling"><?php esc_html_e( 'Styling', 'author-content-manager' ); ?></a>
		<a class="nav-tab" href="#acm-doc-templates"><?php esc_html_e( 'Templates', 'author-content-manager' ); ?></a>
		<a class="nav-tab" href="#acm-doc-developers"><?php esc_html_e( 'Developers', 'author-content-manager' ); ?></a>
		<a class="nav-tab" href="#acm-doc-uninstall"><?php esc_html_e( 'Uninstall', 'author-content-manager' ); ?></a>
	</nav>

	<div class="acm-panel">
		<h2><?php esc_html_e( 'How the plugin works', 'author-content-manager' ); ?></h2>
		<p><?php esc_html_e( 'Author & Content Manager adds three things to WordPress:', 'author-content-manager' ); ?></p>
		<ol class="acm-list acm-list--ordered">
			<li><?php esc_html_e( 'A configurable custom post type registry, saved as an option and registered on the init hook.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'An Authors post type with twelve profile fields, media library support and public profile pages.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'A custom field builder for author profiles and for every custom post type, with media and shortcode support.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'A configurable taxonomy registry plus shortcodes for frontend output.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'A post display engine with per-post-type grid and list shortcodes, display settings and documentation.', 'author-content-manager' ); ?></li>
		</ol>
		<p><?php esc_html_e( 'All definitions are stored in WordPress options, so the plugin does not create custom database tables. Author fields are stored as post meta.', 'author-content-manager' ); ?></p>
	</div>

	<div class="acm-panel" id="acm-doc-post-types">
		<h2><?php esc_html_e( 'Custom Post Types', 'author-content-manager' ); ?></h2>
		<p>
			<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-types' ) ); ?>"><?php esc_html_e( 'Manage Post Types', 'author-content-manager' ); ?></a>
		</p>
		<ol class="acm-list acm-list--ordered">
			<li><?php esc_html_e( 'Open Post Types → Add New Post Type.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Enter a plural name, an optional singular name and a key. Leave the key empty to generate it from the plural name.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Choose the visibility options and the supported editor features, then publish.', 'author-content-manager' ); ?></li>
		</ol>
		<p><?php esc_html_e( 'The new post type appears in the admin menu and its permalinks are refreshed automatically on the next request. Configurations can be deactivated at any time; posts are never deleted.', 'author-content-manager' ); ?></p>
		<p><?php esc_html_e( 'The same editor includes a Custom Fields builder. Add fields for price, duration, location, a photo and more, then fill them on every post of that type. Values can be shown automatically below the post content or with the post field shortcodes.', 'author-content-manager' ); ?></p>
		<p><?php esc_html_e( 'It also includes shortcode display settings: grid or list layout, columns, posts per page, image size, visible elements, ordering and the category taxonomy used for filtering. The complete shortcode reference appears on the same page.', 'author-content-manager' ); ?></p>
		<h3><?php esc_html_e( 'Important notes', 'author-content-manager' ); ?></h3>
		<ul class="acm-list">
			<li><?php esc_html_e( 'The post type key is stored with every post. Changing the key later hides existing posts from the new definition; recreate the previous key to list them again.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Reserved WordPress keys such as post, page, attachment or nav_menu_item cannot be used.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Deleting a definition keeps the posts and does not remove the posts from the database.', 'author-content-manager' ); ?></li>
		</ul>
	</div>

	<div class="acm-panel" id="acm-doc-authors">
		<h2><?php esc_html_e( 'Author Profiles', 'author-content-manager' ); ?></h2>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . ACM_POST_TYPE ) ); ?>"><?php esc_html_e( 'All Authors', 'author-content-manager' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . ACM_POST_TYPE ) ); ?>"><?php esc_html_e( 'Add New Author', 'author-content-manager' ); ?></a>
		</p>
		<p><?php esc_html_e( 'Every author has four profile sections:', 'author-content-manager' ); ?></p>
		<ul class="acm-list">
			<li><strong><?php esc_html_e( 'Basic Information:', 'author-content-manager' ); ?></strong> <?php esc_html_e( 'name, email address and profile picture.', 'author-content-manager' ); ?></li>
			<li><strong><?php esc_html_e( 'Biography:', 'author-content-manager' ); ?></strong> <?php esc_html_e( 'the full biography, basic HTML allowed.', 'author-content-manager' ); ?></li>
			<li><strong><?php esc_html_e( 'Social Media:', 'author-content-manager' ); ?></strong> <?php esc_html_e( 'LinkedIn profile URL.', 'author-content-manager' ); ?></li>
			<li><strong><?php esc_html_e( 'Contact Information:', 'author-content-manager' ); ?></strong> <?php esc_html_e( 'phone, mobile, website, address, city, country and additional information.', 'author-content-manager' ); ?></li>
		</ul>
		<h3><?php esc_html_e( 'Profile picture', 'author-content-manager' ); ?></h3>
		<p><?php esc_html_e( 'Use "Select image" to open the native WordPress Media Library. The preview updates immediately and "Remove image" clears the field. Only image attachments are accepted.', 'author-content-manager' ); ?></p>
		<h3><?php esc_html_e( 'Validation', 'author-content-manager' ); ?></h3>
		<p><?php esc_html_e( 'Email addresses are checked with the WordPress email validator. URLs must be absolute http or https addresses. Invalid values are rejected, the previous value is kept, and an error notice lists the affected fields.', 'author-content-manager' ); ?></p>
		<h3><?php esc_html_e( 'Profile URLs', 'author-content-manager' ); ?></h3>
		<p>
			<?php
			printf(
				/* translators: %s: example author profile URL. */
				esc_html__( 'Profiles are published below the author base slug, for example %s. Change the base under Settings → Author URLs.', 'author-content-manager' ),
				'<code>' . esc_html( home_url( '/' . $acm_base . '/jane-doe/' ) ) . '</code>'
			);
			?>
		</p>
		<h3><?php esc_html_e( 'Searching authors', 'author-content-manager' ); ?></h3>
		<p><?php esc_html_e( 'The author list search also looks at the name, email, city, country, phone, mobile and address fields, not only the title.', 'author-content-manager' ); ?></p>
		<h3><?php esc_html_e( 'Assigning authors to content', 'author-content-manager' ); ?></h3>
		<p><?php esc_html_e( 'Enable one or more post types under Settings → Author Assignment. An "Author Profile" box then appears in the editor sidebar, where any number of author profiles can be selected. Use [custom_authors post_id="123"] to display them.', 'author-content-manager' ); ?></p>
		<h3><?php esc_html_e( 'Custom author fields', 'author-content-manager' ); ?></h3>
		<p>
			<?php esc_html_e( 'Use Author Fields to add extra profile fields such as department, job title or a photo. Fields marked "Show this field automatically on the author profile" appear on profile pages; every field can also be printed with a shortcode.', 'author-content-manager' ); ?>
			<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-fields' ) ); ?>"><?php esc_html_e( 'Manage Author Fields', 'author-content-manager' ); ?></a>
		</p>
	</div>

	<div class="acm-panel" id="acm-doc-fields">
		<h2><?php esc_html_e( 'Custom Fields', 'author-content-manager' ); ?></h2>
		<p><?php esc_html_e( 'The field builder supports text, text area, email, URL, phone, number, date and image fields. Email and URL values are validated, images come from the WordPress Media Library, and empty values are never printed.', 'author-content-manager' ); ?></p>

		<h3><?php esc_html_e( 'Author profile fields', 'author-content-manager' ); ?></h3>
		<ol class="acm-list acm-list--ordered">
			<li><?php esc_html_e( 'Open Author Fields → Add New Field.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Choose a label, an optional key, a type and whether the field is required or shown on the profile.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Edit any author and fill the field in the "Additional Information" box.', 'author-content-manager' ); ?></li>
		</ol>
		<p><?php esc_html_e( 'Use [custom_author_field id="123" field="department"] for one value or [custom_author_fields id="123"] for all custom fields.', 'author-content-manager' ); ?></p>

		<h3><?php esc_html_e( 'Custom post type fields', 'author-content-manager' ); ?></h3>
		<ol class="acm-list acm-list--ordered">
			<li><?php esc_html_e( 'Open Post Types → edit a post type.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Use Add Field to build the fields, then choose the display mode, label behaviour and image size.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Edit a post of that type and fill the "Custom Fields" box.', 'author-content-manager' ); ?></li>
		</ol>
		<p><?php esc_html_e( 'Display modes: "Only with shortcodes" leaves the theme in control, while "Automatically below the post content" appends every non-empty field to the single post page. Use [acm_post_field], [acm_post_fields] and [acm_post_image] anywhere else.', 'author-content-manager' ); ?></p>
		<p><?php esc_html_e( 'Removing a field definition keeps the values already stored with posts and authors. Field keys should not be renamed after content is entered if you want to keep showing existing values.', 'author-content-manager' ); ?></p>
	</div>

	<div class="acm-panel" id="acm-doc-taxonomies">
		<h2><?php esc_html_e( 'Custom Taxonomies', 'author-content-manager' ); ?></h2>
		<p>
			<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-taxonomies' ) ); ?>"><?php esc_html_e( 'Manage Taxonomies', 'author-content-manager' ); ?></a>
		</p>
		<p><?php esc_html_e( 'Create category-like (hierarchical) or tag-like taxonomies and attach them to any registered post type. A default "Author Categories" taxonomy is created on activation so author profiles can be grouped right away.', 'author-content-manager' ); ?></p>
		<p><?php esc_html_e( 'Term management uses the native WordPress screen. Use "Manage terms" in the taxonomy list to add, edit, nest or delete terms.', 'author-content-manager' ); ?></p>
	</div>

	<div class="acm-panel" id="acm-doc-shortcodes">
		<h2><?php esc_html_e( 'Shortcodes', 'author-content-manager' ); ?></h2>
		<p><?php esc_html_e( 'Shortcodes work in the Classic Editor, Gutenberg shortcode blocks, widgets, Elementor and any other builder that runs do_shortcode(). Only fields that contain a value are printed.', 'author-content-manager' ); ?></p>

		<?php
		$this->render_view(
			'partials/shortcodes',
			array(
				'shortcodes' => $acm_shortcodes,
			)
		);
		?>
	</div>

	<div class="acm-panel" id="acm-doc-post-shortcodes">
		<h2><?php esc_html_e( 'Post Type Shortcodes', 'author-content-manager' ); ?></h2>
		<p><?php esc_html_e( 'Every custom post type can display its posts in a grid or list through a shortcode. Each post type has its own display settings, its own dedicated shortcode and a complete reference on its settings page.', 'author-content-manager' ); ?></p>
		<p>
			<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-types' ) ); ?>"><?php esc_html_e( 'Manage Post Types', 'author-content-manager' ); ?></a>
		</p>

		<h3><?php esc_html_e( 'Generic shortcode', 'author-content-manager' ); ?></h3>
		<p class="acm-code-row">
			<code>[acm_posts post_type="portfolio" layout="grid" columns="3" posts_per_page="6"]</code>
			<button type="button" class="button button-small acm-copy" data-acm-copy="[acm_posts post_type=&quot;portfolio&quot; layout=&quot;grid&quot; columns=&quot;3&quot; posts_per_page=&quot;6&quot;]"><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
		</p>
		<p><?php esc_html_e( 'If post_type is omitted on a single post or archive of a plugin post type, the current post type is used.', 'author-content-manager' ); ?></p>

		<table class="acm-shortcode__attributes">
			<tbody>
				<?php foreach ( ACM_Post_Display::attribute_docs() as $acm_attribute => $acm_attribute_description ) : ?>
					<tr>
						<th scope="row"><code><?php echo esc_html( $acm_attribute ); ?></code></th>
						<td><?php echo esc_html( $acm_attribute_description ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Per-post-type shortcodes', 'author-content-manager' ); ?></h3>
		<?php $acm_doc_post_types = ACM_Post_Type_Registry::get_all(); ?>

		<?php if ( empty( $acm_doc_post_types ) ) : ?>
			<p><?php esc_html_e( 'No custom post types exist yet. Create one to get a dedicated shortcode and display settings.', 'author-content-manager' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat striped acm-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Post Type', 'author-content-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Dedicated Shortcode', 'author-content-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Defaults', 'author-content-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Category Taxonomy', 'author-content-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Settings', 'author-content-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $acm_doc_post_types as $acm_doc_slug => $acm_doc_config ) : ?>
						<?php
						$acm_doc_config = wp_parse_args( $acm_doc_config, ACM_Post_Type_Registry::defaults() );
						$acm_doc_tag    = 'acm_posts_' . sanitize_key( $acm_doc_slug );
						$acm_doc_short  = '[' . $acm_doc_tag . ']';
						$acm_doc_tax    = '' !== (string) $acm_doc_config['shortcode_category_taxonomy'] ? (string) $acm_doc_config['shortcode_category_taxonomy'] : '—';
						?>
						<tr>
							<td><strong><?php echo esc_html( isset( $acm_doc_config['plural'] ) ? $acm_doc_config['plural'] : $acm_doc_slug ); ?></strong><br /><code><?php echo esc_html( $acm_doc_slug ); ?></code></td>
							<td>
								<code><?php echo esc_html( $acm_doc_short ); ?></code>
								<button type="button" class="button button-small acm-copy" data-acm-copy="<?php echo esc_attr( $acm_doc_short ); ?>"><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
							</td>
							<td>
								<?php
								printf(
									/* translators: 1: layout, 2: column count, 3: posts per page. */
									esc_html__( '%1$s, %2$s columns, %3$s posts', 'author-content-manager' ),
									esc_html( $acm_doc_config['shortcode_layout'] ),
									esc_html( (string) $acm_doc_config['shortcode_columns'] ),
									esc_html( (string) $acm_doc_config['shortcode_per_page'] )
								);
								?>
							</td>
							<td><code><?php echo esc_html( $acm_doc_tax ); ?></code></td>
							<td>
								<a class="button button-small" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-type-edit', array( 'slug' => $acm_doc_slug ) ) . '#acm-shortcode' ); ?>"><?php esc_html_e( 'Shortcode & Display', 'author-content-manager' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Grid and list layouts', 'author-content-manager' ); ?></h3>
		<ul class="ul-disc">
			<li><?php esc_html_e( 'Grid uses a responsive CSS grid. Set columns from 1 to 6; tablet and mobile columns adapt automatically.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'List stacks posts vertically with a horizontal card layout on larger screens.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Cards show the featured image, title, category/term links, date, excerpt and read more link according to the post type settings.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Nothing is printed when no posts match, and empty images, excerpts, dates, terms or links are omitted.', 'author-content-manager' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Category filtering', 'author-content-manager' ); ?></h3>
		<p><?php esc_html_e( 'Enable Categories or Tags on the post type, or attach one of your custom taxonomies. Then choose it in "Category Taxonomy" and use category="news" or term="news,events". Use taxonomy="..." to override the taxonomy for one shortcode. Missing terms produce no output instead of a fallback list.', 'author-content-manager' ); ?></p>
		<pre><code>[acm_posts post_type="portfolio" category="design"]
[acm_posts post_type="portfolio" taxonomy="project_type" term="branding"]
[acm_posts post_type="portfolio" ids="12,15,18" layout="list"]</code></pre>

		<h3><?php esc_html_e( 'Template overrides', 'author-content-manager' ); ?></h3>
		<pre><code>yourtheme/author-content-manager/post-grid.php
yourtheme/author-content-manager/post-list.php
yourtheme/author-content-manager/post-card.php</code></pre>
	</div>

	<div class="acm-panel" id="acm-doc-styling">
		<h2><?php esc_html_e( 'Styling', 'author-content-manager' ); ?></h2>
		<p><?php esc_html_e( 'Every element uses the acm- prefix. The grid exposes a CSS custom property and a modifier class for the column count, so both can be changed from CSS.', 'author-content-manager' ); ?></p>
		<pre><code>.acm-authors-grid {
	--acm-columns: 4;
}

.acm-authors-grid--cols-3 {
	--acm-columns: 2; /* override the shortcode default */
}

.acm-author-card {
	border-radius: 16px;
}</code></pre>
		<h3><?php esc_html_e( 'Main CSS classes', 'author-content-manager' ); ?></h3>
		<table class="wp-list-table widefat striped acm-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Class', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Description', 'author-content-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr><td><code>.acm-authors-grid</code></td><td><?php esc_html_e( 'Responsive CSS grid wrapper.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-author-card</code></td><td><?php esc_html_e( 'One author card inside the grid.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-author</code></td><td><?php esc_html_e( 'Individual profile wrapper. Add --vertical or --horizontal via the layout attribute.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-author__photo-img</code></td><td><?php esc_html_e( 'Profile picture image.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-author__bio</code></td><td><?php esc_html_e( 'Full biography block.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-author__contact</code></td><td><?php esc_html_e( 'Definition list with the contact rows.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-author__social</code></td><td><?php esc_html_e( 'List of social links.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-custom-fields</code></td><td><?php esc_html_e( 'Definition list of custom field label/value rows.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-custom-field</code></td><td><?php esc_html_e( 'One custom field row.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-custom-field-single</code></td><td><?php esc_html_e( 'Wrapper used by the single custom field shortcodes.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-post-fields--auto</code></td><td><?php esc_html_e( 'Automatically appended custom fields on a single post.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-posts</code></td><td><?php esc_html_e( 'Wrapper for a post type shortcode output.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-posts__items--grid</code>, <code>.acm-posts__items--list</code></td><td><?php esc_html_e( 'Grid or list layout container for post cards.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-post-card</code></td><td><?php esc_html_e( 'One post card. Add --list for the horizontal list layout.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>.acm-author-name</code>, <code>.acm-author-image</code>, <code>.acm-author-email</code></td><td><?php esc_html_e( 'Wrappers used by the single value shortcodes.', 'author-content-manager' ); ?></td></tr>
			</tbody>
		</table>
		<p><?php esc_html_e( 'Add your own class with the class attribute, for example [custom_authors class="my-team-grid"].', 'author-content-manager' ); ?></p>
	</div>

	<div class="acm-panel" id="acm-doc-templates">
		<h2><?php esc_html_e( 'Template Overrides', 'author-content-manager' ); ?></h2>
		<p><?php esc_html_e( 'Copy a template from public/templates into your theme and adjust the markup. Theme overrides always win.', 'author-content-manager' ); ?></p>
		<pre><code>yourtheme/author-content-manager/author-card.php
yourtheme/author-content-manager/author-grid.php
yourtheme/author-content-manager/author-profile.php
yourtheme/author-content-manager/archive-author.php</code></pre>
		<p><?php esc_html_e( 'The author archive uses the theme archive template in block themes. Classic themes receive the plugin grid unless the theme provides archive-acm_author.php.', 'author-content-manager' ); ?></p>
		<p><?php esc_html_e( 'Templates receive a single $args array with the author object and the display settings. Use $args[\'author\'] to access the profile.', 'author-content-manager' ); ?></p>
		<p><?php esc_html_e( 'Single author pages append the profile to the post content, so the theme header, footer, sidebar and block templates keep working. A theme must render the post content for the profile to appear.', 'author-content-manager' ); ?></p>
	</div>

	<div class="acm-panel" id="acm-doc-developers">
		<h2><?php esc_html_e( 'Developer Reference', 'author-content-manager' ); ?></h2>

		<h3><?php esc_html_e( 'Template functions', 'author-content-manager' ); ?></h3>
		<pre><code>// An ACM_Author object or null.
$author = acm_get_author( 123 );

// A single sanitized field value.
$email = acm_get_author_field( 123, 'email' );

// Profile picture markup, empty string when there is no picture.
$photo = acm_get_author_photo( 123, 'medium' );

// Authors assigned to a post through the editor box.
$assigned = acm_get_assigned_authors( get_the_ID() );

// Query many authors.
$authors = acm_get_authors( array(
	'taxonomy' => 'author_category',
	'term'     => 'technology',
	'orderby'  => 'title',
) );

// Custom author fields.
$items = acm_get_author_custom_fields( 123, true );
$dept  = acm_get_author_custom_field( 123, 'department' );

// Custom post fields.
$fields = acm_get_post_fields( get_the_ID() );
$price  = acm_get_post_field( get_the_ID(), 'price' );</code></pre>

		<h3><?php esc_html_e( 'Fields', 'author-content-manager' ); ?></h3>
		<p><?php esc_html_e( 'Field keys: name, email, photo_id, bio, linkedin, phone, mobile, website, address, city, country, contact_extra.', 'author-content-manager' ); ?></p>

		<h3><?php esc_html_e( 'Filters', 'author-content-manager' ); ?></h3>
		<table class="wp-list-table widefat striped acm-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Filter', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Purpose', 'author-content-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr><td><code>acm_default_settings</code></td><td><?php esc_html_e( 'Change the default settings.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_post_type_args</code></td><td><?php esc_html_e( 'Adjust register_post_type() arguments for plugin post types.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_taxonomy_args</code></td><td><?php esc_html_e( 'Adjust register_taxonomy() arguments for plugin taxonomies.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_author_post_type_args</code></td><td><?php esc_html_e( 'Adjust the Authors post type arguments.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_author_fields</code></td><td><?php esc_html_e( 'Add or remove author profile fields.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_sanitize_custom_field</code></td><td><?php esc_html_e( 'Filter a saved author or post custom field definition.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_author_contact_items</code></td><td><?php esc_html_e( 'Filter the contact rows rendered for an author.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_author_social_links</code></td><td><?php esc_html_e( 'Add more social networks.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_author_photo_html</code></td><td><?php esc_html_e( 'Filter the profile picture markup.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_profile_template_args</code>, <code>acm_card_template_args</code></td><td><?php esc_html_e( 'Change template arguments before rendering.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_template_path</code></td><td><?php esc_html_e( 'Point a template at a custom file.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_authors_query_args</code></td><td><?php esc_html_e( 'Change the WP_Query arguments used by [custom_authors].', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_custom_css</code></td><td><?php esc_html_e( 'Filter the custom CSS output.', 'author-content-manager' ); ?></td></tr>
				<tr><td><code>acm_use_plugin_archive_template</code></td><td><?php esc_html_e( 'Disable the plugin archive template.', 'author-content-manager' ); ?></td></tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Constants', 'author-content-manager' ); ?></h3>
		<pre><code>ACM_POST_TYPE    // acm_author
ACM_VERSION      // plugin version
ACM_DIR, ACM_URL // absolute path and URL</code></pre>
	</div>

	<div class="acm-panel" id="acm-doc-uninstall">
		<h2><?php esc_html_e( 'Deactivation & Uninstall', 'author-content-manager' ); ?></h2>
		<ul class="acm-list">
			<li><?php esc_html_e( 'Deactivating the plugin removes its post types and taxonomies from the site but keeps every post, term and profile.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Uninstalling keeps all data by default, including settings.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Enable "Remove Data on Uninstall" under Settings → Uninstall to delete author profiles, plugin settings and transients on uninstall.', 'author-content-manager' ); ?></li>
			<li><?php esc_html_e( 'Posts of custom post types and taxonomy terms are preserved even when data removal is enabled, because they belong to the site content.', 'author-content-manager' ); ?></li>
		</ul>
	</div>
</div>
