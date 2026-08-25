<?php

namespace BlockLigatures\API;

use WP_REST_Response;
use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

class Sources_Delete_Controller extends Abstract_REST_Controller {
    public function get_routes_definitions(): array {
        return array(
            array(
                'route' => '/delete',
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'callback'),
                'permissions_callback' => array($this, 'permissions_callback'),
                'args' => array(
                    'ids' => array(
                        'required'          => true,
                        'type'              => 'array',
                        'validate_callback' => [ $this, 'validate_callback' ],
                        'sanitize_callback' => [ $this, 'sanitize_callback' ],
                    ),
                )
            )
        );
    }

    public function callback(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $ids = $req->get_param('ids');

        try {
            $deleted = $this->repo->delete_by_ids( ...$ids );
        } catch(\Throwable $e) {
            error_log('An error has ocurred: ' . $e->getMessage());
            return new WP_Error( 'blig_delete_failed', 'Deletion failed.', [ 'status' => 500 ] );
        }
        
        return rest_ensure_response(
            array(
                'deleted' => $deleted,
                'failed' => count($ids) - $deleted
            )
        );
    }

    public function permissions_callback() {
        return current_user_can( 'manage_options' );
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
        return array_unique(array_map( 'absint', (array) $value ));
    }
}