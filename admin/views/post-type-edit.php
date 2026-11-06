<?php
/**
 * Post type editor view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_config        = isset( $args['config'] ) ? $args['config'] : ACM_Post_Type_Registry::defaults();
$acm_original_slug = isset( $args['original_slug'] ) ? (string) $args['original_slug'] : '';
$acm_is_new        = ! empty( $args['is_new'] );
$acm_error_html    = isset( $args['error_html'] ) ? $args['error_html'] : '';
$acm_icons         = ACM_Post_Type_Registry::icon_choices();
$acm_features      = ACM_Post_Type_Registry::supported_features();
$acm_field_types   = ACM_Custom_Fields::types();
$acm_image_sizes   = ACM_Settings::available_image_sizes();
$acm_taxonomies    = ACM_Post_Type_Registry::taxonomy_options();
$acm_example_slug  = '' !== (string) $acm_config['slug'] ? (string) $acm_config['slug'] : 'your_post_type';
$acm_shortcode_tag = 'acm_posts_' . sanitize_key( $acm_example_slug );
$acm_shortcode_example = '[acm_posts post_type="' . $acm_example_slug . '" layout="' . ( 'list' === $acm_config['shortcode_layout'] ? 'list' : 'grid' ) . '" columns="' . (string) $acm_config['shortcode_columns'] . '" posts_per_page="' . (string) $acm_config['shortcode_per_page'] . '"]';
$acm_icon          = isset( $acm_config['menu_icon'] ) ? (string) $acm_config['menu_icon'] : 'dashicons-admin-post';

if ( ! isset( $acm_icons[ $acm_icon ] ) ) {
	$acm_icons[ $acm_icon ] = $acm_icon;
}

$acm_form_url = ACM_Admin::page_url( 'acm-post-type-edit', '' !== $acm_original_slug ? array( 'slug' => $acm_original_slug ) : array() );
?>
<div class="wrap acm-wrap">
	<h1 class="wp-heading-inline">
		<?php echo $acm_is_new ? esc_html__( 'Add New Post Type', 'author-content-manager' ) : esc_html__( 'Edit Post Type', 'author-content-manager' ); ?>
	</h1>
	<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-types' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to list', 'author-content-manager' ); ?></a>
	<hr class="wp-header-end" />

	<?php echo wp_kses_post( $acm_error_html ); ?>

	<form method="post" action="<?php echo esc_url( $acm_form_url ); ?>" class="acm-form">
		<?php wp_nonce_field( 'acm_save_post_type' ); ?>
		<input type="hidden" name="acm_action" value="save_post_type" />
		<input type="hidden" name="acm_original_slug" value="<?php echo esc_attr( $acm_original_slug ); ?>" />

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Labels', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_plural"><?php esc_html_e( 'Post Type Name (plural)', 'author-content-manager' ); ?> <span class="acm-required">*</span></label></th>
					<td>
						<input type="text" class="regular-text" id="acm_plural" name="acm_post_type[plural]" value="<?php echo esc_attr( (string) $acm_config['plural'] ); ?>" required="required" />
						<p class="description"><?php esc_html_e( 'Shown in the admin menu, for example "Portfolio Items".', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_singular"><?php esc_html_e( 'Singular Name', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="acm_singular" name="acm_post_type[singular]" value="<?php echo esc_attr( (string) $acm_config['singular'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Used for single item labels, for example "Portfolio Item". Defaults to the plural name.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_slug"><?php esc_html_e( 'Post Type Slug', 'author-content-manager' ); ?> <span class="acm-required">*</span></label></th>
					<td>
						<input type="text" class="regular-text code" id="acm_slug" name="acm_post_type[slug]" value="<?php echo esc_attr( (string) $acm_config['slug'] ); ?>" maxlength="20" data-acm-slug="1" />
						<p class="description">
							<?php esc_html_e( 'Lowercase letters, numbers, dashes and underscores only, 20 characters maximum. This key is saved with every post, so changing it later hides existing posts from the new post type.', 'author-content-manager' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_description"><?php esc_html_e( 'Description', 'author-content-manager' ); ?></label></th>
					<td>
						<textarea class="large-text" id="acm_description" name="acm_post_type[description]" rows="3"><?php echo esc_textarea( (string) $acm_config['description'] ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Menu', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_menu_icon"><?php esc_html_e( 'Menu Icon (Dashicons)', 'author-content-manager' ); ?></label></th>
					<td>
						<div class="acm-icon-field">
							<select id="acm_menu_icon" name="acm_post_type[menu_icon]">
								<?php foreach ( $acm_icons as $acm_icon_class => $acm_icon_label ) : ?>
									<option value="<?php echo esc_attr( $acm_icon_class ); ?>" <?php selected( $acm_icon, $acm_icon_class ); ?>><?php echo esc_html( $acm_icon_label ); ?></option>
								<?php endforeach; ?>
							</select>
							<span class="acm-icon-preview dashicons <?php echo esc_attr( $acm_icon ); ?>" id="acm-icon-preview" aria-hidden="true"></span>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_menu_position"><?php esc_html_e( 'Menu Position', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="number" class="small-text" id="acm_menu_position" name="acm_post_type[menu_position]" value="<?php echo esc_attr( '' === $acm_config['menu_position'] ? '' : (string) $acm_config['menu_position'] ); ?>" min="-100" max="100" step="1" />
						<p class="description"><?php esc_html_e( 'Optional. Leave empty to use the default position.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Visibility & Behaviour', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Public', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_post_type[public]" value="1" <?php checked( ! empty( $acm_config['public'] ) ); ?> />
							<?php esc_html_e( 'Make this post type available on the frontend', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Publicly Queryable', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_post_type[publicly_queryable]" value="1" <?php checked( ! empty( $acm_config['publicly_queryable'] ) ); ?> />
							<?php esc_html_e( 'Allow frontend queries and single item URLs', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show in REST API', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_post_type[show_in_rest]" value="1" <?php checked( ! empty( $acm_config['show_in_rest'] ) ); ?> />
							<?php esc_html_e( 'Enable the block editor and REST API endpoints', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show in Admin Menu', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_post_type[show_in_admin_menu]" value="1" <?php checked( ! empty( $acm_config['show_in_admin_menu'] ) ); ?> />
							<?php esc_html_e( 'Display a management menu for this post type', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Has Archive', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_post_type[has_archive]" value="1" <?php checked( ! empty( $acm_config['has_archive'] ) ); ?> />
							<?php esc_html_e( 'Enable a post type archive page', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Hierarchical', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_post_type[hierarchical]" value="1" <?php checked( ! empty( $acm_config['hierarchical'] ) ); ?> />
							<?php esc_html_e( 'Allow parent and child items like pages', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Categories', 'author-content-manager' ); ?></th>
					<td>
						<input type="hidden" name="acm_post_type[enable_categories]" value="0" />
						<label>
							<input type="checkbox" name="acm_post_type[enable_categories]" value="1" <?php checked( ! empty( $acm_config['enable_categories'] ) ); ?> />
							<?php esc_html_e( 'Enable WordPress categories for this post type', 'author-content-manager' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Adds the category editor box, category columns and category filtering to this post type.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Tags', 'author-content-manager' ); ?></th>
					<td>
						<input type="hidden" name="acm_post_type[enable_tags]" value="0" />
						<label>
							<input type="checkbox" name="acm_post_type[enable_tags]" value="1" <?php checked( ! empty( $acm_config['enable_tags'] ) ); ?> />
							<?php esc_html_e( 'Enable WordPress tags for this post type', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Active', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_post_type[active]" value="1" <?php checked( ! empty( $acm_config['active'] ) ); ?> />
							<?php esc_html_e( 'Register this post type on the website', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Permalinks', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_rewrite_slug"><?php esc_html_e( 'Rewrite Slug', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="text" class="regular-text code" id="acm_rewrite_slug" name="acm_post_type[rewrite_slug]" value="<?php echo esc_attr( (string) $acm_config['rewrite_slug'] ); ?>" />
						<p class="description">
							<?php
							printf(
								/* translators: %s: post type key. */
								esc_html__( 'Leave empty to use the post type key (%s). Saved permalinks are refreshed on the next request.', 'author-content-manager' ),
								esc_html( (string) $acm_config['slug'] )
							);
							?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Supports', 'author-content-manager' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Choose the editing features available for this post type.', 'author-content-manager' ); ?></p>

			<fieldset class="acm-checkbox-grid">
				<legend class="screen-reader-text"><?php esc_html_e( 'Supported features', 'author-content-manager' ); ?></legend>
				<?php foreach ( $acm_features as $acm_feature_key => $acm_feature_label ) : ?>
					<label>
						<input type="checkbox" name="acm_post_type[supports][]" value="<?php echo esc_attr( $acm_feature_key ); ?>" <?php checked( in_array( $acm_feature_key, (array) $acm_config['supports'], true ) ); ?> />
						<?php echo esc_html( $acm_feature_label ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Shortcode Display Settings', 'author-content-manager' ); ?></h2>
			<p class="description"><?php esc_html_e( 'These defaults are used by [acm_posts] and by the dedicated shortcode for this post type. Every value can be overridden per shortcode.', 'author-content-manager' ); ?></p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_shortcode_layout"><?php esc_html_e( 'Layout', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_shortcode_layout" name="acm_post_type[shortcode_layout]">
							<option value="grid" <?php selected( $acm_config['shortcode_layout'], 'grid' ); ?>><?php esc_html_e( 'Grid', 'author-content-manager' ); ?></option>
							<option value="list" <?php selected( $acm_config['shortcode_layout'], 'list' ); ?>><?php esc_html_e( 'List', 'author-content-manager' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_shortcode_columns"><?php esc_html_e( 'Grid Columns', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="number" class="small-text" id="acm_shortcode_columns" name="acm_post_type[shortcode_columns]" value="<?php echo esc_attr( (string) $acm_config['shortcode_columns'] ); ?>" min="1" max="6" step="1" />
						<p class="description"><?php esc_html_e( 'Between 1 and 6. Ignored by the list layout.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_shortcode_per_page"><?php esc_html_e( 'Posts Per Page', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="number" class="small-text" id="acm_shortcode_per_page" name="acm_post_type[shortcode_per_page]" value="<?php echo esc_attr( (string) $acm_config['shortcode_per_page'] ); ?>" min="1" max="50" step="1" />
						<p class="description"><?php esc_html_e( 'Default number of posts. Use posts_per_page="-1" in a shortcode to show all.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_shortcode_image_size"><?php esc_html_e( 'Image Size', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_shortcode_image_size" name="acm_post_type[shortcode_image_size]">
							<option value=""><?php esc_html_e( 'Use the global setting', 'author-content-manager' ); ?></option>
							<?php foreach ( $acm_image_sizes as $acm_size ) : ?>
								<option value="<?php echo esc_attr( $acm_size ); ?>" <?php selected( $acm_config['shortcode_image_size'], $acm_size ); ?>><?php echo esc_html( $acm_size ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show In Cards', 'author-content-manager' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Elements shown in each card', 'author-content-manager' ); ?></legend>
							<input type="hidden" name="acm_post_type[shortcode_show_image]" value="0" />
							<label><input type="checkbox" name="acm_post_type[shortcode_show_image]" value="1" <?php checked( ! empty( $acm_config['shortcode_show_image'] ) ); ?> /> <?php esc_html_e( 'Featured image', 'author-content-manager' ); ?></label><br />
							<input type="hidden" name="acm_post_type[shortcode_show_excerpt]" value="0" />
							<label><input type="checkbox" name="acm_post_type[shortcode_show_excerpt]" value="1" <?php checked( ! empty( $acm_config['shortcode_show_excerpt'] ) ); ?> /> <?php esc_html_e( 'Excerpt', 'author-content-manager' ); ?></label><br />
							<input type="hidden" name="acm_post_type[shortcode_show_date]" value="0" />
							<label><input type="checkbox" name="acm_post_type[shortcode_show_date]" value="1" <?php checked( ! empty( $acm_config['shortcode_show_date'] ) ); ?> /> <?php esc_html_e( 'Date', 'author-content-manager' ); ?></label><br />
							<input type="hidden" name="acm_post_type[shortcode_show_terms]" value="0" />
							<label><input type="checkbox" name="acm_post_type[shortcode_show_terms]" value="1" <?php checked( ! empty( $acm_config['shortcode_show_terms'] ) ); ?> /> <?php esc_html_e( 'Category / term links', 'author-content-manager' ); ?></label><br />
							<input type="hidden" name="acm_post_type[shortcode_show_read_more]" value="0" />
							<label><input type="checkbox" name="acm_post_type[shortcode_show_read_more]" value="1" <?php checked( ! empty( $acm_config['shortcode_show_read_more'] ) ); ?> /> <?php esc_html_e( 'Read more link', 'author-content-manager' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_shortcode_excerpt_length"><?php esc_html_e( 'Excerpt Length', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="number" class="small-text" id="acm_shortcode_excerpt_length" name="acm_post_type[shortcode_excerpt_length]" value="<?php echo esc_attr( (string) $acm_config['shortcode_excerpt_length'] ); ?>" min="0" max="100" step="1" />
						<p class="description"><?php esc_html_e( 'Excerpt length in words. Use 0 for the full excerpt.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_shortcode_orderby"><?php esc_html_e( 'Order By', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_shortcode_orderby" name="acm_post_type[shortcode_orderby]">
							<option value="date" <?php selected( $acm_config['shortcode_orderby'], 'date' ); ?>><?php esc_html_e( 'Date', 'author-content-manager' ); ?></option>
							<option value="title" <?php selected( $acm_config['shortcode_orderby'], 'title' ); ?>><?php esc_html_e( 'Title', 'author-content-manager' ); ?></option>
							<option value="menu_order" <?php selected( $acm_config['shortcode_orderby'], 'menu_order' ); ?>><?php esc_html_e( 'Menu order', 'author-content-manager' ); ?></option>
							<option value="modified" <?php selected( $acm_config['shortcode_orderby'], 'modified' ); ?>><?php esc_html_e( 'Last modified', 'author-content-manager' ); ?></option>
							<option value="rand" <?php selected( $acm_config['shortcode_orderby'], 'rand' ); ?>><?php esc_html_e( 'Random', 'author-content-manager' ); ?></option>
							<option value="ID" <?php selected( $acm_config['shortcode_orderby'], 'ID' ); ?>><?php esc_html_e( 'Post ID', 'author-content-manager' ); ?></option>
						</select>
						<select id="acm_shortcode_order" name="acm_post_type[shortcode_order]">
							<option value="DESC" <?php selected( $acm_config['shortcode_order'], 'DESC' ); ?>><?php esc_html_e( 'Descending', 'author-content-manager' ); ?></option>
							<option value="ASC" <?php selected( $acm_config['shortcode_order'], 'ASC' ); ?>><?php esc_html_e( 'Ascending', 'author-content-manager' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_shortcode_category_taxonomy"><?php esc_html_e( 'Category Taxonomy', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_shortcode_category_taxonomy" name="acm_post_type[shortcode_category_taxonomy]">
							<option value=""><?php esc_html_e( 'None', 'author-content-manager' ); ?></option>
							<?php foreach ( $acm_taxonomies as $acm_taxonomy_key => $acm_taxonomy_label ) : ?>
								<option value="<?php echo esc_attr( $acm_taxonomy_key ); ?>" <?php selected( $acm_config['shortcode_category_taxonomy'], $acm_taxonomy_key ); ?>><?php echo esc_html( $acm_taxonomy_label ); ?> (<?php echo esc_html( $acm_taxonomy_key ); ?>)</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Taxonomy used by the category filter and by the category links in cards. Enable Categories or Tags, or attach a custom taxonomy to this post type, before selecting it.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Custom Fields', 'author-content-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Add fields to the editor of this post type, for example price, duration, location or a photo. Values are displayed with the shortcodes listed in the documentation.', 'author-content-manager' ); ?>
			</p>

			<input type="hidden" name="acm_post_type[fields_present]" value="1" />

			<table class="widefat acm-fields-builder">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Label', 'author-content-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Key', 'author-content-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Type', 'author-content-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Description', 'author-content-manager' ); ?></th>
						<th scope="col" class="acm-fields-builder__required"><?php esc_html_e( 'Required', 'author-content-manager' ); ?></th>
						<th scope="col" class="acm-fields-builder__actions"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'author-content-manager' ); ?></span></th>
					</tr>
				</thead>
				<tbody id="acm-fields-body">
					<?php
					$acm_fields = isset( $acm_config['fields'] ) && is_array( $acm_config['fields'] ) ? array_values( $acm_config['fields'] ) : array();

					foreach ( $acm_fields as $acm_field_index => $acm_field ) :
						$acm_field = wp_parse_args(
							$acm_field,
							array(
								'label'       => '',
								'key'         => '',
								'type'        => 'text',
								'description' => '',
								'required'    => 0,
							)
						);
						?>
						<tr class="acm-field-row">
							<td><input type="text" class="widefat" name="acm_post_type[fields][<?php echo esc_attr( (string) $acm_field_index ); ?>][label]" value="<?php echo esc_attr( (string) $acm_field['label'] ); ?>" /></td>
							<td><input type="text" class="widefat code" name="acm_post_type[fields][<?php echo esc_attr( (string) $acm_field_index ); ?>][key]" value="<?php echo esc_attr( (string) $acm_field['key'] ); ?>" maxlength="40" data-acm-field-key="1" /></td>
							<td>
								<select name="acm_post_type[fields][<?php echo esc_attr( (string) $acm_field_index ); ?>][type]">
									<?php foreach ( $acm_field_types as $acm_type_key => $acm_type_label ) : ?>
										<option value="<?php echo esc_attr( $acm_type_key ); ?>" <?php selected( $acm_field['type'], $acm_type_key ); ?>><?php echo esc_html( $acm_type_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td><input type="text" class="widefat" name="acm_post_type[fields][<?php echo esc_attr( (string) $acm_field_index ); ?>][description]" value="<?php echo esc_attr( (string) $acm_field['description'] ); ?>" /></td>
							<td class="acm-fields-builder__required">
								<input type="checkbox" name="acm_post_type[fields][<?php echo esc_attr( (string) $acm_field_index ); ?>][required]" value="1" <?php checked( ! empty( $acm_field['required'] ) ); ?> />
							</td>
							<td class="acm-fields-builder__actions">
								<button type="button" class="button-link acm-remove-field" data-acm-remove-field="1"><?php esc_html_e( 'Remove', 'author-content-manager' ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p>
				<button type="button" class="button" id="acm-add-field"><?php esc_html_e( 'Add Field', 'author-content-manager' ); ?></button>
			</p>

			<template id="acm-field-template">
				<tr class="acm-field-row">
					<td><input type="text" class="widefat" name="acm_post_type[fields][__INDEX__][label]" value="" /></td>
					<td><input type="text" class="widefat code" name="acm_post_type[fields][__INDEX__][key]" value="" maxlength="40" data-acm-field-key="1" /></td>
					<td>
						<select name="acm_post_type[fields][__INDEX__][type]">
							<?php foreach ( $acm_field_types as $acm_type_key => $acm_type_label ) : ?>
								<option value="<?php echo esc_attr( $acm_type_key ); ?>"><?php echo esc_html( $acm_type_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td><input type="text" class="widefat" name="acm_post_type[fields][__INDEX__][description]" value="" /></td>
					<td class="acm-fields-builder__required"><input type="checkbox" name="acm_post_type[fields][__INDEX__][required]" value="1" /></td>
					<td class="acm-fields-builder__actions"><button type="button" class="button-link acm-remove-field" data-acm-remove-field="1"><?php esc_html_e( 'Remove', 'author-content-manager' ); ?></button></td>
				</tr>
			</template>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_field_display"><?php esc_html_e( 'Field Display', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_field_display" name="acm_post_type[field_display]">
							<option value="shortcode" <?php selected( $acm_config['field_display'], 'shortcode' ); ?>><?php esc_html_e( 'Only with shortcodes', 'author-content-manager' ); ?></option>
							<option value="auto" <?php selected( $acm_config['field_display'], 'auto' ); ?>><?php esc_html_e( 'Automatically below the post content', 'author-content-manager' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Shortcodes work in every mode. Automatic display appends all non-empty fields to the single post view.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Labels', 'author-content-manager' ); ?></th>
					<td>
						<input type="hidden" name="acm_post_type[field_show_labels]" value="0" />
						<label>
							<input type="checkbox" name="acm_post_type[field_show_labels]" value="1" <?php checked( ! empty( $acm_config['field_show_labels'] ) ); ?> />
							<?php esc_html_e( 'Show field labels with the values', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_field_image_size"><?php esc_html_e( 'Image Size', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_field_image_size" name="acm_post_type[field_image_size]">
							<option value=""><?php esc_html_e( 'Use the global setting', 'author-content-manager' ); ?></option>
							<?php foreach ( $acm_image_sizes as $acm_size ) : ?>
								<option value="<?php echo esc_attr( $acm_size ); ?>" <?php selected( $acm_config['field_image_size'], $acm_size ); ?>><?php echo esc_html( $acm_size ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Size used for images added through these custom fields.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel" id="acm-shortcode">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Shortcode', 'author-content-manager' ); ?></h2>
			<p>
				<?php esc_html_e( 'Use the dedicated shortcode for this post type, or the generic shortcode with a post_type attribute. Both use the display settings above and can be overridden per shortcode.', 'author-content-manager' ); ?>
			</p>

			<p class="acm-code-row">
				<code><?php echo esc_html( '[' . $acm_shortcode_tag . ']' ); ?></code>
				<button type="button" class="button button-small acm-copy" data-acm-copy="<?php echo esc_attr( '[' . $acm_shortcode_tag . ']' ); ?>"><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
			</p>
			<p class="acm-code-row">
				<code><?php echo esc_html( $acm_shortcode_example ); ?></code>
				<button type="button" class="button button-small acm-copy" data-acm-copy="<?php echo esc_attr( $acm_shortcode_example ); ?>"><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
			</p>

			<p class="description">
				<?php esc_html_e( 'The dedicated shortcode is registered while this post type exists. If its tag is already used by another plugin, use the generic [acm_posts] shortcode instead. Changing the post type key changes the dedicated shortcode tag.', 'author-content-manager' ); ?>
			</p>

			<h3><?php esc_html_e( 'Parameters', 'author-content-manager' ); ?></h3>
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

			<h3><?php esc_html_e( 'Examples', 'author-content-manager' ); ?></h3>
			<pre><code>[<?php echo esc_html( $acm_shortcode_tag ); ?>]
[acm_posts post_type="<?php echo esc_html( $acm_example_slug ); ?>" layout="grid" columns="3" posts_per_page="6"]
[acm_posts post_type="<?php echo esc_html( $acm_example_slug ); ?>" layout="list"]
[acm_posts post_type="<?php echo esc_html( $acm_example_slug ); ?>" category="news"]
[acm_posts post_type="<?php echo esc_html( $acm_example_slug ); ?>" ids="12,15,18"]
[acm_posts post_type="<?php echo esc_html( $acm_example_slug ); ?>" show_image="false" show_excerpt="false" title="Latest"]</code></pre>

			<h3><?php esc_html_e( 'Category filtering', 'author-content-manager' ); ?></h3>
			<p>
				<?php
				if ( '' !== (string) $acm_config['shortcode_category_taxonomy'] ) {
					printf(
						/* translators: %s: taxonomy key. */
						esc_html__( 'Category and term filters use the %s taxonomy configured above. Use comma separated slugs or IDs in category="..." or term="...". Use taxonomy="..." to override it for one shortcode.', 'author-content-manager' ),
						'<code>' . esc_html( $acm_config['shortcode_category_taxonomy'] ) . '</code>'
					);
				} else {
					esc_html_e( 'No category taxonomy is selected yet. Enable Categories or Tags above, attach a custom taxonomy to this post type, then choose it in the Category Taxonomy setting.', 'author-content-manager' );
				}
				?>
			</p>

			<h3><?php esc_html_e( 'Display behavior', 'author-content-manager' ); ?></h3>
			<ul class="ul-disc">
				<li><?php esc_html_e( 'Only published posts are displayed.', 'author-content-manager' ); ?></li>
				<li><?php esc_html_e( 'Nothing is printed when no posts match the query.', 'author-content-manager' ); ?></li>
				<li><?php esc_html_e( 'Empty images, excerpts, dates, terms and read more links are omitted automatically.', 'author-content-manager' ); ?></li>
				<li><?php esc_html_e( 'Every shortcode attribute overrides the matching display setting for that instance only.', 'author-content-manager' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Template overrides', 'author-content-manager' ); ?></h3>
			<p><?php esc_html_e( 'Copy any template into your theme to customize the markup.', 'author-content-manager' ); ?></p>
			<pre><code>yourtheme/author-content-manager/post-grid.php
yourtheme/author-content-manager/post-list.php
yourtheme/author-content-manager/post-card.php</code></pre>

			<p>
				<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-documentation' ) ); ?>"><?php esc_html_e( 'Open Full Documentation', 'author-content-manager' ); ?></a>
			</p>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php echo $acm_is_new ? esc_html__( 'Create Post Type', 'author-content-manager' ) : esc_html__( 'Save Changes', 'author-content-manager' ); ?></button>
			<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-types' ) ); ?>"><?php esc_html_e( 'Cancel', 'author-content-manager' ); ?></a>
		</p>
	</form>
</div>
