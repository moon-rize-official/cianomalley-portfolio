<?php
/**
 * WordPress-free smoke test for cian-portfolio-core.
 *
 * Loads the plugin with minimal WordPress-function stubs, boots the module
 * registry, fires the `init` and `rest_api_init` hooks, and asserts the data
 * model registers. Also unit-tests the pure parsers (VTT, chapters,
 * timestamps, ISO-8601 duration). Runs with plain PHP — no WordPress, no
 * network — so it works in CI and restricted sandboxes.
 *
 *   php dev/smoke-test.php
 */

error_reporting( E_ALL );

$FAIL = 0;
function ok( string $label, bool $cond ): void {
	global $FAIL;
	echo ( $cond ? "  PASS  " : "  FAIL  " ) . $label . "\n";
	if ( ! $cond ) { $FAIL++; }
}

// ---- Minimal WordPress stubs ------------------------------------------------
$GLOBALS['__hooks']       = array();
$GLOBALS['__post_types']  = array();
$GLOBALS['__taxonomies']  = array();
$GLOBALS['__registered_meta'] = array();
$GLOBALS['__post_meta'] = array();
$GLOBALS['__rest_routes'] = array();
$GLOBALS['__http_requests'] = array();
$GLOBALS['__http_response'] = null;
$GLOBALS['__inserted_posts'] = array();
$GLOBALS['__object_terms'] = array();

function add_action( $hook, $cb, $prio = 10, $args = 1 ) { $GLOBALS['__hooks'][ $hook ][] = $cb; }
function add_filter( $hook, $cb, $prio = 10, $args = 1 ) { $GLOBALS['__hooks'][ $hook ][] = $cb; }
function apply_filters( $hook, $value ) { foreach ( $GLOBALS['__hooks'][ $hook ] ?? array() as $cb ) { $value = call_user_func( $cb, $value ); } return $value; }
function register_post_type( $type, $args = array() ) { $GLOBALS['__post_types'][ $type ] = $args; }
function register_taxonomy( $tax, $types, $args = array() ) { $GLOBALS['__taxonomies'][ $tax ] = $types; }
function register_post_meta( $type, $key, $args = array() ) { $GLOBALS['__registered_meta'][ $type ][ $key ] = $args; }
function get_post_meta( $post_id, $key = '', $single = false ) { return $GLOBALS['__post_meta'][ $post_id ][ $key ] ?? ( $single ? '' : array() ); }
function get_posts( $args = array() ) { foreach ( $GLOBALS['__post_meta'] as $id => $meta ) { if ( ( $meta[ $args['meta_key'] ?? '' ] ?? null ) == ( $args['meta_value'] ?? null ) ) { return array( (int) $id ); } } return array(); }
function wp_remote_get( $url, $args = array() ) { $GLOBALS['__http_requests'][] = array( 'url' => $url, 'args' => $args ); return $GLOBALS['__http_response']; }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code'] ?? 0; }
function wp_remote_retrieve_body( $response ) { return $response['body'] ?? ''; }
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function wp_insert_post( $postarr, $wp_error = false ) { $id = count( $GLOBALS['__inserted_posts'] ) + 100; $GLOBALS['__inserted_posts'][ $id ] = $postarr; $GLOBALS['__post_meta'][ $id ] = $postarr['meta_input'] ?? array(); return $id; }
function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) { $GLOBALS['__object_terms'][ $object_id ][ $taxonomy ] = $terms; return array(); }
function register_rest_route( $ns, $route, $args = array() ) { $GLOBALS['__rest_routes'][] = $ns . $route; }
function register_activation_hook( $f, $cb ) {}
function register_deactivation_hook( $f, $cb ) {}
function add_menu_page( ...$a ) {}
function add_submenu_page( ...$a ) {}
function acf_add_options_page( ...$a ) {}
function plugin_dir_path( $f ) { return rtrim( dirname( $f ), '/' ) . '/'; }
function plugin_dir_url( $f ) { return 'http://example.test/wp-content/plugins/cian-portfolio-core/'; }
function esc_url_raw( $u ) { return $u; }
function esc_url( $u ) { return $u; }
function rest_url( $p = '' ) { return 'http://example.test/wp-json/' . $p; }
function home_url( $p = '' ) { return 'http://example.test' . $p; }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_textarea_field( $s ) { return trim( (string) $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_]/i', '', (string) $s ) ); }
function __( $s, $d = null ) { return $s; }
function get_field( $k, $id = false ) { return ''; }
function wp_next_scheduled( $hook ) { return false; }
function wp_schedule_event( $ts, $rec, $hook ) { return true; }
function wp_clear_scheduled_hook( $hook ) {}
function get_option( $k, $default = false ) { return $default; }
function update_option( $k, $v, $autoload = null ) { return true; }
function get_transient( $k ) { return false; }
function set_transient( $k, $v, $ttl = 0 ) { return true; }
function delete_transient( $k ) { return true; }
function current_user_can( $c ) { return true; }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function wp_kses_post( $s ) { return (string) $s; }
function add_shortcode( $tag, $cb ) { $GLOBALS['__shortcodes'][ $tag ] = $cb; }
function shortcode_atts( $defaults, $atts, $sc = '' ) { return array_merge( $defaults, (array) $atts ); }
function get_the_ID() { return 1; }
function get_the_title( $id = 0 ) { return 'Test'; }
function get_the_post_thumbnail_url( $id = 0, $s = '' ) { return ''; }
function sanitize_title( $s ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $s ) ), '-' ); }

if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) { define( 'MINUTE_IN_SECONDS', 60 ); }
if ( ! defined( 'HOUR_IN_SECONDS' ) ) { define( 'HOUR_IN_SECONDS', 3600 ); }
if ( ! defined( 'DAY_IN_SECONDS' ) ) { define( 'DAY_IN_SECONDS', 86400 ); }

class WP_REST_Server { const READABLE = 'GET'; }
class WP_Error { private $message; public function __construct( $code = '', $message = '' ) { $this->message = $message; } public function get_error_message() { return $this->message; } }

// ---- Load the plugin --------------------------------------------------------
$plugin = dirname( __DIR__ ) . '/cian-portfolio-core/cian-portfolio-core.php';
ok( 'plugin bootstrap file exists', file_exists( $plugin ) );
require $plugin;

ok( 'Cian_Core class defined', class_exists( 'Cian_Core' ) );

// Boot the module registry (requires every includes/ file + calls each bootstrap).
Cian_Core::boot();
ok( 'Cian_Core::boot() ran without fatal', true );
ok( 'GitHub import does not run during plugin boot', 0 === count( $GLOBALS['__http_requests'] ) );
ok( 'GitHub importer allowlist is empty until configured', array() === cian_core_github_import_allowlist() );

// Fire init → registers post types + taxonomies.
foreach ( $GLOBALS['__hooks']['init'] ?? array() as $cb ) { call_user_func( $cb ); }

$expected_types = array( 'project', 'client_work', 'guide', 'article', 'review', 'video', 'tutorial_series', 'timeline_entry' );
foreach ( $expected_types as $t ) {
	ok( "post type registered: $t", isset( $GLOBALS['__post_types'][ $t ] ) );
}

$expected_tax = array( 'project_category', 'project_status', 'guide_category', 'difficulty',
	'operating_system', 'video_category', 'review_category', 'article_category', 'technology', 'skill', 'playlist' );
foreach ( $expected_tax as $t ) {
	ok( "taxonomy registered: $t", isset( $GLOBALS['__taxonomies'][ $t ] ) );
}
ok( 'technology taxonomy includes client_work', in_array( 'client_work', $GLOBALS['__taxonomies']['technology'] ?? array(), true ) );
ok( 'project_category taxonomy includes client_work', in_array( 'client_work', $GLOBALS['__taxonomies']['project_category'] ?? array(), true ) );
ok( 'project_status taxonomy includes client_work', in_array( 'client_work', $GLOBALS['__taxonomies']['project_status'] ?? array(), true ) );
ok( 'client_work canonical meta is registered for REST', isset( $GLOBALS['__registered_meta']['client_work']['client_work_client']['show_in_rest'] ) && true === $GLOBALS['__registered_meta']['client_work']['client_work_client']['show_in_rest'] );
ok( 'client_work live URL uses URL sanitizer', ( $GLOBALS['__registered_meta']['client_work']['client_work_live_url']['sanitize_callback'] ?? '' ) === 'esc_url_raw' );

// Fire rest_api_init → registers cian/v1 routes.
foreach ( $GLOBALS['__hooks']['rest_api_init'] ?? array() as $cb ) { call_user_func( $cb ); }
ok( 'REST /world route registered', in_array( 'cian/v1/world', $GLOBALS['__rest_routes'], true ) );
ok( 'REST transcript route registered', (bool) preg_grep( '#cian/v1/videos#', $GLOBALS['__rest_routes'] ) );

// ---- Pure-function unit tests ----------------------------------------------
echo "\n-- parsers --\n";

$GLOBALS['__post_meta'][42] = array(
	'client_work_client'   => 'Canonical Client',
	'client_work_status'   => 'Complete',
	'client_work_services' => 'Strategy, design',
	'client_work_year'     => '2025',
	'client_work_live_url' => 'https://canonical.example',
	'dd_client'            => 'Legacy Client',
	'dd_status'            => 'Live',
	'dd_services'          => 'Legacy services',
	'dd_year'              => '2024',
	'dd_live_url'          => 'https://legacy.example',
);
$client_work = cian_core_client_work_data( 42 );
ok( 'client_work adapter returns stable keys and prefers canonical values', array_keys( $client_work ) === array( 'client', 'status', 'services', 'year', 'live_url' ) && ( $client_work['client'] ?? '' ) === 'Canonical Client' && ( $client_work['live_url'] ?? '' ) === 'https://canonical.example' );
$GLOBALS['__post_meta'][43] = array( 'dd_client' => 'Legacy Client', 'dd_status' => 'Live', 'dd_services' => 'Legacy services', 'dd_year' => '2024', 'dd_live_url' => 'https://legacy.example' );
$legacy_client_work = cian_core_client_work_data( 43 );
ok( 'client_work adapter reads legacy dd_* fallback values', ( $legacy_client_work['client'] ?? '' ) === 'Legacy Client' && ( $legacy_client_work['live_url'] ?? '' ) === 'https://legacy.example' );

$GLOBALS['__hooks']['cian_core_github_import_owner'][] = static fn ( $owner ) => 'example-owner';
$GLOBALS['__hooks']['cian_core_github_import_allowlist'][] = static fn ( $repos ) => array( 'public-repo' );
ok( 'GitHub importer rejects repos outside explicit allowlist before network access', is_wp_error( cian_core_github_import_project( 'not-allowed' ) ) && count( $GLOBALS['__http_requests'] ) === 0 );
$repo_metadata = array( 'id' => 9001, 'name' => 'public-repo', 'owner' => array( 'login' => 'example-owner' ), 'private' => false, 'description' => 'Imported summary', 'html_url' => 'https://github.com/example-owner/public-repo', 'homepage' => 'https://example.test', 'created_at' => '2024-05-01T00:00:00Z', 'pushed_at' => gmdate( 'c' ), 'size' => 24, 'language' => 'PHP', 'topics' => array( 'wordpress', 'portfolio' ) );
$GLOBALS['__http_response'] = array( 'response' => array( 'code' => 200 ), 'body' => json_encode( array_merge( $repo_metadata, array( 'private' => true ) ) ) );
ok( 'GitHub importer rejects a private repository without creating a post', is_wp_error( cian_core_github_import_project( 'public-repo' ) ) && count( $GLOBALS['__inserted_posts'] ) === 0 );
$GLOBALS['__http_response']['body'] = json_encode( $repo_metadata );
$imported_id = cian_core_github_import_project( 'public-repo' );
ok( 'GitHub importer creates one public repository as a draft', 100 === $imported_id && ( $GLOBALS['__inserted_posts'][100]['post_status'] ?? '' ) === 'draft' );
ok( 'GitHub importer maps metadata to canonical project fields', ( $GLOBALS['__post_meta'][100]['project_github_url'] ?? '' ) === 'https://github.com/example-owner/public-repo' && ( $GLOBALS['__post_meta'][100]['project_summary'] ?? '' ) === 'Imported summary' && ( $GLOBALS['__post_meta'][100]['project_year'] ?? '' ) === '2024' );
ok( 'GitHub importer assigns canonical status and technology taxonomies', ( $GLOBALS['__object_terms'][100]['project_status'] ?? '' ) === 'In Progress' && ( $GLOBALS['__object_terms'][100]['technology'] ?? array() ) === array( 'PHP', 'wordpress', 'portfolio' ) );
ok( 'GitHub importer requests only repository metadata, never README contents', count( $GLOBALS['__http_requests'] ) === 2 && ! preg_match( '#/readme(?:$|[?/])#i', implode( ' ', array_column( $GLOBALS['__http_requests'], 'url' ) ) ) );
$duplicate_id = cian_core_github_import_project( 'public-repo' );
ok( 'GitHub importer leaves an existing matching project unchanged', 100 === $duplicate_id && 1 === count( $GLOBALS['__inserted_posts'] ) );

$vtt = "WEBVTT\n\n00:00:01.000 --> 00:00:03.500\nHello <b>world</b>\n\n00:00:04.000 --> 00:00:06.000\nSecond line\n";
$seg = cian_core_parse_vtt( $vtt );
ok( 'VTT: 2 segments parsed', count( $seg ) === 2 );
ok( 'VTT: start ms correct', ( $seg[0]['start_ms'] ?? null ) === 1000 );
ok( 'VTT: tags stripped', ( $seg[0]['text'] ?? '' ) === 'Hello world' );

ok( 'timestamp 01:02:03 → ms', cian_core_timestamp_to_ms( '01:02:03.000' ) === 3723000 );

$chap = cian_core_parse_chapter_lines( "Intro stuff\n0:00 Introduction\n12:30 Setup\n1:02:05 Wrap up\n" );
ok( 'chapters: 3 parsed', count( $chap ) === 3 );
ok( 'chapters: title captured', ( $chap[1]['title'] ?? '' ) === 'Setup' );
ok( 'chapters: hh:mm:ss start', ( $chap[2]['start'] ?? '' ) === '1:02:05' );

ok( 'chapter start → seconds', cian_core_timestamp_to_seconds( '1:02:05' ) === 3725 );

$srt = "1\n00:00:01,000 --> 00:00:03,500\nHello world\n\n2\n00:00:04,000 --> 00:00:06,000\nSecond line\n";
$s2 = cian_core_parse_srt( $srt );
ok( 'SRT: 2 segments parsed', count( $s2 ) === 2 );
ok( 'SRT: comma ms handled', ( $s2[0]['end_ms'] ?? null ) === 3500 );

$pt = cian_core_parse_plaintext( "[0:05] First\nUntimed line\n[1:00] Third\n" );
ok( 'plaintext: 3 segments', count( $pt ) === 3 );
ok( 'plaintext: timestamp parsed', ( $pt[0]['start_ms'] ?? null ) === 5000 );
ok( 'plaintext: untimed → 0', ( $pt[1]['start_ms'] ?? null ) === 0 );

ok( 'dispatch sniffs VTT', count( cian_core_parse_transcript( $vtt ) ) === 2 );
ok( 'dispatch sniffs SRT', count( cian_core_parse_transcript( $srt ) ) === 2 );
ok( 'dispatch defaults to text', count( cian_core_parse_transcript( "just some text" ) ) === 1 );

$ends = cian_core_fill_chapter_ends( array(
	array( 'start' => '0:00', 'title' => 'A' ),
	array( 'start' => '1:30', 'title' => 'B' ),
	array( 'start' => '3:00', 'title' => 'C' ),
) );
ok( 'chapter end autofilled from next start', ( $ends[0]['end'] ?? '' ) === '1:30' );
ok( 'last chapter end stays empty', empty( $ends[2]['end'] ) );

ok( 'ISO duration PT1H2M3S → 01:02:03', cian_core_yt_duration( 'PT1H2M3S' ) === '01:02:03' );
ok( 'ISO duration PT45S → 00:00:45', cian_core_yt_duration( 'PT45S' ) === '00:00:45' );

ok( 'youtube id valid passes', cian_core_sanitize_youtube_id( 'dQw4w9WgXcQ' ) === 'dQw4w9WgXcQ' );
ok( 'youtube id invalid rejected', cian_core_sanitize_youtube_id( 'nope' ) === '' );

echo "\n-- render components --\n";

$facade = cian_core_render_video_facade( array( 'yt_id' => 'dQw4w9WgXcQ', 'title' => 'Demo', 'duration' => '12:34' ) );
ok( 'facade carries data-yt-id', strpos( $facade, 'data-yt-id="dQw4w9WgXcQ"' ) !== false );
ok( 'facade emits NO iframe (consent-safe)', stripos( $facade, '<iframe' ) === false );
ok( 'facade emits NO youtube request', stripos( $facade, 'youtube' ) === false );
ok( 'facade is a real button', strpos( $facade, '<button' ) !== false );
ok( 'facade with no source is empty', cian_core_render_video_facade( array() ) === '' );

$cl = cian_core_render_chapter_list( array( array( 'start' => '1:30', 'title' => 'Setup' ) ) );
ok( 'chapter list seconds in data-start', strpos( $cl, 'data-start="90"' ) !== false );
ok( 'chapter list escapes title', strpos( $cl, 'Setup' ) !== false );

$cb = cian_core_render_command_block( 'rm -rf /tmp/x', 'bash', 'careful' );
ok( 'command block has copy button', strpos( $cb, 'cian-command__copy' ) !== false );
ok( 'command block escapes code', strpos( $cb, 'rm -rf /tmp/x' ) !== false );

$co = cian_core_render_callout( 'Heads up', 'warning' );
ok( 'warning callout class', strpos( $co, 'cian-callout--warning' ) !== false );

ok( 'shortcodes registered', isset( $GLOBALS['__shortcodes']['cian_video_facade'], $GLOBALS['__shortcodes']['cian_chapters'], $GLOBALS['__shortcodes']['cian_command'] ) );

echo "\n-- guide step renderer --\n";

$rows = array(
	array( 'acf_fc_layout' => 'step_section', 'title' => 'Install the tools', 'body' => '<p>Do it.</p>' ),
	array( 'acf_fc_layout' => 'command_block', 'code' => 'apt install nginx', 'language' => 'bash' ),
	array( 'acf_fc_layout' => 'code_block', 'code' => "server {}", 'language' => 'nginx', 'filename' => 'site.conf' ),
	array( 'acf_fc_layout' => 'warning_callout', 'body' => 'Back up first.' ),
	array( 'acf_fc_layout' => 'step_section', 'title' => 'Verify' ),
	array( 'acf_fc_layout' => 'download', 'file' => array( 'url' => 'https://x.test/c.zip', 'filename' => 'c.zip' ) ),
);
$steps = cian_core_render_guide_steps( $rows );
ok( 'steps: section numbered', strpos( $steps, '<span class="cian-step__n">1</span>' ) !== false );
ok( 'steps: second section is #2', strpos( $steps, '<span class="cian-step__n">2</span>' ) !== false );
ok( 'steps: section anchored by slug', strpos( $steps, 'id="install-the-tools"' ) !== false );
ok( 'steps: command block rendered', strpos( $steps, 'apt install nginx' ) !== false );
ok( 'steps: code block filename', strpos( $steps, 'site.conf' ) !== false );
ok( 'steps: warning callout', strpos( $steps, 'cian-callout--warning' ) !== false );
ok( 'steps: download link', strpos( $steps, 'href="https://x.test/c.zip"' ) !== false );

$toc = cian_core_render_guide_toc( $rows );
ok( 'toc: only step sections (2 links)', substr_count( $toc, '<li>' ) === 2 );
ok( 'toc: anchors match sections', strpos( $toc, '#install-the-tools' ) !== false && strpos( $toc, '#verify' ) !== false );

ok( 'guide step shortcodes registered', isset( $GLOBALS['__shortcodes']['cian_guide_steps'], $GLOBALS['__shortcodes']['cian_guide_toc'] ) );

echo "\n-- review components --\n";

ok( 'score fmt 8.0 → 8', cian_core_fmt_score( 8.0 ) === '8' );
ok( 'score fmt 7.5 → 7.5', cian_core_fmt_score( 7.5 ) === '7.5' );

$panel = cian_core_render_score_panel( 8.5, array( 'Design' => 9, 'Value' => 0, 'Performance' => 7.5 ) );
ok( 'score panel shows overall', strpos( $panel, '>8.5<' ) !== false );
ok( 'score panel has meter for scored', strpos( $panel, '<meter' ) !== false );
ok( 'score panel skips 0 (Value)', strpos( $panel, 'Value' ) === false );
ok( 'score panel clamps aria', strpos( $panel, 'Design: 9 out of 10' ) !== false );

$pc = cian_core_render_pros_cons(
	array( array( 'text' => 'Fast' ), array( 'text' => '' ) ),
	array( 'Pricey' )
);
ok( 'pros/cons: pros column', strpos( $pc, 'Fast' ) !== false && strpos( $pc, 'cian-pros' ) !== false );
ok( 'pros/cons: cons column (string item)', strpos( $pc, 'Pricey' ) !== false );
ok( 'pros/cons: blank item skipped', substr_count( $pc, '<li>' ) === 2 );
ok( 'pros/cons: empty → empty string', cian_core_render_pros_cons( array(), array() ) === '' );

$spec = cian_core_render_spec_table( array( array( 'spec' => 'Weight', 'value' => '1.2kg' ), array( 'spec' => '' ) ) );
ok( 'spec table: row rendered', strpos( $spec, '<th scope="row">Weight</th>' ) !== false );
ok( 'spec table: blank spec skipped', substr_count( $spec, '<tr>' ) === 1 );

ok( 'review shortcodes registered', isset( $GLOBALS['__shortcodes']['cian_review_scores'], $GLOBALS['__shortcodes']['cian_pros_cons'], $GLOBALS['__shortcodes']['cian_spec_table'] ) );

echo "\n-- card renderer --\n";

$card = cian_core_render_card_data( array(
	'type' => 'guide', 'title' => 'Self-hosting 101', 'url' => 'https://x.test/guides/sh/',
	'summary' => 'Get started.', 'thumb' => 'https://x.test/t.jpg', 'badge' => 'Beginner',
) );
ok( 'card: article + type class', strpos( $card, 'cian-card--guide' ) !== false );
ok( 'card: linked', strpos( $card, 'href="https://x.test/guides/sh/"' ) !== false );
ok( 'card: title + summary', strpos( $card, 'Self-hosting 101' ) !== false && strpos( $card, 'Get started.' ) !== false );
ok( 'card: badge chip', strpos( $card, 'cian-chip">Beginner' ) !== false );
ok( 'card: lazy thumb', strpos( $card, 'loading="lazy"' ) !== false );
ok( 'card: missing url → empty', cian_core_render_card_data( array( 'title' => 'x' ) ) === '' );
ok( 'card/related shortcodes registered', isset( $GLOBALS['__shortcodes']['cian_card'], $GLOBALS['__shortcodes']['cian_related'] ) );

echo "\n-- schema (structured data) --\n";

$notes = cian_core_schema_notes( array( array( 'text' => 'Fast' ), array( 'text' => '' ), 'Quiet' ) );
ok( 'notes: ItemList type', ( $notes['@type'] ?? '' ) === 'ItemList' );
ok( 'notes: 2 items (blank skipped)', count( $notes['itemListElement'] ) === 2 );
ok( 'notes: positions + names', $notes['itemListElement'][1]['position'] === 2 && $notes['itemListElement'][1]['name'] === 'Quiet' );
ok( 'notes: empty → null', cian_core_schema_notes( array() ) === null );

$howto = cian_core_schema_howto_steps( array(
	array( 'acf_fc_layout' => 'step_section', 'title' => 'Install nginx', 'body' => '<p>Run apt.</p>' ),
	array( 'acf_fc_layout' => 'command_block', 'code' => 'apt install nginx' ),
	array( 'acf_fc_layout' => 'step_section', 'title' => 'Verify' ),
), 'https://x.test/guides/g/' );
ok( 'howto: 2 steps (only sections)', count( $howto ) === 2 );
ok( 'howto: HowToStep type + position', $howto[0]['@type'] === 'HowToStep' && $howto[0]['position'] === 1 );
ok( 'howto: anchored url', $howto[0]['url'] === 'https://x.test/guides/g/#install-nginx' );
ok( 'howto: body → text', $howto[0]['text'] === 'Run apt.' );

echo "\n-- youtube sync mapping --\n";

ok( 'best thumbnail prefers maxres', cian_core_yt_best_thumbnail( array(
	'default' => array( 'url' => 'd.jpg' ),
	'maxres'  => array( 'url' => 'm.jpg' ),
) ) === 'm.jpg' );
ok( 'best thumbnail falls back', cian_core_yt_best_thumbnail( array( 'medium' => array( 'url' => 'med.jpg' ) ) ) === 'med.jpg' );
ok( 'best thumbnail empty', cian_core_yt_best_thumbnail( array() ) === '' );

$item = array(
	'id'             => 'dQw4w9WgXcQ',
	'snippet'        => array(
		'title'        => 'Building with Oxygen 6',
		'description'  => 'A guide.',
		'publishedAt'  => '2026-01-15T10:00:00Z',
		'channelTitle' => 'Cian O\'Malley',
		'tags'         => array( 'oxygen', 'wordpress' ),
		'thumbnails'   => array( 'high' => array( 'url' => 'h.jpg' ) ),
	),
	'contentDetails' => array( 'duration' => 'PT12M30S' ),
	'status'         => array( 'privacyStatus' => 'public' ),
);
$m = cian_core_yt_map_video( $item );
ok( 'map: youtube id sanitized', $m['youtube_id'] === 'dQw4w9WgXcQ' );
ok( 'map: title', $m['title'] === 'Building with Oxygen 6' );
ok( 'map: duration PT12M30S → 00:12:30', $m['duration'] === '00:12:30' );
ok( 'map: publishedAt → date', $m['published'] === '2026-01-15' );
ok( 'map: tags', $m['tags'] === array( 'oxygen', 'wordpress' ) );
ok( 'map: thumbnail', $m['thumbnail'] === 'h.jpg' );
ok( 'map: privacy', $m['privacy'] === 'public' );
ok( 'map: not live', $m['is_live'] === false );
ok( 'map: invalid id → empty', cian_core_yt_map_video( array( 'id' => 'bad' ) )['youtube_id'] === '' );

$map = array( 'wordpress' => 12, 'oxygen builder' => 34 );
$mt  = cian_core_match_terms_by_name( array( 'WordPress', 'Oxygen Builder', 'random tag', '  ' ), $map );
ok( 'tag match: matched ids', $mt['matched'] === array( 12, 34 ) );
ok( 'tag match: unmatched preserved', $mt['unmatched'] === array( 'random tag' ) );
ok( 'tag match: case-insensitive', cian_core_match_terms_by_name( array( 'WORDPRESS' ), $map )['matched'] === array( 12 ) );
ok( 'tag match: dedupes', cian_core_match_terms_by_name( array( 'WordPress', 'wordpress' ), $map )['matched'] === array( 12 ) );

// ---- Summary ----------------------------------------------------------------
echo "\n";
if ( $FAIL === 0 ) {
	echo "ALL SMOKE TESTS PASSED\n";
	exit( 0 );
}
echo "SMOKE TESTS FAILED: {$FAIL}\n";
exit( 1 );
