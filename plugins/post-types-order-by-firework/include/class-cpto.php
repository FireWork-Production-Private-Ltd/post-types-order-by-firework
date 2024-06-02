<?php
/**
 * Post Types Order by FireWork
 *
 * @package post-types-order-by-firework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CPTO class.
 */
class CPTO {


	/**
	 * Current post type.
	 *
	 * @var mixed
	 */
	public $current_post_type = null;

	/**
	 * Functions.
	 *
	 * @var mixed
	 */
	public $functions;

	/**
	 * Post Type
	 *
	 * @var mixed
	 */
	public $post_type;


	/**
	 * Constructor function.
	 */
	public function __construct() {

		$this->functions = new CptoFunctions();

		$is_configured = get_option( 'CPT_configured' );
		if ( '' === strval( $is_configured ) ) {
			add_action( 'admin_notices', array( $this, 'admin_configure_notices' ) );
		}

		add_filter( 'init', array( $this, 'on_init' ) );
		add_filter( 'init', array( $this, 'compatibility' ) );

		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_filter( 'posts_orderby', array( $this, 'posts_orderby' ), 99, 2 );
	}


	/**
	 * Init function.
	 *
	 * @return mixed
	 */
	public function init() {

		include_once CPTPATH . '/include/class-post-types-order-walker.php';

		add_action( 'admin_init', array( &$this, 'admin_init' ), 10 );
		add_action( 'admin_menu', array( &$this, 'add_menu' ) );

		add_action( 'admin_menu', array( &$this, 'plugin_options_menu' ) );

		// load archive drag&drop sorting dependencies.
		add_action( 'admin_enqueue_scripts', array( &$this, 'archive_drag_drop' ), 10 );

		add_action( 'wp_ajax_update-custom-type-order', array( &$this, 'save_ajax_order' ) );
		add_action( 'wp_ajax_update-custom-type-order-archive', array( &$this, 'save_archive_ajax_order' ) );
	}


	/**
	 * On WordPress Init hook
	 * This is being used to set the navigational links
	 *
	 * @return mixed
	 */
	public function on_init() {
		if ( is_admin() ) {
			return null;
		}

		// check the navigation_sort_apply option.
		$options = $this->functions->get_options();

		$navigation_sort_apply = ( '1' === strval( $options['navigation_sort_apply'] ) ) ? true : false;

		// Deprecated, rely on pto/navigation_sort_apply.
		$navigation_sort_apply = apply_filters( 'cpto_navigation_sort_apply', $navigation_sort_apply );

		$navigation_sort_apply = apply_filters( 'pto_navigation_sort_apply', $navigation_sort_apply );

		if ( ! $navigation_sort_apply ) {
			return null;
		}

		add_filter( 'get_previous_post_where', array( $this->functions, 'cpto_get_previous_post_where' ), 99, 3 );
		add_filter( 'get_previous_post_sort', array( $this->functions, 'cpto_get_previous_post_sort' ) );
		add_filter( 'get_next_post_where', array( $this->functions, 'cpto_get_next_post_where' ), 99, 3 );
		add_filter( 'get_next_post_sort', array( $this->functions, 'cpto_get_next_post_sort' ) );

		return null;
	}



	/**
	 * Compatibility with different 3rd codes
	 *
	 * @return mixed
	 */
	public function compatibility() {
		include_once CPTPATH . '/include/class-cpt-compatibility.php';
		return null;
	}



	/**
	 * Pre get posts filter
	 *
	 * @param mixed $query query.
	 *
	 * @return mixed
	 */
	public function pre_get_posts( $query ) {

		// no need if it's admin interface.
		if ( is_admin() ) {
			return $query;
		}

		// check for ignore_custom_sort.
		if ( isset( $query->query_vars['ignore_custom_sort'] ) && true === boolval( $query->query_vars['ignore_custom_sort'] ) ) {
			return $query;
		}

		// ignore if  "nav_menu_item".
		if ( isset( $query->query_vars ) && isset( $query->query_vars['post_type'] ) && 'nav_menu_item' === strval( $query->query_vars['post_type'] ) ) {
			return $query;
		}

		$options = $this->functions->get_options();

		// if auto sort.
		if ( '1' === strval( $options['autosort'] ) ) {
			// remove the supresed filters.
			if ( isset( $query->query['suppress_filters'] ) ) {
				$query->query['suppress_filters'] = false;
			}

			if ( isset( $query->query_vars['suppress_filters'] ) ) {
				$query->query_vars['suppress_filters'] = false;
			}
		}

		return $query;
	}



	/**
	 * Posts orderby filter
	 *
	 * @param mixed $order_by orderby.
	 * @param mixed $query    query.
	 *
	 * @return mixed
	 */
	public function posts_orderby( $order_by, $query ) {
		global $wpdb;

		$options = $this->functions->get_options();

		// check for ignore_custom_sort.
		if ( isset( $query->query_vars['ignore_custom_sort'] ) && true === boolval( $query->query_vars['ignore_custom_sort'] ) ) {
			return $order_by;
		}

		// ignore the bbpress.
		if ( isset( $query->query_vars['post_type'] ) && ( ( is_array( $query->query_vars['post_type'] ) && in_array( 'reply', $query->query_vars['post_type'], true ) ) || ( 'reply' === strval( $query->query_vars['post_type'] ) ) ) ) {
			return $order_by;
		}
		if ( isset( $query->query_vars['post_type'] ) && ( ( is_array( $query->query_vars['post_type'] ) && in_array( 'topic', $query->query_vars['post_type'], true ) ) || ( 'topic' === strval( $query->query_vars['post_type'] ) ) ) ) {
			return $order_by;
		}

		// check for orderby GET paramether in which case return default data.
		if ( isset( $_GET['orderby'] ) && 'menu_order' !== $_GET['orderby'] ) { // // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $order_by;
		}

		// Avada orderby.
		if ( isset( $_GET['product_orderby'] ) && 'default' !== strval( sanitize_text_field( wp_unslash( $_GET['product_orderby'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $order_by;
		}

		/**
		 * Deprecated filter
		 * do not rely on this anymore
		 */
		if ( false === boolval( apply_filters( 'pto_posts_orderby', $order_by, $query ) ) ) {
			return $order_by;
		}

		$ignore = apply_filters( 'pto_posts_orderby_ignore', false, $order_by, $query );
		if ( true === boolval( $ignore ) ) {
			return $order_by;
		}

		// ignore search.
		if ( $query->is_search() && isset( $query->query['s'] ) && ! empty( $query->query['s'] ) ) {
			return( $order_by );
		}

		if ( ( is_admin() && ! wp_doing_ajax() ) || ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'query-attachments' === strval( sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( '1' === strval( $options['adminsort'] ) || ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'query-attachments' === strval( sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				global $post;

				$order = apply_filters( 'pto_posts_order', '', $query );

				// temporary ignore ACF group and admin ajax calls, should be fixed within ACF plugin sometime later.
				if ( ( is_object( $post ) && ( 'acf-field-group' === strval( $post->post_type ) ) ) || ( ( defined( 'DOING_AJAX' ) && ( isset( $_REQUEST['action'] ) && 0 === (int) strpos( sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ), 'acf/' ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					return $order_by;
				}

				if ( isset( $_POST['query'] ) && isset( $_POST['query']['post__in'] ) && is_array( $_POST['query']['post__in'] ) && count( $_POST['query']['post__in'] ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					return $order_by;
				}

				$order_by = "{$wpdb->posts}.menu_order {$order}, {$wpdb->posts}.post_date DESC";
			}
		} else {
			$order = '';
			if ( '1' === strval( $options['use_query_ASC_DESC'] ) ) {
				$order = isset( $query->query_vars['order'] ) ? ' ' . $query->query_vars['order'] : '';
			}

			$order = apply_filters( 'pto_posts_order', $order, $query );

			if ( '1' === strval( $options['autosort'] ) ) {
				if ( '' === strval( trim( $order_by ) ) ) {
					$order_by = "{$wpdb->posts}.menu_order " . $order;
				} else {
					$order_by = "{$wpdb->posts}.menu_order" . $order . ', ' . $order_by;
				}
			}
		}

		return( $order_by );
	}



	/**
	 * Show not configured notive
	 *
	 * @return void
	 */
	public function admin_configure_notices() {
		if ( isset( $_POST['form_submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified.
			return;
		}

		?>
		<div class="error fade">
			<p><strong>
					<?php esc_html_e( 'Post Types Order must be configured. Please go to', 'post-types-order-by-firework' ); ?> <a
						href="<?php echo esc_url( get_admin_url() ); ?>options-general.php?page=cpto-options">
						<?php esc_html_e( 'Settings Page', 'post-types-order-by-firework' ); ?>
					</a>
					<?php esc_html_e( 'make the configuration and save', 'post-types-order-by-firework' ); ?>
				</strong></p>
		</div>
		<?php
	}


	/**
	 * Plugin options menu
	 *
	 * @return void
	 */
	public function plugin_options_menu() {

		include CPTPATH . '/include/class-cptooptionsinterface.php';

		$options_interface = new CptoOptionsInterface();
		$options_interface->check_options_update();

		$hook_id = add_options_page( 'Post Types Order', '<img class="menu_pto" src="' . CPTURL . '/images/menu-icon.png" alt="" height="20px" width="20px" style="object-fit: cover; vertical-align: middle;" /> Post Types Order', 'manage_options', 'cpto-options', array( $options_interface, 'plugin_options_interface' ) );
		add_action( 'admin_print_styles-' . $hook_id, array( $this, 'admin_options_print_styles' ) );
	}

	/**
	 * Admin options print styles
	 *
	 * @return void
	 */
	public function admin_options_print_styles() {
		wp_register_style( 'pto-options', CPTURL . '/css/cpt-options.css', array(), '1.0.0', 'all' );
		wp_enqueue_style( 'pto-options' );
	}


	/**
	 * Load archive drag&drop sorting dependencies
	 *
	 * Since version 1.0.0
	 *
	 * @return void
	 */
	public function archive_drag_drop() {
		$options = $this->functions->get_options();

		// if adminsort turned off no need to continue.
		if ( '1' !== strval( $options['adminsort'] ) ) {
			return;
		}

		$screen = get_current_screen();

		// check if the right interface.
		if ( ! isset( $screen->post_type ) || empty( $screen->post_type ) ) {
			return;
		}

		if ( isset( $screen->taxonomy ) && ! empty( $screen->taxonomy ) ) {
			return;
		}

		if ( empty( $options['allow_reorder_default_interfaces'][ $screen->post_type ] ) || ( isset( $options['allow_reorder_default_interfaces'][ $screen->post_type ] ) && 'yes' !== strval( $options['allow_reorder_default_interfaces'][ $screen->post_type ] ) ) ) {
			return;
		}

		if ( wp_is_mobile() || ( function_exists( 'jetpack_is_mobile' ) && jetpack_is_mobile() ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_is_mobile_wp_is_mobile -- checking with jetpack also.
			return;
		}

		// if is taxonomy term filter return.
		if ( is_category() || is_tax() ) {
			return;
		}

		// return if use orderby columns.
		if ( isset( $_GET['orderby'] ) && 'menu_order' !== strval( sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		// return if post status filtering.
		if ( isset( $_GET['post_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		// return if post author filtering.
		if ( isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		// load required dependencies.
		wp_enqueue_style( 'cpt-archive-dd', CPTURL . '/css/cpt-archive-dd.css', array(), '1.0.0', 'all' );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_register_script( 'cpto', CPTURL . '/js/cpt.js', array( 'jquery' ), '1.0.0', true );

		global $userdata;

		// Localize the script with new data.
		$cpto_variables = array(
			'post_type'          => $screen->post_type,
			'archive_sort_nonce' => wp_create_nonce( 'CPTO_archive_sort_nonce_' . $userdata->ID ),
		);
		wp_localize_script( 'cpto', 'CPTO', $cpto_variables );

		// Enqueued script with localized data.
		wp_enqueue_script( 'cpto' );
	}



	/**
	 * Admin init
	 *
	 * @return void
	 */
	public function admin_init() {
		if ( isset( $_GET['page'] ) && 'order-post-types-' === strval( substr( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 0, 17 ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->current_post_type = get_post_type_object( str_replace( 'order-post-types-', '', sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( null === $this->current_post_type ) {
				wp_die( 'Invalid post type' );
			}
		}

		// add compatibility filters and code.
		include_once CPTPATH . '/compatibility/class-pto-litespeed-cache.php';
	}


	/**
	 * Save the order set through separate interface
	 *
	 * @return void
	 */
	public function save_ajax_order() {

		set_time_limit( 600 );

		global $wpdb;

		$nonce = isset( $_POST['interface_sort_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['interface_sort_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified.

		// verify the nonce.
		if ( ! wp_verify_nonce( $nonce, 'interface_sort_nonce' ) ) {
			die();
		}

		parse_str( ( isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '' ), $data );

		if ( is_array( $data ) ) {
			foreach ( $data as $key => $values ) {
				if ( 'item' === strval( $key ) ) {
					foreach ( $values as $position => $id ) {

						// sanitize.
						$id = (int) $id;

						$data = array( 'menu_order' => $position );

						// Deprecated, rely on pto/save-ajax-order.
						$data = apply_filters( 'post_types_order_save_ajax_order', $data, $key, $id );

						$data = apply_filters( 'pto_save_ajax_order', $data, $key, $id );

						$wpdb->update( $wpdb->posts, $data, array( 'ID' => $id ) );  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					}
				} else {
					foreach ( $values as $position => $id ) {

						// sanitize.
						$id = (int) $id;

						$data = array(
							'menu_order'  => $position,
							'post_parent' => str_replace( 'item_', '', $key ),
						);

						// Deprecated, rely on pto/save-ajax-order.
						$data = apply_filters( 'post_types_order_save_ajax_order', $data, $key, $id );

						$data = apply_filters( 'pto_save_ajax_order', $data, $key, $id );

						$wpdb->update( $wpdb->posts, $data, array( 'ID' => $id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					}
				}
			}
		}

		// trigger action completed.
		do_action( 'pto_order_update_complete' );

		wp_cache_flush();
	}


	/**
	 * Save the order set throgh the Archive
	 *
	 * @return void
	 */
	public function save_archive_ajax_order() {

		set_time_limit( 600 );

		global $wpdb, $userdata;

		$post_type = isset( $_POST['post_type'] ) ? preg_replace( '/[^a-zA-Z0-9_\-]/', '', sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified.
		$paged     = isset( $_POST['paged'] ) ? filter_var( sanitize_text_field( wp_unslash( $_POST['paged'] ) ), FILTER_SANITIZE_NUMBER_INT ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified.
		$nonce     = ( isset( $_POST['archive_sort_nonce'] ) ) ? sanitize_text_field( wp_unslash( $_POST['archive_sort_nonce'] ) ) : '';

		// verify the nonce.
		if ( ! wp_verify_nonce( $nonce, 'CPTO_archive_sort_nonce_' . $userdata->ID ) ) {
			die();
		}

		parse_str( sanitize_text_field( isset( $_POST['order'] ) ?? sanitize_text_field( wp_unslash( $_POST['order'] ) ) ), $data );

		if ( ! is_array( $data ) || count( $data ) < 1 ) {
			die();
		}

		// retrieve a list of all objects.
		$mysql_query = $wpdb->prepare(
			'SELECT ID FROM ' . $wpdb->posts . " 
				WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future', 'inherit')
				ORDER BY menu_order, post_date DESC",
			$post_type
		);
		$results     = $wpdb->get_results( $mysql_query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! is_array( $results ) || count( $results ) < 1 ) {
			die();
		}

		// create the list of ID's.
		$objects_ids = array();
		foreach ( $results    as  $result ) {
			$objects_ids[] = (int) $result->ID;
		}

		if ( 'attachment' === strval( $post_type ) ) {
			$objects_per_page = get_user_meta( $userdata->ID, 'upload_per_page', true );
		} else {
			$objects_per_page = get_user_meta( $userdata->ID, 'edit_' . $post_type . '_per_page', true );
		}
		$objects_per_page = apply_filters( "edit_{$post_type}_per_page", $objects_per_page );
		if ( empty( $objects_per_page ) ) {
			$objects_per_page = 20;
		}

		$edit_start_at = $paged * $objects_per_page - $objects_per_page;
		$index         = 0;
		for ( $i  = $edit_start_at; $i < ( $edit_start_at + $objects_per_page ); $i++ ) {
			if ( ! isset( $objects_ids[ $i ] ) ) {
				break;
			}

			$objects_ids[ $i ] = (int) $data['post'][ $index ];
			++$index;
		}

		// update the menu_order within database.
		foreach ( $objects_ids as $menu_order   => $id ) {
			$data = array(
				'menu_order' => $menu_order,
			);

			// Deprecated, rely on pto/save-ajax-order.
			$data = apply_filters( 'post_types_order_save_ajax_order', $data, $menu_order, $id );

			$data = apply_filters( 'pto_save_ajax_order', $data, $menu_order, $id );

			$wpdb->update( $wpdb->posts, $data, array( 'ID' => $id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

			clean_post_cache( $id );
		}

		// trigger action completed.
		do_action( 'pto_order_update_complete' );

		wp_cache_flush();
	}


	/**
	 * List pages
	 *
	 * @return void
	 */
	public function add_menu() {
		global $userdata;
		// put a menu for all custom_type.
		$post_types = get_post_types();

		$options = $this->functions->get_options();
		// get the required user capability.
		$capability = '';
		if ( isset( $options['capability'] ) && ! empty( $options['capability'] ) ) {
			$capability = $options['capability'];
		} elseif ( is_numeric( $options['level'] ) ) {
			$capability = $this->functions->userdata_get_user_level();
		} else {
			$capability = 'manage_options';
		}

		foreach ( $post_types as $post_type_name ) {
			if ( 'page' === strval( $post_type_name ) ) {
				continue;
			}

			// ignore bbpress.
			if ( 'reply' === strval( $post_type_name ) || 'topic' === strval( $post_type_name ) ) {
				continue;
			}

			if ( is_post_type_hierarchical( $post_type_name ) ) {
				continue;
			}

			$post_type_data = get_post_type_object( $post_type_name );
			if ( false === boolval( $post_type_data->show_ui ) ) {
				continue;
			}

			if ( isset( $options['show_reorder_interfaces'][ $post_type_name ] ) && 'show' !== strval( $options['show_reorder_interfaces'][ $post_type_name ] ) ) {
				continue;
			}

			$required_capability = apply_filters( 'pto_edit_capability', $capability, $post_type_name );

			if ( 'post' === strval( $post_type_name ) ) {
				$hook_id = add_submenu_page( 'edit.php', __( 'Re-Order', 'post-types-order-by-firework' ), __( 'Re-Order', 'post-types-order-by-firework' ), $required_capability, 'order-post-types-' . $post_type_name, array( &$this, 'sort_page' ) );
			} elseif ( 'attachment' === strval( $post_type_name ) ) {
				$hook_id = add_submenu_page( 'upload.php', __( 'Re-Order', 'post-types-order-by-firework' ), __( 'Re-Order', 'post-types-order-by-firework' ), $required_capability, 'order-post-types-' . $post_type_name, array( &$this, 'sort_page' ) );
			} else {
				$hook_id = add_submenu_page( 'edit.php?post_type=' . $post_type_name, __( 'Re-Order', 'post-types-order-by-firework' ), __( 'Re-Order', 'post-types-order-by-firework' ), $required_capability, 'order-post-types-' . $post_type_name, array( &$this, 'sort_page' ) );
			}

			add_action( 'admin_print_styles-' . $hook_id, array( $this, 'admin_reorder_print_styles' ) );
		}
	}


	/**
	 * Admin reorder print styles
	 *
	 * @return void
	 */
	public function admin_reorder_print_styles() {

		if ( null !== $this->current_post_type ) {
			wp_enqueue_script( 'jQuery' );
			wp_enqueue_script( 'jquery-ui-sortable' );
		}

		wp_register_style( 'CPTStyleSheets', CPTURL . '/css/cpt.css', array(), '1.0.0', 'all' );
		wp_enqueue_style( 'CPTStyleSheets' );
	}


	/**
	 * Sort page
	 *
	 * @return void
	 */
	public function sort_page() {
		?>
		<div id="cpto" class="wrap">
			<div class="icon32" id="icon-edit"><br></div>
			<h2>
				<?php echo esc_html( $this->current_post_type->labels->singular_name . ' -  ' . esc_html__( 'Re-Order', 'post-types-order-by-firework' ) ); ?>
			</h2>

			<?php $this->functions->cpt_info_box(); ?>

			<div id="ajax-response"></div>

			<noscript>
				<div class="error message">
					<p>
						<?php esc_html_e( 'This plugin can\'t work without javascript, because it\'s use drag and drop and AJAX.', 'post-types-order-by-firework' ); ?>
					</p>
				</div>
			</noscript>

			<div id="order-post-type">
				<ul id="sortable">
					<?php $this->list_pages( 'hide_empty=0&title_li=&post_type=' . $this->current_post_type->name ); ?>
				</ul>

				<div class="clear"></div>
			</div>

			<p class="submit">
				<a href="javascript: void(0)" id="save-order" class="button-primary">
					<?php esc_html_e( 'Update', 'post-types-order-by-firework' ); ?>
				</a>
			</p>

			<?php wp_nonce_field( 'interface_sort_nonce', 'interface_sort_nonce' ); ?>

			<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery("#sortable").sortable({
						'tolerance': 'intersect',
						'cursor': 'pointer',
						'items': 'li',
						'placeholder': 'placeholder',
						'nested': 'ul'
					});

					jQuery("#sortable").disableSelection();
					jQuery("#save-order").bind("click", function () {

						jQuery("html, body").animate({ scrollTop: 0 }, "fast");

						jQuery.post(ajaxurl, { action: 'update-custom-type-order', order: jQuery("#sortable").sortable("serialize"), 'interface_sort_nonce': jQuery('#interface_sort_nonce').val() }, function () {
							jQuery("#ajax-response").html('<div class="message updated fade"><p><?php esc_html_e( 'Items Order Updated', 'post - types - order' ); ?></p></div>');
							jQuery("#ajax-response div").delay(3000).hide("slow");
						});
					});
				});
			</script>

		</div>
		<?php
	}

	/**
	 * List pages function
	 *
	 * @param mixed $args args.
	 *
	 * @return void
	 */
	public function list_pages( $args = '' ) {
		$defaults = array(
			'depth'       => -1,
			'date_format' => get_option( 'date_format' ),
			'child_of'    => 0,
			'sort_column' => 'menu_order',
			'post_status' => 'any',
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP ); // phpcs:ignore

		$output = '';

		$r['exclude'] = implode( ',', apply_filters( 'wp_list_pages_excludes', array() ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude

		// Query pages.
		$r['hierarchical'] = 0;
		$args              = array(
			'sort_column'    => 'menu_order',
			'post_type'      => $post_type, // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- post type is defined in the parent function.
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'orderby'        => array(
				'menu_order' => 'ASC',
				'post_date'  => 'DESC',
			),
		);

		$the_query = new WP_Query( $args );
		$pages     = $the_query->posts;

		if ( ! empty( $pages ) ) {
			$output .= $this->walk_tree( $pages, $r['depth'], $r );
		}

		echo wp_kses_post( $output );
	}

	/**
	 * Walk tree function
	 *
	 * @param mixed $pages pages.
	 * @param mixed $depth depth.
	 * @param mixed $r     r.
	 *
	 * @return mixed
	 */
	public function walk_tree( $pages, $depth, $r ) {
		$walker = new Post_Types_Order_Walker();

		$args = array( $pages, $depth, $r );
		return call_user_func_array( array( &$walker, 'walk' ), $args );
	}
}




?>