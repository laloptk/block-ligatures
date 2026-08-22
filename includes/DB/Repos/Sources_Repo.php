<?php 

namespace BlockLigatures\DB\Repos;

class Sources_Repo extends Abstract_Repo {

    protected function get_table_name():string {
        return 'bl_reference_sources';
    }

    public function get_valid_statuses(): array {
        return ['active', 'trashed', 'orphaned'];
    }

    public function insert_source( array $data ):int|false {
        $inserted = $this->wpdb->insert(
            $this->table,
            array(
                'source_name' => $data['source_name'],
                'source_block_id' => $data['source_block_id'],
                'source_post_id' => $data['source_post_id'],
                'source_content' => $data['source_content'],
                'owning_slug' => $data['owning_slug'],
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%s',
                '%s'
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $this->wpdb->insert_id;
    }

    public function find_by_id(int $source_id):array|false {
        $this->throw_if_invalid_id( $source_id, __METHOD__ );

        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE source_id=%d",
                $source_id
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $row;
    }

    public function search_by_name(string $name, int $limit = 10):array {
        if( $limit <= 0 ) {
            throw new \InvalidArgumentException(
                "search_by_name: \$limit must be greater than 0, got {$limit}."
            );
        }

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE source_name LIKE %s LIMIT %d",
                '%' . $this->wpdb->esc_like( $name ) . '%',
                $limit
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $results;
    }

    public function find_all_by_source_post( int $post_id ): array {
        $this->throw_if_invalid_id( $post_id, __METHOD__ );

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE source_post_id = %d",
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

        $valid_statuses = $this->get_valid_statuses();

        foreach ( $statuses as $status ) {
            if ( ! in_array( $status, $valid_statuses, true ) ) {
                throw new \InvalidArgumentException(
                    'find_by_status: $statuses must only contain ' . implode( ', ', $valid_statuses ) . ", got {$status}."
                );
            }
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
        $placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE status IN ({$placeholders}) LIMIT %d OFFSET %d",
                array_merge( $statuses, [ $limit, $offset ] )
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $results;
    }

    public function mark_trashed(int $post_id) {
        $this->throw_if_invalid_id( $post_id, __METHOD__ );
        
        $updated = $this->wpdb->update(
            $this->table,
            array('status' => 'trashed'),
            array(
                'source_post_id' => $post_id,
                'status' => 'active'
            ),
            array('%s'),
            array('%d', '%s')
        );

        $this->throw_if_db_error( __METHOD__ );

        return $updated;
    }

    public function mark_active(int $post_id) {
        $this->throw_if_invalid_id( $post_id, __METHOD__ );
        
        $updated = $this->wpdb->update(
            $this->table,
            array('status' => 'active'),
            array(
                'source_post_id' => $post_id,
                'status' => 'trashed'
            ),
            array('%s'),
            array('%d', '%s')
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

        $placeholders = implode( ', ', array_fill( 0, count( $source_ids ), '%d' ) );

        $updated = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table} SET status = 'orphaned' WHERE source_id IN ({$placeholders})",
                $source_ids
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

        $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table} WHERE source_id IN ({$placeholders})",
                $ids
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $deleted;
    }
}