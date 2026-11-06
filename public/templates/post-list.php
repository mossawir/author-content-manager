<?php
/**
 * Post type list template.
 *
 * Available variables: $args with posts and display settings.
 *
 * This file can be overridden by copying it to
 * yourtheme/author-content-manager/post-list.php.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_posts = isset( $args['posts'] ) ? $args['posts'] : array();

if ( empty( $acm_posts ) || ! is_array( $acm_posts ) ) {
	return;
}

$acm_classes = array( 'acm-posts', 'acm-posts--list' );
$acm_extra   = isset( $args['class'] ) ? acm_sanitize_classes( $args['class'] ) : '';

if ( '' !== $acm_extra ) {
	$acm_classes[] = $acm_extra;
}

$acm_cards = array();

foreach ( $acm_posts as $acm_post ) {
	$acm_card_args           = $args;
	$acm_card_args['post']   = $acm_post;
	$acm_card_args['layout'] = 'list';

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

	<div class="acm-posts__items acm-posts__items--list">
		<?php
		foreach ( $acm_cards as $acm_card ) {
			echo $acm_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template markup escapes its own output.
		}
		?>
	</div>
</div>
