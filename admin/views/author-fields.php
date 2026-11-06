<?php
/**
 * Author custom fields list view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_fields = isset( $args['fields'] ) && is_array( $args['fields'] ) ? $args['fields'] : array();
$acm_types  = ACM_Custom_Fields::types();
?>
<div class="wrap acm-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Author Custom Fields', 'author-content-manager' ); ?></h1>
	<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-field-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Field', 'author-content-manager' ); ?></a>
	<hr class="wp-header-end" />

	<p class="acm-lead">
		<?php esc_html_e( 'Add extra profile fields such as department, job title, certifications or photo galleries. Fields marked for the profile are rendered automatically on the author profile page, and every field can be printed anywhere with a shortcode.', 'author-content-manager' ); ?>
	</p>

	<?php if ( empty( $acm_fields ) ) : ?>
		<div class="acm-empty">
			<h2><?php esc_html_e( 'No custom author fields yet', 'author-content-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create a field to extend every author profile.', 'author-content-manager' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-field-edit' ) ); ?>"><?php esc_html_e( 'Add New Field', 'author-content-manager' ); ?></a>
			</p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped acm-table">
			<thead>
				<tr>
					<th scope="col" class="column-primary"><?php esc_html_e( 'Label', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Key', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Type', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Required', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'On Profile', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Shortcode', 'author-content-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'author-content-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $acm_fields as $acm_field ) : ?>
					<?php
					$acm_type  = isset( $acm_field['type'] ) ? $acm_field['type'] : 'text';
					$acm_short = '[custom_author_field field="' . $acm_field['key'] . '"]';
					?>
					<tr>
						<td class="column-primary">
							<strong><a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-field-edit', array( 'field' => $acm_field['key'] ) ) ); ?>"><?php echo esc_html( $acm_field['label'] ); ?></a></strong>
							<div class="row-actions">
								<span class="edit">
									<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-field-edit', array( 'field' => $acm_field['key'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'author-content-manager' ); ?></a>
								</span>
								<span class="trash">
									| <a
										class="submitdelete"
										href="<?php echo esc_url( ACM_Admin::action_url( 'acm_delete_author_field', array( 'field' => $acm_field['key'] ) ) ); ?>"
										data-acm-confirm="<?php echo esc_attr__( 'Delete this custom field? Stored values will be kept in the database.', 'author-content-manager' ); ?>"
									><?php esc_html_e( 'Delete', 'author-content-manager' ); ?></a>
								</span>
							</div>
						</td>
						<td><code><?php echo esc_html( $acm_field['key'] ); ?></code></td>
						<td><?php echo esc_html( isset( $acm_types[ $acm_type ] ) ? $acm_types[ $acm_type ] : $acm_type ); ?></td>
						<td><?php echo ! empty( $acm_field['required'] ) ? esc_html__( 'Yes', 'author-content-manager' ) : '&mdash;'; ?></td>
						<td><?php echo ! empty( $acm_field['show_in_profile'] ) ? esc_html__( 'Yes', 'author-content-manager' ) : '&mdash;'; ?></td>
						<td>
							<code><?php echo esc_html( $acm_short ); ?></code>
							<button type="button" class="button button-small acm-copy" data-acm-copy="<?php echo esc_attr( $acm_short ); ?>"><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
						</td>
						<td>
							<a class="button button-small" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-field-edit', array( 'field' => $acm_field['key'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'author-content-manager' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<div class="acm-note">
		<span class="dashicons dashicons-info" aria-hidden="true"></span>
		<?php esc_html_e( 'Deleting a field definition keeps the values already stored for existing authors. Use [custom_author_fields] to print all custom fields for an author.', 'author-content-manager' ); ?>
	</div>
</div>
