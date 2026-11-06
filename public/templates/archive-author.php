<?php
/**
 * Author archive template.
 *
 * Used for classic themes when the active theme does not provide
 * archive-acm_author.php. Block themes keep their own archive template.
 *
 * This file can be overridden by copying it to
 * yourtheme/author-content-manager/archive-author.php.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_columns = min( 6, max( 1, (int) ACM_Settings::get( 'grid_columns', 3 ) ) );
$acm_title   = post_type_archive_title( '', false );
$acm_desc    = get_the_archive_description();
$acm_args    = array(
	'show_image'   => true,
	'show_bio'     => true,
	'show_contact' => false,
	'show_social'  => true,
	'show_link'    => true,
	'image_size'   => ACM_Settings::get( 'image_size', 'medium' ),
	'class'        => '',
);

get_header();
?>
<div class="acm-archive">
	<div class="acm-archive__inner">
		<header class="acm-archive__header">
			<h1 class="acm-archive__title"><?php echo esc_html( $acm_title ); ?></h1>
			<?php if ( '' !== trim( (string) $acm_desc ) ) : ?>
				<div class="acm-archive__description"><?php echo wp_kses_post( $acm_desc ); ?></div>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="acm-authors-grid acm-authors-grid--cols-<?php echo esc_attr( (string) $acm_columns ); ?>">
				<?php
				while ( have_posts() ) :
					the_post();

					$acm_author = ACM_Author::get( get_the_ID() );

					if ( ! $acm_author ) {
						continue;
					}

					$acm_card_args           = $acm_args;
					$acm_card_args['author'] = $acm_author;

					echo ACM_Templates::render( 'author-card.php', $acm_card_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template markup escapes its own output.
				endwhile;
				?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => esc_html__( 'Previous', 'author-content-manager' ),
					'next_text' => esc_html__( 'Next', 'author-content-manager' ),
				)
			);
			?>
		<?php else : ?>
			<p class="acm-archive__empty"><?php esc_html_e( 'No author profiles have been published yet.', 'author-content-manager' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
