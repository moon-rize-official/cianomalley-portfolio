<?php
/**
 * Client work field contract for builder-independent frontend templates.
 *
 * Canonical values use the `client_work_*` meta keys. The Digital District
 * theme used `dd_*` keys before the content model moved into this plugin, so
 * reads fall back to those keys until existing posts are migrated.
 */

defined( 'ABSPATH' ) || exit;

/** Register canonical client-work fields for authenticated REST editing. */
function cian_core_module_client_work(): void {
	$fields = array(
		'client'   => 'text',
		'status'   => 'text',
		'services' => 'text',
		'year'     => 'text',
		'live_url' => 'url',
	);

	foreach ( $fields as $field => $type ) {
		$key = 'client_work_' . $field;
		register_post_meta(
			'client_work',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'url' === $type ? 'esc_url_raw' : 'sanitize_text_field',
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ): bool {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}

/**
 * Stable data interface for client-work cards and single templates.
 *
 * Canonical `client_work_*` meta takes precedence over legacy `dd_*` meta.
 * Values are returned as strings and must be escaped by the rendering layer.
 *
 * @return array{client:string,status:string,services:string,year:string,live_url:string}
 */
function cian_core_client_work_data( int $post_id ): array {
	$legacy_fields = array(
		'client'   => 'dd_client',
		'status'   => 'dd_status',
		'services' => 'dd_services',
		'year'     => 'dd_year',
		'live_url' => 'dd_live_url',
	);
	$data = array();

	foreach ( $legacy_fields as $field => $legacy_key ) {
		$value = get_post_meta( $post_id, 'client_work_' . $field, true );
		if ( '' === $value || null === $value || false === $value ) {
			$value = get_post_meta( $post_id, $legacy_key, true );
		}
		$data[ $field ] = is_scalar( $value ) ? (string) $value : '';
	}

	return $data;
}
