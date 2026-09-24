<?php
/**
 * Plugin Name:       Cian Portfolio Core
 * Plugin URI:        https://cianomalley.works
 * Description:       Site-specific core for the Digital District portfolio: post types, taxonomies, ACF registration, relationships, YouTube synchronization, transcripts, chapters, REST API, and conditional assets. Builder-independent by design.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Cian O'Malley
 * License:           GPL-2.0-or-later
 * Text Domain:       cian-portfolio
 *
 * Architecture contract (docs/plan/09): all data, sync, and business logic
 * live here — never in Oxygen/Breakdance code blocks. Layout lives in the
 * builder; this plugin must keep working if the builder is replaced.
 */

defined( 'ABSPATH' ) || exit;

define( 'CIAN_CORE_VERSION', '0.1.0' );
define( 'CIAN_CORE_FILE', __FILE__ );
define( 'CIAN_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CIAN_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Module registry. Each module is an includes/ file exposing a
 * cian_core_module_{name}() bootstrap. A module can be disabled with the
 * CIAN_DISABLE_{NAME} constant in wp-config.php (e.g. CIAN_DISABLE_WORLD).
 */
final class Cian_Core {

	/** @var string[] Module slug => includes filename. */
	private const MODULES = array(
		'post_types'      => 'post-types.php',
		'taxonomies'      => 'taxonomies.php',
		'client_work'     => 'client-work.php',
		'github_import'  => 'github-import.php',
		'acf_fields'      => 'acf-fields.php',
		'relationships'   => 'relationships.php',
		'assets'          => 'assets.php',
		'render'          => 'render.php',
		'rest_api'        => 'rest-api.php',
		'youtube_api'     => 'youtube-api.php',
		'youtube_sync'    => 'youtube-sync.php',
		'youtube_oauth'   => 'youtube-oauth.php',
		'video_import'    => 'video-import.php',
		'transcripts'     => 'transcripts.php',
		'chapters'        => 'chapters.php',
		'scheduled_tasks' => 'scheduled-tasks.php',
		'admin_pages'     => 'admin-pages.php',
		'seo'             => 'seo.php',
		'security'        => 'security.php',
		'privacy'         => 'privacy.php',
	);

	public static function boot(): void {
		foreach ( self::MODULES as $slug => $file ) {
			if ( defined( 'CIAN_DISABLE_' . strtoupper( $slug ) ) ) {
				continue;
			}
			require_once CIAN_CORE_DIR . 'includes/' . $file;
			$bootstrap = 'cian_core_module_' . $slug;
			if ( function_exists( $bootstrap ) ) {
				$bootstrap();
			}
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once CIAN_CORE_DIR . 'cli/youtube-sync-command.php';
			require_once CIAN_CORE_DIR . 'cli/github-import-command.php';
		}
	}

	public static function activate(): void {
		// Register content model, then flush permalinks once.
		require_once CIAN_CORE_DIR . 'includes/post-types.php';
		require_once CIAN_CORE_DIR . 'includes/taxonomies.php';
		cian_core_register_post_types();
		cian_core_register_taxonomies();
		cian_core_seed_terms();

		require_once CIAN_CORE_DIR . 'includes/relationships.php';
		cian_core_create_relationship_table();

		require_once CIAN_CORE_DIR . 'includes/transcripts.php';
		cian_core_create_transcript_table();

		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'cian_youtube_sync' );
		flush_rewrite_rules();
	}
}

register_activation_hook( __FILE__, array( 'Cian_Core', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Cian_Core', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'Cian_Core', 'boot' ) );
