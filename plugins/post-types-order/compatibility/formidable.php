<?php
/**
 * Compatibility     : Formidable Forms
 * Introduced at     :  6.8.2
 * 
 * @package post-types-order
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

	
/**
 * PTO_Compatibility_Formidables class.
 */
class PTO_Compatibility_Formidables {
	
			
	/**
	 * Constructor function.
	 */
	public function __construct() {
		if ( ! $this->is_plugin_active() ) {
			return false;
		}
						
			add_filter( 'pto/posts_orderby/ignore', array( $this, 'ignore_post_types_order_sort' ), 10, 3 );
	}                        
			
	/**
	 * Check if the plugin is active.
	 * 
	 * @return bool
	 */
	public function is_plugin_active() {
					
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
					
		if ( is_plugin_active( 'formidable/formidable.php' ) ) {
			return true;
		} else {
			return false;
		}
	}
				
				
	/**
	 * Check if the plugin is active.
	 * 
	 * @param string $ignore   ignore.
	 * @param mixed  $order_by  orderby.
	 * @param mixed  $query    query.
	 * 
	 * @return bool
	 */
	public function ignore_post_types_order_sort( $ignore, $order_by, $query ) { 
		if ( isset( $query->query ) && ! empty( $query->query['post_type'] ) && 'frm_styles' == $query->query['post_type'] ) { 
			$ignore = true;
		} 
					
			return $ignore;
	}
}

new PTO_Compatibility_Formidables();
