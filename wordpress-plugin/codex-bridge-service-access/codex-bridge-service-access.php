<?php
/**
 * Plugin Name: Codex Bridge — Content Access
 * Description: Allows Codex Bridge to manage services and WooCommerce products.
 * Version: 1.1.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add site-managed content types to the Bridge allowlist without replacing its
 * existing types.
 *
 * WordPress still enforces each post type's native capabilities for reading,
 * creating, editing, and deleting individual records.
 *
 * @param mixed $post_types Post types allowed by Codex Bridge.
 * @return array
 */
function codex_bridge_allow_service_post_type( $post_types ): array {
	$post_types   = is_array( $post_types ) ? $post_types : array();
	$post_types[] = 'service';
	$post_types[] = 'product';
	$post_types[] = 'product_variation';

	return array_values( array_unique( $post_types ) );
}

add_filter( 'codex_bridge_allowed_post_types', 'codex_bridge_allow_service_post_type' );
