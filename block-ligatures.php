<?php
/**
 * Plugin Name:       Block Ligatures
 * Description:       Adds ligature support to block editor typography.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Lalo
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       block-ligatures
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOCK_LIGATURES_VERSION', '1.0.0' );
define( 'BLOCK_LIGATURES_PATH', plugin_dir_path( __FILE__ ) );
define( 'BLOCK_LIGATURES_URL', plugin_dir_url( __FILE__ ) );

require_once BLOCK_LIGATURES_PATH . 'vendor/autoload.php';
use BlockLigatures\DB\Source_Table;
use BlockLigatures\DB\Relationships_Table;
use BlockLigatures\DB\Table_Installer;

/*
** This will load the manifest to register blocks
** New blocks are added automatically to the manifest
** When running npm build or npm start
*/
function block_ligatures_register_blocks() {
	wp_register_block_types_from_metadata_collection(
		__DIR__ . '/build',
		__DIR__ . '/build/blocks-manifest.php'
	);
}
add_action( 'init', 'block_ligatures_register_blocks' );


/*
** Installing the custom tables to store sources and relationships
*/
function bl_create_tables() {
	$source_table        = new Source_Table();
	$relationships_table = new Relationships_Table();
	$tables_obj          = new Table_Installer( $source_table, $relationships_table );
	$tables_obj->install();
}

register_activation_hook(
	__FILE__,
	'bl_create_tables'
);

use BlockLigatures\DB\Repos\Sources_Repo;
use BlockLigatures\API\Sources_Search_Controller;
use BlockLigatures\API\Sources_Resolve_Controller;
use BlockLigatures\API\Sources_List_Controller;
use BlockLigatures\API\Sources_Delete_Controller;


add_action( 'plugins_loaded', function () {
    $sources_repo = new Sources_Repo();

    $search_controller = new Sources_Search_Controller( $sources_repo );
    $resolve_controller = new Sources_Resolve_Controller( $sources_repo );
	$list_controller = new Sources_List_Controller($sources_repo);
	$delete_controller = new Sources_Delete_Controller($sources_repo);
} );
