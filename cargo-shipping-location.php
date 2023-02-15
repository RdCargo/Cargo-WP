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

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if ( ! defined( 'AWSB_URL' ) ) {
    define( 'AWSB_URL', plugins_url( '/', __FILE__ ) );
}

if ( ! defined( 'AWSB_PATH' ) ) {
    define( 'AWSB_PATH', plugin_dir_path( __FILE__ ) );
}
if(! defined('MAIN_FILE_PATH')) {
	define( 'MAIN_FILE_PATH', plugin_dir_path(__FILE__) );
}

if(!class_exists('Awsb_Shipping') ) {
    class Awsb_Shipping {
        
        function __construct() {

            add_action( 'admin_init', array($this, 'awsb_shipping_api_settings_init') );

            if(/*get_option('cargo_consumer_key') != '' && get_option('cargo_consumer_secret_key') != '' && */get_option('shipping_cargo_express') != '') {
                add_action( 'woocommerce_shipping_init', array($this,'awsb_shipping_method') );

                add_filter( 'woocommerce_shipping_methods', array($this,'awsb_add_Baldarp_shipping_method') );
                add_action( 'woocommerce_after_shipping_rate', array($this, 'awsb_after_shipping_rate'), 20, 2) ;
                add_action('woocommerce_checkout_update_order_meta', array($this,'custom_checkout_field_update_order_meta'));
                add_action( 'woocommerce_checkout_process',array($this,'action_woocommerce_checkout_process'),10,1);
                add_action('woocommerce_order_status_changed',array($this,'cargo_status_change_event'),10,3);
                add_filter( 'handle_bulk_actions-edit-shop_order', array($this,'bulk_order_cargo_shipmment'), 10, 3);
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

            add_action('wp_ajax_get_order_tracking_details', array($this,'get_order_tracking_details') );

            add_action('wp_ajax_getOrderStatus', array($this,'getOrderStatusFromCargo') );
            add_action( 'wp_ajax_nopriv_getOrderStatus', array($this,'getOrderStatusFromCargo' ));

            add_action('wp_ajax_get_delivery_location', array($this,'awsb_ajax_delivery_location') );
            add_action( 'wp_ajax_nopriv_get_delivery_location', array($this,'awsb_ajax_delivery_location' ));

            add_action('wp_ajax_sendOrderCARGO', array($this,'send_order_to_cargo') );
            add_action('wp_ajax_getShipmenlable', array($this,'getShipmenlable') );

            add_action( 'wp_head', array($this, 'awsb_script_checkout') );
            
            if ( is_admin() ) {
                register_activation_hook(__FILE__, array($this,'activate'));
                
                register_deactivation_hook(__FILE__, array($this,'awsb_deactivate'));
                // plugin uninstallation
                register_uninstall_hook(__FILE__, 'awsb_uninstall');
            }
        }
        function custom_changes_css() {
	      	echo '<style>
		        a.edit-address-cargo::after {
				    font-family: Dashicons;
				    content: "\f464";
				}
		      </style>';
			  //echo "<div class='loader-admin' style='display:none;'><img src='".AWSB_URL."assets/image/Wedges-3s-84px.svg' alt='Loading...' /></div>";
	    }
		function cargo_bulk_action_admin_notice(){
            global $pagenow;

            if ( 'edit.php' === $pagenow && isset($_GET['post_type']) 
            && 'shop_order' === $_GET['post_type'] && isset($_GET['cargo_send'])) {
        
                $count = intval( $_REQUEST['processed_count'] );
				if(isset($_REQUEST['processed_ids'])){
					$orderIdArr = explode(",",$_REQUEST['processed_ids']);
					if($_REQUEST['old_stutus'] != "" &&  $_REQUEST['is_cargo'] == 1 && $_REQUEST['new_status'] == 'send-cargo' && get_option('disable_order_status')){
						foreach($orderIdArr as $key => $orderId){
							$order = wc_get_order($orderId);
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
	    function replace_text($my_text){
	    // if(is_checkout()) {
		//   $my_text = str_replace('Flat rate', 'שילוח עד הבית CARGO Express', $my_text);
	    // }
		  return $my_text;
		}
		// function change_shipping_methods_label_names( $rates, $package ) {

		// 	foreach( $rates as $rate_key => $rate ) {
		// 		if ( 'cargo-express' == $rate->method_id )
		// 			$rates[$rate_key]->label = __( 'שילוח עד הבית CARGO Express', 'woocommerce' ); // New label name
				
		// 	}
		// 	return $rates;
		// }
	    function get_order_tracking_details(){
	    	$data = array();
	    	$data['name'] = "test Name ";
	    	$data['orderId'] = $_POST['orderID'];
	    	include( plugin_dir_path( __FILE__ ). 'templates/my-account/order-on-way.php');

			
			die();
	    }
	    function add_model_footer(){
	    	if(is_account_page()) {
	    		?>
	    		<div class="modal order-tracking-model" tabindex="-1" role="dialog" >
	                <div class="modal-dialog" role="document" style="max-width: 1000px !important;">
	                    <div class="modal-content">
	                        <div class="modal-header">
	                           <img src="<?php echo site_url();?>/wp-content/uploads/2022/07/cropped-logo_blue.png" alt="Cargo" width="100">
	                            <h5 class="modal-title">order traking</h5>
	                            <button type="button" class="close" id="modal-close" data-dismiss="modal" aria-label="Close">
	                            <span aria-hidden="true">&times;</span>
	                            </button>
	                        </div>
	                        <div class="modal-body order-details-ajax">
	                            <!--  <input id="pac-input" class="controls" type="text" placeholder="Search Box"> -->
	                            <?php require_once AWSB_PATH . 'templates/my-account/order-on-way.php'; ?>
	                        </div>
	                        <div class="modal-footer" style="display: block;">
	                           <div id="FlyingCargo_footer" style="display: none;">נקודת איסוף מסומנת:<div id="FlyingCargo_loc_name"></div>
	                            <button type="button" class="selected-location" id="FlyingCargo_confirm" data-lat="" data-long="" data-fullAdd="" data-disctiPointID="" data-pointName="" data-city="" data-street="" data-streetNum="" data-comment="" data-locationName="">בחירה וסיום</button>
	                        </div>
	                        </div>
	                    </div>
	                </div>
            	</div>
	    		<?php 
	    	}
	    }
	    /*Add New column Track order in the my account order tab */
	    function add_account_orders_column( $columns ){
		    $columns['order-track'] = __( 'Track order', 'woocommerce' );

		    return $columns;
		}
		/*Add Track order link in my account page in order tab */
		function add_account_orders_column_rows( $order ) {
			$orderId = $order->get_id();
			$cargoDekiveryId = get_post_meta($orderId,'cargo_shipping_id',true);
			$shippingMethod = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];
        	$customerID = $shipping_method_id == 'woo-baldarp-pickup' ? '3175' : '2808';
			if($cargoDekiveryId) {
				echo '<a href="javascript:void(0);" class="btn woocommerce-button tack-order-cus" data-delivery="'.$cargoDekiveryId.'" data-customer="'.$customerID.'" data-id="'.$orderId.'">Track Order</a>';
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

			if(trim(get_option('from_stree')) == '' || trim(get_option('from_stree_name')) == '' || trim(get_option('from_city')) == ''  || trim(get_option('phonenumber_from')) == '' || trim(get_option('shipping_cargo_express')) == '' || trim(get_option('shipping_cargo_box')) == '') {
				echo json_encode(array("shipmentId" => "","error_msg" => "Please enter all details from plugin setting"));
				exit;
			}
			$remove[] = "'";
			$remove[] = '"';
	    	$order_id = $_POST['orderId'];
	    	$order = wc_get_order( $order_id );
	    	$orderData  = $order->get_data();
        	$order_status = $order->get_status();
        	$shippingMethod = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];
        	$consumer_key = get_option('cargo_consumer_key');
            $consumer_secret = get_option('cargo_consumer_secret_key');
            $googleMapApi = get_option('cargo_google_api_key');  
            $homeUrl = get_home_url();
    		$data['id'] = $order_id;
            $data['consumer_key'] = $consumer_key;
            $data['consumer_secret'] = $consumer_secret;
            $data['googleMapApiKey'] = $googleMapApi;
            $data['url'] = $homeUrl;
            $data['customerID'] = $shipping_method_id == 'woo-baldarp-pickup' ? '3175' : '2808';
            $CarrierID = $shipping_method_id == 'woo-baldarp-pickup' ? '2' : '1';
            $CarrierName = $shipping_method_id == 'woo-baldarp-pickup' ? 'BOX' : 'EXPRESS';
            $customerCode = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
        	$data = array();
        	/* Create array to pass in cargo shipment */
			$data['Method'] = "ship";
			$data['Params']['shipping_type'] = "1";
			$data['Params']['to_address']['name'] = $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'];
			$data['Params']['to_address']['company'] = $orderData['shipping']['company'];
			$data['Params']['to_address']['street1'] =  str_replace($remove,"",$orderData['shipping']['address_1']);
			$data['Params']['to_address']['street2'] =  str_replace($remove,"",$orderData['shipping']['address_2']);
			$data['Params']['to_address']['city'] =  str_replace($remove,"",$orderData['shipping']['city']);
			$data['Params']['to_address']['state'] = $orderData['shipping']['state'];
			$data['Params']['to_address']['zip'] = $orderData['shipping']['postcode'];
			$data['Params']['to_address']['country'] = $orderData['shipping']['country'];
			$data['Params']['to_address']['phone'] = $orderData['billing']['phone'];
			$data['Params']['to_address']['email'] = $orderData['billing']['email'];
			if($shipping_method_id == 'woo-baldarp-pickup'){
				// $data['Params']['to_address']['name'] = get_post_meta($order_id,'DistributionPointName',TRUE);
				$data['Params']['to_address']['name'] = $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'];
				$data['Params']['to_address']['company'] = get_option( 'website_name_cargo' );
				$data['Params']['to_address']['street1'] = get_post_meta($order_id,'StreetNum',TRUE);
				$data['Params']['to_address']['street2'] = get_post_meta($order_id,'StreetName',TRUE);
				$data['Params']['to_address']['city'] = get_post_meta($order_id,'CityName',TRUE);
				$data['Params']['to_address']['state'] = "";
				$data['Params']['to_address']['zip'] = "";
				$data['Params']['to_address']['country'] = "";
				// $data['Params']['to_address']['phone'] = get_post_meta($order_id,'cargoPhone',TRUE);
				$data['Params']['to_address']['phone'] = $orderData['billing']['phone'];
				$data['Params']['to_address']['email'] = "";
				$data['Params']['boxPointId'] = get_post_meta($order_id,'DistributionPointID',TRUE);
			}
			$data['Params']['from_address']['name'] = $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'];
			$data['Params']['from_address']['company'] = get_option( 'website_name_cargo' );
			$data['Params']['from_address']['street1'] = get_option('from_stree');
			$data['Params']['from_address']['street2'] = get_option('from_stree_name');
			$data['Params']['from_address']['city'] = get_option('from_city');
			$data['Params']['from_address']['state'] = $orderData['shipping']['state'];
			$data['Params']['from_address']['zip'] = $orderData['shipping']['postcode'];
			$data['Params']['from_address']['country'] =$orderData['shipping']['country'];
			$data['Params']['from_address']['phone'] = get_option('phonenumber_from');
			$data['Params']['from_address']['email'] = $orderData['shipping']['email'];
			$data['Params']['noOfParcel'] = "1";
			$data['Params']['TotalValue'] =  $order->get_total();
			$data['Params']['TransactionID'] = $order_id;
			$data['Params']['ContentDescription'] = "";
			$data['Params']['CashOnDeliveryType'] = 0;
			$data['Params']['CarrierName'] = "CARGO";
			$data['Params']['CarrierService'] = $CarrierName;
			$data['Params']['CarrierID'] = intval($CarrierID);
			$data['Params']['OrderID'] = $order_id;
			$data['Params']['PaymentMethod'] = $orderData['payment_method'];
			$data['Params']['Note'] = $orderData['customer_note'];
			$data['Params']['customerCode'] = $customerCode;
			if($_SERVER['REMOTE_ADDR'] == '103.81.94.55') {
				//echo "DATA : <pre>";print_r($data);exit;
			}
        	$response = $this->passDataToCargo($data);
        	/* Update order meta if recieved shipment ID in response */
			if($response['shipmentId'] != '') {
				$order->update_meta_data( 'cargo_shipping_id',$response['shipmentId']);
				update_post_meta( $order_id, 'customerCode', $customerCode);
				$order->update_meta_data( 'cargo_shipping_id',$response['shipmentId']);
				$order->update_meta_data( 'customerCode',$customerCode);
				$order->update_meta_data( 'lineNumber',$response['linetext']);
				$order->update_meta_data( 'drivername',$response['drivername']);
				$order->save();
				update_post_meta( $order_id, 'cargo_shipping_id', $response['shipmentId'] );
				$boxDetails = "";
				$boxName = "";
				if($shipping_method_id == 'woo-baldarp-pickup') {
					$boxDetails = get_post_meta($order_id,'StreetNum',TRUE).' '.get_post_meta($order_id,'StreetName',TRUE).' '.get_post_meta($order_id,'CityName',TRUE).' '.get_post_meta($order_id,'store_comment',TRUE).' '.get_post_meta($order_id,'cargoPhone',TRUE);
					$boxName = get_post_meta($order_id,'DistributionPointName',TRUE);
				}
				$message = '==============================' . PHP_EOL;
				$message .= "ORDER ID : ".$order_id." | DELIVERY ID  : ".$response['shipmentId']." | SEND ON CARGO BY : ".date('Y-m-d H:i:d')."SHIPMENT TYPE : ".$CarrierName." | CUSTOMER CODE : ".$customerCode. PHP_EOL;
				$message .= "CARGO BOX POINT NAME : ".$boxName." | CARGO BOX ADDRESS : ".$boxDetails. PHP_EOL;
				$message .= "Shipment Data : ".json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
				$message .= "Response : ".json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
				//echo "msg".$message;
				$this->add_log_message($message);
			}else{
				$message = '==============================' . PHP_EOL;
				$message .= "Shipment Data : ".json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
				$message .= "Response : ".json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
				$this->add_log_message($message);
			}
			echo json_encode($response);
			exit();
	    }


	    /* Add Log for Order */

	    function add_log_message($msg) {
	    	//exit("Test");
	    	$upload = wp_upload_dir();
 			$upload_dir = $upload['basedir'];
 			$upload_dir = $upload_dir . '/cargo-shipping-location';

			//echo "PAth ".$path.'<br>';
			if (! is_dir($upload_dir)) {
				 mkdir( $upload_dir, 0700 );
			}
			$path = $upload_dir.'/order_log_' . date('Ymd') . '.txt';
	        if (!file_exists($path)) {
	            $file = fopen($path, 'w') or die("Can't create file");
	           //	echo "FILE ".$file.'<br>';
	        }

	        //echo "msg ".$msg.'<br>';exit;
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
		     if ( ! $template_path ) 
		        $template_path = $woocommerce->template_url;
		 
		     $plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) )  . '/woocommerce/templates/';
		 
		    // Look within passed path within the theme - this is priority
		    $template = locate_template(
		    array(
		      $template_path . $template_name,
		      $template_name
		    )
		   );
		 
		   if( ! $template && file_exists( $plugin_path . $template_name ) )
		    $template = $plugin_path . $template_name;
		 
		   if ( ! $template )
		    $template = $_template;
			}
		   return $template;

		}
		/* Add order status for Cargo */

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
		/*Add Meta box in admin order */
		public function add_meta_box( $post_type ) {
			global $post,$pagenow,$typenow;
			if( ('edit.php' === $pagenow || 'post.php' === $pagenow) && 'shop_order' === $typenow && is_admin()) {
				$order = wc_get_order($post->ID);
				$shippingMethod = @array_shift($order->get_shipping_methods() );
				$shipping_method_id = $shippingMethod['method_id'];
				if($shipping_method_id == 'cargo-express' || $shipping_method_id == 'woo-baldarp-pickup' || get_option('send_to_cargo_all')) {
					add_meta_box(
						'cargo_custom_box',
						__( '<img src="'.AWSB_URL."assets/image/howitworks.png".'" alt="Cargo" width="100" style="width:50px;">CARGO', 'textdomain' ),
						array( $this, 'render_meta_box_content' ),
						'shop_order',
						'side',
						'core'
					);
				}
			}
	    }
	    /*remove meta box from the admin order page */
	    public function remove_shop_order_meta_boxe() {
		    remove_meta_box( 'postcustom', 'shop_order', 'normal' );
		}

	    public function render_meta_box_content( $post ) {
	        // Use get_post_meta to retrieve an existing value from the database.
	        $value = get_post_meta( $post->ID, 'cargo_shipping_id', true );
	 		$order = wc_get_order($post->ID);
		    $shippingMethod = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];
			if($shipping_method_id == 'cargo-express' || $shipping_method_id == 'woo-baldarp-pickup' || get_option('send_to_cargo_all')) {
				?>
				<label for="myplugin_new_field">
					<?php //_e( 'בקש סטטוס משלוח', 'textdomain' ); ?>
					<a href="javascript:void(0);" class="edit-address-cargo"><?php _e( 'בקש סטטוס משלוח', 'textdomain' ); ?></a>
				</label>

				<div class="cargo-button">
				<?php 
				if($value) { ?>
					<input type="button" class="cancel-cargo-shipping" value="בטל משלוח" data-id="<?php echo $post->ID; ?>">
				<?php }else{ ?>
					<input type="button" class="submit-cargo-shipping" value="שלח  לCARGO" data-id="<?php echo $post->ID; ?>">
				<?php  } ?>
				</div>
				<div class="cargo-button">
				<?php 
				if($value) { ?>
					<input type="button" class="label-cargo-shipping" value="הדפס תווית" data-id="<?php echo $value; ?>">
				<?php } ?>
				</div>
				<div class="checkstatus-section">
					<?php 
					$order = wc_get_order($post->ID);
					$shippingMethod = @array_shift($order->get_shipping_methods());
					$shipping_method_id = $shippingMethod['method_id'];
					$customerId = $shipping_method_id == 'woo-baldarp-pickup' ? '3175' : '2808';
					$customerCode = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
					$type = $shipping_method_id == 'woo-baldarp-pickup' ? "BOX" : "EXPRESS";
					if(get_post_meta($post->ID,'cargo_shipping_id',true)) {
						echo "<a href='javascript:void(0);' class='btn btn-success send-status' data-orderlist='0' data-id=".$post->ID." data-customerCode=".$customerCode." data-type=".$type." data-deliveryId=".get_post_meta($post->ID,'cargo_shipping_id',true).">בקש סטטוס משלוח</a>";
					}
					?>
				</div>
				<?php if($shipping_method_id == 'woo-baldarp-pickup') { ?>
					<div>
						<h2>Cargo Store Details</h2>
						<?php echo '<h3 style="margin:0;"><strong>'.get_post_meta($post->ID,'DistributionPointName',TRUE).'</strong></h2><h4 style="margin:0;">'.get_post_meta($post->ID,'StreetNum',TRUE).' '.get_post_meta($post->ID,'StreetName',TRUE).' '.get_post_meta($post->ID,'CityName',TRUE).'</h4><h4 style="margin:0;">'.get_post_meta($post->ID,'store_comment',TRUE).'</h4><h3 style="margin:0;">'.get_post_meta($post->ID,'cargoPhone',TRUE).'</h4>'; ?>
					</div><span>
				<?php } 
			}
        	//echo $shipping_method_id;exit("Metho");
	        // Display the form, using the current value.
	        
	    }
 

		/*Add order status in Array */

		function custom_order_status( $order_statuses ) {
		    $order_statuses['wc-send-cargo'] = _x( 'Send to CARGO', 'Order status', 'woocommerce' ); 
		    $order_statuses['wc-cancel-cargo'] = _x( 'Cancel to Send CARGO', 'Order status', 'woocommerce' ); 
		    return $order_statuses;
		}

		/*Add Order Status to Admin Order list */

		function custom_dropdown_bulk_actions_shop_order($actions ){
			 $new_actions = array();

    		// Add new custom order status after processing
		    foreach ($actions as $key => $action) {
		        $new_actions[$key] = $action;
		        if ('mark_processing' === $key) {
		            $new_actions['mark_send-cargo'] = __( 'Send to CARGO', 'woocommerce' );
		            $new_actions['mark_cancel-cargo'] = __( 'Cancel to Send CARGO', 'woocommerce' );
		        }
		    }

		    return $new_actions;
		}
		
		function getShipmenlable() {
			$delievryNumber = $_POST['shipmentId'];
			$cargoGetDeliveryStatusUr = "https://api.carg0.co.il/Webservice/generateShipmentLabel";
			$data['deliveryId'] = (int)$delievryNumber;
			$options = [
				'body'        => wp_json_encode( $data ),
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
			$response = wp_remote_post( $cargoGetDeliveryStatusUr,  $options);
			$arrData = wp_remote_retrieve_body($response) or die("Error: Cannot create object");
			$data = (array)json_decode($arrData);
			if($data['error_msg']  == '') {
				echo json_encode(array("error_msg" => "","pdfLink" => $data['pdfLink'] ));
			}else{
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
			$customerId = $_POST['customerCode'];
			$delievryNumber = $_POST['deliveryId'];
			$type = $_POST['type'];
			$deliveryType = $customerId == "3175" ? "BOX" : "EXPRESS";
			// $cargoGetDeliveryStatusUr = "https://phpstack-789093-2706633.cloudwaysapps.com/Webservice/CheckShipmentStatus";
			$cargoGetDeliveryStatusUr = "https://api.carg0.co.il/Webservice/CheckShipmentStatus";
			$data['deliveryId'] = (int)$delievryNumber;
			$data['DeliveryType'] = $type;
			$data['customerCode'] = $customerId;
			$options = [
			    'body'        => wp_json_encode( $data ),
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
			$response = wp_remote_post( $cargoGetDeliveryStatusUr,  $options);
            $arrData = wp_remote_retrieve_body($response) or die("Error: Cannot create object");
			$data = (array)json_decode($arrData);
			if($data['errorMsg']  == '') {
				if((int)$data['deliveryStatus'] > 0) {
					update_post_meta($_POST['orderId'],'get_status_cargo',(int)$data['deliveryStatus']);
					update_post_meta($_POST['orderId'],'get_status_cargo_text',$data['DeliveryStatusText']);
					echo json_encode(array("type" => "success","data" => $data['DeliveryStatusText'],"orderStatus"=>(int)$data['deliveryStatus']));
				}else{
					echo json_encode(array("type" => "failed","data" => 'Not Getting Data'));
				}
			}else{
				echo json_encode(array("type" => "failed","data" => 'something went wrong'));
			}
			exit;
		}
		/* Show Shipping Info in Admin Edit Order */
       	function show_shipping_info($order ) {
       		$cargo_shipping_id = $order->get_meta('cargo_shipping_id');
    
		    if ( ! empty($cargo_shipping_id) ) {
		        echo '<p><strong>'.__('מזהה משלוח').':</strong> ' . $cargo_shipping_id . '</p>';
		    }
       	}

       /* function add_order_notes_column_style() {
		    $css = '.no_of_package { cursor: default !important; }';
		    wp_add_inline_style( 'woocommerce_admin_styles', $css );
		}*/

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

		/* Add Custom Column in Admin Order List */
        function add_custom_column_content( $column ) {
		    global $post;
			$order = wc_get_order($post->ID);
			$shippingMethod = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];
			$customerCode = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
			if($shipping_method_id == 'cargo-express' || $shipping_method_id == 'woo-baldarp-pickup' || get_option('send_to_cargo_all')) {
				if ( 'delivery_status' === $column ) {
					$order = wc_get_order($post->ID);
					$shippingMethod = @array_shift($order->get_shipping_methods());
					$shipping_method_id = $shippingMethod['method_id'];
					$customerId = $shipping_method_id == 'woo-baldarp-pickup' ? '3175' : '2808';
					$customerCode = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
					$type = $shipping_method_id == 'woo-baldarp-pickup' ? "BOX" : "EXPRESS";

					if(get_post_meta($post->ID,'cargo_shipping_id',true)) {
						echo "<a href='javascript:void(0);' class='btn btn-success send-status' data-orderlist='1' data-id=".$post->ID." data-customerCode=".$customerCode."  data-type=".$type." data-deliveryId=".get_post_meta($post->ID,'cargo_shipping_id',true)."> בדוק מצב הזמנה</a>";
					}
				}
				if ( 'send_to_cargo' === $column ) {
					if(get_post_meta($post->ID,'cargo_shipping_id',true)) {
						echo "<a href='javascript:void(0);' class='btn btn-success cancel-cargo-shipping' data-id=".$post->ID." data-customerCode=".$customerCode." data-deliveryId=".get_post_meta($post->ID,'cargo_shipping_id',true).">בטל משלוח</a>";
					}else{
						echo "<a href='javascript:void(0);' class='btn btn-success submit-cargo-shipping' data-id=".$post->ID." >שלח  לCARGO</a>";
					}
					
				}
				if ( 'cargo_delivery_id' === $column ) {
					if(get_post_meta($post->ID,'cargo_shipping_id',true)){
						echo "<p>".get_post_meta($post->ID,'cargo_shipping_id',true)."</p>";
						echo '<a  href="javascript:void(0)" class="btn btn-success label-cargo-shipping" data-id="'.get_post_meta($post->ID,'cargo_shipping_id',true).'">הדפס תווית</a>';
					}

				}
				if('delievry_statuss' === $column) {
					$order = wc_get_order($post->ID);
					$shippingMethod = @array_shift($order->get_shipping_methods());
					$shipping_method_id = $shippingMethod['method_id'];
					$customerId = $shipping_method_id == 'woo-baldarp-pickup' ? '3175' : '2808';
					if(get_post_meta($post->ID,'get_status_cargo',true)) {
						echo '<p>'.get_post_meta($post->ID,'get_status_cargo_text',true).'</p>';
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
		            $new_columns['delievry_statuss'] = __( 'סטטוס משלוח', 'cargo' );
		        }
		    }

		    return $new_columns;
        }
        public function transfer_order_data_for_shipment($order_id ) {
        	if ( ! $order_id )
        		return;
        	$order = wc_get_order( $order_id );
        	$shippingMethod = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];
        	if($shipping_method_id === 'woo-baldarp-pickup') {
    			update_post_meta( $order_id, 'DistributionPointID',  $_POST['DistributionPointID'] );
    			update_post_meta( $order_id, 'DistributionPointName',  $_POST['DistributionPointName'] );
    			update_post_meta( $order_id, 'CityName',  $_POST['CityName'] );
    			update_post_meta( $order_id, 'StreetName',  $_POST['StreetName'] );
    			update_post_meta( $order_id, 'StreetNum',  $_POST['StreetNum'] );
    			update_post_meta( $order_id, 'cargoPhone',  $_POST['cargoPhone'] );
    			update_post_meta( $order_id, 'store_comment',  $_POST['Comment'] );
        	}
        }
        public function bulk_order_cargo_shipmment($redirect_to, $action, $ids) {
			$is_cargo = 0;
			$old_status = "";
			$new_status = "";
            if ( false !== strpos( $action, 'mark_' ) ) {
                $new_status     = substr( $action, 5 ); // Get the status name from action.
                $report_action  = 'wc-' . $new_status;
                if('wc-send-cargo' == $report_action || get_option('cargo_order_status') == $report_action) {
                	//exit("test123");
                    $consumer_key = get_option('cargo_consumer_key');
                    $consumer_secret = get_option('cargo_consumer_secret_key');
                    $googleMapApi = get_option('cargo_google_api_key');  
					$remove[] = "'";
					$remove[] = '"';
                    foreach ($ids as $key => $value) {
						$data = array();
						$order = wc_get_order($value);
						$old_status = $order->get_status();
                    	if(get_post_meta($value,'cargo_shipping_id',TRUE)){
                    		continue;	
                    	}
                        $orderData  = $order->get_data();
                        $shippingMethod = @array_shift($order->get_shipping_methods());
        				$shipping_method_id = $shippingMethod['method_id'];

        				$customerId = $shipping_method_id == 'woo-baldarp-pickup' ? '3175' : '2808';
        				$CarrierID = $shipping_method_id == 'woo-baldarp-pickup' ? '2' : '1';
        				$CarrierName = $shipping_method_id == 'woo-baldarp-pickup' ? 'B0X' : 'EXPRESS';
        				$customerCode = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
                        $data = array();
						$data['Method'] = "ship";
						$data['Params']['shipping_type'] = "1";
						$data['Params']['to_address']['name'] = $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'];
						$data['Params']['to_address']['company'] = $orderData['shipping']['company'];
						$data['Params']['to_address']['street1'] =  str_replace($remove,"",$orderData['shipping']['address_1']);
						$data['Params']['to_address']['street2'] =  str_replace($remove,"",$orderData['shipping']['address_2']);
						$data['Params']['to_address']['city'] =  str_replace($remove,"",$orderData['shipping']['city']);
						$data['Params']['to_address']['state'] = $orderData['shipping']['state'];
						$data['Params']['to_address']['zip'] = $orderData['shipping']['postcode'];
						$data['Params']['to_address']['country'] = $orderData['shipping']['country'];
						$data['Params']['to_address']['phone'] = $orderData['billing']['phone'];
						$data['Params']['to_address']['email'] = $orderData['billing']['email'];
						if($shipping_method_id == 'woo-baldarp-pickup'){
							// $data['Params']['to_address']['name'] = get_post_meta($order_id,'DistributionPointName',TRUE);
							$data['Params']['to_address']['name'] = $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'];
							$data['Params']['to_address']['company'] = get_option( 'website_name_cargo' );
							$data['Params']['to_address']['street1'] = get_post_meta($value,'StreetNum',TRUE);
							$data['Params']['to_address']['street2'] = get_post_meta($value,'StreetName',TRUE);
							$data['Params']['to_address']['city'] = get_post_meta($value,'CityName',TRUE);
							$data['Params']['to_address']['state'] = "";
							$data['Params']['to_address']['zip'] = "";
							$data['Params']['to_address']['country'] = "";
							// $data['Params']['to_address']['phone'] = get_post_meta($order_id,'cargoPhone',TRUE);
							$data['Params']['to_address']['phone'] = $orderData['billing']['phone'];
							$data['Params']['to_address']['email'] = "";
							$data['Params']['boxPointId'] = get_post_meta($value,'DistributionPointID',TRUE);
						}
						$data['Params']['from_address']['name'] = $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'];
						$data['Params']['from_address']['company'] = get_option( 'website_name_cargo' );
						$data['Params']['from_address']['street1'] = get_option('from_stree');
						$data['Params']['from_address']['street2'] = get_option('from_stree_name');
						$data['Params']['from_address']['city'] = get_option('from_city');
						$data['Params']['from_address']['state'] = $orderData['shipping']['state'];
						$data['Params']['from_address']['zip'] = $orderData['shipping']['postcode'];
						$data['Params']['from_address']['country'] =$orderData['shipping']['country'];
						$data['Params']['from_address']['phone'] = get_option('phonenumber_from');
						$data['Params']['from_address']['email'] = $orderData['shipping']['email'];
						$data['Params']['noOfParcel'] = "1";
						$data['Params']['TotalValue'] =  $order->get_total();
						$data['Params']['TransactionID'] = $value;
						$data['Params']['ContentDescription'] = "";
						$data['Params']['CashOnDeliveryType'] = 0;
						$data['Params']['CarrierName'] = "CARGO";
						$data['Params']['CarrierService'] = $CarrierName;
						$data['Params']['CarrierID'] = intval($CarrierID);
						$data['Params']['OrderID'] = $value;
						$data['Params']['PaymentMethod'] = $orderData['payment_method'];
						$data['Params']['Note'] = $orderData['customer_note'];	
						$data['Params']['customerCode'] = $customerCode;	
                        $response = $this->passDataToCargo($data);
                        if($response['shipmentId'] != '') {
		                	update_post_meta( $value, 'cargo_shipping_id',  (int)$response['shipmentId'] );
		                	update_post_meta( $value, 'get_status_cargo',  1 );
		                	update_post_meta( $value, 'get_status_cargo_text', "Open");
							update_post_meta( $value, 'customerCode', $customerCode);
							$order->update_meta_data( 'cargo_shipping_id',$response['shipmentId']);
							$order->update_meta_data( 'customerCode',$customerCode);
							$order->update_meta_data( 'lineNumber',$response['linetext']);
							$order->update_meta_data( 'drivername',$response['drivername']);
							$order->save();
		                	$boxDetails = "";
							$boxName = "";
							if($shipping_method_id == 'woo-baldarp-pickup') {
								$boxDetails = get_post_meta($value,'StreetNum',TRUE).' '.get_post_meta($value,'StreetName',TRUE).' '.get_post_meta($value,'CityName',TRUE).' '.get_post_meta($value,'store_comment',TRUE).' '.get_post_meta($value,'cargoPhone',TRUE);
								$boxName = get_post_meta($value,'DistributionPointName',TRUE);
							}
							$message = '==============================' . PHP_EOL;
							$message .= "ORDER ID : ".$value." | DELIVERY ID  : ".$response['shipmentId']." | SEND ON CARGO BY : ".date('Y-m-d H:i:d')."SHIPMENT TYPE : ".$CarrierName." | CUSTOMER CODE : ".$customerCode. PHP_EOL;
							$message .= "CARGO BOX POINT NAME : ".$boxName." | CARGO BOX ADDRESS : ".$boxDetails. PHP_EOL;

							$this->add_log_message($message);
		                }else{
							$message = '==============================' . PHP_EOL;
							$message .= "Shipment Data : ".json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
							$message .= "Response : ".json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
							$this->add_log_message($message);
						}
                    }
                }elseif ('wc-cancel-cargo' == $report_action) {
                	return;
                }      
            }
			$redirect_to = add_query_arg( array(
                'cargo_send' => '1',
				'old_stutus' => $old_status,
				'is_cargo' => $is_cargo,
				'new_status' => $new_status,
                'processed_count' => count( $ids ),
                'processed_ids' => implode( ',', $ids ),
            ), $redirect_to );
            return $redirect_to;
        }
        public function cargo_status_change_event($order_id, $old_status, $new_status)
        {
        	if(!is_checkout()) {
        		if('wc-send-cargo' == 'wc-'.$new_status) {
					if(trim(get_option('from_stree')) != '' && trim(get_option('from_stree_name')) != '' && trim(get_option('from_city')) != ''  && trim(get_option('phonenumber_from')) != '' && trim(get_option('shipping_cargo_express')) != '' && trim(get_option('shipping_cargo_box')) != '') {
						//exit("here");
						$this->createShipment($order_id);
					}
					$order = $order = wc_get_order($order_id);
					$old_status = $order->get_status();
					if(get_option('disable_order_status')){
						$order->update_status( $old_status);
					}
	            }elseif ('wc-cancel-cargo' == 'wc-'.$new_status) {
                	return;
                } else{
					if(get_option('cargo_order_status') == 'wc-'.$new_status){
						if(get_post_meta($order_id,'cargo_shipping_id',TRUE)){
                    		return;	
                    	}
					    $this->createShipment($order_id);
					}
				}
        	}else{
				$order = wc_get_order($order_id);
				if(get_option('cargo_order_status') == "wc-".$order->get_status()){
					if(!get_post_meta($order_id,'cargo_shipping_id',TRUE)){
						$this->createShipment($order_id);
					}
				}
			}
        }
		function createShipment($order_id){
			$consumer_key = get_option('cargo_consumer_key');
			$consumer_secret = get_option('cargo_consumer_secret_key');
			$googleMapApi = get_option('cargo_google_api_key');    
			$data = array();
			$remove[] = "'";
			$remove[] = '"';
			$order = wc_get_order($order_id);
			if(get_post_meta($order_id,'cargo_shipping_id',TRUE)){
				return;	
			}
			$orderData  = $order->get_data();
			$shippingMethod = @array_shift($order->get_shipping_methods());
			$shipping_method_id = $shippingMethod['method_id'];

			$customerId = $shipping_method_id == 'woo-baldarp-pickup' ? '3175' : '2808';
			$CarrierID = $shipping_method_id == 'woo-baldarp-pickup' ? '2' : '1';
			$CarrierName = $shipping_method_id == 'woo-baldarp-pickup' ? 'B0X' : 'EXPRESS';
			$customerCode = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
			$data = array();
			$data['Method'] = "ship";
			$data['Params']['shipping_type'] = "1";
			$data['Params']['to_address']['name'] = $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'];
			$data['Params']['to_address']['company'] = $orderData['shipping']['company'];
			$data['Params']['to_address']['street1'] = str_replace($remove,"",$orderData['shipping']['address_1']);
			$data['Params']['to_address']['street2'] = str_replace($remove,"",$orderData['shipping']['address_2']);
			$data['Params']['to_address']['city'] = str_replace($remove,"",$orderData['shipping']['city']);
			$data['Params']['to_address']['state'] = $orderData['shipping']['state'];
			$data['Params']['to_address']['zip'] = $orderData['shipping']['postcode'];
			$data['Params']['to_address']['country'] = $orderData['shipping']['country'];
			$data['Params']['to_address']['phone'] = $orderData['billing']['phone'];
			$data['Params']['to_address']['email'] = $orderData['billing']['email'];
			$data['Params']['to_address']['floor'] = get_post_meta($order_id,'cargo_floor',TRUE);
			$data['Params']['to_address']['appartment'] = get_post_meta($order_id,'cargo_apartment',TRUE);
			if($shipping_method_id == 'woo-baldarp-pickup'){
				// $data['Params']['to_address']['name'] = get_post_meta($order_id,'DistributionPointName',TRUE);
				$data['Params']['to_address']['name'] = $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'];
				$data['Params']['to_address']['company'] = get_option( 'website_name_cargo' );
				$data['Params']['to_address']['street1'] = get_post_meta($order_id,'StreetNum',TRUE);
				$data['Params']['to_address']['street2'] = get_post_meta($order_id,'StreetName',TRUE);
				$data['Params']['to_address']['city'] = get_post_meta($order_id,'CityName',TRUE);
				$data['Params']['to_address']['state'] = "";
				$data['Params']['to_address']['zip'] = "";
				$data['Params']['to_address']['country'] = "";
				// $data['Params']['to_address']['phone'] = get_post_meta($order_id,'cargoPhone',TRUE);
				$data['Params']['to_address']['phone'] = $orderData['billing']['phone'];
				$data['Params']['to_address']['email'] = "";
				$data['Params']['boxPointId'] = get_post_meta($order_id,'DistributionPointID',TRUE);
			}
			$data['Params']['from_address']['name'] = $orderData['shipping']['first_name'].' '.$orderData['shipping']['last_name'];
			$data['Params']['from_address']['company'] = get_option( 'website_name_cargo' );
			$data['Params']['from_address']['street1'] = get_option('from_stree');
			$data['Params']['from_address']['street2'] = get_option('from_stree_name');
			$data['Params']['from_address']['city'] = get_option('from_city');
			$data['Params']['from_address']['state'] = $orderData['shipping']['state'];
			$data['Params']['from_address']['zip'] = $orderData['shipping']['postcode'];
			$data['Params']['from_address']['country'] =$orderData['shipping']['country'];
			$data['Params']['from_address']['phone'] = get_option('phonenumber_from');
			$data['Params']['from_address']['email'] = $orderData['shipping']['email'];
			$data['Params']['noOfParcel'] = "1";
			$data['Params']['TotalValue'] =  $order->get_total();
			$data['Params']['TransactionID'] = $order_id;
			$data['Params']['ContentDescription'] = "";
			$data['Params']['CashOnDeliveryType'] = 0;
			$data['Params']['CarrierName'] = "CARGO";
			$data['Params']['CarrierService'] = $CarrierName;
			$data['Params']['CarrierID'] = intval($CarrierID);
			$data['Params']['OrderID'] = $order_id;
			$data['Params']['PaymentMethod'] = $orderData['payment_method'];
			$data['Params']['Note'] = $orderData['customer_note'];	
			$data['Params']['customerCode'] = $customerCode;		
			$response = $this->passDataToCargo($data);
			if($response['shipmentId'] != '') {
				update_post_meta( $order_id, 'cargo_shipping_id',  (int)$response['shipmentId'] );
				update_post_meta( $order_id, 'get_status_cargo',  1 );
				update_post_meta( $order_id, 'get_status_cargo_text', "Open");
				update_post_meta( $order_id, 'customerCode', $customerCode);
				$order->update_meta_data( 'cargo_shipping_id',$response['shipmentId']);
				$order->update_meta_data( 'customerCode',$customerCode);
				$order->update_meta_data( 'lineNumber',$response['linetext']);
				$order->update_meta_data( 'drivername',$response['drivername']);
				$order->save();
				$boxDetails = "";
				$boxName = "";
				if($shipping_method_id == 'woo-baldarp-pickup') {
					$boxDetails = get_post_meta($order_id,'StreetNum',TRUE).' '.get_post_meta($order_id,'StreetName',TRUE).' '.get_post_meta($order_id,'CityName',TRUE).' '.get_post_meta($order_id,'store_comment',TRUE).' '.get_post_meta($order_id,'cargoPhone',TRUE);
					$boxName = get_post_meta($order_id,'DistributionPointName',TRUE);
				}
				$message = '==============================' . PHP_EOL;
				$message .= "ORDER ID : ".$order_id." | DELIVERY ID  : ".$response['shipmentId']." | SEND ON CARGO BY : ".date('Y-m-d H:i:d')."SHIPMENT TYPE : ".$CarrierName." | CUSTOMER CODE : ".$customerCode. PHP_EOL;
				$message .= "CARGO BOX POINT NAME : ".$boxName." | CARGO BOX ADDRESS : ".$boxDetails. PHP_EOL;

				$this->add_log_message($message);
			}else{
				$message = '==============================' . PHP_EOL;
				$message .= "Shipment Data : ".json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
				$message .= "Response : ".json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
				$this->add_log_message($message);
			}
		}

        public function passDataToCargo($data = array()) {
            if(!empty($data)) {
            	$body = wp_json_encode( $data );
				//echo "<pre>";print_r($body);exit;
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
               	return (array)$status;
            }else{
            	return 0;
            }

        }
        
        public function action_woocommerce_checkout_process($wccs_custom_checkout_field_pro_process ) {
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            $chosen_method_id = explode(':', $chosen_shipping_methods[0]);
            $chosen_method_id = reset($chosen_method_id);
            if( $chosen_method_id == 'woo-baldarp-pickup' ) {
                if($_POST['DistributionPointID'] == '') {
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
            //if($screen_id === 'edit-shop_order') {
            	wp_enqueue_script( 'cargo-admin-script', AWSB_URL .'assets/js/admin/admin-baldarp-script.js', array(), '', true);
            	wp_localize_script( 'cargo-admin-script', 'admin_cargo_obj',
                array( 
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'ajax_nonce'    => wp_create_nonce( 'awsb_shipping_nonce' ),
					'path' => AWSB_URL,
                )
            );
           // }
        }
        /*Update Order meta */
        public function custom_checkout_field_update_order_meta($order_id){
            $order = wc_get_order( $order_id );
            $shippingMethod = explode(':',$_POST['shipping_method'][0]);
            if( reset($shippingMethod) == 'woo-baldarp-pickup') {

                if($_POST['DistributionPointID']){
                    $order->update_meta_data( 'cargo_DistributionPointID', $_POST['DistributionPointID']);
                }
                if($_POST['DistributionPointName']){
                    $order->update_meta_data( 'cargo_DistributionPointName', $_POST['DistributionPointName']);
                }
                if($_POST['CityName']){
                    $order->update_meta_data( 'cargo_CityName', $_POST['CityName']);
                }
                if($_POST['StreetName']){
                    $order->update_meta_data( 'cargo_StreetName', $_POST['StreetName']);
                }
                if($_POST['StreetNum']){
                    $order->update_meta_data( 'cargo_StreetNum', $_POST['StreetNum']);
                }
                if($_POST['Comment']){
                    $order->update_meta_data( 'cargo_Comment', $_POST['Comment'] );
                }
                if($_POST['Latitude']){
                    $order->update_meta_data( 'cargo_Latitude', $_POST['Latitude']);
                }
                if($_POST['Longitude']){
                    $order->update_meta_data( 'cargo_Longitude', $_POST['Longitude']);
                }
            }
        }

        public function awsb_plugin_scripts() {
			if(is_cart() || is_checkout()) {
				wp_enqueue_script( 'baldarp-script', AWSB_URL .'assets/js/baldarp-script.js', array(), '', true);
				wp_localize_script( 'baldarp-script', 'baldarp_obj',
					array( 
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'ajax_nonce'    => wp_create_nonce( 'awsb_shipping_nonce' ),
					)
				);
				// wp_enqueue_script( 'baldarp-map-jquery', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyCAIwTPctnSM2PWcbK6cMdlZaSgEYIKp5U', null, null, true );
				wp_enqueue_script( 'baldarp-map-jquery', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyATriqvOSeSLdO-eVqCquY7dYlp6p2jAzU&language=he&libraries=places&v=weekly', null, null, true );
				wp_enqueue_style('badarp-front-css', AWSB_URL.'assets/css/front.css');
				
				if(get_option('bootstrap_enalble') == 1){
					//echo get_option("bootstrap_enalble");exit;
					wp_enqueue_script( 'baldarp-bootstrap-jquery',  AWSB_URL .'assets/js/boostrap_bundle.js', array(), '', false );
					wp_enqueue_style('badarp-bootstrap-css', AWSB_URL .'assets/css/boostrap_min.css');
				}
				// wp_enqueue_script( 'baldarp-bootstrap-jquery', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js', null, null, true );
				// wp_enqueue_style('badarp-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css');
			}
        }

        public function awsb_script_checkout() { 
			if(is_checkout() || is_cart()){
			?>
            <!-- <input type="hidden" id="default_markers" value="<?php echo AWSB_URL.'assets/image/default_new.png' ?>" > -->
            <input type="hidden" id="default_markers" value="<?php echo AWSB_URL.'assets/image/cargo-icon-svg.svg' ?>" >
        	<input type="hidden" id="selected_marker" value="<?php echo AWSB_URL.'assets/image/selected_new.png' ?>" >
            <div class="modal" id="mapmodelcargo" tabindex="-1" role="dialog" style="display:none;" style="z-index:9999999 !important;">
                <div class="modal-dialog" role="document" style="max-width: 1000px !important;max-height: 100%;width:850px;">
                    <div class="modal-content">
                        <div class="modal-header">
                           <img src="<?php echo AWSB_URL.'assets/image/howitworks.png'; ?>" alt="Cargo" width="100">
                            <div style="direction: rtl;">
                            	<a href="javascript:void(0);" class="open-how-it-works">איך זה עובד ?</a>
                            </div>
                            <div class="modal-search" style="direction: rtl;">
                            	<input id="search-input-cus" name="search-input-cus" type="text" placeholder="חיפוש נקודת איסוף  " value=""/>
	                            <div class="startup">
	                            	<ul class="startup-dropdown">
	                            		
	                            	</ul>
	                            </div>

                            </div>
	                        <!-- <input type="text" class="input-text " name="search_new" id="search_new" placeholder="" value="" autocomplete=""> -->

                            <h5 class="modal-title"> נקודות איסוף</h5>
                            
                            <button type="button" class="close" id="modal-close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div style="padding-top: 5px;text-align: center;"><strong style="color: #2579b5;">לחצו על נקודת האיסוף כדי לבחור או חפשו בתיבת החיפוש מעלה</strong></div>
                        <div class="modal-body">
                            <!--  <input id="pac-input" class="controls" type="text" placeholder="Search Box"> -->
                            <div id="map" style="width:auto;height:400px;" ></div>
                        </div>
                        <div class="modal-footer" style="display: block;">
                           <div id="FlyingCargo_footer" style="display: none;">נקודת איסוף מסומנת:<div id="FlyingCargo_loc_name"></div>
                            <button type="button" class="selected-location" id="FlyingCargo_confirm" data-lat="" data-long="" data-fullAdd="" data-disctiPointID="" data-pointName="" data-city="" data-street="" data-streetNum="" data-comment="" data-locationName="">בחירה וסיום</button>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal descript" tabindex="-1" role="dialog" style="margin-top: 90px;display:none;" >
                <div class="modal-dialog" role="document" style="max-width: 700px !important;">
                    <div class="modal-content">
                        <div class="modal-header">
                           <img src="<?php echo AWSB_URL.'assets/image/howitworks.png'; ?>" alt="Cargo" width="100" style="height: 100px;">
                            <h5 class="modal-title">CARGO BOX - איך זה עובד</h5>
                            <button type="button" class="close" id="modal-close-desc" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="direction: rtl;">
                            <!--  <input id="pac-input" class="controls" type="text" placeholder="Search Box"> -->
                            <div> CARGO BOX </div>
                            <div>נקודות החלוקה שלנו בפריסה ארצית לנוחיותכם,</div>
                            <div><p>אוספים את החבילה בדרך הקלה והמהירה ביותר!</p></div>
							<div><p>איסוף החבילה שלכם יתבצע בנקודת חלוקה הקרובה לביתכם או למקום עבודתכם, היכן שתבחרו, ללא המתנה לשליח, ללא צורך בזמינות, בצורה היעילה, הזולה והפשוטה ביותר  </p></div>
							<div>כמה פשוט? ככה פשוט- </div>
							<div>בוחרים נקודת חלוקה שמתאימה לכם </div>
							<div>כאשר החבילה שלכם מגיעה ליעד אתם מקבלים SMS ומייל </div>
							<div>ומגיעים לאסוף את החבילה </div>
                        </div>
                    </div>
                </div>
            </div>
           <?php } 
        }

        public function awsb_ajax_delivery_location() {
            global $wpdb; // this is how you get access to the database

            $api_u = get_option('shipping_api_username');
            $api_p = get_option('shipping_api_pwd');
            $api_int1 = get_option('shipping_api_int1');
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            $result = array();

        	$usrnaem = "Cargo";
        	$pass = "Crg2468";
        	$apiInt1 = "924568";
            if(!empty($usrnaem) && !empty($pass) && !empty($apiInt1)) {
                $data['userName'] = "Cargo";
				$data['password'] = "Crg2468";
				$data['APICode'] = 924568;
				$body = json_encode($data);
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
                //$data = 'userName='.$usrnaem.'&password='.$pass.'&comments=GetDistributionPoint&int1='.$apiInt1.'&int2=0&int3=0&dbl1=0&dbl2=0&dbl3=0&str1=0&str2=0&str3=0&str4=0&str5=0&str6=0&xml1=0&xml2=0&xml3=0&bin1=0&bin2=0&bin3=0';
                // $response = wp_remote_post( 'http://185.241.7.143/Baldarp/service.asmx/ExecuteTask', array( 'body' => $data,'timeout'     => 45 ) );
                //$response = wp_remote_post( 'https://carg0.co.il/ExecuteTask', array( 'body' => $data,'timeout'     => 45 ) ); 
				$point = array();
                $response = wp_remote_post( 'https://api.carg0.co.il/Webservice/getPickUpPoints', $options ); 

                $arrData = wp_remote_retrieve_body($response) or die("Error: Cannot create object");
				$results = json_decode($arrData);

				//echo "<pre>";print_r($results);exit;
                // echo "REsponse <pre>";print_r($arrData);exit;
                // //echo "DATA : <pre>";print_r($arrData);exit;
                // $arrData = simplexml_load_string($arrData);
                // $newvalue = str_replace('100"DistributionPoint:',"",(string)$arrData->StrValue);
                // $newvalue = str_replace('"DistributionPoint":',"",$newvalue);
                // $value = str_replace('"IdNum": 0', '"IdNum": ', trim($newvalue));
                // $value = str_replace('"IdNum": 0', '"IdNum": ', trim($value));
                // $value = str_replace('"IdNum":  ', '"IdNum": ""', trim($value));

                // $dataVal = "[".$value."]";
                $point = array();
                $inctivePoints =  json_encode(array());
                // foreach (json_decode($dataVal) as $key => $value) {
                // 	if($value->Comment != '') {
                // 		$points[] = $value;
                // 	}else{

                // 		$inctivePoints[] = $value;
                // 	}
                // }
                // $point = json_encode($points);
                // $inctivePoints = json_encode($inctivePoints);
               // exit;
				if(!empty($results->PointsDetails)){
					$point = $results->PointsDetails;
				}
                $result["result"] = 1;
                $result["info"] = "Your requested reference number was not found.";
                $result["data"] = $value;
                $result["dataval"] = json_encode($point);
                $result["dataInac"] = $inctivePoints;
                $result['shippingMethod'] = $chosen_shipping_methods[0];
            } else {
                $result["result"] = 1;
                $result["info"] = "Your requested reference number was not found.";
                $result["data"] = 0;
            }
            
            echo json_encode($result);
            wp_die(); // this is required to terminate immediately and return a proper response
        }

        public function awsb_after_shipping_rate ( $method, $index ) {
            if( is_cart() ) { return; }
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods')[ $index ];
            $chosen_method_id = explode(':', $chosen_shipping_methods);
            $chosen_method_id = reset($chosen_method_id);
            $chosen_method_ids = explode(':', $method->id);
            $chosen_method_ids = reset($chosen_method_ids);
            if( $chosen_method_id != 'woo-baldarp-pickup' ) {
                return;
            } else {
                if( $chosen_method_ids == 'woo-baldarp-pickup') {
                    $pointName = '';
                    $pointId = '';
                    $city = '';
                    $street = '';
                    $streetNum = '';
                    $comment  = '';
                    $latitude = '';
                    $longitude = '';
					$phone = '';
                    if(isset($_COOKIE['cargoPointName'])) {
                        $pointName = $_COOKIE['cargoPointName'];
                    }
                    if(isset($_COOKIE['cargoPointID'])) {
                        $pointId = $_COOKIE['cargoPointID'];
                    }
                    if(isset($_COOKIE['CargoCityName'])) {
                        $city = $_COOKIE['CargoCityName'];
                    }
                    if(isset($_COOKIE['cargoStreetName'])) {
                        $street = $_COOKIE['cargoStreetName'];
                    }
                    if(isset($_COOKIE['cargoStreetNum'])) {
                        $streetNum = $_COOKIE['cargoStreetNum'];
                    }
                    if(isset($_COOKIE['cargoComment'])) {
                        $comment = $_COOKIE['cargoComment'];
                    }
                    if(isset($_COOKIE['cargoLatitude'])) {
                        $latitude = $_COOKIE['cargoLatitude'];
                    }
                    if(isset($_COOKIE['cargoLongitude'])) {
                        $longitude = $_COOKIE['cargoLongitude'];
                    }
                    if(isset($_COOKIE['cargoPhone'])) {
                        $phone = $_COOKIE['cargoPhone'];
                    }
                    echo __("<span class='baldrap-btn' id='mapbutton'> בחירת נקודה </span>
                    <div id='selected_cargo'></div>
                    <input type='hidden' id='DistributionPointID' name='DistributionPointID' value='".$pointId."' >
                    <input type='hidden' id='DistributionPointName' name='DistributionPointName' value='".$pointName."'>
                    <input type='hidden' id='CityName' name='CityName' value='".$city."'>
                    <input type='hidden' id='StreetName' name='StreetName' value='".$street."'>
                    <input type='hidden' id='StreetNum' name='StreetNum' value='".$streetNum."'>
                    <input type='hidden' id='Comment' name='Comment' value='".$comment."' >
                    <input type='hidden' id='cargoPhone' name='cargoPhone' value='".$phone."' >
                    <input type='hidden' id='Latitude' name='Latitude' value='".$latitude."' >
                    <input type='hidden' id='Longitude' name='Longitude' value='".$longitude."'>
                     ");
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
            register_setting('awsb_shipping_api_settings_fg', 'from_stree');
            register_setting('awsb_shipping_api_settings_fg', 'from_stree_name');
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
            //add_submenu_page('loaction-api-ref', 'Settings', 'Settings', 'manage_options', 'loaction_api_settings', array($this, 'settings'));
            add_submenu_page('loaction_api_settings', 'LogFiles', 'LogFiles', 'manage_options', 'cargo_shipping_log', array($this, 'logs'));
        }
        
        public function RenderPage(){
            $this->checkWooCommerce(); ?>
            <div class='wrap'>
                <h2>Shipping Location API - Dashboard</h2>
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