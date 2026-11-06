<?php
/**
 * Post type grid template.
 *
 * Available variables: $args with posts, columns and display settings.
 *
 * This file can be overridden by copying it to
 * yourtheme/author-content-manager/post-grid.php.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_posts = isset( $args['posts'] ) ? $args['posts'] : array();

if ( empty( $acm_posts ) || ! is_array( $acm_posts ) ) {
	return;
}

$acm_columns = isset( $args['columns'] ) ? (int) $args['columns'] : 3;
$acm_columns = min( 6, max( 1, $acm_columns ) );
$acm_classes = array( 'acm-posts', 'acm-posts--grid' );
$acm_extra   = isset( $args['class'] ) ? acm_sanitize_classes( $args['class'] ) : '';

if ( '' !== $acm_extra ) {
	$acm_classes[] = $acm_extra;
}

$acm_cards = array();

foreach ( $acm_posts as $acm_post ) {
	$acm_card_args           = $args;
	$acm_card_args['post']   = $acm_post;
	$acm_card_args['layout'] = 'grid';

	$acm_card = ACM_Templates::render( 'post-card.php', $acm_card_args );

	if ( '' !== $acm_card ) {
		$acm_cards[] = $acm_card;
	}
}

if ( empty( $acm_cards ) ) {
	return;
}
?>
<div class="<?php echo esc_attr( implode( ' ', $acm_classes ) ); ?>">
	<?php if ( ! empty( $args['title'] ) ) : ?>
		<h2 class="acm-posts__title"><?php echo esc_html( $args['title'] ); ?></h2>
	<?php endif; ?>

	<div class="acm-posts__items acm-posts__items--grid acm-posts__items--cols-<?php echo esc_attr( (string) $acm_columns ); ?>">
		<?php
		foreach ( $acm_cards as $acm_card ) {
			echo $acm_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template markup escapes its own output.
		}
		?>
	</div>
</div>
