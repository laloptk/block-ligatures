<?php

namespace BlockLigatures\DB\Repos;

class Relationships_Repo extends Abstract_Repo {

    protected function get_table_name(): string {
        return 'bl_reference_relationships';
    }

    public function get_valid_kinds(): array {
        return [ 'block', 'link_modal', 'link_anchor' ];
    }

    public function find_by_source_id( int $source_id ): array {
        $this->throw_if_invalid_id( $source_id, __METHOD__ );

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE source_id = %d",
                $source_id
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $results;
    }

    public function find_by_consuming_post( int $post_id ): array {
        $this->throw_if_invalid_id( $post_id, __METHOD__ );

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE reference_post_id = %d",
                $post_id
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $results;
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
                "DELETE FROM {$this->table} WHERE relationship_id IN ({$placeholders})",
                $ids
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $deleted;
    }

    public function insert_relationships( array $rows, int $post_id ): int {
        if ( empty( $rows ) ) {
            throw new \InvalidArgumentException(
                "insert_relationships: \$rows can't be an empty array."
            );
        }

        $this->throw_if_invalid_id( $post_id, __METHOD__ );

        $values     = array();
        $row_groups = array();

        foreach ( $rows as $row ) {
            $this->throw_if_invalid_relationship_row( $row, __METHOD__ );
            $values[]     = $row['source_id'];
            $values[]     = $row['reference_kind'];
            $values[]     = $post_id;
            $values[]     = $row['reference_block_id'];
            $row_groups[] = '(%d, %s, %d, %s)';
        }

        $values_sql = implode( ', ', $row_groups );

        $inserted = $this->wpdb->query(
            $this->wpdb->prepare(
                "INSERT INTO {$this->table}
                    (source_id, reference_kind, reference_post_id, reference_block_id)
                VALUES
                    {$values_sql}",
                $values
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $inserted;
    }

    public function update_kind(int $relationship_id, string $new_kind) {
        $this->throw_if_invalid_id( $relationship_id, __METHOD__ );

        if ( ! in_array( $new_kind, $this->get_valid_kinds(), true ) ) {
            $valid_kinds = implode( ', ', $this->get_valid_kinds() );
            throw new \InvalidArgumentException(
                "update_kind: \$new_kind must be one of: {$valid_kinds}, got {$new_kind}."
            );
        }

        $updated = $this->wpdb->update(
            $this->table,
            array(
                'reference_kind' => $new_kind
            ),
            array(
                'relationship_id' => $relationship_id
            ),
            array(
                '%s'
            ),
            array(
                '%d'
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $updated;
    }

    public function delete_by_source_ids(int ...$ids) {
        if ( empty( $ids ) ) {
            throw new \InvalidArgumentException(
                'delete_by_source_ids: at least one id must be provided.'
            );
        }

        foreach($ids as $id) {
            $this->throw_if_invalid_id($id, __METHOD__ );
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table} WHERE source_id IN ({$placeholders})",
                $ids
            )
        );

        $this->throw_if_db_error( __METHOD__ );

        return $deleted;
    }

    public function throw_if_invalid_relationship_row( array $row, string $context ): void {
        if ( ! isset( $row['source_id'] ) ) {
            throw new \InvalidArgumentException(
                "{$context}: the row array must include source_id."
            );
        }
        $this->throw_if_invalid_id( $row['source_id'], $context );

        if ( ! isset( $row['reference_kind'] ) ) {
            throw new \InvalidArgumentException(
                "{$context}: the row array must include reference_kind."
            );
        }
        if ( ! in_array( $row['reference_kind'], $this->get_valid_kinds(), true ) ) {
            $valid_kinds = implode( ', ', $this->get_valid_kinds() );
            throw new \InvalidArgumentException(
                "{$context}: reference_kind must be one of: {$valid_kinds}, got {$row['reference_kind']}."
            );
        }

        if ( ! isset( $row['reference_block_id'] ) ) {
            throw new \InvalidArgumentException(
                "{$context}: the row array must include reference_block_id."
            );
        }
        if ( ! is_string( $row['reference_block_id'] ) ) {
            throw new \InvalidArgumentException(
                "{$context}: reference_block_id must be a string."
            );
        }
    }
}