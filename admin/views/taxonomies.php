<?php
/**
 * Taxonomy list view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : array();

/**
 * Resolve a readable post type label.
 *
 * @param string $post_type Post type key.
 * @return string
 */
$acm_post_type_label = static function ( $post_type ) {
	$object = get_post_type_object( $post_type );

	if ( $object && isset( $object->labels->singular_name ) ) {
		return $object->labels->singular_name;
	}

	$config = ACM_Post_Type_Registry::get( $post_type );

	if ( $config && isset( $config['singular'] ) ) {
		return (string) $config['singular'];
	}

	return $post_type;
};
?>
<div class="wrap acm-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Custom Taxonomies', 'author-content-manager' ); ?></h1>
	<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-taxonomy-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Taxonomy', 'author-content-manager' ); ?></a>
	<hr class="wp-header-end" />

	<p class="acm-lead"><?php esc_html_e( 'Group content with category-like or tag-like taxonomies and attach them to any registered post type.', 'author-content-manager' ); ?></p>

	<?php if ( empty( $acm_items ) ) : ?>
		<div class="acm-empty">
			<h2><?php esc_html_e( 'No custom taxonomies yet', 'author-content-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create a taxonomy to organise your custom post types or author profiles.', 'author-content-manager' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-taxonomy-edit' ) ); ?>"><?php esc_html_e( 'Add New Taxonomy', 'author-content-manager' ); ?></a>
			</p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped acm-table">
			<thead>
				<tr>
					<th scope="col" class="column-primary"><?php esc_html_e( 'Taxonomy', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Key', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Post Types', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Terms', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Type', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'author-content-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $acm_items as $acm_slug => $acm_item ) : ?>
					<?php
					$acm_config = $acm_item['config'];
					$acm_active = ! empty( $acm_config['active'] );
					$acm_types  = array();

					foreach ( (array) $acm_config['post_types'] as $acm_post_type ) {
						$acm_types[] = $acm_post_type_label( $acm_post_type );
					}
					?>
					<tr>
						<td class="column-primary">
							<strong><a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-taxonomy-edit', array( 'slug' => $acm_slug ) ) ); ?>"><?php echo esc_html( $acm_config['plural'] ); ?></a></strong>
							<div class="row-actions">
								<span class="edit">
									<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-taxonomy-edit', array( 'slug' => $acm_slug ) ) ); ?>"><?php esc_html_e( 'Edit', 'author-content-manager' ); ?></a>
								</span>
								<?php if ( $acm_active && taxonomy_exists( $acm_slug ) ) : ?>
									<span class="view">
										| <a href="<?php echo esc_url( ACM_Taxonomy_Registry::admin_terms_url( $acm_slug ) ); ?>"><?php esc_html_e( 'Manage terms', 'author-content-manager' ); ?></a>
									</span>
								<?php endif; ?>
								<span class="trash">
									| <a
										class="submitdelete"
										href="<?php echo esc_url( ACM_Admin::action_url( 'acm_delete_taxonomy', array( 'slug' => $acm_slug ) ) ); ?>"
										data-acm-confirm="<?php echo esc_attr__( 'Delete this configuration? Its terms will be kept.', 'author-content-manager' ); ?>"
									><?php esc_html_e( 'Delete', 'author-content-manager' ); ?></a>
								</span>
							</div>
						</td>
						<td><code><?php echo esc_html( (string) $acm_slug ); ?></code></td>
						<td><?php echo esc_html( implode( ', ', $acm_types ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $acm_item['count'] ) ); ?></td>
						<td>
							<?php echo ! empty( $acm_config['hierarchical'] ) ? esc_html__( 'Category-like', 'author-content-manager' ) : esc_html__( 'Tag-like', 'author-content-manager' ); ?>
						</td>
						<td>
							<?php if ( $acm_active ) : ?>
								<span class="acm-badge acm-badge--active"><?php esc_html_e( 'Active', 'author-content-manager' ); ?></span>
							<?php else : ?>
								<span class="acm-badge acm-badge--inactive"><?php esc_html_e( 'Inactive', 'author-content-manager' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $acm_active ) : ?>
								<a class="button button-small" href="<?php echo esc_url( ACM_Admin::action_url( 'acm_toggle_taxonomy', array( 'slug' => $acm_slug, 'active' => 0 ) ) ); ?>"><?php esc_html_e( 'Deactivate', 'author-content-manager' ); ?></a>
							<?php else : ?>
								<a class="button button-small" href="<?php echo esc_url( ACM_Admin::action_url( 'acm_toggle_taxonomy', array( 'slug' => $acm_slug, 'active' => 1 ) ) ); ?>"><?php esc_html_e( 'Activate', 'author-content-manager' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<div class="acm-note">
		<span class="dashicons dashicons-info" aria-hidden="true"></span>
		<?php esc_html_e( 'Deleting a taxonomy configuration keeps every term and its relationships. Terms are only removed if you delete them manually.', 'author-content-manager' ); ?>
	</div>
</div>
