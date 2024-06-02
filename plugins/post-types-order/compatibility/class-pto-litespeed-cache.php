<?php
/**
 * Compatibility     : LiteSpeed Cache
 * Introduced at     :
 *
 * @package post-types-order
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * PTO_LiteSpeed_Cache class.
 */
class PTO_LiteSpeed_Cache {



	/**
	 * Constructor function.
	 */
	public function __construct() {
		if ( $this->is_plugin_active() ) {
			add_action( 'PTO/order_update_complete', array( $this, 'order_update_complete' ) );
		}
	}


	/**
	 * Check if the plugin is active.
	 *
	 * @return bool
	 */
	public function is_plugin_active() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( is_plugin_active( 'litespeed-cache/litespeed-cache.php' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Purge all cache when order is updated.
	 *
	 * @return void
	 */
	public function order_update_complete() {

		if ( method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
			LiteSpeed_Cache_API::purge_all();
		}
	}
}

new PTO_LiteSpeed_Cache();
