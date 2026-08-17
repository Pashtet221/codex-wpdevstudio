<?php
/**
 * Plugin Name: Codex Bridge — Rank Math Meta
 * Description: Adds an authenticated Rank Math meta endpoint to Codex Bridge.
 * Version: 1.0.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

final class Codex_Bridge_Rank_Math_Meta {
	private const NAMESPACE = 'codex-bridge/v1';
	private const META_KEYS = array(
		'rank_math_title',
		'rank_math_description',
		'rank_math_focus_keyword',
	);

	public static function register(): void {
		register_rest_route(
			self::NAMESPACE,
			'/posts/(?P<id>\d+)/seo',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'read' ),
					'permission_callback' => array( self::class, 'can_read' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( self::class, 'update' ),
					'permission_callback' => array( self::class, 'can_update' ),
				),
				'schema' => array( self::class, 'schema' ),
			)
		);
	}

	public static function can_read( WP_REST_Request $request ) {
		return self::authorize( $request, 'read_post' );
	}

	public static function can_update( WP_REST_Request $request ) {
		return self::authorize( $request, 'edit_post' );
	}

	private static function authorize( WP_REST_Request $request, string $capability ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post ) {
			return new WP_Error( 'codex_bridge_post_not_found', 'Post not found.', array( 'status' => 404 ) );
		}

		if ( ! in_array( $post->post_type, self::allowed_post_types(), true ) ) {
			return new WP_Error( 'codex_bridge_post_type_forbidden', 'Post type is not allowed by Codex Bridge.', array( 'status' => 403 ) );
		}

		if ( ! current_user_can( $capability, $post->ID ) ) {
			return new WP_Error( 'codex_bridge_forbidden', 'You are not allowed to access this post.', array( 'status' => 403 ) );
		}

		return true;
	}

	private static function allowed_post_types(): array {
		$health = rest_do_request( new WP_REST_Request( 'GET', '/' . self::NAMESPACE . '/health' ) );
		if ( ! $health->is_error() ) {
			$data = $health->get_data();
			if ( isset( $data['allowed_types'] ) && is_array( $data['allowed_types'] ) ) {
				return array_values( array_filter( array_map( 'sanitize_key', $data['allowed_types'] ) ) );
			}
		}

		// Fail closed if the installed Bridge cannot provide its whitelist.
		return array();
	}

	public static function read( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response( self::response_data( (int) $request['id'] ) );
	}

	public static function update( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$values  = array();

		foreach ( self::META_KEYS as $key ) {
			if ( ! $request->has_param( $key ) ) {
				continue;
			}

			$value = $request->get_param( $key );
			if ( null !== $value && ! is_string( $value ) ) {
				return new WP_Error( 'codex_bridge_invalid_seo_meta', sprintf( '%s must be a string or null.', $key ), array( 'status' => 400 ) );
			}

			$values[ $key ] = null === $value ? null : sanitize_text_field( $value );
		}

		if ( array() === $values ) {
			return new WP_Error( 'codex_bridge_empty_seo_update', 'Provide at least one supported Rank Math field.', array( 'status' => 400 ) );
		}

		foreach ( $values as $key => $value ) {
			if ( null === $value || '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}

		return rest_ensure_response( self::response_data( $post_id ) );
	}

	private static function response_data( int $post_id ): array {
		$data = array( 'id' => $post_id );
		foreach ( self::META_KEYS as $key ) {
			$data[ $key ] = (string) get_post_meta( $post_id, $key, true );
		}

		return $data;
	}

	public static function schema(): array {
		$properties = array( 'id' => array( 'type' => 'integer', 'readonly' => true ) );
		foreach ( self::META_KEYS as $key ) {
			$properties[ $key ] = array( 'type' => array( 'string', 'null' ) );
		}

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'codex-bridge-rank-math-meta',
			'type'       => 'object',
			'properties' => $properties,
		);
	}
}

add_action( 'rest_api_init', array( Codex_Bridge_Rank_Math_Meta::class, 'register' ) );
