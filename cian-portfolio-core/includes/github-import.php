<?php
/**
 * Explicit, public-only GitHub project import. Nothing runs during activation
 * or on a schedule; an operator must invoke the importer for one allowlisted
 * repository by name.
 */

defined( 'ABSPATH' ) || exit;

/** This module intentionally registers no automatic import hooks. */
function cian_core_module_github_import(): void {}

/**
 * Owner configured in wp-config.php or overridden by a site integration.
 * Empty by default so importing requires deliberate configuration.
 */
function cian_core_github_import_owner(): string {
	$owner = defined( 'CIAN_GITHUB_IMPORT_OWNER' ) ? (string) CIAN_GITHUB_IMPORT_OWNER : '';
	return (string) apply_filters( 'cian_core_github_import_owner', trim( $owner ) );
}

/**
 * Repository names explicitly approved for import.
 *
 * @return string[]
 */
function cian_core_github_import_allowlist(): array {
	$repos = defined( 'CIAN_GITHUB_IMPORT_ALLOWLIST' ) && is_array( CIAN_GITHUB_IMPORT_ALLOWLIST )
		? CIAN_GITHUB_IMPORT_ALLOWLIST
		: array();
	$repos = apply_filters( 'cian_core_github_import_allowlist', $repos );
	if ( ! is_array( $repos ) ) {
		return array();
	}
	return array_values( array_unique( array_filter( array_map( static fn ( $repo ): string => sanitize_title( (string) $repo ), array_filter( $repos, 'is_scalar' ) ) ) ) );
}

function cian_core_github_import_is_allowed( string $owner, string $repo ): bool {
	$configured_owner = cian_core_github_import_owner();
	if ( '' === $configured_owner || 0 !== strcasecmp( $configured_owner, $owner ) ) {
		return false;
	}
	return in_array( strtolower( $repo ), array_map( 'strtolower', cian_core_github_import_allowlist() ), true );
}

/**
 * Ensure a repository response is the requested public repository.
 *
 * @param array<string,mixed> $repo Repository metadata from api.github.com.
 */
function cian_core_github_repo_is_public( array $repo, string $owner, string $name ): bool {
	return array_key_exists( 'private', $repo )
		&& false === $repo['private']
		&& ! empty( $repo['id'] )
		&& isset( $repo['name'], $repo['owner']['login'] )
		&& 0 === strcasecmp( $owner, (string) $repo['owner']['login'] )
		&& 0 === strcasecmp( $name, (string) $repo['name'] );
}

/**
 * Convert one public GitHub repository to canonical project storage fields.
 *
 * @param array<string,mixed> $repo
 * @return array<string,mixed>
 */
function cian_core_github_project_post_data( array $repo ): array {
	$title       = sanitize_text_field( (string) ( $repo['name'] ?? '' ) );
	$description = sanitize_textarea_field( (string) ( $repo['description'] ?? '' ) );
	$created     = isset( $repo['created_at'] ) ? strtotime( (string) $repo['created_at'] ) : false;
	$homepage    = esc_url_raw( (string) ( $repo['homepage'] ?? '' ) );
	$repo_url    = esc_url_raw( (string) ( $repo['html_url'] ?? '' ) );

	return array(
		'post_type'    => 'project',
		'post_status'  => 'draft',
		'post_title'   => $title,
		'post_excerpt' => $description,
		'post_content' => '',
		'meta_input'   => array(
			'project_github_repo_id' => (string) (int) ( $repo['id'] ?? 0 ),
			'project_github_url'     => $repo_url,
			'project_live_url'       => $homepage,
			'project_summary'        => $description,
			'project_year'           => $created ? gmdate( 'Y', $created ) : '',
		),
		'_cian_import_status' => cian_core_github_project_status( $repo ),
		'_cian_import_terms'  => array_values(
			array_unique(
				array_filter(
					array_merge(
						array( sanitize_text_field( (string) ( $repo['language'] ?? '' ) ) ),
					array_slice( array_map( 'sanitize_title', (array) ( $repo['topics'] ?? array() ) ), 0, 5 )
					)
				)
			)
		),
	);
}

/** @param array<string,mixed> $repo */
function cian_core_github_project_status( array $repo ): string {
	if ( ! empty( $repo['archived'] ) ) {
		return 'Archived';
	}
	if ( isset( $repo['size'] ) && (int) $repo['size'] <= 12 ) {
		return 'Prototype';
	}
	$pushed = isset( $repo['pushed_at'] ) ? strtotime( (string) $repo['pushed_at'] ) : false;
	return $pushed && $pushed > time() - 120 * DAY_IN_SECONDS ? 'In Progress' : 'Research';
}

/**
 * Import one allowlisted repository as a draft. Only the public metadata API
 * endpoint is requested: no token, README, file contents, private endpoint, or
 * bulk account listing is used. Existing matching imports are left untouched.
 *
 * @return int|WP_Error New or existing project ID, or a policy/API error.
 */
function cian_core_github_import_project( string $repo_name ) {
	$owner = cian_core_github_import_owner();
	$repo_name = trim( $repo_name );
	if ( ! cian_core_github_import_is_allowed( $owner, $repo_name ) ) {
		return new WP_Error( 'cian_github_not_allowlisted', 'Configure a GitHub owner and explicitly allowlist this repository before importing.' );
	}

	$url = 'https://api.github.com/repos/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo_name );
	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'CianPortfolioCore',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'cian_github_http', 'GitHub repository metadata request failed.' );
	}
	$repo = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $repo ) || ! cian_core_github_repo_is_public( $repo, $owner, $repo_name ) ) {
		return new WP_Error( 'cian_github_not_public', 'The requested repository could not be verified as public metadata for the configured owner.' );
	}

	$repo_id = (string) (int) $repo['id'];
	foreach ( array( 'project_github_repo_id', 'dd_repo_id' ) as $meta_key ) {
		$existing = get_posts(
			array(
				'post_type'   => 'project',
				'post_status' => 'any',
				'meta_key'    => $meta_key,
				'meta_value'  => $repo_id,
				'fields'      => 'ids',
				'numberposts' => 1,
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}
	}

	$data   = cian_core_github_project_post_data( $repo );
	$status = $data['_cian_import_status'];
	$terms  = $data['_cian_import_terms'];
	unset( $data['_cian_import_status'], $data['_cian_import_terms'] );
	$post_id = wp_insert_post( $data, true );
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return is_wp_error( $post_id ) ? $post_id : new WP_Error( 'cian_github_insert_failed', 'Could not create a draft project.' );
	}
	wp_set_object_terms( (int) $post_id, $status, 'project_status', false );
	if ( $terms ) {
		wp_set_object_terms( (int) $post_id, $terms, 'technology', false );
	}
	return (int) $post_id;
}
