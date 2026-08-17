<?php

define( 'ABSPATH', __DIR__ );

$GLOBALS['test_filters'] = array();

function add_filter( string $hook, callable $callback ): void {
	$GLOBALS['test_filters'][ $hook ][] = $callback;
}

require dirname( __DIR__ ) . '/wordpress-plugin/codex-bridge-service-access/codex-bridge-service-access.php';

$allowed = array( 'page', 'post' );
foreach ( $GLOBALS['test_filters']['codex_bridge_allowed_post_types'] as $callback ) {
	$allowed = $callback( $allowed );
}

assert( array( 'page', 'post', 'service' ) === $allowed );
assert( array( 'service' ) === codex_bridge_allow_service_post_type( array( 'service' ) ) );
assert( array( 'service' ) === codex_bridge_allow_service_post_type( null ) );

echo json_encode( $allowed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
