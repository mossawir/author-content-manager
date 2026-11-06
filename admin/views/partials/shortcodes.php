<?php
/**
 * Shortcode reference partial.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_shortcodes = isset( $args['shortcodes'] ) && is_array( $args['shortcodes'] ) ? $args['shortcodes'] : array();

if ( empty( $acm_shortcodes ) ) {
	return;
}
?>
<div class="acm-shortcodes">
	<?php foreach ( $acm_shortcodes as $acm_doc ) : ?>
		<div class="acm-shortcode">
			<div class="acm-shortcode__header">
				<h3 class="acm-shortcode__title"><?php echo esc_html( $acm_doc['title'] ); ?></h3>
				<span class="acm-shortcode__code">
					<code><?php echo esc_html( $acm_doc['code'] ); ?></code>
					<button type="button" class="button button-small acm-copy" data-acm-copy="<?php echo esc_attr( $acm_doc['code'] ); ?>"><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
				</span>
			</div>

			<p><?php echo esc_html( $acm_doc['description'] ); ?></p>

			<details class="acm-details">
				<summary><?php esc_html_e( 'Parameters', 'author-content-manager' ); ?></summary>
				<table class="acm-shortcode__attributes">
					<tbody>
						<?php foreach ( $acm_doc['attributes'] as $acm_attribute => $acm_attribute_description ) : ?>
							<tr>
								<th scope="row"><code><?php echo esc_html( $acm_attribute ); ?></code></th>
								<td><?php echo esc_html( $acm_attribute_description ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</details>

			<p class="acm-shortcode__example">
				<strong><?php esc_html_e( 'Example:', 'author-content-manager' ); ?></strong>
				<code><?php echo esc_html( $acm_doc['example'] ); ?></code>
				<button type="button" class="button button-small acm-copy" data-acm-copy="<?php echo esc_attr( $acm_doc['example'] ); ?>"><?php esc_html_e( 'Copy', 'author-content-manager' ); ?></button>
			</p>
		</div>
	<?php endforeach; ?>
</div>
