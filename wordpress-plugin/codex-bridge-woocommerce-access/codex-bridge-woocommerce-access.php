<?php
/**
 * Plugin Name: Codex Bridge — WooCommerce Access
 * Description: Allows Codex Bridge to manage WooCommerce products and variations.
 * Version: 1.0.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add WooCommerce content types to the Bridge allowlist without replacing its
 * existing types. WooCommerce remains responsible for registering both types
 * and WordPress continues to enforce their native capabilities.
 *
 * @param mixed $post_types Post types allowed by Codex Bridge.
 * @return array
 */
function codex_bridge_allow_woocommerce_post_types( $post_types ): array {
	$post_types   = is_array( $post_types ) ? $post_types : array();
	$post_types[] = 'product';
	$post_types[] = 'product_variation';

	return array_values( array_unique( $post_types ) );
}

add_filter( 'codex_bridge_allowed_post_types', 'codex_bridge_allow_woocommerce_post_types' );
