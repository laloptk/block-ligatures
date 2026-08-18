<?php
/**
 * Plugin Name:       Block Ligatures
 * Description:       Adds ligature support to block editor typography.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
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