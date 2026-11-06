<?php
/**
 * Post type list view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : array();
$acm_features = ACM_Post_Type_Registry::supported_features();
?>
<div class="wrap acm-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Custom Post Types', 'author-content-manager' ); ?></h1>
	<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-type-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Post Type', 'author-content-manager' ); ?></a>
	<hr class="wp-header-end" />

	<p class="acm-lead"><?php esc_html_e( 'Register unlimited post types without code. Deactivating or deleting a configuration never deletes posts.', 'author-content-manager' ); ?></p>

	<?php if ( empty( $acm_items ) ) : ?>
		<div class="acm-empty">
			<h2><?php esc_html_e( 'No custom post types yet', 'author-content-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create your first custom post type, for example Portfolio, Services or Events.', 'author-content-manager' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-type-edit' ) ); ?>"><?php esc_html_e( 'Add New Post Type', 'author-content-manager' ); ?></a>
			</p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped acm-table">
			<thead>
				<tr>
					<th scope="col" class="column-primary"><?php esc_html_e( 'Post Type', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Key', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Content', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Supports', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Fields', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'author-content-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $acm_items as $acm_slug => $acm_item ) : ?>
					<?php
					$acm_config = $acm_item['config'];
					$acm_counts = $acm_item['counts'];
					$acm_active = ! empty( $acm_config['active'] );
					$acm_supports = array();

					foreach ( (array) $acm_config['supports'] as $acm_feature ) {
						if ( isset( $acm_features[ $acm_feature ] ) ) {
							$acm_supports[] = $acm_features[ $acm_feature ];
						}
					}
					?>
					<tr>
						<td class="column-primary">
							<strong><a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-type-edit', array( 'slug' => $acm_slug ) ) ); ?>"><?php echo esc_html( $acm_config['plural'] ); ?></a></strong>
							<div class="row-actions">
								<span class="edit">
									<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-type-edit', array( 'slug' => $acm_slug ) ) ); ?>"><?php esc_html_e( 'Edit', 'author-content-manager' ); ?></a>
								</span>
								<?php if ( $acm_active && post_type_exists( $acm_slug ) ) : ?>
									<span class="view">
										| <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $acm_slug ) ); ?>"><?php esc_html_e( 'View posts', 'author-content-manager' ); ?></a>
									</span>
								<?php endif; ?>
								<span class="view">
									| <a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-type-edit', array( 'slug' => $acm_slug ) ) . '#acm-shortcode' ); ?>"><?php esc_html_e( 'Shortcode', 'author-content-manager' ); ?></a>
								</span>
								<span class="trash">
									| <a
										class="submitdelete"
										href="<?php echo esc_url( ACM_Admin::action_url( 'acm_delete_post_type', array( 'slug' => $acm_slug ) ) ); ?>"
										data-acm-confirm="<?php echo esc_attr__( 'Delete this configuration? Existing posts will be kept.', 'author-content-manager' ); ?>"
									><?php esc_html_e( 'Delete', 'author-content-manager' ); ?></a>
								</span>
							</div>
						</td>
						<td><code><?php echo esc_html( (string) $acm_slug ); ?></code></td>
						<td>
							<?php
							printf(
								/* translators: 1: total post count, 2: published post count. */
								esc_html__( '%1$s total, %2$s published', 'author-content-manager' ),
								esc_html( number_format_i18n( (int) $acm_counts['total'] ) ),
								esc_html( number_format_i18n( (int) $acm_counts['published'] ) )
							);
							?>
						</td>
						<td><?php echo esc_html( implode( ', ', $acm_supports ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( isset( $acm_config['fields'] ) && is_array( $acm_config['fields'] ) ? count( $acm_config['fields'] ) : 0 ) ); ?></td>
						<td>
							<?php if ( $acm_active ) : ?>
								<span class="acm-badge acm-badge--active"><?php esc_html_e( 'Active', 'author-content-manager' ); ?></span>
							<?php else : ?>
								<span class="acm-badge acm-badge--inactive"><?php esc_html_e( 'Inactive', 'author-content-manager' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $acm_active ) : ?>
								<a class="button button-small" href="<?php echo esc_url( ACM_Admin::action_url( 'acm_toggle_post_type', array( 'slug' => $acm_slug, 'active' => 0 ) ) ); ?>"><?php esc_html_e( 'Deactivate', 'author-content-manager' ); ?></a>
							<?php else : ?>
								<a class="button button-small" href="<?php echo esc_url( ACM_Admin::action_url( 'acm_toggle_post_type', array( 'slug' => $acm_slug, 'active' => 1 ) ) ); ?>"><?php esc_html_e( 'Activate', 'author-content-manager' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<div class="acm-note">
		<span class="dashicons dashicons-info" aria-hidden="true"></span>
		<?php esc_html_e( 'Deleting a configuration only removes the definition. Posts remain in the database and become available again if you recreate a post type with the same key.', 'author-content-manager' ); ?>
	</div>
</div>
