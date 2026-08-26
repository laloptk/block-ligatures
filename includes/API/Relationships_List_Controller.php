<?php

namespace BlockLigatures\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

class Relationships_List_Controller extends Abstract_REST_Controller {

    public function get_routes_definitions(): array {
        return array(
            array(
                'route'                => '/relationships',
                'methods'              => WP_REST_Server::READABLE,
                'callback'             => [ $this, 'callback' ],
                'permissions_callback' => [ $this, 'permissions_callback' ],
                'args'                 => array(
                    'post_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => [ $this, 'validate_callback' ],
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
        );
    }

    public function callback( WP_REST_Request $req ): WP_REST_Response|WP_Error {
        $post_id = $req->get_param( 'post_id' );

        try {
            $results = $this->repo->find_by_consuming_post( $post_id );
        } catch ( \Throwable $e ) {
            error_log( 'An error occurred: ' . $e->getMessage() );
            return new WP_Error( 'blig_relationships_failed', 'Fetching relationships failed.', [ 'status' => 500 ] );
        }

        return rest_ensure_response( $results );
    }

    public function permissions_callback(): bool {
        return true;
    }

    public function validate_callback( $value ): bool {
        return is_numeric( $value ) && (int) $value > 0;
    }
}