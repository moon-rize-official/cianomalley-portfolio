<?php
/**
 * Template Name: About
 *
 * The page owner supplies personal biography and verified claims in the editor.
 * This template adds published portfolio entries without inventing profile data.
 *
 * @package DigitalDistrict
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<section class="single-hero"><div class="container">
		<p class="hud reveal">// <?php esc_html_e( 'About', 'digital-district' ); ?></p>
		<h1 class="reveal" data-delay="60"><?php the_title(); ?></h1>
		<?php if ( trim( get_the_content() ) ) : ?><div class="lede reveal" data-delay="120"><?php the_content(); ?></div><?php endif; ?>
	</div></section>
	<?php
endwhile;
$projects = new WP_Query( array( 'post_type' => array( 'project', 'client_work' ), 'posts_per_page' => 6, 'no_found_rows' => true ) );
if ( $projects->have_posts() ) : ?>
	<section class="container"><h2><?php esc_html_e( 'Selected work', 'digital-district' ); ?></h2><div class="grid grid--3">
	<?php $i=0; while ( $projects->have_posts() ) : $projects->the_post(); dd_card( $i++ ); endwhile; ?>
	</div></section>
<?php endif; wp_reset_postdata();
get_footer();
