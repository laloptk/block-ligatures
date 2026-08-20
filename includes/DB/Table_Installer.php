<?php

namespace BlockLigatures\DB;

use WP_Error;

class Table_Installer {
    /** @var Abstract_Table[] */
    private array $tables;

    public function __construct( Abstract_Table ...$tables ) {
        $this->tables = $tables;
    }

    public function install(): bool|WP_Error {
        $errors = new WP_Error();

        foreach ( $this->tables as $table ) {
            $result = $table->create();

            if ( is_wp_error( $result ) ) {
                $errors->merge_from( $result );
            }
        }

        return $errors->has_errors() ? $errors : true;
    }
}