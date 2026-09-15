<?php

define( 'ABSPATH', __DIR__ );

$GLOBALS['test_filters'] = array();

function add_filter( string $hook, callable $callback ): void {
	$GLOBALS['test_filters'][ $hook ][] = $callback;
}

require dirname( __DIR__ ) . '/wordpress-plugin/codex-bridge-woocommerce-access/codex-bridge-woocommerce-access.php';

$allowed = array( 'page', 'post' );
foreach ( $GLOBALS['test_filters']['codex_bridge_allowed_post_types'] as $callback ) {
	$allowed = $callback( $allowed );
}

assert( array( 'page', 'post', 'product', 'product_variation' ) === $allowed );
assert( array( 'product', 'product_variation' ) === codex_bridge_allow_woocommerce_post_types( array( 'product' ) ) );
assert( array( 'product', 'product_variation' ) === codex_bridge_allow_woocommerce_post_types( null ) );

echo json_encode( $allowed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
