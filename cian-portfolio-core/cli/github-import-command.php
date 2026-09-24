<?php
/** Manual WP-CLI entry point for explicitly allowlisted public repositories. */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command(
	'cian project import-github',
	static function ( array $args ): void {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Usage: wp cian project import-github <allowlisted-repository>' );
		}
		$result = cian_core_github_import_project( (string) $args[0] );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( sprintf( 'Project #%d is available as a draft.', (int) $result ) );
	},
	array( 'shortdesc' => 'Import one allowlisted public GitHub repository as a project draft.' )
);
