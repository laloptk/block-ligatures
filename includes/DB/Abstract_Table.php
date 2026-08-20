<?php

namespace BlockLigatures\DB;

use wpdb;
use WP_Error;

abstract class Abstract_Table {
    protected wpdb $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    abstract protected function get_table_name(): string;

    abstract protected function get_schema(): string;

    protected function get_table_full_name(): string {
        return $this->wpdb->prefix . $this->get_table_name();
    }

    protected function get_charset_collate(): string {
        return $this->wpdb->get_charset_collate();
    }

    public function create(): bool|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $this->get_schema() );

        return $this->verify_table_exists();
    }

    protected function verify_table_exists(): bool|WP_Error {
        $table_full_name = $this->get_table_full_name();
        $table_exists    = $this->wpdb->get_var(
            $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table_full_name )
        );

        if ( $table_exists === $table_full_name ) {
            return true;
        }

        if ( ! empty( $this->wpdb->last_error ) ) {
            $message = 'Block Ligatures: dbDelta database error creating ' . $table_full_name . ': ' . $this->wpdb->last_error;
        } else {
            $message = 'Block Ligatures: table ' . $table_full_name . ' was not created — check schema formatting for dbDelta.';
        }

        error_log( $message );

        return new WP_Error( 'bl_table_creation_failed', $message );
    }
}