<?php

namespace BlockLigatures\API;

use WP_REST_Response;
use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

class Sources_List_Controller extends Abstract_REST_Controller {
	public function get_routes_definitions(): array {
		return array(
			array(
				'route'                => '/list',
				'methods'              => WP_REST_Server::READABLE,
				'callback'             => array( $this, 'callback' ),
				'permissions_callback' => array( $this, 'permissions_callback' ),
				'args'                 => array(
					'statuses' => array(
						'required'          => false,
						'type'              => 'array',
						'validate_callback' => array( $this, 'statuses_validation_callback' ),
					),
					'per_page' => array(
						'required'          => false,
						'type'              => 'integer',
						'validate_callback' => array( $this, 'num_validation_callback' ),
						'sanitize_callback' => array( $this, 'num_sanitization_callback' ),
						'default'           => 20,
					),
					'page'     => array(
						'required'          => false,
						'type'              => 'integer',
						'validate_callback' => array( $this, 'num_validation_callback' ),
						'sanitize_callback' => array( $this, 'num_sanitization_callback' ),
						'default'           => 1,
					),
				),
			),
		);
	}

	public function callback( WP_REST_Request $req ): WP_REST_Response|WP_Error {
		$statuses = $req->get_param( 'statuses' ) ?? $this->repo->get_valid_statuses();
		$per_page = $req->get_param( 'per_page' );
		$page     = $req->get_param( 'page' );

		try {
			$results = $this->repo->find_by_status( $statuses, $per_page, $page );
			$total   = $this->repo->count_by_status( $statuses );
		} catch ( \Throwable $e ) {
			error_log( 'An error occurred: ' . $e->getMessage() );
			return new WP_Error( 'blig_list_failed', 'List failed.', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'items'      => $results,
				'pagination' => array(
					'total'        => $total,
					'total_pages'  => (int) ceil( $total / $per_page ),
					'current_page' => $page,
					'per_page'     => $per_page,
				),
			)
		);
	}

	public function permissions_callback(): bool {
		return current_user_can( 'manage_options' );
	}

	public function statuses_validation_callback( $statuses ): bool {
		if ( ! is_array( $statuses ) || empty( $statuses ) ) {
			return false;
		}

		$valid_statuses = $this->repo->get_valid_statuses();

		foreach ( $statuses as $status ) {
			if ( ! in_array( $status, $valid_statuses, true ) ) {
				return false;
			}
		}

		return true;
	}

	public function num_validation_callback( $value ): bool {
		return is_numeric( $value ) && (int) $value > 0;
	}

	public function num_sanitization_callback( $value ): int {
		return absint( $value );
	}
}
