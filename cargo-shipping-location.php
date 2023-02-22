<?php
 /**
 * Plugin Name: Cargo Shipping Location for WooCommerce
 * Plugin URI: https://api.cargo.co.il/Webservice/pluginInstruction
 * Description: Location Selection for Shipping Method for WooCommerce
 * Version: 1.0.9.1
 * Author: WebSolution
 * Author URI: http://websolutions.co.il/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: astra-woo-cargo
 */

if ( !defined( 'ABSPATH' ) ) {
    die;
}

if ( !defined( 'AWSB_URL' ) ) {
    define( 'AWSB_URL', plugins_url( '/', __FILE__ ) );
}

if ( !defined( 'AWSB_PATH' ) ) {
    define( 'AWSB_PATH', plugin_dir_path( __FILE__ ) );
}
if( !defined('MAIN_FILE_PATH')) {
	define( 'MAIN_FILE_PATH', plugin_dir_path(__FILE__) );
}

if( !class_exists('Awsb_Shipping') ) {
    class Awsb_Shipping {
        
        function __construct() {
            add_action( 'admin_init', array($this, 'awsb_shipping_api_settings_init') );

            if ( get_option('shipping_cargo_express') != '' ) {
                add_action( 'woocommerce_shipping_init', array($this,'awsb_shipping_method') );

                add_filter( 'woocommerce_shipping_methods', array($this,'awsb_add_Baldarp_shipping_method') );
                add_action( 'woocommerce_after_shipping_rate', array($this, 'awsb_after_shipping_rate'), 20, 2) ;
                add_action('woocommerce_checkout_update_order_meta', array($this,'custom_checkout_field_update_order_meta'));
                add_action( 'woocommerce_checkout_process',array($this,'action_woocommerce_checkout_process'),10,1);
                add_action('woocommerce_order_status_changed',array($this,'cargo_status_change_event'),10,3);
                add_filter( 'handle_bulk_actions-edit-shop_order', array($this,'bulk_order_cargo_shipment'), 10, 3);
				add_action( 'admin_notices',array($this,'cargo_bulk_action_admin_notice') );
               //add_action('woocommerce_thankyou', array($this,'transfer_order_data_for_shipment'), 10, 1); 
               add_action('woocommerce_checkout_order_processed', array($this,'transfer_order_data_for_shipment'), 10, 1); 
               add_filter( 'manage_edit-shop_order_columns', array($this,'add_order_delivery_status_column_header'), 20 ); 
               add_action( 'manage_shop_order_posts_custom_column', array($this,'add_custom_column_content') );
               add_filter( 'post_class', array($this,'add_no_link_to_post_class') );
              // add_action( 'admin_print_styles', array($this,'add_order_notes_column_style') );
               add_action( 'woocommerce_admin_order_data_after_billing_address', array($this,'show_shipping_info' ));
               add_filter( 'woocommerce_locate_template',array($this,'intercept_wc_template'), 10, 3 );
               add_action( 'init', array($this,'register_order_status_for_cargo'));
               add_filter( 'wc_order_statuses', array($this,'custom_order_status'));
               add_filter( 'bulk_actions-edit-shop_order', array($this,'custom_dropdown_bulk_actions_shop_order'), 20, 1 );
               add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
               add_action( 'add_meta_boxes', array( $this, 'remove_shop_order_meta_boxe' ), 90  );
               add_filter('the_content', array( $this,'replace_text'));
               add_action('admin_head', array( $this,'custom_changes_css'));
              // add_filter( 'woocommerce_my_account_my_orders_actions',array($this,'my_code_add_myaccount_order_track_button'), 10, 2 );
               add_filter( 'woocommerce_account_orders_columns', array($this,'add_account_orders_column'), 10, 1 );
               add_action( 'woocommerce_my_account_my_orders_column_order-track', array($this,'add_account_orders_column_rows') );
               add_action('wp_footer',array($this,'add_model_footer'));
			  // add_filter( 'woocommerce_package_rates', array($this,'change_shipping_methods_label_names'), 10, 2 );
            }

            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($this,  'misha_settings_link' ));
            add_action( 'admin_enqueue_scripts', array($this,'awsb_admin_plugin_scripts') );
            add_action( 'wp_enqueue_scripts', array($this,'awsb_plugin_scripts') );

            add_action( 'wp_ajax_get_order_tracking_details', array($this,'get_order_tracking_details') );
            add_action( 'wp_ajax_getOrderStatus', array($this,'getOrderStatusFromCargo') );
            add_action( 'wp_ajax_nopriv_getOrderStatus', array($this,'getOrderStatusFromCargo' ) );
            add_action( 'wp_ajax_get_delivery_location', array($this,'awsb_ajax_delivery_location') );
            add_action( 'wp_ajax_nopriv_get_delivery_location', array($this,'awsb_ajax_delivery_location' ) );

            add_action('wp_ajax_sendOrderCARGO', array($this,'send_order_to_cargo') );
            add_action('wp_ajax_get_shipment_label', array($this,'get_shipment_label') );

            add_action( 'wp_head', array($this, 'awsb_script_checkout') );
            
            if ( is_admin() ) {
                register_activation_hook(__FILE__, array($this,'activate'));
                
                register_deactivation_hook(__FILE__, array($this,'awsb_deactivate'));
                // plugin uninstallation
                register_uninstall_hook(__FILE__, 'awsb_uninstall');
            }
        }

        /**
        * Custom CSS
        */
        function custom_changes_css() {
	      	echo '<style>
		        a.edit-address-cargo::after {
				    font-family: Dashicons;
				    content: "\f464";
				}
		      </style>';
	    }

	    /**
        * Add Bulk Action in admin panel.
        */
		function cargo_bulk_action_admin_notice() {
            global $pagenow;

            if ( 'edit.php' === $pagenow
                && isset($_GET['post_type'])
                && 'shop_order' === $_GET['post_type']
                && isset($_GET['cargo_send']) ) {
        
                $count = intval( $_REQUEST['processed_count'] );

				if( isset($_REQUEST['processed_ids']) ){
					$order_id_array = explode(",", $_REQUEST['processed_ids'] );

					if( $_REQUEST['old_stutus'] != ""
					    && $_REQUEST['is_cargo'] == 1
					    && $_REQUEST['new_status'] == 'send-cargo'
					    && get_option('disable_order_status') ) {
						foreach( $order_id_array as $key => $order_id){
							$order = wc_get_order( $order_id );
							$order->update_status( $_REQUEST['old_stutus']); // 
						}	
					}
				}
                printf( '<div class="notice notice-success fade is-dismissible"><p>' .
                    _n( '%s Order Sent for Shipment',
                    '%s Orders Sent For Shipment',
                    $count,
                    'woocommerce'
                ) . '</p></div>', $count );
            }
        }

        /**
        * @param $my_text
        * @return mixed
        */
	    function replace_text( $my_text ){
		  return $my_text;
		}

	    function get_order_tracking_details() {
	    	$data = array();
	    	$data['name'] = "Test Name";
	    	$data['orderId'] = $_POST['orderID'];
	    	include( plugin_dir_path( __FILE__ ). 'templates/my-account/order-on-way.php');
			
			die();
	    }

	    function add_model_footer() {
	    	if( is_account_page() ) { ?>
	    		<div class="modal order-tracking-model" tabindex="-1" role="dialog" >
	                <div class="modal-dialog" role="document" style="max-width: 1000px; width: 100%;">
	                    <div class="modal-content">
	                        <div class="modal-header">
	                           <img src="<?php echo site_url();?>/wp-content/uploads/2022/07/cropped-logo_blue.png" alt="Cargo" width="100">
	                            <h5 class="modal-title"><?= __('Order Tracking', 'cargo') ?></h5>
	                            <button type="button" class="close" id="modal-close" data-dismiss="modal" aria-label="Close">
	                            <span aria-hidden="true">&times;</span>
	                            </button>
	                        </div>
	                        <div class="modal-body order-details-ajax">
	                            <?php require_once AWSB_PATH . 'templates/my-account/order-on-way.php'; ?>
	                        </div>
	                        <div class="modal-footer" style="display: block;">
                                <div id="FlyingCargo_footer" style="display: none;"><?= __('נקודת איסוף מסומנת:', 'cargo') ?><div id="FlyingCargo_loc_name"></div>
                                <button type="button" class="selected-location" id="FlyingCargo_confirm" data-lat="" data-long="" data-fullAdd="" data-disctiPointID="" data-pointName="" data-city="" data-street="" data-streetNum="" data-comment="" data-locationName=""><?= __('בחירה וסיום', 'cargo') ?></button>
	                        </div>
	                        </div>
	                    </div>
	                </div>
            	</div>
	    		<?php 
	    	}
	    }

	    /**
        * @param $columns
        * @return mixed
        *
        * Add New column Track order in the my account order tab
        */
	    function add_account_orders_column( $columns ){
		    $columns['order-track'] = __( 'Track order', 'woocommerce' );

		    return $columns;
		}

		/**
        * @param $order
         *
         * Add Track order link in my account page in order tab
        */
		function add_account_orders_column_rows( $order ) {
			$order_id = $order->get_id();
			$cargo_delivery_id = get_post_meta( $order_id, 'cargo_shipping_id', true );
			$shippingMethod = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];
        	$customer_id = $shipping_method_id == 'woo-baldarp-pickup' ? '3175' : '2808';
			if( $cargo_delivery_id ) {
				echo '<a href="javascript:void(0);" class="btn woocommerce-button tack-order-cus" data-delivery="' . $cargo_delivery_id . '" data-customer="' . $customer_id . '" data-id="' . $order_id . '">' . __('Track Order', 'cargo') . '</a>';
			}
		}

	    /**
		* Send Order to CARGO (Baldar) For Shipping Process.
		*
		* @param string $_POST data
		*
		* @return Return Success Msg and store Cargo Shipping ID in Meta.
		*/
	    function send_order_to_cargo() {

			if( trim( get_option('from_street') ) == ''
                || trim( get_option('from_street_name') ) == ''
                || trim( get_option('from_city') ) == ''
                || trim( get_option('phonenumber_from') ) == ''
                || trim( get_option('shipping_cargo_express') ) == ''
                || trim( get_option('shipping_cargo_box') ) == '' ) {
				echo json_encode( array("shipmentId" => "", "error_msg" => __('Please enter all details from plugin setting', 'cargo') ) );
				exit;
			}

	    	$order_id   = $_POST['orderId'];
            $response = $this->createShipment($order_id);

            echo json_encode($response);
			exit();
	    }

	    /**
        * @param $msg
        *
        * Add Log for Order
        */
	    function add_log_message($msg) {
	    	$upload = wp_upload_dir();
 			$upload_dir = $upload['basedir'];
 			$upload_dir = $upload_dir . '/cargo-shipping-location';

			if (! is_dir($upload_dir)) {
				 mkdir( $upload_dir, 0700 );
			}
			$path = $upload_dir.'/order_log_' . date('Ymd') . '.txt';
	        if (!file_exists($path)) {
	            $file = fopen($path, 'w') or die("Can't create file");
	        }

	        file_put_contents($path, $msg, FILE_APPEND) or die('failed to put');
	    }

        /**
		* Filter the thankyou template path to use thankyou.php in this plugin instead of the one in WooCommerce.
		*
		* @param string $template      Default template file path.
		* @param string $template_name Template file slug.
		* @param string $template_path Template file name.
		*
		* @return string The new Template file path.
		*/
		function intercept_wc_template( $template, $template_name, $template_path ) {
			global $woocommerce;
			$_template = $template;
			if ( 'thankyou.php' === basename( $template ) ) {
			    if ( ! $template_path ) $template_path = $woocommerce->template_url;

			    $plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) )  . '/woocommerce/templates/';
		 
                // Look within passed path within the theme - this is priority
                $template = locate_template(
                    array(
                      $template_path . $template_name,
                      $template_name
                    )
                );

                if ( !$template && file_exists( $plugin_path . $template_name ) )
                    $template = $plugin_path . $template_name;

                if ( !$template )
                    $template = $_template;
            }
            return $template;

		}

		/**
        * Add order status for Cargo
        */
		function register_order_status_for_cargo() {
			register_post_status( 'wc-send-cargo', array(
		        'label'                     => 'Send to CARGO',
		        'public'                    => true,
		        'exclude_from_search'       => false,
		        'show_in_admin_all_list'    => true,
		        'show_in_admin_status_list' => true,
		        'label_count'               => _n_noop( 'Send to CARGO <span class="count">(%s)</span>', 'Send to CARGO <span class="count">(%s)</span>' )
		    ) );

		    register_post_status( 'wc-cancel-cargo', array(
		        'label'                     => 'Cancel to Send CARGO',
		        'public'                    => true,
		        'exclude_from_search'       => false,
		        'show_in_admin_all_list'    => true,
		        'show_in_admin_status_list' => true,
		        'label_count'               => _n_noop( 'Cancel to Send CARGO <span class="count">(%s)</span>', 'Cancel to Send CARGO <span class="count">(%s)</span>' )
		    ) );
		}

		/**
        * @param $post_type
        *
        * Add Meta box in admin order
        */
		public function add_meta_box( $post_type ) {
			global $post, $pagenow, $typenow;
			if( ('edit.php' === $pagenow || 'post.php' === $pagenow) && 'shop_order' === $typenow && is_admin() ) {
				$order = wc_get_order($post->ID);
				$shippingMethod = @array_shift($order->get_shipping_methods() );
				$shipping_method_id = $shippingMethod['method_id'];

				if ( $shipping_method_id == 'cargo-express' || $shipping_method_id == 'woo-baldarp-pickup' || get_option('send_to_cargo_all')) {
					add_meta_box(
						'cargo_custom_box',
						__( '<img src="'.AWSB_URL."assets/image/howitworks.png".'" alt="Cargo" width="100" style="width:50px;">CARGO', 'cargo' ),
						array( $this, 'render_meta_box_content' ),
						'shop_order',
						'side',
						'core'
					);
				}
			}
	    }

	    /**
        * remove meta box from the admin order page
        */
	    public function remove_shop_order_meta_boxe() {
		    remove_meta_box( 'postcustom', 'shop_order', 'normal' );
		}

	    public function render_meta_box_content( $post ) {
	        // Use get_post_meta to retrieve an existing value from the database.
	        $value = get_post_meta( $post->ID, 'cargo_shipping_id', true );
	 		$order = wc_get_order($post->ID);
		    $shippingMethod = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];

			if( $shipping_method_id == 'cargo-express' || $shipping_method_id == 'woo-baldarp-pickup' || get_option('send_to_cargo_all')) {
				?>
				<label for="myplugin_new_field">
					<a href="javascript:void(0);" class="edit-address-cargo"><?php _e( 'בקש סטטוס משלוח', 'cargo' ); ?></a>
				</label>

				<div class="cargo-button">
                    <?php if ( !$value ) : ?>
					<input type="button"
                            class="submit-cargo-shipping"
                            value="שלח ל CARGO"
                            data-id="<?php echo $post->ID; ?>">
                    <?php endif; ?>
				</div>
				<div class="cargo-button">
				<?php if( $value ) { ?>
					<input type="button" class="label-cargo-shipping" value="הדפס תווית" data-id="<?= $value; ?>">
				<?php } ?>
				</div>
				<div class="checkstatus-section">
					<?php
                        $customerCode       = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
                        $type               = $shipping_method_id == 'woo-baldarp-pickup' ? "BOX" : "EXPRESS";
                        if ( get_post_meta($post->ID,'cargo_shipping_id',true) ) {
                            echo "<a href='javascript:void(0);' class='btn btn-success send-status' data-orderlist='0' data-id=".$post->ID." data-customerCode=".$customerCode." data-type=".$type." data-deliveryId=".get_post_meta($post->ID,'cargo_shipping_id',true).">בקש סטטוס משלוח</a>";
                        }
					?>
				</div>
				<?php if ( $shipping_method_id == 'woo-baldarp-pickup' ) { ?>
					<div>
						<h2><?= __('Cargo Store Details', 'cargo') ?></h2>
						<h3 style="margin:0;">
						    <strong><?= get_post_meta($post->ID,'DistributionPointName',TRUE) ?></strong>
						</h3>
						<h4 style="margin:0;"><?= get_post_meta($post->ID,'StreetNum', TRUE).' '.get_post_meta($post->ID,'StreetName',TRUE).' '.get_post_meta($post->ID,'CityName',TRUE) ?></h4>
						<h4 style="margin:0;"><?= get_post_meta($post->ID,'store_comment', TRUE) ?></h4>
						<h3 style="margin:0;"><?= get_post_meta($post->ID,'cargoPhone',TRUE) ?></h3>
					</div>
				<?php } 
			}
	    }

        /**
         * @param $order_statuses
         * @return mixed
         *
         * Add order status in Array
         */
		function custom_order_status( $order_statuses ) {
		    $order_statuses['wc-send-cargo'] = _x( 'Send to CARGO', 'Order status', 'cargo' );
		    $order_statuses['wc-cancel-cargo'] = _x( 'Cancel to Send CARGO', 'Order status', 'cargo' );
		    return $order_statuses;
		}

        /**
         * @param $actions
         * @return array
         *
         * Add Order Status to Admin Order list
         */
		function custom_dropdown_bulk_actions_shop_order($actions ){
			 $new_actions = array();

    		// Add new custom order status after processing
		    foreach ($actions as $key => $action) {
		        $new_actions[$key] = $action;

		        if ('mark_processing' === $key) {
		            $new_actions['mark_send-cargo'] = __( 'Send to CARGO', 'cargo' );
		            $new_actions['mark_cancel-cargo'] = __( 'Cancel to Send CARGO', 'cargo' );
		        }
		    }

		    return $new_actions;
		}

        /**
         *
         */
		function get_shipment_label() {
			$options = [
				'body'        => wp_json_encode( array( 'deliveryId' => (int) $_POST['shipmentId'] ) ),
				'headers'     => [
					'Content-Type' => 'application/json',
				],
				'timeout'     => 60,
				'redirection' => 5,
				'blocking'    => true,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'data_format' => 'body',
			];
			$response = wp_remote_post( "https://api.carg0.co.il/Webservice/generateShipmentLabel",  $options);
			$arrData = wp_remote_retrieve_body($response) or die("Error: Cannot create object");
			$data = (array) json_decode($arrData);
			if ( $data['error_msg']  == '') {
				echo json_encode(array("error_msg" => "","pdfLink" => $data['pdfLink'] ));
			} else {
				echo json_encode(array("error_msg" => "יצירת התווית נכשלה","pdfLink" => ''));
			}
			exit;
		}

		/**
		* Check the Shipping Setting from cargo 
		*
		* @param $_POST DATA 
		* @return int shipping Status
		*/
		function getOrderStatusFromCargo() {
			$post_data = array(
                'deliveryId' => (int) $_POST['deliveryId'],
                'DeliveryType' => $_POST['type'],
                'customerCode' => $_POST['customerCode'],
            );
			
			$options = [
			    'body'        => wp_json_encode( $post_data ),
			    'headers'     => [
			        'Content-Type' => 'application/json',
			    ],
			    'timeout'     => 60,
			    'redirection' => 5,
			    'blocking'    => true,
			    'httpversion' => '1.0',
			    'sslverify'   => false,
			    'data_format' => 'body',
			];
			$response = wp_remote_post( "https://api.carg0.co.il/Webservice/CheckShipmentStatus",  $options);
            $arrData = wp_remote_retrieve_body($response) or die("Error: Cannot create object");
			$data = (array) json_decode($arrData);
			if ( $data['errorMsg']  == '' ) {
				if ( (int) $data['deliveryStatus'] > 0) {
					update_post_meta($_POST['orderId'], 'get_status_cargo', (int) $data['deliveryStatus']);
					update_post_meta($_POST['orderId'], 'get_status_cargo_text', $data['DeliveryStatusText']);
					echo json_encode(array( "type" => "success", "data" => $data['DeliveryStatusText'], "orderStatus" => (int)$data['deliveryStatus']));
				} else {
					echo json_encode(array("type" => "failed","data" => 'Not Getting Data'));
				}
			} else {
				echo json_encode(array("type" => "failed","data" => 'something went wrong'));
			}
			exit;
		}

		/*
		 * Show Shipping Info in Admin Edit Order
		 */
       	function show_shipping_info($order ) {
       		$cargo_shipping_id = $order->get_meta('cargo_shipping_id');
    
		    if ( ! empty($cargo_shipping_id) ) {
		        echo '<p><strong>'.__('מזהה משלוח', 'cargo').':</strong> ' . $cargo_shipping_id . '</p>';
		    }
       	}

        function add_no_link_to_post_class( $classes ) {
			//if ( current_user_can( 'manage_woocommerce' ) ) { //make sure we are shop managers 
		        foreach ( $classes as $class ) {
			        if( $class == 'delivery_status' ) {
			            $classes[] = 'no-link';
			        }
			        if( $class == 'no_of_package' ) {
			            $classes[] = 'no-link';
			        }
		    	}
		   // }
		    return $classes;
		}

        /**
         * @param $column
         * 
         * Add Custom Column in Admin Order List
         */
        function add_custom_column_content( $column ) {
		    global $post;
			$order              = wc_get_order($post->ID);
			$shippingMethod     = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];
			$customerCode = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');

			if ( $shipping_method_id == 'cargo-express' || $shipping_method_id == 'woo-baldarp-pickup' || get_option('send_to_cargo_all')) {
				if ( 'delivery_status' === $column ) {
					$type = $shipping_method_id == 'woo-baldarp-pickup' ? "BOX" : "EXPRESS";

					if ( get_post_meta($post->ID,'cargo_shipping_id',true) ) {
						echo "<a href='javascript:void(0);' class='btn btn-success send-status' data-orderlist='1' data-id=".$post->ID." data-customerCode=".$customerCode."  data-type=".$type." data-deliveryId=".get_post_meta($post->ID,'cargo_shipping_id',true)."> בדוק מצב הזמנה</a>";
					}
				}

				if ( 'send_to_cargo' === $column ) {
					if ( get_post_meta($post->ID,'cargo_shipping_id',true) ) {
						echo "<a href='javascript:void(0);' class='btn btn-success cancel-cargo-shipping' data-id=".$post->ID." data-customerCode=".$customerCode." data-deliveryId=".get_post_meta($post->ID,'cargo_shipping_id',true).">בטל משלוח</a>";
					} else {
						echo "<a href='javascript:void(0);' class='btn btn-success submit-cargo-shipping' data-id=".$post->ID." >שלח  לCARGO</a>";
					}
				}

				if ( 'cargo_delivery_id' === $column ) {
					if ( get_post_meta($post->ID,'cargo_shipping_id',true) ) {
						echo "<p>". get_post_meta($post->ID, 'cargo_shipping_id',true). "</p>";
						echo '<a  href="javascript:void(0)" class="btn btn-success label-cargo-shipping" data-id="'.get_post_meta($post->ID,'cargo_shipping_id',true).'">הדפס תווית</a>';
					}

				}

				if ( 'delivery_status' === $column ) {
					if ( get_post_meta($post->ID, 'get_status_cargo', true) ) {
						echo '<p>' . get_post_meta($post->ID, 'get_status_cargo_text', true) . '</p>';
					}
				}
			}
		}

        public function add_order_delivery_status_column_header($columns){

		    $new_columns = array();

		    foreach ( $columns as $column_name => $column_info ) {

		        $new_columns[ $column_name ] = $column_info;

		        if ( 'order_status' === $column_name ) {
		            $new_columns['delivery_status'] = __( 'בדוק מצב הזמנה', 'cargo' );
		            $new_columns['send_to_cargo'] = __( 'שלח משלוח לCARGO', 'cargo' );
		            $new_columns['cargo_delivery_id'] = __( 'מזהה משלוח', 'cargo' );
		            $new_columns['delivery_status'] = __( 'סטטוס משלוח', 'cargo' );
		        }
		    }

		    return $new_columns;
        }

        /**
         * @param $order_id
         *
         * Add Headlines in order list.
         */
        public function transfer_order_data_for_shipment($order_id ) {
        	if ( ! $order_id ) return;

        	$order = wc_get_order( $order_id );
        	$shippingMethod = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];

        	if ( $shipping_method_id === 'woo-baldarp-pickup' ) {
    			update_post_meta( $order_id, 'DistributionPointID', $_POST['DistributionPointID'] );
    			update_post_meta( $order_id, 'DistributionPointName', $_POST['DistributionPointName'] );
    			update_post_meta( $order_id, 'CityName', $_POST['CityName'] );
    			update_post_meta( $order_id, 'StreetName', $_POST['StreetName'] );
    			update_post_meta( $order_id, 'StreetNum', $_POST['StreetNum'] );
    			update_post_meta( $order_id, 'cargoPhone', $_POST['cargoPhone'] );
    			update_post_meta( $order_id, 'store_comment', $_POST['Comment'] );
        	}
        }

        /**
         * @param $redirect_to
         * @param $action
         * @param $ids
         *
         * Add bulk actions in order list
         */
        public function bulk_order_cargo_shipment($redirect_to, $action, $ids) {
			$is_cargo = 0;
			$old_status = "";
			$new_status = "";
            if ( false !== strpos( $action, 'mark_' ) ) {
                $new_status     = substr( $action, 5 ); // Get the status name from action.
                $report_action  = 'wc-' . $new_status;
                if ( 'wc-send-cargo' == $report_action || get_option('cargo_order_status') == $report_action ) {

                    foreach ($ids as $key => $order_id) {
						$this->createShipment($order_id);
                    }
                } else if ( 'wc-cancel-cargo' === $report_action ) {
                	return;
                }      
            }

			$redirect_to = add_query_arg(
                array(
                    'cargo_send'     => '1',
                    'old_stutus'     => $old_status,
                    'is_cargo'       => $is_cargo,
                    'new_status'     => $new_status,
                    'processed_count' => count( $ids ),
                    'processed_ids'  => implode( ',', $ids ),
                ),
                $redirect_to );
            return $redirect_to;
        }

        /**
         * @param $order_id
         * @param $old_status
         * @param $new_status
         *
         * Change Event
         */
        public function cargo_status_change_event($order_id, $old_status, $new_status)
        {
        	if ( !is_checkout() ) {
        		if ( 'wc-send-cargo' == 'wc-'.$new_status ) {
					if( trim(get_option('from_street')) != ''
                        && trim(get_option('from_street_name')) != ''
                        && trim(get_option('from_city')) != ''
                        && trim(get_option('phonenumber_from')) != ''
                        && trim(get_option('shipping_cargo_express')) != ''
                        && trim(get_option('shipping_cargo_box')) != '') {

						$this->createShipment($order_id);
					}

					$order = wc_get_order( $order_id );
					$old_status = $order->get_status();
					if ( get_option('disable_order_status') ){
						$order->update_status( $old_status);
					}
	            } else if ('wc-cancel-cargo' == 'wc-'.$new_status) {
                	return;
                } else {
					if ( get_option('cargo_order_status') == 'wc-'.$new_status ) {
						if ( get_post_meta($order_id, 'cargo_shipping_id', TRUE) ) {
                    		return;	
                    	}
					    $this->createShipment($order_id);
					}
				}
        	} else {
				$order = wc_get_order($order_id);
				if ( get_option('cargo_order_status') == "wc-".$order->get_status() ){
					if ( !get_post_meta($order_id, 'cargo_shipping_id', TRUE) ) {
						$this->createShipment($order_id);
					}
				}
			}
        }
        
		function createShipment($order_id) {
			$order = wc_get_order($order_id);
			if( get_post_meta($order_id,'cargo_shipping_id',TRUE) ){
				return;	
			}
			$orderData      = $order->get_data();
			$shippingMethod = @array_shift($order->get_shipping_methods());
			$shipping_method_id = $shippingMethod['method_id'];

			$CarrierID = $shipping_method_id == 'woo-baldarp-pickup' ? '2' : '1';
			$CarrierName = $shipping_method_id == 'woo-baldarp-pickup' ? 'B0X' : 'EXPRESS';
			$customerCode = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');

            $data['Method'] = "ship";

            $data['Params'] = array(
                'shipping_type'         => "1",
                'noOfParcel'            => "1",
                'TotalValue'            => $order->get_total(),
                'TransactionID'         => $order_id,
                'ContentDescription'    => "",
                'CashOnDeliveryType'    => 0,
                'CarrierName'           => "CARGO",
                'CarrierService'        => $CarrierName,
                'CarrierID'             => intval($CarrierID),
                'OrderID'               => $order_id,
                'PaymentMethod'         => $orderData['payment_method'],
                'Note'                  => $orderData['customer_note'],
                'customerCode'          => $customerCode,

                'to_address' => array(
                    'name'      => $orderData['shipping']['first_name'] . ' ' . $orderData['shipping']['last_name'],
                    'company'   => $orderData['shipping']['company'],
                    'street1'   => $orderData['shipping']['address_1'],
                    'street2'   => $orderData['shipping']['address_2'],
                    'city'      => $orderData['shipping']['city'],
                    'state'     => $orderData['shipping']['state'],
                    'zip'       => $orderData['shipping']['postcode'],
                    'country'   => $orderData['shipping']['country'],
                    'phone'     => $orderData['billing']['phone'],
                    'email'     => $orderData['billing']['email'],
                    'floor'     => get_post_meta($order_id, 'cargo_floor', TRUE),
                    'appartment' => get_post_meta($order_id, 'cargo_apartment', TRUE),
                ),

                'from_address' => array(
                    'name'      => $orderData['shipping']['first_name']. ' '. $orderData['shipping']['last_name'],
                    'company'   => get_option( 'website_name_cargo' ),
                    'street1'   => get_option('from_street'),
                    'street2'   => get_option('from_street_name'),
                    'city'      => get_option('from_city'),
                    'state'     => $orderData['shipping']['state'],
                    'zip'       => $orderData['shipping']['postcode'],
                    'country'   => $orderData['shipping']['country'],
                    'phone'     => get_option('phonenumber_from'),
                    'email'     => $orderData['shipping']['email'],
                )
            );

            if ( $shipping_method_id == 'woo-baldarp-pickup' ) {
                $data['Params']['to_address'] = array(
                    'name'      => $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'],
                    'company'   => get_option( 'website_name_cargo' ),
                    'street1'   => get_post_meta($order_id,'StreetNum',TRUE),
                    'street2'   => get_post_meta($order_id,'StreetName',TRUE),
                    'city'      => get_post_meta($order_id,'CityName',TRUE),
                    'state'     => "",
                    'zip'       => "",
                    'country'   => "",
                    'phone'     => $orderData['billing']['phone'],
                    'email'     => "",
                );

                $data['Params']['boxPointId'] = get_post_meta($order_id,'DistributionPointID',TRUE);
            }

            $response = $this->passDataToCargo( $data );
            $message = '==============================' . PHP_EOL;

            if ( $response['shipmentId'] != '' ) {
				update_post_meta( $order_id, 'get_status_cargo', 1 );
				update_post_meta( $order_id, 'get_status_cargo_text', "Open");
				$order->update_meta_data( 'cargo_shipping_id', $response['shipmentId']);
				$order->update_meta_data( 'customerCode', $customerCode);
				$order->update_meta_data( 'lineNumber', $response['linetext']);
				$order->update_meta_data( 'drivername', $response['drivername']);
				$order->save();
				$boxDetails = "";
				$boxName = "";

				if( $shipping_method_id == 'woo-baldarp-pickup' ) {
					$boxDetails = get_post_meta($order_id,'StreetNum',TRUE).' '.get_post_meta($order_id,'StreetName',TRUE).' '.get_post_meta($order_id,'CityName',TRUE).' '.get_post_meta($order_id,'store_comment',TRUE).' '.get_post_meta($order_id,'cargoPhone',TRUE);
					$boxName = get_post_meta($order_id,'DistributionPointName',TRUE);
				}

                $message .= "ORDER ID : $order_id | DELIVERY ID  : {$response['shipmentId']} | SEND ON CARGO BY : ".date('Y-m-d H:i:d')."SHIPMENT TYPE : $CarrierName | CUSTOMER CODE : $customerCode" . PHP_EOL;
                $message .= "CARGO BOX POINT NAME : $boxName | CARGO BOX ADDRESS : $boxDetails". PHP_EOL;
			}

            $message .= "Shipment Data : ".json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
            $message .= "Response : ".json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
            $this->add_log_message($message);
			return $response;
		}

        /**
         * @param array $data
         * @return array|int
         *
         * Pass data to cargo API
         */
        public function passDataToCargo($data = array()) {
            if ( !empty($data) ) {
            	$body = wp_json_encode( $data );
				$options = [
				    'body'        => $body,
				    'headers'     => [
				        'Content-Type' => 'application/json',
				    ],
				    'timeout'     => 60,
				    'redirection' => 5,
				    'blocking'    => true,
				    'httpversion' => '1.0',
				    'sslverify'   => false,
				    'data_format' => 'body',
				];
            	$response = wp_remote_post( 'https://api.carg0.co.il/Webservice/CreateShipment',  $options);
            	$arrData = wp_remote_retrieve_body($response) or die("Error: Cannot create object");

                $status  = json_decode($arrData);
               	return (array) $status;
            } else {
            	return 0;
            }
        }
        
        public function action_woocommerce_checkout_process( $wccs_custom_checkout_field_pro_process ) {
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            $chosen_method_id = explode(':', $chosen_shipping_methods[0]);
            $chosen_method_id = reset($chosen_method_id);

            if ( $chosen_method_id == 'woo-baldarp-pickup' ) {
                if ( $_POST['DistributionPointID'] == '' ) {
                    wc_add_notice( __( 'Please select Shipping Collection Points' ), 'error' );
                }
            }

        }

        public function awsb_admin_plugin_scripts() {
            $screen = get_current_screen(); 

            $screen_id    = $screen ? $screen->id : '';
            
            if($screen_id === 'toplevel_page_loaction_api_settings') {
                wp_enqueue_style( 'admin-baldarp-style', AWSB_URL .'assets/css/admin-baldarp-style.css');
            }
            wp_enqueue_style( 'admin-baldarp-styles', AWSB_URL .'assets/css/admin-baldarp-styles.css');
            	wp_enqueue_script( 'cargo-admin-script', AWSB_URL .'assets/js/admin/admin-baldarp-script.js', array(), '', true);
            	wp_localize_script( 'cargo-admin-script', 'admin_cargo_obj',
                array( 
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'ajax_nonce'    => wp_create_nonce( 'awsb_shipping_nonce' ),
					'path' => AWSB_URL,
                )
            );
        }

        /**
         * @param $order_id
         *
         * Update Order meta
         */
        public function custom_checkout_field_update_order_meta($order_id){
            $order = wc_get_order( $order_id );
            $shippingMethod = explode(':',$_POST['shipping_method'][0]);
            if( reset($shippingMethod) == 'woo-baldarp-pickup') {

                if ( $_POST['DistributionPointID'] ) {
                    $order->update_meta_data( 'cargo_DistributionPointID', $_POST['DistributionPointID']);
                }
                if ( $_POST['DistributionPointName'] ) {
                    $order->update_meta_data( 'cargo_DistributionPointName', $_POST['DistributionPointName']);
                }
                if ( $_POST['CityName'] ) {
                    $order->update_meta_data( 'cargo_CityName', $_POST['CityName']);
                }
                if( $_POST['StreetName'] ) {
                    $order->update_meta_data( 'cargo_StreetName', $_POST['StreetName']);
                }
                if ( $_POST['StreetNum'] ) {
                    $order->update_meta_data( 'cargo_StreetNum', $_POST['StreetNum']);
                }
                if ( $_POST['Comment'] ) {
                    $order->update_meta_data( 'cargo_Comment', $_POST['Comment'] );
                }
                if ( $_POST['Latitude'] ) {
                    $order->update_meta_data( 'cargo_Latitude', $_POST['Latitude']);
                }
                if ( $_POST['Longitude'] ) {
                    $order->update_meta_data( 'cargo_Longitude', $_POST['Longitude']);
                }
            }
        }

        public function awsb_plugin_scripts() {
			if ( is_cart() || is_checkout() ) {
				wp_enqueue_script( 'baldarp-script', AWSB_URL .'assets/js/baldarp-script.js', array(), '', true);
				wp_localize_script( 'baldarp-script', 'baldarp_obj',
					array( 
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'ajax_nonce'    => wp_create_nonce( 'awsb_shipping_nonce' ),
					)
				);
				wp_enqueue_script( 'baldarp-map-jquery', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyATriqvOSeSLdO-eVqCquY7dYlp6p2jAzU&language=he&libraries=places&v=weekly', null, null, true );
				wp_enqueue_style('badarp-front-css', AWSB_URL.'assets/css/front.css');
				
				if(get_option('bootstrap_enalble') == 1){
					wp_enqueue_script( 'baldarp-bootstrap-jquery',  AWSB_URL .'assets/js/boostrap_bundle.js', array(), '', false );
					wp_enqueue_style('badarp-bootstrap-css', AWSB_URL .'assets/css/boostrap_min.css');
				}
			}
        }

        public function awsb_script_checkout() { 
			if ( is_checkout() || is_cart() ){
			?>
            <input type="hidden" id="default_markers" value="<?php echo AWSB_URL.'assets/image/cargo-icon-svg.svg' ?>" >
        	<input type="hidden" id="selected_marker" value="<?php echo AWSB_URL.'assets/image/selected_new.png' ?>" >
            <div class="modal" id="mapmodelcargo" tabindex="-1" role="dialog" style="display:none;" style="z-index:9999999;">
                <div class="modal-dialog" role="document" style="max-width: 95%; width: 1000px;max-height: 100%;">
                    <div class="modal-content">
                        <div class="modal-header">
                           <img src="<?php echo AWSB_URL.'assets/image/howitworks.png'; ?>" alt="Cargo" width="100">
                            <div style="direction: rtl;">
                            	<a href="javascript:void(0);" class="open-how-it-works"><?= __('איך זה עובד ?', 'cargo') ?></a>
                            </div>
                            <div class="modal-search" style="direction: rtl;">
                            	<input id="search-input-cus" name="search-input-cus" type="text" placeholder="<?= __('חיפוש נקודת איסוף  ', 'cargo') ?>" value=""/>
	                            <div class="startup">
	                            	<ul class="startup-dropdown">
	                            		
	                            	</ul>
	                            </div>

                            </div>

                            <h5 class="modal-title"><?= __(' נקודות איסוף', 'cargo')?></h5>
                            
                            <button type="button" class="close" id="modal-close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div style="padding-top: 5px;text-align: center;"><strong style="color: #2579b5;"><?= __('לחצו על נקודת האיסוף כדי לבחור או חפשו בתיבת החיפוש מעלה', 'cargo') ?></strong></div>
                        <div class="modal-body">
                            <div id="map" style="width:auto;height:400px;" ></div>
                        </div>
                        <div class="modal-footer" style="display: block;">
                           <div id="FlyingCargo_footer" style="display: none;">
                               <span><?= __('נקודת איסוף מסומנת:', 'cargo') ?></span>
                               <div id="FlyingCargo_loc_name"></div>
                                <button type="button"
                                        class="selected-location"
                                        id="FlyingCargo_confirm"
                                        data-lat=""
                                        data-long=""
                                        data-fullAdd=""
                                        data-disctiPointID=""
                                        data-pointName=""
                                        data-city=""
                                        data-street=""
                                        data-streetNum=""
                                        data-comment="" data-locationName=""><?= __('בחירה וסיום', 'cargo') ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal descript" tabindex="-1" role="dialog" style="margin-top: 90px;display:none;" >
                <div class="modal-dialog" role="document" style="max-width: 700px; width: 100%;">
                    <div class="modal-content">
                        <div class="modal-header">
                           <img src="<?php echo AWSB_URL.'assets/image/howitworks.png'; ?>" alt="Cargo" width="100" style="height: 100px;">
                            <h5 class="modal-title"><?= __('CARGO BOX - איך זה עובד', 'cargo') ?></h5>
                            <button type="button" class="close" id="modal-close-desc" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="direction: rtl;">
                            <div><?= __(' CARGO BOX ', 'cargo') ?></div>
                            <div><?= __('נקודות החלוקה שלנו בפריסה ארצית לנוחיותכם,', 'cargo') ?></div>
                            <div><p><?= __('אוספים את החבילה בדרך הקלה והמהירה ביותר!', 'cargo') ?></p></div>
							<div><p><?= __('איסוף החבילה שלכם יתבצע בנקודת חלוקה הקרובה לביתכם או למקום עבודתכם, היכן שתבחרו, ללא המתנה לשליח, ללא צורך בזמינות, בצורה היעילה, הזולה והפשוטה ביותר', 'cargo') ?></p></div>
							<div><?= __('כמה פשוט? ככה פשוט-', 'cargo') ?></div>
							<div><?= __('בוחרים נקודת חלוקה שמתאימה לכם', 'cargo') ?></div>
							<div><?= __('כאשר החבילה שלכם מגיעה ליעד אתם מקבלים SMS ומייל ', 'cargo') ?></div>
							<div><?= __('ומגיעים לאסוף את החבילה ', 'cargo') ?></div>
                        </div>
                    </div>
                </div>
            </div>
           <?php } 
        }

        public function awsb_ajax_delivery_location() {
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            $result = array();

            // TODO need to remove credentials somehow.
            $data['userName'] = "Cargo";
            $data['password'] = "Crg2468";
            $data['APICode'] = 924568;

            $body = json_encode( $data );
            $options = [
                'body'        => $body,
                'headers'     => [
                    'Content-Type' => 'application/json',
                ],
                'timeout'     => 60,
                'redirection' => 5,
                'blocking'    => true,
                'httpversion' => '1.0',
                'sslverify'   => false,
                'data_format' => 'body',
            ];

            $response = wp_remote_post( 'https://api.carg0.co.il/Webservice/getPickUpPoints', $options );
            $arrData = wp_remote_retrieve_body($response) or die("Error: Cannot create object");

            if( !is_wp_error($arrData) ) {

                $results = json_decode($arrData);

                $point = !empty($results->PointsDetails) ? $results->PointsDetails : '';

                $result["info"] = "Everything is fine.";
                $result["data"] = 1;
                $result["dataval"] = json_encode($point);
                $result['shippingMethod'] = $chosen_shipping_methods[0];
            } else {
                $result["info"] = $arrData->get_error_message();
                $result["data"] = 0;
            }
            
            echo json_encode($result);
            wp_die();
        }

        public function awsb_after_shipping_rate( $method, $index ) {
            if( is_cart() ) { return; }
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods')[ $index ];
            $chosen_method_id = explode(':', $chosen_shipping_methods);
            $chosen_method_id = reset($chosen_method_id);
            $chosen_method_ids = explode(':', $method->id);
            $chosen_method_ids = reset($chosen_method_ids);
            if ( $chosen_method_id != 'woo-baldarp-pickup' ) {
                return;
            } else {
                if( $chosen_method_ids == 'woo-baldarp-pickup') {
                    $pointName  = $_COOKIE['cargoPointName'] ?? '';
                    $pointId    = $_COOKIE['cargoPointID'] ?? '';
                    $city       = $_COOKIE['CargoCityName'] ?? '';
                    $street     = $_COOKIE['cargoStreetName'] ?? '';
                    $streetNum  = $_COOKIE['cargoStreetNum'] ?? '';
                    $comment    = $_COOKIE['cargoComment'] ?? '';
                    $latitude   = $_COOKIE['cargoLatitude'] ?? '';
                    $longitude  = $_COOKIE['cargoLongitude'] ?? '';
                    $phone      = $_COOKIE['cargoPhone'] ?? '';

                    ?>
                        <span class='baldrap-btn' id='mapbutton'><?= __(' בחירת נקודה', 'cargo') ?></span>
                        <div id='selected_cargo'></div>
                        <input type='hidden' id='DistributionPointID' name='DistributionPointID' value='<?= $pointId ?>' >
                        <input type='hidden' id='DistributionPointName' name='DistributionPointName' value='<?= $pointName ?>'>
                        <input type='hidden' id='CityName' name='CityName' value='<?= $city ?>'>
                        <input type='hidden' id='StreetName' name='StreetName' value='<?= $street ?>'>
                        <input type='hidden' id='StreetNum' name='StreetNum' value='<?= $streetNum ?>'>
                        <input type='hidden' id='Comment' name='Comment' value='<?= $comment ?>' >
                        <input type='hidden' id='cargoPhone' name='cargoPhone' value='<?= $phone ?>' >
                        <input type='hidden' id='Latitude' name='Latitude' value='<?= $latitude ?>' >
                        <input type='hidden' id='Longitude' name='Longitude' value='<?= $longitude ?>'>
                     <?php
                } 
            }
        }
        
        public function awsb_shipping_method() {
            require_once AWSB_PATH . 'includes/woo-baldarp-shipping.php';
			require_once AWSB_PATH . 'includes/woo-baldarp-express-shipping.php';
        }
        
        public function awsb_add_Baldarp_shipping_method( $methods ) {
            $methods['woo-baldarp-pickup'] = 'Baldarp_Shipping_Method';
			$methods['cargo-express'] = 'Cargo_Express_Shipping_Method';
            return $methods;
        }

        public function awsb_shipping_api_settings_init() {
            register_setting('awsb_shipping_api_settings_fg', 'shipping_api_username');
            register_setting('awsb_shipping_api_settings_fg', 'shipping_api_pwd');
            register_setting('awsb_shipping_api_settings_fg', 'shipping_api_int1');
            register_setting('awsb_shipping_api_settings_fg', 'cargo_order_status');
            register_setting('awsb_shipping_api_settings_fg', 'cargo_consumer_key');
            register_setting('awsb_shipping_api_settings_fg', 'cargo_consumer_secret_key');
            register_setting('awsb_shipping_api_settings_fg', 'cargo_google_api_key');
            register_setting('awsb_shipping_api_settings_fg', 'shipping_cargo_express');
            register_setting('awsb_shipping_api_settings_fg', 'shipping_cargo_box');
            register_setting('awsb_shipping_api_settings_fg', 'from_street');
            register_setting('awsb_shipping_api_settings_fg', 'from_street_name');
            register_setting('awsb_shipping_api_settings_fg', 'from_city');
            register_setting('awsb_shipping_api_settings_fg', 'phonenumber_from');
            register_setting('awsb_shipping_api_settings_fg', 'website_name_cargo');
			register_setting('awsb_shipping_api_settings_fg', 'bootstrap_enalble');
			register_setting('awsb_shipping_api_settings_fg', 'send_to_cargo_all');
			register_setting('awsb_shipping_api_settings_fg', 'disable_order_status');
        }
		
        public function misha_settings_link( $links_array ) {          
            array_unshift( $links_array, '<a href="' . admin_url( 'admin.php?page=loaction_api_settings' ) . '">' . __('Settings') . '</a>' );
            return $links_array;
        }

        public function activate() {
			if( class_exists( 'Awsb_Express_Shipping' ) ) {
				error_log( 'You can Only use only one Plugin Cargo Shipping Location Or Cargo Express Shipping Location' );      
				$args = var_export( func_get_args(), true );
				error_log( $args );
				wp_die( 'You can Only use only one Plugin Cargo Shipping Location Or Cargo Express Shipping Location' );
			} 
            //flush permalinks
			flush_rewrite_rules();
        }
        public function awsb_deactivate() {
			//flush permalinks
			flush_rewrite_rules();
		}
        
        public function awsb_uninstall() {
            //flush permalinks
			flush_rewrite_rules();
            delete_option('shipping_api_username');
            delete_option('shipping_api_pwd');
            delete_option('shipping_api_int1');
            delete_option('cargo_order_status');
            delete_option('cargo_consumer_key');
            delete_option('cargo_consumer_secret_key');
            delete_option('cargo_google_api_key');
            delete_option('shipping_cargo_express');
            delete_option('shipping_cargo_box');
            delete_option('website_name_cargo');
			delete_option('bootstrap_enalble');
			delete_option('send_to_cargo_all');
			delete_option('disable_order_status');
			
        }

        public function InitPlugin(){
            add_action('admin_menu', array($this, 'PluginMenu'));
        }
        
        public function PluginMenu(){
            add_menu_page('Cargo Shipping Location', 'Cargo Shipping Location', 'manage_options', 'loaction_api_settings', array($this, 'settings'),plugin_dir_url( __FILE__ ) . 'assets/image/cargo-icon-with-white-bg-svg.svg');
            add_submenu_page('loaction_api_settings', 'LogFiles', 'LogFiles', 'manage_options', 'cargo_shipping_log', array($this, 'logs'));
        }
        
        public function RenderPage(){
            $this->checkWooCommerce(); ?>
            <div class='wrap'>
                <h2><?= __('Shipping Location API - Dashboard', 'cargo') ?></h2>
            </div>
            <?php
        }
        public function logs(){
			$this->checkWooCommerce();
			$this->loadTemplate('logs');
		}


        public function settings(){
            $this->checkWooCommerce();
            $this->loadTemplate('settings');
        }
        
        public function checkWooCommerce(){
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if (! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                echo '<div class="error"><p><strong>Cargo Shipping Location API requires WooCommerce to be installed and active. You can download <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> here.</strong></p></div>';
                die();
            }
        }
        public function loadTemplate($templateName = ''){
            if($templateName != ''){
                require_once AWSB_PATH . 'templates/'.$templateName.'.php';
            }
        }
    }
}

$awsb_shipping = new Awsb_Shipping();
$awsb_shipping->InitPlugin();