<?php

define( 'ABSPATH', __DIR__ );

class WP_REST_Server {
	public const READABLE = 'GET';
	public const EDITABLE = 'POST, PUT, PATCH';
}
class WP_Error {}
class WP_REST_Response {
	public function __construct( private array $data ) {}
	public function get_data(): array { return $this->data; }
	public function is_error(): bool { return false; }
}
class WP_REST_Request implements ArrayAccess {
	public function __construct( private string $method = '', private string $route = '', private array $params = array() ) {}
	public function offsetExists( mixed $offset ): bool { return isset( $this->params[ $offset ] ); }
	public function offsetGet( mixed $offset ): mixed { return $this->params[ $offset ] ?? null; }
	public function offsetSet( mixed $offset, mixed $value ): void { $this->params[ $offset ] = $value; }
	public function offsetUnset( mixed $offset ): void { unset( $this->params[ $offset ] ); }
	public function has_param( string $key ): bool { return array_key_exists( $key, $this->params ); }
	public function get_param( string $key ): mixed { return $this->params[ $key ] ?? null; }
}

$GLOBALS['test_meta'] = array();
function add_action() {}
function register_rest_route() {}
function get_post( int $id ): object { return (object) array( 'ID' => $id, 'post_type' => 'page', 'post_name' => 'test', 'post_title' => 'Test', 'post_content' => 'Content', 'post_excerpt' => 'Excerpt', 'post_status' => 'draft' ); }
function get_permalink(): string { return 'https://example.com/test/'; }
function get_the_title( object $post ): string { return $post->post_title; }
function current_user_can(): bool { return true; }
function sanitize_key( string $value ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
function sanitize_text_field( string $value ): string { return trim( strip_tags( $value ) ); }
function esc_url_raw( string $value ): string { return filter_var( $value, FILTER_SANITIZE_URL ); }
function apply_filters( string $name, mixed $value ): mixed { return $value; }
function rest_do_request(): WP_REST_Response { return new WP_REST_Response( array( 'allowed_types' => array( 'page', 'post', 'wpds-case' ) ) ); }
function rest_ensure_response( array $data ): WP_REST_Response { return new WP_REST_Response( $data ); }
function update_post_meta( int $id, string $key, string $value ): void { $GLOBALS['test_meta'][ $id ][ $key ] = $value; }
function delete_post_meta( int $id, string $key ): void { unset( $GLOBALS['test_meta'][ $id ][ $key ] ); }
function get_post_meta( int $id, string $key ): string { return $GLOBALS['test_meta'][ $id ][ $key ] ?? ''; }

require dirname( __DIR__ ) . '/wordpress-plugin/codex-bridge-rank-math/codex-bridge-rank-math.php';

$request = new WP_REST_Request(
	'PATCH',
	'/codex-bridge/v1/posts/101/seo',
	array(
		'id'                          => 101,
		'rank_math_title'             => ' Bridge test title ',
		'rank_math_description'       => 'Bridge test description',
		'rank_math_focus_keyword'     => 'bridge test keyword',
		'rank_math_canonical_url'      => 'https://example.com/test/',
		'rank_math_robots'             => 'index, follow, invalid',
	)
);

assert( true === Codex_Bridge_Rank_Math_Meta::can_update( $request ) );
$written = Codex_Bridge_Rank_Math_Meta::update( $request )->get_data();
$read    = Codex_Bridge_Rank_Math_Meta::read( new WP_REST_Request( 'GET', '', array( 'id' => 101 ) ) )->get_data();

assert( $written === $read );
assert( 'Bridge test title' === $read['rank_math_title'] );
assert( 'Bridge test description' === $read['rank_math_description'] );
assert( 'bridge test keyword' === $read['rank_math_focus_keyword'] );
assert( 'https://example.com/test/' === $read['rank_math_canonical_url'] );
assert( 'index,follow' === $read['rank_math_robots'] );
assert( 'page' === $read['post_type'] );
assert( 'test' === $read['slug'] );
assert( array() === $read['rendered']['h1'] );

echo json_encode( $read, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
