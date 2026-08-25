<?php
namespace BlockLigatures\API;

use BlockLigatures\DB\Repos\Abstract_Repo;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

class Sources_Search_Controller extends Abstract_REST_Controller {

    public function get_routes_definitions(): array {
        return array(
            array(
                'route'                => '/search',
                'methods'              => WP_REST_Server::READABLE,
                'callback'             => [ $this, 'callback' ],
                'permissions_callback' => [ $this, 'permissions_callback' ],
                'args'                 => [
                    'query' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function ( $value ) {
                            return is_string( $value ) && strlen( $value ) <= 100;
                        },
                    ],
                    'limit' => [
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'default'           => 20,
                    ],
                ],
            ),
        );
    }

    public function callback( WP_REST_Request $req ): WP_REST_Response|WP_Error {
        $query = $req->get_param( 'query' );

        if ( empty( $query ) ) {
            return new WP_Error( 'blig_missing_query', 'A search query is required.', [ 'status' => 400 ] );
        }

        $limit = $req->get_param( 'limit' ) ?? 10;

        try {
            $results = $this->repo->search_by_name( $query, $limit );
        } catch ( \Throwable $e ) {
            error_log( 'An error occurred: ' . $e->getMessage() );
            return new WP_Error( 'blig_search_failed', 'Search failed.', [ 'status' => 500 ] );
        }

        return rest_ensure_response( $results );
    }

    public function permissions_callback(): bool {
        return current_user_can( 'edit_posts' );
    }
}