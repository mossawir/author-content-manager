<?php
/**
 * Taxonomy editor view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_config        = isset( $args['config'] ) ? $args['config'] : ACM_Taxonomy_Registry::defaults();
$acm_original_slug = isset( $args['original_slug'] ) ? (string) $args['original_slug'] : '';
$acm_is_new        = ! empty( $args['is_new'] );
$acm_error_html    = isset( $args['error_html'] ) ? $args['error_html'] : '';
$acm_post_types    = ACM_Taxonomy_Registry::available_post_types();
$acm_selected      = array_map( 'sanitize_key', (array) $acm_config['post_types'] );

if ( $acm_is_new && empty( $acm_error_html ) ) {
	$acm_selected = array_values( array_intersect( array( 'post', ACM_POST_TYPE ), array_keys( $acm_post_types ) ) );
}

$acm_form_url = ACM_Admin::page_url( 'acm-taxonomy-edit', '' !== $acm_original_slug ? array( 'slug' => $acm_original_slug ) : array() );
?>
<div class="wrap acm-wrap">
	<h1 class="wp-heading-inline">
		<?php echo $acm_is_new ? esc_html__( 'Add New Taxonomy', 'author-content-manager' ) : esc_html__( 'Edit Taxonomy', 'author-content-manager' ); ?>
	</h1>
	<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-taxonomies' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to list', 'author-content-manager' ); ?></a>
	<hr class="wp-header-end" />

	<?php echo wp_kses_post( $acm_error_html ); ?>

	<form method="post" action="<?php echo esc_url( $acm_form_url ); ?>" class="acm-form">
		<?php wp_nonce_field( 'acm_save_taxonomy' ); ?>
		<input type="hidden" name="acm_action" value="save_taxonomy" />
		<input type="hidden" name="acm_original_slug" value="<?php echo esc_attr( $acm_original_slug ); ?>" />

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Labels', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_tax_plural"><?php esc_html_e( 'Taxonomy Name (plural)', 'author-content-manager' ); ?> <span class="acm-required">*</span></label></th>
					<td>
						<input type="text" class="regular-text" id="acm_tax_plural" name="acm_taxonomy[plural]" value="<?php echo esc_attr( (string) $acm_config['plural'] ); ?>" required="required" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_tax_singular"><?php esc_html_e( 'Singular Name', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="acm_tax_singular" name="acm_taxonomy[singular]" value="<?php echo esc_attr( (string) $acm_config['singular'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Defaults to the plural name.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_tax_slug"><?php esc_html_e( 'Taxonomy Slug', 'author-content-manager' ); ?> <span class="acm-required">*</span></label></th>
					<td>
						<input type="text" class="regular-text code" id="acm_tax_slug" name="acm_taxonomy[slug]" value="<?php echo esc_attr( (string) $acm_config['slug'] ); ?>" maxlength="32" data-acm-slug="1" />
						<p class="description"><?php esc_html_e( 'Lowercase letters, numbers, dashes and underscores only, 32 characters maximum.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_tax_description"><?php esc_html_e( 'Description', 'author-content-manager' ); ?></label></th>
					<td>
						<textarea class="large-text" id="acm_tax_description" name="acm_taxonomy[description]" rows="3"><?php echo esc_textarea( (string) $acm_config['description'] ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Associated Post Types', 'author-content-manager' ); ?></h2>
			<p class="description"><?php esc_html_e( 'The taxonomy is attached to every selected post type. Choose at least one.', 'author-content-manager' ); ?></p>

			<?php if ( empty( $acm_post_types ) ) : ?>
				<p><?php esc_html_e( 'No post types are available yet. Create a custom post type first.', 'author-content-manager' ); ?></p>
			<?php else : ?>
				<fieldset class="acm-checkbox-grid">
					<legend class="screen-reader-text"><?php esc_html_e( 'Associated post types', 'author-content-manager' ); ?></legend>
					<?php foreach ( $acm_post_types as $acm_post_type => $acm_label ) : ?>
						<label>
							<input type="checkbox" name="acm_taxonomy[post_types][]" value="<?php echo esc_attr( $acm_post_type ); ?>" <?php checked( in_array( $acm_post_type, $acm_selected, true ) ); ?> />
							<?php echo esc_html( $acm_label ); ?>
							<code><?php echo esc_html( $acm_post_type ); ?></code>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Behaviour', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Hierarchical', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_taxonomy[hierarchical]" value="1" <?php checked( ! empty( $acm_config['hierarchical'] ) ); ?> />
							<?php esc_html_e( 'Category-like: terms can have parents', 'author-content-manager' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Leave unchecked for tag-like behaviour.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Public', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_taxonomy[public]" value="1" <?php checked( ! empty( $acm_config['public'] ) ); ?> />
							<?php esc_html_e( 'Show term links and term archives on the frontend', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show in REST API', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_taxonomy[show_in_rest]" value="1" <?php checked( ! empty( $acm_config['show_in_rest'] ) ); ?> />
							<?php esc_html_e( 'Enable the block editor term panel and REST endpoints', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show in Admin Column', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_taxonomy[show_admin_column]" value="1" <?php checked( ! empty( $acm_config['show_admin_column'] ) ); ?> />
							<?php esc_html_e( 'Add a term column to the post list tables', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show in Navigation Menus', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_taxonomy[show_in_nav_menus]" value="1" <?php checked( ! empty( $acm_config['show_in_nav_menus'] ) ); ?> />
							<?php esc_html_e( 'Allow term archives in Appearance → Menus', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Active', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_taxonomy[active]" value="1" <?php checked( ! empty( $acm_config['active'] ) ); ?> />
							<?php esc_html_e( 'Register this taxonomy on the website', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Permalinks', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_tax_rewrite_slug"><?php esc_html_e( 'Rewrite Slug', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="text" class="regular-text code" id="acm_tax_rewrite_slug" name="acm_taxonomy[rewrite_slug]" value="<?php echo esc_attr( (string) $acm_config['rewrite_slug'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Leave empty to use the taxonomy key. Permalinks are refreshed on the next request.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php echo $acm_is_new ? esc_html__( 'Create Taxonomy', 'author-content-manager' ) : esc_html__( 'Save Changes', 'author-content-manager' ); ?></button>
			<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-taxonomies' ) ); ?>"><?php esc_html_e( 'Cancel', 'author-content-manager' ); ?></a>
		</p>
	</form>
</div>
