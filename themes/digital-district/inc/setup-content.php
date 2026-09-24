<?php
/**
 * Optional presentation setup. The core plugin owns portfolio content models.
 * Theme activation creates only missing structural pages and never removes or
 * imports content.
 *
 * @package DigitalDistrict
 */

defined( 'ABSPATH' ) || exit;

function dd_after_switch_theme() {
	if ( ! function_exists( 'cian_core_register_post_types' ) ) {
		return;
	}
	flush_rewrite_rules();
	dd_create_pages();
}
add_action( 'after_switch_theme', 'dd_after_switch_theme' );

function dd_create_pages() {
	$front = get_page_by_path( 'home' );
	if ( ! $front ) {
		$front_id = wp_insert_post( array(
			'post_title'   => __( 'Home', 'digital-district' ),
			'post_name'    => 'home',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		) );
		if ( $front_id && ! is_wp_error( $front_id ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $front_id );
		}
	}

	if ( ! get_page_by_path( 'about' ) ) {
		wp_insert_post( array(
			'post_title'   => __( 'About', 'digital-district' ),
			'post_name'    => 'about',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		) );
	}

	if ( ! get_page_by_path( 'contact' ) ) {
		wp_insert_post( array(
			'post_title'    => __( 'Contact', 'digital-district' ),
			'post_name'     => 'contact',
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'page_template' => 'page-contact.php',
			'post_content'  => '',
		) );
	}

	$blog = get_page_by_path( 'blog' );
	if ( ! $blog ) {
		$blog_id = wp_insert_post( array(
			'post_title'   => __( 'Blog', 'digital-district' ),
			'post_name'    => 'blog',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		) );
	} else {
		$blog_id = $blog->ID;
	}
	if ( $blog_id && ! is_wp_error( $blog_id ) ) {
		update_option( 'page_for_posts', $blog_id );
	}
}
