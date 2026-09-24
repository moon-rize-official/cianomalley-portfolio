<?php
/**
 * Custom taxonomies + initial term seeding (docs/plan/03 §7).
 *
 * Seed lists reflect docs/discovery.md #5: guides center on self-hosting,
 * Hermes Agent, Oxygen Builder, WordPress, JetBrains; reviews start with
 * software/IDEs (incl. the Oxygen 6 review).
 */

defined( 'ABSPATH' ) || exit;

function cian_core_module_taxonomies(): void {
	add_action( 'init', 'cian_core_register_taxonomies' );
}

function cian_core_register_taxonomies(): void {

	$taxonomies = array(
		'project_category' => array(
			'types'        => array( 'project', 'client_work' ),
			'label'        => 'Project Categories',
			'hierarchical' => true,
			'slug'         => 'projects/category',
		),
		'project_status' => array(
			'types'        => array( 'project', 'client_work' ),
			'label'        => 'Project Status',
			'hierarchical' => false,
			'slug'         => 'projects/status',
		),
		'guide_category' => array(
			'types'        => array( 'guide' ),
			'label'        => 'Guide Categories',
			'hierarchical' => true,
			'slug'         => 'guides/topic',
		),
		'difficulty' => array(
			'types'        => array( 'guide', 'video', 'tutorial_series' ),
			'label'        => 'Difficulty',
			'hierarchical' => false,
			'slug'         => 'difficulty',
		),
		'operating_system' => array(
			'types'        => array( 'guide', 'video' ),
			'label'        => 'Operating Systems',
			'hierarchical' => false,
			'slug'         => 'os',
		),
		'video_category' => array(
			'types'        => array( 'video' ),
			'label'        => 'Video Categories',
			'hierarchical' => true,
			'slug'         => 'videos/category',
		),
		'review_category' => array(
			'types'        => array( 'review' ),
			'label'        => 'Review Categories',
			'hierarchical' => true,
			'slug'         => 'reviews/category',
		),
		'article_category' => array(
			'types'        => array( 'article' ),
			'label'        => 'Article Categories',
			'hierarchical' => true,
			'slug'         => 'articles/category',
		),
		'technology' => array(
			'types'        => array( 'project', 'client_work', 'guide', 'article', 'video', 'review' ),
			'label'        => 'Technologies',
			'hierarchical' => false,
			'slug'         => 'tech',
		),
		'skill' => array(
			'types'        => array( 'project' ),
			'label'        => 'Skills',
			'hierarchical' => false,
			'slug'         => 'skills',
		),
		'playlist' => array(
			'types'        => array( 'video' ),
			'label'        => 'Playlists',
			'hierarchical' => false,
			'slug'         => 'playlists',
		),
	);

	foreach ( $taxonomies as $taxonomy => $args ) {
		register_taxonomy(
			$taxonomy,
			$args['types'],
			array(
				'label'             => $args['label'],
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => $args['hierarchical'],
				'rewrite'           => array(
					'slug'       => $args['slug'],
					'with_front' => false,
				),
			)
		);
	}
}

/**
 * Seed initial terms on activation. Idempotent — existing terms are skipped.
 */
function cian_core_seed_terms(): void {

	$seeds = array(
		'project_category' => array(
			'Web Development', 'Artificial Intelligence', 'Automation', 'Infrastructure',
			'Self-Hosting', 'Research', 'Streaming', 'Hardware', 'Experimental',
		),
		'project_status'   => array( 'Completed', 'In Progress', 'Prototype', 'Research', 'Archived' ),
		'guide_category'   => array(
			'Installation', 'Configuration', 'Development', 'WordPress', 'AI', 'Automation',
			'Self-Hosting', 'Servers', 'Windows', 'Linux', 'macOS', 'Hardware', 'Streaming', 'Troubleshooting',
		),
		'difficulty'       => array( 'Beginner', 'Intermediate', 'Advanced', 'Expert' ),
		'operating_system' => array( 'Windows', 'Linux', 'macOS', 'Cross-platform' ),
		'video_category'   => array( 'Tutorial', 'Project Log', 'Review', 'Livestream Archive', 'Short' ),
		'review_category'  => array( 'Software', 'IDEs & Developer Tools', 'Hardware', 'Peripherals', 'Audio', 'Streaming Gear', 'Services' ),
		'technology'       => array(
			'WordPress', 'Oxygen Builder', 'PHP', 'JavaScript', 'Three.js', 'Docker',
			'Hermes Agent', 'JetBrains', 'MariaDB', 'Nginx', 'Redis', 'Python',
		),
	);

	foreach ( $seeds as $taxonomy => $terms ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		foreach ( $terms as $term ) {
			if ( ! term_exists( $term, $taxonomy ) ) {
				wp_insert_term( $term, $taxonomy );
			}
		}
	}
}
