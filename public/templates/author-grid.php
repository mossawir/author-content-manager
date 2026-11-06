<?php
/**
 * Author grid template.
 *
 * Available variables: $args with `authors`, `columns` and card settings.
 *
 * This file can be overridden by copying it to
 * yourtheme/author-content-manager/author-grid.php.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_authors = isset( $args['authors'] ) ? $args['authors'] : array();
$acm_columns = isset( $args['columns'] ) ? (int) $args['columns'] : 0;

if ( empty( $acm_authors ) || ! is_array( $acm_authors ) ) {
	return;
}

if ( $acm_columns < 1 || $acm_columns > 6 ) {
	$acm_columns = min( 6, max( 1, (int) ACM_Settings::get( 'grid_columns', 3 ) ) );
}

$acm_classes = array( 'acm-authors-grid', 'acm-authors-grid--cols-' . $acm_columns );

if ( ! empty( $args['class'] ) ) {
	$acm_classes[] = $args['class'];
}
?>
<div class="<?php echo esc_attr( implode( ' ', $acm_classes ) ); ?>">
	<?php
	foreach ( $acm_authors as $acm_author ) {
		$acm_card_args           = $args;
		$acm_card_args['author'] = $acm_author;

		echo ACM_Templates::render( 'author-card.php', $acm_card_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template markup escapes its own output.
	}
	?>
</div>
