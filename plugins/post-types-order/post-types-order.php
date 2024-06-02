<?php
/**
 * Plugin Name: Post Types Order
 * Plugin URI: https://github.com/FireWork-Production-Private-Ltd/post-types-order
 * Description: Posts Order and Post Types Objects Order using a Drag and Drop Sortable javascript capability
 * Author: FireWork Production Private Limited
 * Author URI: https://github.com/FireWork-Production-Private-Ltd
 * Version: 1.0.0
 * Text Domain: post-types-order
 * Domain Path: /languages/
 *
 * @package post-types-order
 */

define( 'CPTPATH', plugin_dir_path( __FILE__ ) );
define( 'CPTURL', plugins_url( '', __FILE__ ) );

require_once CPTPATH . '/include/class-cpto.php';
require_once CPTPATH . '/include/class-cptofunctions.php';


add_action( 'plugins_loaded', 'cpto_class_load' );
/**
 * Load the plugin
 *
 * @return void
 */
function cpto_class_load() {

	global $cpto;
	$cpto = new CPTO();
}


add_action( 'plugins_loaded', 'cpto_load_textdomain' );
/**
 * Load the plugin text domain
 *
 * @return void
 */
function cpto_load_textdomain() {
	load_plugin_textdomain( 'post-types-order', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}


add_action( 'wp_loaded', 'init_cpto' );

/**
 * Init the plugin
 *
 * @return void
 */
function init_cpto() {
	global $cpto;

	$options = $cpto->functions->get_options();

	if ( is_admin() ) {
		if ( isset( $options['capability'] ) && ! empty( $options['capability'] ) ) {
			if ( current_user_can( $options['capability'] ) ) {
				$cpto->init();
			}
		} elseif ( is_numeric( $options['level'] ) ) {
			if ( $cpto->functions->userdata_get_user_level( true ) >= $options['level'] ) {
				$cpto->init();
			}
		} else {
			$cpto->init();
		}
	}
}
