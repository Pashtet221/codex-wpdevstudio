<?php
/**
 * Plugin Name: Codex Bridge — Rank Math Meta
 * Description: Adds an authenticated Rank Math meta endpoint to Codex Bridge.
 * Version: 1.1.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

final class Codex_Bridge_Rank_Math_Meta {
	private const NAMESPACE = 'codex-bridge/v1';
	private const META_KEYS = array(
		'rank_math_title',
		'rank_math_description',
		'rank_math_focus_keyword',
		'rank_math_canonical_url',
		'rank_math_robots',
	);
	private const IMMUTABLE_KEYS = array( 'id', 'ID', 'slug', 'url', 'post_type', 'status' );

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

		foreach ( self::IMMUTABLE_KEYS as $key ) {
			if ( $request->has_param( $key ) && ! ( 'id' === $key && $post_id === (int) $request->get_param( $key ) ) ) {
				return new WP_Error( 'codex_bridge_immutable_seo_field', sprintf( '%s cannot be changed through the SEO endpoint.', $key ), array( 'status' => 400 ) );
			}
		}

		foreach ( self::META_KEYS as $key ) {
			if ( ! $request->has_param( $key ) ) {
				continue;
			}

			$value = $request->get_param( $key );
			if ( null !== $value && ! is_string( $value ) ) {
				return new WP_Error( 'codex_bridge_invalid_seo_meta', sprintf( '%s must be a string or null.', $key ), array( 'status' => 400 ) );
			}

			$values[ $key ] = null === $value ? null : self::sanitize_meta( $key, $value );
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
		$post = get_post( $post_id );
		$data = array(
			'id'        => $post_id,
			'post_type' => $post ? $post->post_type : '',
			'url'       => $post ? get_permalink( $post ) : '',
			'slug'      => $post ? $post->post_name : '',
			'title'     => $post ? get_the_title( $post ) : '',
			'content'   => $post ? $post->post_content : '',
			'excerpt'   => $post ? $post->post_excerpt : '',
		);
		foreach ( self::META_KEYS as $key ) {
			$data[ $key ] = (string) get_post_meta( $post_id, $key, true );
		}
		$data['seo_acf'] = self::seo_acf( $post_id );
		$data['rendered'] = self::rendered_seo( $post );

		return $data;
	}

	private static function sanitize_meta( string $key, string $value ): string {
		if ( 'rank_math_canonical_url' === $key ) {
			return esc_url_raw( $value );
		}
		if ( 'rank_math_robots' === $key ) {
			$tokens = array_filter( array_map( 'sanitize_key', preg_split( '/[\s,]+/', $value ) ) );
			$tokens = array_intersect( $tokens, array( 'index', 'noindex', 'follow', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ) );
			return implode( ',', array_values( array_unique( $tokens ) ) );
		}
		return sanitize_text_field( $value );
	}

	/** Return only explicitly allowlisted ACF fields; relationship values are reduced to IDs. */
	private static function seo_acf( int $post_id ): array {
		if ( ! function_exists( 'get_field_object' ) ) {
			return array();
		}
		$names = apply_filters( 'codex_bridge_seo_acf_fields', array(), $post_id );
		$data  = array();
		foreach ( array_filter( array_map( 'sanitize_key', (array) $names ) ) as $name ) {
			$field = get_field_object( $name, $post_id, false, false );
			if ( ! $field ) {
				continue;
			}
			$value = $field['value'] ?? null;
			if ( is_array( $value ) ) {
				$value = array_map( static fn( $item ) => is_object( $item ) && isset( $item->ID ) ? (int) $item->ID : $item, $value );
			}
			$data[ $name ] = array( 'type' => $field['type'], 'value' => $value );
		}
		return $data;
	}

	/** Read final public markup so filters/templates, not merely stored meta, are verified. */
	private static function rendered_seo( $post ): array {
		$result = array( 'h1' => array(), 'canonical' => '', 'robots' => '', 'title' => '', 'description' => '', 'http_status' => 0 );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return $result;
		}
		$response = wp_remote_get( get_permalink( $post ), array( 'timeout' => 15, 'redirection' => 3 ) );
		if ( is_wp_error( $response ) ) {
			$result['error'] = $response->get_error_message();
			return $result;
		}
		$html = wp_remote_retrieve_body( $response );
		$result['http_status'] = wp_remote_retrieve_response_code( $response );
		preg_match_all( '/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $h1 );
		$result['h1'] = array_map( static fn( $value ) => trim( wp_strip_all_tags( $value ) ), $h1[1] ?? array() );
		foreach ( array( 'title' => '/<title[^>]*>(.*?)<\/title>/is', 'canonical' => '/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)/is', 'robots' => '/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']+)/is', 'description' => '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)/is' ) as $key => $pattern ) {
			if ( preg_match( $pattern, $html, $match ) ) {
				$result[ $key ] = html_entity_decode( trim( wp_strip_all_tags( $match[1] ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			}
		}
		return $result;
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
