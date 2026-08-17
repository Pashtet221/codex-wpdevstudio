<?php
/**
 * Plugin Name: Codex Bridge — Service Access
 * Description: Allows Codex Bridge to manage the service post type.
 * Version: 1.0.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add services to the Bridge allowlist without replacing its existing types.
 *
 * WordPress still enforces the service post type's native capabilities for
 * reading, creating, editing, and deleting individual records.
 *
 * @param mixed $post_types Post types allowed by Codex Bridge.
 * @return array
 */
function codex_bridge_allow_service_post_type( $post_types ): array {
	$post_types = is_array( $post_types ) ? $post_types : array();
	$post_types[] = 'service';

	return array_values( array_unique( $post_types ) );
}

add_filter( 'codex_bridge_allowed_post_types', 'codex_bridge_allow_service_post_type' );
