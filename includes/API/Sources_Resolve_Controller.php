<?php

namespace BlockLigatures\API;

use BlockLigatures\DB\Repos\Abstract_Repo;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

class Sources_Resolve_Controller extends Abstract_REST_Controller {

    public function get_routes_definitions(): array {
        return array(
            array(
                'route'   => '/resolve',
                // POST (not GET) is intentional: batch resolve accepts a
                // potentially long list of IDs, which is awkward/unreliable
                // as URL query params. This is semantically a read (no state
                // change) — permissions_callback stays public — POST here is
                // purely about payload shape, not intent.
                'methods'              => WP_REST_Server::CREATABLE,
                'callback'             => [ $this, 'callback' ],
                'permissions_callback' => [ $this, 'permissions_callback' ],
                'args'                 => array(
                    'ids' => array(
                        'required'          => true,
                        'type'              => 'array',
                        'validate_callback' => [ $this, 'validate_callback' ],
                        'sanitize_callback' => [ $this, 'sanitize_callback' ],
                    ),
                ),
            ),
        );
    }

    public function callback( WP_REST_Request $req ): WP_REST_Response|WP_Error {
        $ids = $req->get_param( 'ids' );

        try {
            $results = $this->repo->find_by_ids( ...$ids );
        } catch ( \Throwable $e ) {
            error_log( 'An error occurred: ' . $e->getMessage() );
            return new WP_Error( 'blig_resolve_failed', 'Resolve failed.', [ 'status' => 500 ] );
        }

        $resolved_ids = array_map(
            static fn( $row ) => (int) $row->source_id,
            $results
        );
        $failed_ids = array_values( array_diff( $ids, $resolved_ids ) );

        return rest_ensure_response( [
            'resolved' => $results,
            'failed'   => $failed_ids,
        ] );
    }

    public function permissions_callback(): bool {
        return true;
    }

    public function validate_callback( $value ): bool {
        if ( ! is_array( $value ) || empty( $value ) ) {
            return false;
        }

        foreach ( $value as $id ) {
            if ( ! is_int( $id ) && ! ( is_string( $id ) && ctype_digit( $id ) ) ) {
                return false;
            }

            if ( (int) $id <= 0 ) {
                return false;
            }
        }

        return true;
    }

    public function sanitize_callback( $value ): array {
        return array_map( 'absint', (array) $value );
    }
}