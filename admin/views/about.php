<?php
/**
 * About developer view.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

$acm_args    = isset( $args ) && is_array( $args ) ? $args : array();
$acm_version = isset( $acm_args['version'] ) ? (string) $acm_args['version'] : ACM_VERSION;
?>
<div class="wrap acm-wrap acm-about">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'About', 'author-content-manager' ); ?></h1>
	<hr class="wp-header-end" />

	<p class="acm-lead">
		<?php esc_html_e( 'Author & Content Manager is designed and developed by Mossawir Ahmed, a Frontend & UI/UX Developer with more than 15 years of experience building practical WordPress solutions.', 'author-content-manager' ); ?>
	</p>

	<div class="acm-about__hero">
		<div class="acm-about__avatar" aria-hidden="true">MA</div>
		<div class="acm-about__intro">
			<h2 class="acm-about__name"><?php esc_html_e( 'Mossawir Ahmed', 'author-content-manager' ); ?></h2>
			<p class="acm-about__role"><?php esc_html_e( 'Frontend & UI/UX Developer', 'author-content-manager' ); ?></p>
			<p class="acm-about__meta">
				<?php esc_html_e( 'WordPress, WooCommerce, Shopify, React and modern JavaScript. Focused on high-performance, user-friendly and scalable web solutions.', 'author-content-manager' ); ?>
			</p>

			<div class="acm-about__links">
				<a class="button button-primary" href="https://mossawir.com/" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Visit Website', 'author-content-manager' ); ?>
				</a>
				<a class="button" href="https://linkedin.com/in/mossawirahmed/" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'LinkedIn', 'author-content-manager' ); ?>
				</a>
				<a class="button" href="mailto:me@mossawir.com">
					<?php esc_html_e( 'Email the Developer', 'author-content-manager' ); ?>
				</a>
			</div>
		</div>
	</div>

	<div class="acm-about__grid">
		<div class="acm-about__main">
			<div class="acm-panel">
				<h2 class="acm-panel__title"><?php esc_html_e( 'About the Developer', 'author-content-manager' ); ?></h2>

				<div class="acm-about__bio">
					<p>
						<?php esc_html_e( 'Mossawir Ahmed is an experienced Frontend & UI/UX Developer with over 15 years of experience in web development, specializing in creating high-performance, user-friendly, and scalable web solutions.', 'author-content-manager' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'With extensive experience in WordPress development, custom theme and plugin development, WooCommerce, Shopify, React, and modern JavaScript technologies, Mossawir combines technical expertise with a strong understanding of design and user experience.', 'author-content-manager' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'His approach to development focuses on building practical, lightweight, reliable, and easy-to-use solutions that help developers, businesses, and website owners achieve more with less complexity.', 'author-content-manager' ); ?>
					</p>
					<p>
						<?php esc_html_e( 'Through his work, he aims to contribute to the WordPress ecosystem by developing useful tools and plugins that prioritize performance, flexibility, accessibility, and ease of use.', 'author-content-manager' ); ?>
					</p>
				</div>

				<div class="acm-about__highlight">
					<strong><?php esc_html_e( 'Development philosophy', 'author-content-manager' ); ?></strong>
					<p><?php esc_html_e( 'Practical, lightweight, reliable and easy to use — so developers, businesses and website owners can achieve more with less complexity.', 'author-content-manager' ); ?></p>
				</div>
			</div>

			<div class="acm-panel">
				<h2 class="acm-panel__title"><?php esc_html_e( 'About the Plugin', 'author-content-manager' ); ?></h2>
				<p>
					<?php esc_html_e( 'Author & Content Manager gives WordPress sites a complete content toolkit without writing code. Everything is built around native WordPress data, so content remains available even when a configuration is removed.', 'author-content-manager' ); ?>
				</p>

				<ul class="ul-disc">
					<li><?php esc_html_e( 'Custom post types and custom taxonomies with full labels, visibility, archive and REST settings.', 'author-content-manager' ); ?></li>
					<li><?php esc_html_e( 'Author profiles with social, contact and unlimited custom fields.', 'author-content-manager' ); ?></li>
					<li><?php esc_html_e( 'Custom fields and photos for every post type, with automatic or shortcode output.', 'author-content-manager' ); ?></li>
					<li><?php esc_html_e( 'Responsive grid and list post shortcodes with category filtering and per-type display settings.', 'author-content-manager' ); ?></li>
					<li><?php esc_html_e( 'Settings, complete documentation, developer APIs and safe uninstall behavior.', 'author-content-manager' ); ?></li>
				</ul>

				<p class="acm-about__actions">
					<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-documentation' ) ); ?>"><?php esc_html_e( 'Documentation', 'author-content-manager' ); ?></a>
					<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'author-content-manager' ); ?></a>
					<a class="button" href="<?php echo esc_url( ACM_Admin::page_url( 'acm-post-types' ) ); ?>"><?php esc_html_e( 'Post Types', 'author-content-manager' ); ?></a>
				</p>
			</div>
		</div>

		<div class="acm-about__side">
			<div class="acm-panel">
				<h2 class="acm-panel__title"><?php esc_html_e( 'Connect with the Developer', 'author-content-manager' ); ?></h2>

				<ul class="acm-about__facts">
					<li>
						<span><?php esc_html_e( 'Website', 'author-content-manager' ); ?></span>
						<a href="https://mossawir.com/" target="_blank" rel="noopener noreferrer">www.mossawir.com</a>
					</li>
					<li>
						<span><?php esc_html_e( 'LinkedIn', 'author-content-manager' ); ?></span>
						<a href="https://linkedin.com/in/mossawirahmed/" target="_blank" rel="noopener noreferrer">linkedin.com/in/mossawirahmed</a>
					</li>
					<li>
						<span><?php esc_html_e( 'Email', 'author-content-manager' ); ?></span>
						<a href="mailto:me@mossawir.com">me@mossawir.com</a>
					</li>
				</ul>
			</div>

			<div class="acm-panel">
				<h2 class="acm-panel__title"><?php esc_html_e( 'Plugin Details', 'author-content-manager' ); ?></h2>

				<ul class="acm-about__facts">
					<li>
						<span><?php esc_html_e( 'Version', 'author-content-manager' ); ?></span>
						<strong><?php echo esc_html( $acm_version ); ?></strong>
					</li>
					<li>
						<span><?php esc_html_e( 'Developer', 'author-content-manager' ); ?></span>
						<strong><?php esc_html_e( 'Mossawir Ahmed', 'author-content-manager' ); ?></strong>
					</li>
					<li>
						<span><?php esc_html_e( 'Requires WordPress', 'author-content-manager' ); ?></span>
						<strong><?php esc_html_e( '6.0 or newer', 'author-content-manager' ); ?></strong>
					</li>
					<li>
						<span><?php esc_html_e( 'Requires PHP', 'author-content-manager' ); ?></span>
						<strong><?php esc_html_e( '7.4 or newer', 'author-content-manager' ); ?></strong>
					</li>
					<li>
						<span><?php esc_html_e( 'License', 'author-content-manager' ); ?></span>
						<strong><?php esc_html_e( 'GPL-2.0-or-later', 'author-content-manager' ); ?></strong>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
