<?php
/**
 * Compatibility class for Post Types Order
 * 
 * @package post-types-order
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
	  
/**
 * CPT_Compatibility class.
 */
class CPT_Compatibility {

		  
	/**
	 * Constructor function.
	 */
	public function __construct() {
					
				$this->init();
	}
				
				
				
	/**
	 * Init function.
	 * 
	 * @return mixed
	 */
	public function init() {
					
				$compatibility_files = array(
					'the-events-calendar.php',
					'LiteSpeed_Cache.php',
					'formidable.php',
												  
				);
				foreach ( $compatibility_files as $compatibility_file ) {
					if ( is_file( CPTPATH . 'compatibility/' . $compatibility_file ) ) {
						include_once CPTPATH . 'compatibility/' . $compatibility_file;
					}
				}
					   
				/**
				 * Themes
				 */
					
				$theme = wp_get_theme();
					
				if ( ! $theme instanceof WP_Theme ) {
					return false;
				}
						
				$compatibility_themes = array(
					'enfold' => 'enfold.php',
				);
					
				if ( isset( $theme->template ) ) {
					foreach ( $compatibility_themes as  $theme_slug     => $compatibility_file ) {
						if ( strtolower( $theme->template ) == $theme_slug || strtolower( $theme->name ) == $theme_slug ) {
								include_once CPTPATH . 'compatibility/themes/' . $compatibility_file;    
						}
					}
				}
					
						   
				do_action( 'cpt_compatibility_init' );
	}
}   
			

new CPT_Compatibility();
