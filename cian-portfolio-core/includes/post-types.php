<?php
/**
 * Custom post types (docs/plan/03 §7).
 *
 * All CPTs are registered here, never in a builder. Rewrite slugs define the
 * public URL structure; changing one requires a permalink flush.
 */

defined( 'ABSPATH' ) || exit;

function cian_core_module_post_types(): void {
	add_action( 'init', 'cian_core_register_post_types' );
}

function cian_core_register_post_types(): void {

	$types = array(
		'client_work' => array(
			'labels'   => cian_core_cpt_labels( 'Client Project', 'Client Work' ),
			'slug'     => 'work',
			'icon'     => 'dashicons-businessman',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
		),
		'project' => array(
			'labels'   => cian_core_cpt_labels( 'Project', 'Projects' ),
			'slug'     => 'projects',
			'icon'     => 'dashicons-portfolio',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
		),
		'guide' => array(
			'labels'   => cian_core_cpt_labels( 'Guide', 'Guides' ),
			'slug'     => 'guides',
			'icon'     => 'dashicons-book-alt',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
		),
		'article' => array(
			'labels'   => cian_core_cpt_labels( 'Article', 'Articles' ),
			'slug'     => 'articles',
			'icon'     => 'dashicons-welcome-write-blog',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
		),
		'review' => array(
			'labels'   => cian_core_cpt_labels( 'Review', 'Reviews' ),
			'slug'     => 'reviews',
			'icon'     => 'dashicons-star-half',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
		),
		'video' => array(
			'labels'   => cian_core_cpt_labels( 'Video', 'Videos' ),
			'slug'     => 'videos',
			'icon'     => 'dashicons-video-alt3',
			'supports' => array( 'title', 'editor', 'thumbnail', 'revisions' ),
		),
		'tutorial_series' => array(
			'labels'   => cian_core_cpt_labels( 'Tutorial Series', 'Tutorial Series' ),
			'slug'     => 'series',
			'icon'     => 'dashicons-playlist-video',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
		),
	);

	foreach ( $types as $type => $args ) {
		register_post_type(
			$type,
			array(
				'labels'       => $args['labels'],
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => true,
				'menu_icon'    => $args['icon'],
				'supports'     => $args['supports'],
				'rewrite'      => array(
					'slug'       => $args['slug'],
					'with_front' => false,
				),
			)
		);
	}

	// Timeline entries render only on /about/ — no single view, no archive.
	register_post_type(
		'timeline_entry',
		array(
			'labels'             => cian_core_cpt_labels( 'Timeline Entry', 'Timeline Entries' ),
			'public'             => false,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'publicly_queryable' => false,
			'has_archive'        => false,
			'menu_icon'          => 'dashicons-backup',
			'supports'           => array( 'title', 'editor' ),
			'rewrite'            => false,
		)
	);
}

/**
 * @return array<string, string>
 */
function cian_core_cpt_labels( string $singular, string $plural ): array {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'add_new_item'       => sprintf( 'Add New %s', $singular ),
		'edit_item'          => sprintf( 'Edit %s', $singular ),
		'new_item'           => sprintf( 'New %s', $singular ),
		'view_item'          => sprintf( 'View %s', $singular ),
		'search_items'       => sprintf( 'Search %s', $plural ),
		'not_found'          => sprintf( 'No %s found', strtolower( $plural ) ),
		'not_found_in_trash' => sprintf( 'No %s found in Trash', strtolower( $plural ) ),
		'all_items'          => sprintf( 'All %s', $plural ),
	);
}
