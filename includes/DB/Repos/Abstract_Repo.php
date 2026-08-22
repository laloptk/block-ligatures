<?php 

namespace BlockLigatures\DB\Repos;

use wpdb;

abstract class Abstract_Repo {
    protected wpdb $wpdb;
    protected string $table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->get_table_full_name();
    }

    abstract protected function get_table_name(): string;

    private function get_table_full_name(): string {
        return $this->wpdb->prefix . $this->get_table_name();
    }

    /**
     * Helpers
    **/
    protected function throw_if_db_error( string $context ): void {
        if ( $this->wpdb->last_error ) {
            throw new \RuntimeException(
                "{$context} query failed: {$this->wpdb->last_error}"
            );
        }
    }

    protected function throw_if_invalid_id(int $id, $context): void {
        if($id < 0) {
            throw new \InvalidArgumentException(
                "{$context}: \$id argument must be greater than 0, got {$id}."
            );
        }
    }
}