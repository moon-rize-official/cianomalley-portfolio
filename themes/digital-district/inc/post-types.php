<?php
/**
 * Presentation queries for the shared Cian Portfolio Core content model.
 * Post types and taxonomies are registered only by the core plugin.
 *
 * @package DigitalDistrict
 */

defined( 'ABSPATH' ) || exit;

function dd_archive_order( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( array( 'project', 'client_work', 'guide', 'review' ) ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
		$query->set( 'posts_per_page', 24 );
	}
}
add_action( 'pre_get_posts', 'dd_archive_order' );