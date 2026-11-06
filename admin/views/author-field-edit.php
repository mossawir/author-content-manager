<?php
/**
 * Author custom field editor view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_field         = isset( $args['field'] ) ? $args['field'] : array();
$acm_original_key  = isset( $args['original_key'] ) ? (string) $args['original_key'] : '';
$acm_is_new        = ! empty( $args['is_new'] );
$acm_error_html    = isset( $args['error_html'] ) ? $args['error_html'] : '';
$acm_types         = ACM_Custom_Fields::types();
$acm_form_url      = ACM_Admin::page_url( 'acm-author-field-edit', '' !== $acm_original_key ? array( 'field' => $acm_original_key ) : array() );
?>
<div class="wrap acm-wrap">
	<h1 class="wp-heading-inline">
		<?php echo $acm_is_new ? esc_html__( 'Add New Author Field', 'author-content-manager' ) : esc_html__( 'Edit Author Field', 'author-content-manager' ); ?>
	</h1>
	<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-fields' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to list', 'author-content-manager' ); ?></a>
	<hr class="wp-header-end" />

	<?php echo wp_kses_post( $acm_error_html ); ?>

	<form method="post" action="<?php echo esc_url( $acm_form_url ); ?>" class="acm-form">
		<?php wp_nonce_field( 'acm_save_author_field' ); ?>
		<input type="hidden" name="acm_action" value="save_author_field" />
		<input type="hidden" name="acm_original_field" value="<?php echo esc_attr( $acm_original_key ); ?>" />

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Field Settings', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_field_label"><?php esc_html_e( 'Label', 'author-content-manager' ); ?> <span class="acm-required">*</span></label></th>
					<td>
						<input type="text" class="regular-text" id="acm_field_label" name="acm_field[label]" value="<?php echo esc_attr( (string) $acm_field['label'] ); ?>" required="required" />
						<p class="description"><?php esc_html_e( 'Shown next to the value on the author profile.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_field_key"><?php esc_html_e( 'Field Key', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="text" class="regular-text code" id="acm_field_key" name="acm_field[key]" value="<?php echo esc_attr( (string) $acm_field['key'] ); ?>" maxlength="40" data-acm-field-key="1" />
						<p class="description"><?php esc_html_e( 'Lowercase letters, numbers and underscores. Leave empty to generate it from the label.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_field_type"><?php esc_html_e( 'Field Type', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_field_type" name="acm_field[type]">
							<?php foreach ( $acm_types as $acm_type_key => $acm_type_label ) : ?>
								<option value="<?php echo esc_attr( $acm_type_key ); ?>" <?php selected( $acm_field['type'], $acm_type_key ); ?>><?php echo esc_html( $acm_type_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_field_description"><?php esc_html_e( 'Description', 'author-content-manager' ); ?></label></th>
					<td>
						<textarea class="large-text" id="acm_field_description" name="acm_field[description]" rows="3"><?php echo esc_textarea( (string) $acm_field['description'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Optional help text shown in the author editor.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Required', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_field[required]" value="1" <?php checked( ! empty( $acm_field['required'] ) ); ?> />
							<?php esc_html_e( 'Warn when this field is empty on save', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Profile Display', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_field[show_in_profile]" value="1" <?php checked( ! empty( $acm_field['show_in_profile'] ) ); ?> />
							<?php esc_html_e( 'Show this field automatically on the author profile', 'author-content-manager' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'The [custom_author_field] shortcode works even when automatic display is disabled.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php echo $acm_is_new ? esc_html__( 'Create Field', 'author-content-manager' ) : esc_html__( 'Save Changes', 'author-content-manager' ); ?></button>
			<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-fields' ) ); ?>"><?php esc_html_e( 'Cancel', 'author-content-manager' ); ?></a>
		</p>
	</form>

	<div class="acm-panel">
		<h2 class="acm-panel__title"><?php esc_html_e( 'Shortcode', 'author-content-manager' ); ?></h2>
		<p><?php esc_html_e( 'Print this field for a specific author, or print every custom field of an author.', 'author-content-manager' ); ?></p>
		<p class="acm-code-row">
			<code>[custom_author_field id="123" field="<?php echo esc_attr( (string) $acm_field['key'] ); ?>"]</code>
			<button type="button" class="button button-small acm-copy" data-acm-copy="[custom_author_field id=&quot;123&quot; field=&quot;<?php echo esc_attr( (string) $acm_field['key'] ); ?>&quot;]"><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
		</p>
		<p class="acm-code-row">
			<code>[custom_author_fields id="123"]</code>
			<button type="button" class="button button-small acm-copy" data-acm-copy="[custom_author_fields id=&quot;123&quot;]"><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
		</p>
	</div>
</div>
