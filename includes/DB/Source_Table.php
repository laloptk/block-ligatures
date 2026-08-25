<?php

namespace BlockLigatures\DB;

class Source_Table extends Abstract_Table {
	protected function get_table_name(): string {
		return 'bl_reference_sources';
	}

	protected function get_schema(): string {
		return sprintf(
			"CREATE TABLE %s (
                source_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                source_name varchar(80) NOT NULL,
                source_block_id varchar(64) NOT NULL,
                source_post_id bigint(20) UNSIGNED NOT NULL,
                source_content longtext NOT NULL,
                owning_slug varchar(100) NOT NULL,
                status varchar(12) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (source_id),
                UNIQUE KEY  source_post_block (source_post_id, source_block_id),
                KEY  source_post_id (source_post_id),
                KEY  status (status)
            ) %s;",
			$this->get_table_full_name(),
			$this->get_charset_collate()
		);
	}
}
