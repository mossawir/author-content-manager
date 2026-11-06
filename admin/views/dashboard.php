<?php
/**
 * Dashboard view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_args = isset( $args ) && is_array( $args ) ? $args : array();

$acm_total_authors     = isset( $acm_args['total_authors'] ) ? (int) $acm_args['total_authors'] : 0;
$acm_published_authors = isset( $acm_args['published_authors'] ) ? (int) $acm_args['published_authors'] : 0;
$acm_post_type_count   = isset( $acm_args['post_type_count'] ) ? (int) $acm_args['post_type_count'] : 0;
$acm_taxonomy_count    = isset( $acm_args['taxonomy_count'] ) ? (int) $acm_args['taxonomy_count'] : 0;
$acm_recent_authors    = isset( $acm_args['recent_authors'] ) ? $acm_args['recent_authors'] : array();
$acm_post_types        = isset( $acm_args['post_types'] ) ? $acm_args['post_types'] : array();
$acm_taxonomies        = isset( $acm_args['taxonomies'] ) ? $acm_args['taxonomies'] : array();
?>
<div class="wrap acm-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Author & Content Manager', 'author-content-manager' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . ACM_POST_TYPE ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Author', 'author-content-manager' ); ?></a>
	<hr class="wp-header-end" />

	<p class="acm-lead"><?php esc_html_e( 'Manage author profiles, custom post types and custom taxonomies from a single place. All content stays available even if a configuration is removed.', 'author-content-manager' ); ?></p>

	<div class="acm-stats">
		<div class="acm-stat">
			<span class="dashicons dashicons-groups acm-stat__icon" aria-hidden="true"></span>
			<span class="acm-stat__value"><?php echo esc_html( number_format_i18n( $acm_total_authors ) ); ?></span>
			<span class="acm-stat__label"><?php esc_html_e( 'Total Authors', 'author-content-manager' ); ?></span>
		</div>
		<div class="acm-stat">
			<span class="dashicons dashicons-visibility acm-stat__icon" aria-hidden="true"></span>
			<span class="acm-stat__value"><?php echo esc_html( number_format_i18n( $acm_published_authors ) ); ?></span>
			<span class="acm-stat__label"><?php esc_html_e( 'Published Profiles', 'author-content-manager' ); ?></span>
		</div>
		<div class="acm-stat">
			<span class="dashicons dashicons-admin-post acm-stat__icon" aria-hidden="true"></span>
			<span class="acm-stat__value"><?php echo esc_html( number_format_i18n( $acm_post_type_count ) ); ?></span>
			<span class="acm-stat__label"><?php esc_html_e( 'Custom Post Types', 'author-content-manager' ); ?></span>
		</div>
		<div class="acm-stat">
			<span class="dashicons dashicons-tag acm-stat__icon" aria-hidden="true"></span>
			<span class="acm-stat__value"><?php echo esc_html( number_format_i18n( $acm_taxonomy_count ) ); ?></span>
			<span class="acm-stat__label"><?php esc_html_e( 'Custom Taxonomies', 'author-content-manager' ); ?></span>
		</div>
	</div>

	<div class="acm-columns">
		<div class="acm-column acm-column--main">
			<div class="acm-panel">
				<h2 class="acm-panel__title"><?php esc_html_e( 'Recent Author Profiles', 'author-content-manager' ); ?></h2>

				<form method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="acm-search-form">
					<input type="hidden" name="post_type" value="<?php echo esc_attr( ACM_POST_TYPE ); ?>" />
					<label class="screen-reader-text" for="acm-author-search"><?php esc_html_e( 'Search author profiles', 'author-content-manager' ); ?></label>
					<input type="search" id="acm-author-search" name="s" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Search name, email, city…', 'author-content-manager' ); ?>" />
					<button type="submit" class="button"><?php esc_html_e( 'Search Authors', 'author-content-manager' ); ?></button>
				</form>

				<?php if ( empty( $acm_recent_authors ) ) : ?>
					<p><?php esc_html_e( 'No author profiles yet.', 'author-content-manager' ); ?></p>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . ACM_POST_TYPE ) ); ?>"><?php esc_html_e( 'Create your first author', 'author-content-manager' ); ?></a>
					</p>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped acm-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Name', 'author-content-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Email', 'author-content-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Status', 'author-content-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Actions', 'author-content-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $acm_recent_authors as $acm_post ) : ?>
								<?php
								$acm_author = ACM_Author::get( $acm_post );

								if ( ! $acm_author ) {
									continue;
								}

								$acm_name = $acm_author->get_name();
								?>
								<tr>
									<td>
										<strong>
											<a href="<?php echo esc_url( (string) get_edit_post_link( $acm_author->get_id() ) ); ?>">
												<?php echo '' !== $acm_name ? esc_html( $acm_name ) : esc_html__( '(no title)', 'author-content-manager' ); ?>
											</a>
										</strong>
									</td>
									<td><?php echo '' !== $acm_author->get_email() ? esc_html( $acm_author->get_email() ) : '&mdash;'; ?></td>
									<td><?php echo esc_html( get_post_status_object( (string) get_post_status( $acm_author->get_id() ) )->label ?? '' ); ?></td>
									<td>
										<a href="<?php echo esc_url( (string) get_edit_post_link( $acm_author->get_id() ) ); ?>"><?php esc_html_e( 'Edit', 'author-content-manager' ); ?></a>
										<?php if ( '' !== $acm_author->get_profile_url() ) : ?>
											<span aria-hidden="true">|</span>
											<a href="<?php echo esc_url( $acm_author->get_profile_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'author-content-manager' ); ?></a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<div class="acm-panel">
				<h2 class="acm-panel__title"><?php esc_html_e( 'Quick Actions', 'author-content-manager' ); ?></h2>
				<p class="acm-actions">
					<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-type-edit' ) ); ?>"><?php esc_html_e( 'Add Post Type', 'author-content-manager' ); ?></a>
					<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-taxonomy-edit' ) ); ?>"><?php esc_html_e( 'Add Taxonomy', 'author-content-manager' ); ?></a>
					<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-author-field-edit' ) ); ?>"><?php esc_html_e( 'Add Author Field', 'author-content-manager' ); ?></a>
					<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'author-content-manager' ); ?></a>
					<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-documentation' ) ); ?>"><?php esc_html_e( 'Shortcode Documentation', 'author-content-manager' ); ?></a>
				</p>
			</div>
		</div>

		<div class="acm-column acm-column--side">
			<div class="acm-panel">
				<h2 class="acm-panel__title"><?php esc_html_e( 'How to display authors', 'author-content-manager' ); ?></h2>
				<p><?php esc_html_e( 'Paste a shortcode into any post, page, widget or page builder element.', 'author-content-manager' ); ?></p>

				<p class="acm-code-row">
					<code>[custom_authors columns="3"]</code>
					<button type="button" class="button button-small acm-copy" data-acm-copy='[custom_authors columns="3"]'><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
				</p>
				<p class="acm-code-row">
					<code>[custom_author id="123"]</code>
					<button type="button" class="button button-small acm-copy" data-acm-copy='[custom_author id="123"]'><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
				</p>

				<p>
					<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-documentation' ) ); ?>"><?php esc_html_e( 'See all shortcodes and options', 'author-content-manager' ); ?></a>
				</p>
			</div>

			<div class="acm-panel">
				<h2 class="acm-panel__title"><?php esc_html_e( 'Status', 'author-content-manager' ); ?></h2>
				<ul class="acm-list">
					<li>
						<?php esc_html_e( 'Author profile base:', 'author-content-manager' ); ?>
						<code><?php echo esc_html( ACM_Author_Manager::get_base() ); ?></code>
					</li>
					<li>
						<?php esc_html_e( 'Author archives:', 'author-content-manager' ); ?>
						<?php echo ACM_Settings::get( 'author_archive', 1 ) ? esc_html__( 'Enabled', 'author-content-manager' ) : esc_html__( 'Disabled', 'author-content-manager' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Frontend CSS:', 'author-content-manager' ); ?>
						<?php echo ACM_Settings::get( 'enable_css', 1 ) ? esc_html__( 'Enabled', 'author-content-manager' ) : esc_html__( 'Disabled', 'author-content-manager' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Author custom fields:', 'author-content-manager' ); ?>
						<?php echo esc_html( number_format_i18n( count( ACM_Custom_Fields::author_definitions() ) ) ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Post types with fields:', 'author-content-manager' ); ?>
						<?php
						$acm_post_types_with_fields = 0;

						foreach ( $acm_post_types as $acm_post_type_key => $acm_post_type_config ) {
							if ( ACM_Custom_Fields::post_type_has_fields( $acm_post_type_key ) ) {
								$acm_post_types_with_fields++;
							}
						}

						echo esc_html( number_format_i18n( $acm_post_types_with_fields ) );
						?>
					</li>
				</ul>
			</div>

			<?php if ( ! empty( $acm_post_types ) || ! empty( $acm_taxonomies ) ) : ?>
				<div class="acm-panel">
					<h2 class="acm-panel__title"><?php esc_html_e( 'Registered Content', 'author-content-manager' ); ?></h2>
					<?php if ( ! empty( $acm_post_types ) ) : ?>
						<p><strong><?php esc_html_e( 'Post types', 'author-content-manager' ); ?></strong></p>
						<ul class="acm-list">
							<?php foreach ( $acm_post_types as $acm_slug => $acm_config ) : ?>
								<li>
									<code><?php echo esc_html( (string) $acm_slug ); ?></code>
									<?php echo ! empty( $acm_config['active'] ) ? esc_html__( '(active)', 'author-content-manager' ) : esc_html__( '(inactive)', 'author-content-manager' ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( ! empty( $acm_taxonomies ) ) : ?>
						<p><strong><?php esc_html_e( 'Taxonomies', 'author-content-manager' ); ?></strong></p>
						<ul class="acm-list">
							<?php foreach ( $acm_taxonomies as $acm_slug => $acm_config ) : ?>
								<li>
									<code><?php echo esc_html( (string) $acm_slug ); ?></code>
									<?php echo ! empty( $acm_config['active'] ) ? esc_html__( '(active)', 'author-content-manager' ) : esc_html__( '(inactive)', 'author-content-manager' ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<p class="acm-credit">
		<?php esc_html_e( 'Developed by', 'author-content-manager' ); ?>
		<a href="<?php echo esc_url( ACM_Admin::page_url( 'acm-about' ) ); ?>">Mossawir Ahmed</a>
		<span aria-hidden="true">&middot;</span>
		<a href="https://mossawir.com/" target="_blank" rel="noopener noreferrer">www.mossawir.com</a>
	</p>
</div>
