<?php

namespace BlockLigatures\DB\Repos;

class Sources_Repo extends Abstract_Repo {

	protected function get_table_name(): string {
		return 'bl_reference_sources';
	}

	public function get_valid_statuses(): array {
		return array( 'active', 'trashed', 'orphaned' );
	}

	protected function throw_if_invalid_status( string $status, string $method ): void {
		$valid_statuses = $this->get_valid_statuses();

		if ( ! in_array( $status, $valid_statuses, true ) ) {
			throw new \InvalidArgumentException(
				"{$method}: \$status must be one of " . implode( ', ', $valid_statuses ) . ", got {$status}."
			);
		}
	}

	public function insert_source( array $data ): int|false {
		$inserted = $this->wpdb->insert(
			$this->table,
			array(
				'source_name'     => $data['source_name'],
				'source_block_id' => $data['source_block_id'],
				'source_post_id'  => $data['source_post_id'],
				'source_content'  => $data['source_content'],
				'owning_slug'     => $data['owning_slug'],
			),
			array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
			)
		);

		$this->throw_if_db_error( __METHOD__ );

		return $this->wpdb->insert_id;
	}

	public function find_by_id( int $source_id ): array|false {
		$this->throw_if_invalid_id( $source_id, __METHOD__ );

		$wpdb = $this->wpdb;
		$row  = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE source_id=%d',
				$this->table,
				$source_id
			)
		);

		$this->throw_if_db_error( __METHOD__ );

		return $row;
	}

	public function find_by_ids( int ...$ids ) {
		if ( empty( $ids ) ) {
			throw new \InvalidArgumentException(
				'find_by_ids: at least one id must be provided.'
			);
		}

		foreach ( $ids as $id ) {
			$this->throw_if_invalid_id( $id, __METHOD__ );
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM %i WHERE source_id IN ({$placeholders})",
				$this->table,
				...$ids
			)
		);

		$this->throw_if_db_error( __METHOD__ );

		return $results;
	}

	public function search_by_name( string $name, int $limit = 10 ): array {
		if ( $limit <= 0 ) {
			throw new \InvalidArgumentException(
				"search_by_name: \$limit must be greater than 0, got {$limit}."
			);
		}

		$wpdb    = $this->wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE source_name LIKE %s LIMIT %d',
				$this->table,
				'%' . $wpdb->esc_like( $name ) . '%',
				$limit
			)
		);

		$this->throw_if_db_error( __METHOD__ );

		return $results;
	}

	public function find_all_by_source_post( int $post_id ): array {
		$this->throw_if_invalid_id( $post_id, __METHOD__ );

		$wpdb    = $this->wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE source_post_id = %d',
				$this->table,
				$post_id
			)
		);

		$this->throw_if_db_error( __METHOD__ );

		return $results;
	}

	public function find_by_status( array $statuses, int $limit = 20, int $page = 1 ): array {
		if ( empty( $statuses ) ) {
			throw new \InvalidArgumentException(
				'find_by_status: $statuses must not be empty.'
			);
		}

		foreach ( $statuses as $status ) {
			$this->throw_if_invalid_status( $status, __METHOD__ );
		}

		if ( $limit <= 0 ) {
			throw new \InvalidArgumentException(
				"find_by_status: \$limit must be greater than 0, got {$limit}."
			);
		}

		if ( $page <= 0 ) {
			throw new \InvalidArgumentException(
				"find_by_status: \$page must be greater than 0, got {$page}."
			);
		}

		$offset = ( $page - 1 ) * $limit;
		$wpdb   = $this->wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE status IN (' . implode( ', ', array_fill( 0, count( $statuses ), '%s' ) ) . ') LIMIT %d OFFSET %d',
				array_merge( array( $this->table ), $statuses, array( $limit, $offset ) )
			)
		);

		$this->throw_if_db_error( __METHOD__ );

		return $results;
	}

	public function count_by_status( array $statuses ): int {
		if ( empty( $statuses ) ) {
			throw new \InvalidArgumentException(
				'count_by_status: at least one status must be provided.'
			);
		}

		foreach ( $statuses as $status ) {
			$this->throw_if_invalid_status( $status, __METHOD__ );
		}

		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE status IN ({$placeholders})",
				$this->table,
				...$statuses
			)
		);

		$this->throw_if_db_error( __METHOD__ );

		return (int) $count;
	}

	public function mark_trashed( int $post_id ) {
		$this->throw_if_invalid_id( $post_id, __METHOD__ );

		$updated = $this->wpdb->update(
			$this->table,
			array( 'status' => 'trashed' ),
			array(
				'source_post_id' => $post_id,
				'status'         => 'active',
			),
			array( '%s' ),
			array( '%d', '%s' )
		);

		$this->throw_if_db_error( __METHOD__ );

		return $updated;
	}

	public function mark_active( int $post_id ) {
		$this->throw_if_invalid_id( $post_id, __METHOD__ );

		$updated = $this->wpdb->update(
			$this->table,
			array( 'status' => 'active' ),
			array(
				'source_post_id' => $post_id,
				'status'         => 'trashed',
			),
			array( '%s' ),
			array( '%d', '%s' )
		);

		$this->throw_if_db_error( __METHOD__ );

		return $updated;
	}

	public function mark_orphaned( int ...$source_ids ): int {
		if ( empty( $source_ids ) ) {
			throw new \InvalidArgumentException(
				'mark_orphaned: at least one id must be provided.'
			);
		}

		foreach ( $source_ids as $source_id ) {
			$this->throw_if_invalid_id( $source_id, __METHOD__ );
		}

		$wpdb = $this->wpdb;

		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET status = 'orphaned' WHERE source_id IN (" . implode( ', ', array_fill( 0, count( $source_ids ), '%d' ) ) . ')',
				array_merge( array( $this->table ), $source_ids )
			)
		);

		$this->throw_if_db_error( __METHOD__ );

		return $updated;
	}

	public function delete_by_ids( int ...$ids ): int {
		if ( empty( $ids ) ) {
			throw new \InvalidArgumentException(
				'delete_by_ids: at least one id must be provided.'
			);
		}

		foreach ( $ids as $id ) {
			$this->throw_if_invalid_id( $id, __METHOD__ );
		}

		$wpdb = $this->wpdb;

		$deleted = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE source_id IN (' . implode( ', ', array_fill( 0, count( $ids ), '%d' ) ) . ')',
				array_merge( array( $this->table ), $ids )
			)
		);

		$this->throw_if_db_error( __METHOD__ );

		return $deleted;
	}
}