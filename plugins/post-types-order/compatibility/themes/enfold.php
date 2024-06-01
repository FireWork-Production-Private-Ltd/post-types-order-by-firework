<?php
/**
 * Compatibility     : Endfold
 * Introduced at     : 5.6.2
 * 
 * @package post-types-order
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
	
/**
 * PTO_Enfold class.
 */
class PTO_Enfold {
	
						
	/**
	 * Constructor function.
	 */
	public function __construct() {
					
			add_filter( 'pto/posts_orderby/ignore', array( $this, 'ignore_post_types_order_sort' ), 10, 3 );
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
		if ( isset( $query->query_vars ) && ! empty( $query->query_vars['post_type'] ) ) {
				$query_post_types = array();
			foreach ( (array) $query->query_vars['post_type'] as $_post_type ) {
				$query_post_types[] = $_post_type;
			}
							
			if ( in_array( 'avia_framework_post', $query_post_types ) ) {
				$ignore = true;
			} 
		}
					
			return $ignore;
	}
}
		
new PTO_Enfold();
