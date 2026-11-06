<?php
/**
 * Settings view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_settings   = isset( $args['settings'] ) ? $args['settings'] : ACM_Settings::all();
$acm_sizes      = isset( $args['sizes'] ) ? $args['sizes'] : ACM_Settings::available_image_sizes();
$acm_post_types = isset( $args['post_types'] ) ? $args['post_types'] : ACM_Settings::assignable_post_types();
$acm_assigned   = array_map( 'sanitize_key', (array) $acm_settings['assign_post_types'] );
$acm_example    = home_url( '/' . ACM_Settings::sanitize_author_base( $acm_settings['author_base'] ) . '/jane-doe/' );
?>
<div class="wrap acm-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Settings', 'author-content-manager' ); ?></h1>
	<hr class="wp-header-end" />

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="acm-form">
		<input type="hidden" name="action" value="acm_save_settings" />
		<?php wp_nonce_field( 'acm_save_settings' ); ?>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Frontend Appearance', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Frontend CSS', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_settings[enable_css]" value="1" <?php checked( ! empty( $acm_settings['enable_css'] ) ); ?> />
							<?php esc_html_e( 'Load the plugin stylesheet on the frontend', 'author-content-manager' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Disable this when your theme provides its own author styles. Custom CSS below is always printed.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_custom_css"><?php esc_html_e( 'Custom CSS', 'author-content-manager' ); ?></label></th>
					<td>
						<textarea class="large-text code" id="acm_custom_css" name="acm_settings[custom_css]" rows="10" spellcheck="false"><?php echo esc_textarea( (string) $acm_settings['custom_css'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Printed in the site head. HTML is removed automatically.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Author Defaults', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_image_size"><?php esc_html_e( 'Default Author Image Size', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_image_size" name="acm_settings[image_size]">
							<?php foreach ( $acm_sizes as $acm_size ) : ?>
								<option value="<?php echo esc_attr( $acm_size ); ?>" <?php selected( $acm_settings['image_size'], $acm_size ); ?>><?php echo esc_html( $acm_size ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_grid_columns"><?php esc_html_e( 'Default Author Grid Columns', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="number" class="small-text" id="acm_grid_columns" name="acm_settings[grid_columns]" value="<?php echo esc_attr( (string) $acm_settings['grid_columns'] ); ?>" min="1" max="6" step="1" />
						<p class="description"><?php esc_html_e( 'Used by shortcodes and the author archive. Between 1 and 6.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_default_layout"><?php esc_html_e( 'Default Author Layout', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_default_layout" name="acm_settings[default_layout]">
							<option value="vertical" <?php selected( $acm_settings['default_layout'], 'vertical' ); ?>><?php esc_html_e( 'Vertical', 'author-content-manager' ); ?></option>
							<option value="horizontal" <?php selected( $acm_settings['default_layout'], 'horizontal' ); ?>><?php esc_html_e( 'Horizontal', 'author-content-manager' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Layout used by [custom_author] when no layout attribute is given.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_bio_limit"><?php esc_html_e( 'Biography Character Limit', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="number" class="small-text" id="acm_bio_limit" name="acm_settings[bio_limit]" value="<?php echo esc_attr( (string) $acm_settings['bio_limit'] ); ?>" min="0" max="2000" step="10" />
						<p class="description"><?php esc_html_e( 'Maximum number of characters in author cards. Use 0 for the full biography.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Custom Fields', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="acm_custom_field_image_size"><?php esc_html_e( 'Custom Field Image Size', 'author-content-manager' ); ?></label></th>
					<td>
						<select id="acm_custom_field_image_size" name="acm_settings[custom_field_image_size]">
							<?php foreach ( $acm_sizes as $acm_size ) : ?>
								<option value="<?php echo esc_attr( $acm_size ); ?>" <?php selected( $acm_settings['custom_field_image_size'], $acm_size ); ?>><?php echo esc_html( $acm_size ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Default size for images added through author or post type custom fields. Each post type can override this in its own editor.', 'author-content-manager' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<p>
				<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-fields' ) ); ?>"><?php esc_html_e( 'Manage Author Custom Fields', 'author-content-manager' ); ?></a>
			</p>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Author URLs', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Author Archive Pages', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_settings[author_archive]" value="1" <?php checked( ! empty( $acm_settings['author_archive'] ) ); ?> />
							<?php esc_html_e( 'Enable the public archive of all author profiles', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="acm_author_base"><?php esc_html_e( 'Author Archive Slug', 'author-content-manager' ); ?></label></th>
					<td>
						<input type="text" class="regular-text code" id="acm_author_base" name="acm_settings[author_base]" value="<?php echo esc_attr( (string) $acm_settings['author_base'] ); ?>" />
						<p class="description">
							<?php
							printf(
								/* translators: %s: example author profile URL. */
								esc_html__( 'Example profile URL: %s', 'author-content-manager' ),
								'<code>' . esc_html( $acm_example ) . '</code>'
							);
							?>
						</p>
						<p class="description"><?php esc_html_e( 'Reserved slugs such as "author", "category" or "tag" are rejected. Permalinks are refreshed automatically.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Profile Sections', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Social Links', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_settings[enable_social]" value="1" <?php checked( ! empty( $acm_settings['enable_social'] ) ); ?> />
							<?php esc_html_e( 'Allow social profiles to be displayed', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Contact Information', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_settings[enable_contact]" value="1" <?php checked( ! empty( $acm_settings['enable_contact'] ) ); ?> />
							<?php esc_html_e( 'Allow contact details to be displayed', 'author-content-manager' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Author Assignment', 'author-content-manager' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Select the post types that should offer an "Author Profile" box in the editor. This lets you connect author profiles to unlimited content.', 'author-content-manager' ); ?></p>

			<?php if ( empty( $acm_post_types ) ) : ?>
				<p><?php esc_html_e( 'No assignable post types were found.', 'author-content-manager' ); ?></p>
			<?php else : ?>
				<fieldset class="acm-checkbox-grid">
					<legend class="screen-reader-text"><?php esc_html_e( 'Post types with author assignment', 'author-content-manager' ); ?></legend>
					<?php foreach ( $acm_post_types as $acm_post_type => $acm_label ) : ?>
						<label>
							<input type="checkbox" name="acm_settings[assign_post_types][]" value="<?php echo esc_attr( $acm_post_type ); ?>" <?php checked( in_array( $acm_post_type, $acm_assigned, true ) ); ?> />
							<?php echo esc_html( $acm_label ); ?>
							<code><?php echo esc_html( $acm_post_type ); ?></code>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>
		</div>

		<div class="acm-panel">
			<h2 class="acm-panel__title"><?php esc_html_e( 'Uninstall', 'author-content-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Remove Data on Uninstall', 'author-content-manager' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="acm_settings[delete_data]" value="1" <?php checked( ! empty( $acm_settings['delete_data'] ) ); ?> />
							<?php esc_html_e( 'Delete author profiles and plugin settings when the plugin is deleted', 'author-content-manager' ); ?>
						</label>
						<p class="description acm-warning"><?php esc_html_e( 'Off by default. Posts of custom post types and taxonomy terms are always preserved, even when this option is enabled.', 'author-content-manager' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'author-content-manager' ); ?></button>
		</p>
	</form>

	<div class="acm-panel acm-panel--danger">
		<h2 class="acm-panel__title"><?php esc_html_e( 'Reset Settings', 'author-content-manager' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="acm_reset_settings" />
			<?php wp_nonce_field( 'acm_reset_settings' ); ?>
			<p><?php esc_html_e( 'Restore every setting on this page to its default value. Author profiles and content are not affected.', 'author-content-manager' ); ?></p>
			<p>
				<button type="submit" class="button acm-button-danger" data-acm-confirm="<?php echo esc_attr__( 'Reset every plugin setting to its default value?', 'author-content-manager' ); ?>"><?php esc_html_e( 'Reset Settings', 'author-content-manager' ); ?></button>
			</p>
		</form>
	</div>

	<div class="acm-panel" id="acm-shortcodes">
		<h2 class="acm-panel__title"><?php esc_html_e( 'Shortcode Reference', 'author-content-manager' ); ?></h2>
		<p><?php esc_html_e( 'Copy a shortcode into any post, page, widget or page builder element.', 'author-content-manager' ); ?></p>

		<?php
		$this->render_view(
			'partials/shortcodes',
			array(
				'shortcodes' => ACM_Admin::shortcode_docs(),
			)
		);
		?>
	</div>
</div>
