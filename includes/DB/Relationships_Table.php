<?php

namespace BlockLigatures\DB;

class Relationships_Table extends Abstract_Table {
    protected function get_table_name(): string {
        return 'bl_reference_relationships';
    }

    protected function get_schema(): string {
        return sprintf(
            "CREATE TABLE %s (
                relationship_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                source_id bigint(20) UNSIGNED NOT NULL,
                reference_block_id varchar(64) NOT NULL,
                reference_post_id bigint(20) UNSIGNED NOT NULL,
                reference_kind varchar(12) NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (relationship_id),
                KEY source_id (source_id),
                KEY reference_post_block (reference_post_id, reference_block_id)
            ) %s;",
            $this->get_table_full_name(),
            $this->get_charset_collate()
        );
    }
}