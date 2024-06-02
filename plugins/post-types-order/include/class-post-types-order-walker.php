<?php
/**
 * Post Types Order Walker
 *
 * @package post-types-order
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Post_Types_Order_Walker class.
 */
class Post_Types_Order_Walker extends Walker {



	/**
	 * DB fields
	 *
	 * @var array
	 */
	public $db_fields = array(
		'parent' => 'post_parent',
		'id'     => 'ID',
	);


	/**
	 * Start level
	 *
	 * @param string $output output.
	 * @param int    $depth  depth.
	 * @param array  $args   args.
	 *
	 * @return void
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent  = str_repeat( "\t", $depth );
		$output .= "\n$indent<ul class='children'>\n";
	}


	/**
	 * End level
	 *
	 * @param string $output output.
	 * @param int    $depth  depth.
	 * @param array  $args   args.
	 *
	 * @return void
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent  = str_repeat( "\t", $depth );
		$output .= "$indent</ul>\n";
	}


	/**
	 * Start element
	 *
	 * @param string $output output.
	 * @param object $page   page.
	 * @param int    $depth  depth.
	 * @param array  $args   args.
	 * @param int    $id     id.
	 *
	 * @return void
	 */
	public function start_el( &$output, $page, $depth = 0, $args = array(), $id = 0 ) {
		if ( $depth ) {
			$indent = str_repeat( "\t", $depth );
		} else {
			$indent = '';
		}

	extract( $args, EXTR_SKIP ); // phpcs:ignore 

		$item_details = apply_filters( 'the_title', $page->post_title, $page->ID );

		// Deprecated, rely on pto/interface_itme_data.
		$item_details = apply_filters( 'cpto_interface_itme_data', $item_details, $page );

		$item_details = apply_filters( 'pto_interface_item_data', $item_details, $page );

		$output .= $indent . '<li id="item_' . $page->ID . '"><span>' . $item_details . '</span>';
	}


	/**
	 * End element
	 *
	 * @param string $output output.
	 * @param object $page   page.
	 * @param int    $depth  depth.
	 * @param array  $args   args.
	 *
	 * @return void
	 */
	public function end_el( &$output, $page, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
}
