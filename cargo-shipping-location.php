<?php
 /**
 * Plugin Name: Cargo Shipping Location for WooCommerce
 * Plugin URI: https://api.cargo.co.il/Webservice/pluginInstruction
 * Description: Location Selection for Shipping Method for WooCommerce
 * Version: 2.1.3
 * Author: Astraverdes
 * Author URI: https://astraverdes.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cargo-shipping-location-for-woocommerce
 */

if ( !defined( 'ABSPATH' ) ) {
    die;
}

if ( !defined( 'CSLFW_URL' ) ) {
    define( 'CSLFW_URL', plugins_url( '/', __FILE__ ) );
}

if ( !defined( 'CSLFW_PATH' ) ) {
    define( 'CSLFW_PATH', plugin_dir_path( __FILE__ ) );
}

if( !class_exists('CSLFW_Shipping') ) {
    class CSLFW_Shipping {

        function __construct() {
            add_action( 'admin_init', array($this, 'cslfw_shipping_api_settings_init') );

            if ( get_option('shipping_cargo_express') != '' ) {
                add_action( 'woocommerce_shipping_init', array( $this,'cslfw_shipping_method' ) );
                add_filter( 'woocommerce_shipping_methods', array( $this,'cslfw_add_Baldarp_shipping_method' ) );
                add_action( 'woocommerce_after_shipping_rate', array( $this, 'cslfw_after_shipping_rate' ), 20, 2) ;
                add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'custom_checkout_field_update_order_meta' ));
                add_action( 'woocommerce_checkout_process', array( $this, 'action_woocommerce_checkout_process' ),10,1);
                add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'bulk_order_cargo_shipment' ), 10, 3);
                add_action( 'admin_notices', array( $this, 'cargo_bulk_action_admin_notice') );
                add_action( 'woocommerce_checkout_order_processed', array( $this, 'transfer_order_data_for_shipment' ), 10, 1);
                add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_delivery_status_column_header' ), 20 );
                add_action( 'manage_shop_order_posts_custom_column', array( $this,'add_custom_column_content') );
                add_filter( 'post_class', array( $this, 'add_no_link_to_post_class' ) );
                add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'show_shipping_info' ) );
                add_filter( 'woocommerce_locate_template', array( $this, 'intercept_wc_template' ), 10, 3 );
                add_action( 'init', array( $this, 'register_order_status_for_cargo' ) );
                add_filter( 'bulk_actions-edit-shop_order', array( $this, 'custom_dropdown_bulk_actions_shop_order' ), 20, 1 );
                add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
                add_action( 'add_meta_boxes', array( $this, 'remove_shop_order_meta_box' ), 90  );
                add_filter( 'the_content', array( $this, 'replace_text' ) );
                add_action( 'admin_head', array( $this, 'custom_changes_css' ) );
                add_filter( 'woocommerce_account_orders_columns', array( $this, 'add_account_orders_column'), 10, 1 );
                add_action( 'woocommerce_my_account_my_orders_column_order-track', array( $this, 'add_account_orders_column_rows' ) );
                add_action( 'wp_footer', array( $this, 'add_model_footer' ) );
            }
            add_action( 'woocommerce_order_details_after_order_table', array( $this, 'tracking_button' ) );
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this,  'cargo_settings_link' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'cslfw_admin_plugin_scripts' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'cslfw_plugin_scripts' ) );

            add_action( 'wp_ajax_get_order_tracking_details', array( $this,'get_order_tracking_details') );
            add_action( 'wp_ajax_getOrderStatus', array( $this,'getOrderStatusFromCargo' ) );
            add_action( 'wp_ajax_nopriv_getOrderStatus', array( $this, 'getOrderStatusFromCargo' ) );
            add_action( 'wp_ajax_get_delivery_location', array( $this, 'cslfw_ajax_delivery_location' ) );
            add_action( 'wp_ajax_nopriv_get_delivery_location', array( $this, 'cslfw_ajax_delivery_location' ) );
            add_action('wp_ajax_cslfw_send_email', array( $this, 'send_email' ) );

            add_action('wp_ajax_sendOrderCARGO', array( $this, 'send_order_to_cargo' ) );
            add_action('wp_ajax_get_shipment_label', array( $this,'get_shipment_label' ) );
            add_action('woocommerce_new_order', array($this, 'clean_cookies'), 10, 1);

            add_action( 'wp_head', array( $this, 'cslfw_script_checkout' ) );

            if ( is_admin() ) {
                register_activation_hook(__FILE__, array( $this, 'activate'));

                register_deactivation_hook(__FILE__, array( $this, 'cslfw_deactivate'));
                // plugin uninstallation
                register_uninstall_hook(__FILE__, 'cslfw_uninstall');
            }
        }

        function send_email() {
            parse_str($_POST['form_data'], $data);
            $site_url = get_site_url();
            $to = 'dev@astraverdes.com';
            $subject = 'BUG REPORT';
            $body = "<p><strong>REASON: </strong>{$data['reason']}</p></br>";
            $body .= "<p><strong>MESSAGE: </strong>{$data['content']}</p></br>";
            $body .= "<p><strong>URL: </strong>{$site_url}</p></br>";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $headers[] = "From: Me Myself <{$data['email']}>";
            $response = wp_mail( $to, $subject, $body, $headers );
            if ( $response ) {
                echo json_encode(array('error' => false, 'message' => 'Email successfully sent'));
            } else {
                echo json_encode(array('error' => true, 'message' => 'Something went wrong.'));

            }
            wp_die();
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

        function clean_cookies( $order_id ) {
            setcookie("cargoPointID", "", time()-3600);
            setcookie("CargoCityName", "", time()-3600);
            setcookie("cargoPhone", "", time()-3600);
            setcookie("cargoLongitude", "", time()-3600);
            setcookie("cargoLatitude", "", time()-3600);
            setcookie("fullAddress", "", time()-3600);
            setcookie("cargoStreetNum", "", time()-3600);
            setcookie("CargoCityName", "", time()-3600);
            setcookie("cargoPointName", "", time()-3600);
        }

        /**
        * @param $my_text
        * @return mixed
        */
	    function replace_text( $my_text ){
		  return $my_text;
		}

	    function get_order_tracking_details() {
	    	if ( !isset($_POST['shipping_id']) || sanitize_text_field($_POST['shipping_id']) === '' ) {
	    	    echo json_encode( array(
                    'status'    => 'fail',
                    'message'   => __('No shipping id provided. Contact support please.', 'cargo-shipping-location-for-woocommerce')
                ));
	    	    wp_die();
            }

	    	$data['deliveryId'] = (int) $_POST['shipping_id'];
            $result = $this->cargoAPI('https://api.carg0.co.il/Webservice/CheckShipmentStatusAndTime', $data);
            echo json_encode( $result );
			die();
	    }

	    function add_model_footer() {
	    	if( is_account_page() ) { ?>
	    		<div class="modal order-tracking-model" tabindex="-1" role="dialog" style="display: none;">
	                <div class="modal-dialog" role="document" style="max-width: 1000px; width: 100%;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div class="cargo-logo">
                                    <img src="<?php echo esc_url(CSLFW_URL.'assets/image/howitworks.png'); ?>" alt="Cargo" width="60">
                                </div>

	                            <h5 class="modal-title"><?php _e('Order Tracking', 'cargo-shipping-location-for-woocommerce') ?></h5>
	                            <button type="button" class="close js-modal-close" id="modal-close" data-dismiss="modal" aria-label="Close">
	                                <span aria-hidden="true">&times;</span>
	                            </button>
	                        </div>
	                        <div class="modal-body order-details-ajax">
                                <div class="delivery-status" style="padding: 10px 20px; display: none;"></div>
                                <div class="delivery-error woocommerce-error" style="display: none;"></div>
	                        </div>
	                        <div class="modal-footer" style="display: block;">
                                <div id="FlyingCargo_footer" style="display: none;"><?php _e('נקודת איסוף מסומנת:', 'cargo-shipping-location-for-woocommerce') ?>
                                    <div id="FlyingCargo_loc_name"></div>
                                    <button type="button" class="selected-location btn button wp-element-button" id="FlyingCargo_confirm" data-lat="" data-long="" data-fullAdd="" data-disctiPointID="" data-pointName="" data-city="" data-street="" data-streetNum="" data-comment="" data-locationName=""><?php _e('בחירה וסיום', 'cargo-shipping-location-for-woocommerce') ?></button>
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
         * Function for `woocommerce_order_details_after_order_table` action-hook.
         *
         * @param  $order
         *
         * @return void
         */
        function tracking_button( $order ){
            $order_id           = $order->get_id();
            $cargo_delivery_id  = get_post_meta( $order_id, 'cargo_shipping_id', true );
            if ( $cargo_delivery_id )
                if ( is_array($cargo_delivery_id) ) {
                    foreach($cargo_delivery_id as $key => $value) {
                        echo wp_kses_post('<a href="#" class="btn wp-element-button button woocommerce-button js-cargo-track" data-delivery="'. $value .'">' . __('Track Order', 'cargo-shipping-location-for-woocommerce') . '</a>');
                    }
                } else {
                    echo wp_kses_post('<a href="#" class="btn wp-element-button button woocommerce-button js-cargo-track" data-delivery="'. $cargo_delivery_id .'">' . __('Track Order', 'cargo-shipping-location-for-woocommerce') . '</a>');
                }
        }

		/**
        * @param $order
         *
         * Add Track order link in my account page in order tab
        */
		function add_account_orders_column_rows( $order ) {
			$order_id           = $order->get_id();
			$cargo_delivery_id  = get_post_meta( $order_id, 'cargo_shipping_id', true );

			if( $cargo_delivery_id ) {
			    if (is_array($cargo_delivery_id)) {
			        foreach ( $cargo_delivery_id as $key => $value ) {
                        echo wp_kses_post('<a href="#" class="btn woocommerce-button js-cargo-track" data-delivery="' . $value . '">' . __('Track Order', 'cargo-shipping-location-for-woocommerce') . '</a>');
                    }
                } else {
                    echo wp_kses_post('<a href="#" class="btn woocommerce-button js-cargo-track" data-delivery="' . $cargo_delivery_id . '">' . __('Track Order', 'cargo-shipping-location-for-woocommerce') . '</a>');
                }
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
                || trim( get_option('phonenumber_from') ) == '' ) {
				echo json_encode( array("shipmentId" => "", "error_msg" => __('Please enter all details from plugin setting', 'cargo-shipping-location-for-woocommerce') ) );
				exit;
			}

	    	$order_id   = sanitize_text_field($_POST['orderId']);
            $order = wc_get_order($order_id);
            $shippingMethod = @array_shift($order->get_shipping_methods() );
            $shipping_method_id = $shippingMethod['method_id'];
            if ( $shipping_method_id == 'cargo-express' && trim( get_option('shipping_cargo_express') ) == '' ) {
                echo json_encode( array("shipmentId" => "", "error_msg" => __('Cargo Express ID is missing from plugin settings.', 'cargo-shipping-location-for-woocommerce') ) );
                exit;
            }
            if ( $shipping_method_id == 'woo-baldarp-pickup' && trim( get_option('shipping_cargo_box') ) == '' ) {
                echo json_encode( array("shipmentId" => "", "error_msg" => __('Cargo BOX ID is missing from plugin settings.', 'cargo-shipping-location-for-woocommerce') ) );
                exit;
            }

			$args = array(
                'double_delivery'   => (int) sanitize_text_field($_POST['double_delivery']),
                'shipment_type'     => (int) sanitize_text_field($_POST['shipment_type']),
                'no_of_parcel'      => (int) sanitize_text_field($_POST['no_of_parcel']),
                'cargo_cod'         => (int) sanitize_text_field($_POST['cargo_cod']),
                'cargo_cod_type'    => (int) sanitize_text_field($_POST['cargo_cod_type'])
            );

            $response = $this->createShipment($order_id, $args);

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
						'<img src="'.CSLFW_URL."assets/image/howitworks.png".'" alt="Cargo" width="100" style="width:50px;">CARGO',
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
	    public function remove_shop_order_meta_box() {
		    remove_meta_box( 'postcustom', 'shop_order', 'normal' );
		}

	    public function render_meta_box_content( $post ) {
	        // Use get_post_meta to retrieve an existing value from the database.
            $cargo_shipping_id = get_post_meta( $post->ID, 'cargo_shipping_id', true );
	 		$order              = wc_get_order($post->ID);
		    $shippingMethod     = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];
            $payment_method     = $order->get_payment_method();
            $payment_method_check = get_option( 'cslfw_cod_check' ) ?  get_option( 'cslfw_cod_check' ) : 'cod';

			if ( $shipping_method_id === 'cargo-express' || $shipping_method_id === 'woo-baldarp-pickup') {
                if ( !$cargo_shipping_id ) : ?>
                <div class="cargo-button">
                    <strong><?php _e('Double Delivery', 'cargo-shipping-location-for-woocommerce') ?></strong>
                    <label for="cargo_double-delivery">
                        <input type="checkbox" name="cargo_double_delivery" id="cargo_double-delivery" />
                        <span><?php _e('Yes', 'cargo-shipping-location-for-woocommerce') ?></span>
                    </label>
                </div>
                <?php
                    if ( $shipping_method_id === 'cargo-express' ) : ?>
                <div class="cargo-button">
                    <strong><?php _e('Cash on delivery', 'cargo-shipping-location-for-woocommerce') ?></strong>
                    <label for="cargo_cod">
                        <input type="checkbox" name="cargo_cod" id="cargo_cod" <?php if ($payment_method === $payment_method_check) echo esc_attr('checked'); ?> />
                        <span><?php _e('Yes', 'cargo-shipping-location-for-woocommerce') ?></span>
                    </label>
                </div>
                <?php
                    $cod_type_array = array(
                        '0' => __('Cash (Default)', 'cargo-shipping-location-for-woocommerce'),
                        '1' => __('Cashier\'s check', 'cargo-shipping-location-for-woocommerce'),
                        '2' => __('Check', 'cargo-shipping-location-for-woocommerce'),
                        '3' => __('All Payment Methods', 'cargo-shipping-location-for-woocommerce')
                    );
                ?>
                <div class="cargo-button cargo_cod_type" style="display: <?php echo esc_html($payment_method === $payment_method_check ? 'block' : 'none' ) ?>">
                    <strong><?php _e('Cash on delivery Type', 'cargo-shipping-location-for-woocommerce') ?></strong>
                    <?php foreach ($cod_type_array as $key=>$value) : ?>
                        <label for="cargo_cod_type_<?php echo esc_attr($key) ?>">
                            <input type="radio" name="cargo_cod_type" id="cargo_cod_type_<?php echo esc_attr($key) ?>" value="<?php echo esc_attr($key) ?>" />
                            <span><?php echo esc_html($value) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php endif; // End express check ?>
                <div class="cargo-radio">
                    <strong><?php _e('Shipment Type', 'cargo-shipping-location-for-woocommerce') ?></strong>
                    <label for="cargo_shipment_type_regular">
                        <input type="radio" name="cargo_shipment_type" id="cargo_shipment_type_regular" checked value="1" />
                        <span><?php _e('Regular', 'cargo-shipping-location-for-woocommerce') ?></span>
                    </label>
                    <?php if ( $shipping_method_id !== 'woo-baldarp-pickup' ) : ?>
                    <label for="cargo_shipment_type_pickup">
                        <input type="radio" name="cargo_shipment_type" id="cargo_shipment_type_pickup" value="2" />
                        <span><?php _e('Pickup', 'cargo-shipping-location-for-woocommerce') ?></span>
                    </label>
                    <?php endif; ?>
                </div>

                <div class="cargo-button">
                    <strong><?php _e('Packages', 'cargo-shipping-location-for-woocommerce') ?></strong>
                    <input type="number" name="cargo_packages" id="cargo_packages" value="1" min="1" max="100" style="max-width: 80px;"/>
                </div>

				<div class="cargo-button">
					<a href="#"
                       class="submit-cargo-shipping"
                       data-id="<?php echo esc_attr($post->ID); ?>"><?php _e('שלח ל CARGO', 'cargo-shipping-location-for-woocommerce') ?></a>
				</div>

                <?php else :
                    $cargo_shipping_id = is_array($cargo_shipping_id) ? implode(',', $cargo_shipping_id) : $cargo_shipping_id;
                ?>
                    <div class="cargo-button">
                        <div><strong><?php _e('Shipping ID\'s: ', 'cargo-shipping-location-for-woocommerce' ) ?></strong><?php echo esc_html($cargo_shipping_id) ?></div>
                        <a href="#" class="label-cargo-shipping"  data-id="<?php echo esc_attr($cargo_shipping_id); ?>"><?php _e('הדפס תווית', 'cargo-shipping-location-for-woocommerce') ?></a>
                    </div>
                <?php endif; ?>



				<div class="checkstatus-section">
					<?php
                        $customerCode       = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
                        $type               = $shipping_method_id == 'woo-baldarp-pickup' ? "BOX" : "EXPRESS";
                        $cargo_shipping_id = get_post_meta( $post->ID, 'cargo_shipping_id', true );
                        if ( $cargo_shipping_id ) {
                            if ( is_array($cargo_shipping_id) ) {
                                foreach ($cargo_shipping_id as $key => $value) {
                                    echo wp_kses_post("<a href='#' class='btn btn-success send-status' style='margin-bottom: 10px;' data-orderlist='0' data-id=" . $post->ID . " data-customerCode=" . $customerCode . " data-type=" . $type . " data-deliveryId=" . $value . ">" . __('בקש סטטוס משלוח', 'cargo-shipping-location-for-woocommerce') . " $value</a>");
                                }
                            } else {
                                echo wp_kses_post("<a href='#' class='btn btn-success send-status' data-orderlist='0' data-id=".$post->ID." data-customerCode=".$customerCode." data-type=".$type." data-deliveryId=".$cargo_shipping_id.">בקש סטטוס משלוח</a>");
                            }
                        }
					?>
				</div>
				<?php if ( $shipping_method_id == 'woo-baldarp-pickup' ) {
                    $DistributionPointName = get_post_meta($post->ID,'cargo_DistributionPointName',TRUE) ?? get_post_meta($post->ID,'DistributionPointName',TRUE);
                    $DistributionPointID = get_post_meta($post->ID,'cargo_DistributionPointID',TRUE) ?? get_post_meta($post->ID,'DistributionPointID',TRUE);
				    ?>
					<div>
						<h3 style="margin-bottom: 5px;"><?php _e('Cargo Store Details', 'cargo-shipping-location-for-woocommerce') ?></h3>
                        <?php if (get_option('cargo_box_style') === 'cargo_automatic' && !$DistributionPointName) { ?>
                            <p><?php _e('Details will appear after sending to cargo.', 'cargo-shipping-location-for-woocommerce') ?></p>
                        <?php } else { ?>
						<h2 style="padding:0;">
						    <strong><?php echo wp_kses_post( $DistributionPointName ) ?> : <?php echo wp_kses_post($DistributionPointID ) ?></strong>
						</h2>
						<h4 style="margin:0;"><?php echo wp_kses_post( get_post_meta($post->ID,'cargo_StreetNum', TRUE).' '.get_post_meta( $post->ID,'cargo_StreetName',TRUE).' '.get_post_meta($post->ID,'cargo_CityName',TRUE) ) ?></h4>
						<h4 style="margin:0;"><?php echo wp_kses_post( get_post_meta($post->ID,'cargo_Comment', TRUE) ) ?></h4>
						<h3 style="margin:0;"><?php echo wp_kses_post( get_post_meta($post->ID,'cargo_cargoPhone',TRUE) ) ?></h3>
                        <?php } ?>
					</div>
				<?php }
			} else {
			    esc_html_e('Shipping type is not related to cargo', 'cargo-shipping-location-for-woocommerce');
            }
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
		            $new_actions['mark_send-cargo'] = __( 'Send to CARGO', 'cargo-shipping-location-for-woocommerce' );
		        }
		    }

		    return $new_actions;
		}

        /**
         *
         */
		function get_shipment_label() {
			$options = [
				'body'        => wp_json_encode( array(
                                                    'deliveryId' => sanitize_text_field($_POST['shipmentId']),
                                                    'shipmentId' => sanitize_text_field($_POST['shipmentId'])
                                                    )
                                                ),
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
//			var_dump($arrData);
//			var_dump(sanitize_text_field($_POST['shipmentId']));
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
                'deliveryId' => (int) sanitize_text_field($_POST['deliveryId']),
                'DeliveryType' => sanitize_text_field($_POST['type']),
                'customerCode' => sanitize_text_field($_POST['customerCode']),
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
					update_post_meta(sanitize_text_field($_POST['orderId']), 'get_status_cargo', (int) $data['deliveryStatus']);
					update_post_meta(sanitize_text_field($_POST['orderId']), 'get_status_cargo_text', $data['DeliveryStatusText']);
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
                $cargo_shipping_id = is_array( $cargo_shipping_id ) ? implode(',', $cargo_shipping_id) : $cargo_shipping_id;
		        echo wp_kses_post('<p><strong>'.__('מזהה משלוח', 'cargo-shipping-location-for-woocommerce').':</strong> ' . $cargo_shipping_id . '</p>');
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
        public function add_custom_column_content( $column ) {
		    global $post;
			$order              = wc_get_order($post->ID);
			$shippingMethod     = @array_shift($order->get_shipping_methods());
        	$shipping_method_id = $shippingMethod['method_id'];
			$customerCode = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');

			if ( $shipping_method_id == 'cargo-express' || $shipping_method_id == 'woo-baldarp-pickup' || get_option('send_to_cargo_all')) {
				if ( 'delivery_status' === $column ) {
					$type = $shipping_method_id == 'woo-baldarp-pickup' ? "BOX" : "EXPRESS";
                    $cargo_shipping_id = get_post_meta( $post->ID, 'cargo_shipping_id', true );

					if ( $cargo_shipping_id ) {
                        if ( get_post_meta($post->ID, 'get_status_cargo', true) ) {
                            echo wp_kses_post('<p>Status - ' . get_post_meta($post->ID, 'get_status_cargo_text', true) . '</p>');
                        }
                        if ( is_array($cargo_shipping_id) ) {
                            foreach ($cargo_shipping_id as $key => $value) {
                                echo wp_kses_post("<a href='#' class='btn btn-success send-status' style='margin-bottom: 5px;' data-orderlist='1' data-id=".$post->ID." data-customerCode=".$customerCode."  data-type=".$type." data-deliveryId=".$value.">$value  בדוק מצב הזמנה</a>");
                            }
                        } else {
                            echo wp_kses_post("<a href='#' class='btn btn-success send-status' data-orderlist='1' data-id=".$post->ID." data-customerCode=".$customerCode." data-type=".$type." data-deliveryId=".$cargo_shipping_id."> בדוק מצב הזמנה</a>");
                        }
					}
				}

				if ( 'send_to_cargo' === $column ) {
					if ( get_post_meta($post->ID,'cargo_shipping_id',true) ) {
					        $cargo_shipping_id = get_post_meta($post->ID, 'cargo_shipping_id',true);
					        $cargo_shipping_id = is_array($cargo_shipping_id) ? implode(',', $cargo_shipping_id) : $cargo_shipping_id;
                            echo wp_kses_post("<p>". $cargo_shipping_id . "</p>");
                            echo wp_kses_post('<a  href="#" class="btn btn-success label-cargo-shipping" data-id="'.$cargo_shipping_id.'">הדפס תווית</a>');
                        } else {
						echo wp_kses_post("<a href='#' class='btn btn-success submit-cargo-shipping' data-id=".$post->ID." >שלח  לCARGO</a>");
					}
				}
			}
		}

        public function add_order_delivery_status_column_header($columns){

		    $new_columns = array();

		    foreach ( $columns as $column_name => $column_info ) {

		        $new_columns[ $column_name ] = $column_info;

		        if ( 'order_status' === $column_name ) {
		            $new_columns['delivery_status'] = __( 'בדוק מצב הזמנה', 'cargo-shipping-location-for-woocommerce' );
		            $new_columns['send_to_cargo'] = __( 'שלח משלוח לCARGO', 'cargo-shipping-location-for-woocommerce' );
		            $new_columns['delivery_status'] = __( 'סטטוס משלוח', 'cargo-shipping-location-for-woocommerce' );
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
    			update_post_meta( $order_id, 'cargo_DistributionPointID', sanitize_text_field($_POST['DistributionPointID']) );
    			update_post_meta( $order_id, 'cargo_DistributionPointName', sanitize_text_field($_POST['DistributionPointName']) );
    			update_post_meta( $order_id, 'cargo_CityName', sanitize_text_field($_POST['CityName']) );
    			update_post_meta( $order_id, 'cargo_StreetName', sanitize_text_field($_POST['StreetName']) );
    			update_post_meta( $order_id, 'cargo_StreetNum', sanitize_text_field($_POST['StreetNum']) );
    			update_post_meta( $order_id, 'cargo_cargoPhone', sanitize_text_field($_POST['cargoPhone']) );
    			update_post_meta( $order_id, 'cargo_Comment', sanitize_text_field($_POST['Comment']) );
        	}
        }

        /**
         * Add Bulk Action in admin panel.
         */
        function cargo_bulk_action_admin_notice() {
            global $pagenow;

            if ( 'edit.php' === $pagenow
                && isset($_GET['post_type'])
                && 'shop_order' === $_GET['post_type']
                && isset( $_GET['cargo_send']) ) {

                $count = intval( sanitize_text_field($_REQUEST['processed_count']) );

                if( isset($_REQUEST['processed_ids']) ){
                    echo wp_kses_post( printf( '<div class="notice notice-success fade is-dismissible"><p>' .
                        _n( '%s Order Sent for Shipment',
                            '%s Orders Sent For Shipment',
                            $count,
                            'woocommerce'
                        ) . '</p></div>', $count ) );
                }
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
            if ( false !== strpos( $action, 'mark_' ) ) {
                $action_name     = substr( $action, 5 ); // Get the status name from action.
                if ($action_name === 'send-cargo') {
                    $is_cargo = 1;
                    foreach ($ids as $key => $order_id) {
						$this->createShipment($order_id);
                    }
                }
            }

			$redirect_to = add_query_arg(
                array(
                    'cargo_send'     => $is_cargo,
                    'old_stutus'     => $old_status,
                    'processed_count' => count( $ids ),
                    'processed_ids'  => implode( ',', $ids ),
                ),
                $redirect_to );
            return $redirect_to;
        }

		function createShipment($order_id, $args = []) {

			$order = wc_get_order($order_id);
			if( get_post_meta($order_id,'cargo_shipping_id',TRUE) ){
				return;
			}
			$orderData          = $order->get_data();
			$shippingMethod     = @array_shift($order->get_shipping_methods());
			$shipping_method_id = $shippingMethod['method_id'];
            $cargo_box_style    = get_option('cargo_box_style');

			$CarrierName    = $shipping_method_id == 'woo-baldarp-pickup' ? 'B0X' : 'EXPRESS';
			$customerCode   = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');

			$name = $orderData['shipping']['first_name'] ? $orderData['shipping']['first_name']. ' ' . $orderData['shipping']['last_name'] : $orderData['billing']['first_name']. ' ' . $orderData['billing']['last_name'];
            $data['Method'] = "ship";

            $data['Params'] = array(
                'shipping_type'         => isset($args['shipping_type']) ? $args['shipping_type'] : 1,
                'doubleDelivery'        => isset($args['double_delivery']) ? $args['double_delivery'] : 1,
                'noOfParcel'            => isset($args['no_of_parcel']) ? $args['no_of_parcel'] : 0,
                'TotalValue'            => $order->get_total(),
                'TransactionID'         => $order_id,
                'ContentDescription'    => "",
                'CashOnDelivery'        => isset($args['cargo_cod']) ? $args['cargo_cod'] : 0,
                'CarrierName'           => "CARGO",
                'CarrierService'        => $CarrierName,
                'CarrierID'             => $shipping_method_id == 'woo-baldarp-pickup' ? 0 : 1,
                'OrderID'               => $order_id,
                'PaymentMethod'         => $orderData['payment_method'],
                'Note'                  => $orderData['customer_note'],
                'customerCode'          => $customerCode,

                'to_address' => array(
                    'name'      => $name,
                    'company'   => $orderData['shipping']['company'] ?? $orderData['billing']['company'],
                    'street1'   => !empty( $orderData['shipping']['address_1'] ) ? $orderData['shipping']['address_1'] : $orderData['billing']['address_1'],
                    'street2'   => !empty( $orderData['shipping']['address_2'] ) ? $orderData['shipping']['address_2'] : $orderData['billing']['address_2'],
                    'city'      =>  !empty( $orderData['shipping']['city'] ) ? $orderData['shipping']['city'] : $orderData['billing']['city'],
                    'state'     =>  !empty( $orderData['shipping']['state'] ) ? $orderData['shipping']['state'] : $orderData['billing']['state'],
                    'zip'       =>  !empty( $orderData['shipping']['postcode'] ) ? $orderData['shipping']['postcode'] : $orderData['billing']['postcode'],
                    'country'   =>  !empty( $orderData['shipping']['country'] ) ? $orderData['shipping']['country'] : $orderData['billing']['country'],
                    'phone'     =>  !empty( $orderData['shipping']['phone'] ) ? $orderData['shipping']['phone'] : $orderData['billing']['phone'],
                    'email'     =>  !empty( $orderData['shipping']['email'] ) ? $orderData['shipping']['email'] : $orderData['billing']['email'],
                    'floor'     => get_post_meta($order_id, 'cargo_floor', TRUE),
                    'appartment' => get_post_meta($order_id, 'cargo_apartment', TRUE),
                ),

                'from_address' => array(
                    'name'      => $name,
                    'company'   => get_option( 'website_name_cargo' ),
                    'street1'   => get_option('from_street'),
                    'street2'   => get_option('from_street_name'),
                    'city'      => get_option('from_city'),
                    'state'     => !empty( $orderData['shipping']['state'] ) ? $orderData['shipping']['state'] : $orderData['billing']['state'],
                    'zip'       => !empty( $orderData['shipping']['postcode'] ) ? $orderData['shipping']['postcode'] : $orderData['billing']['postcode'],
                    'country'   => !empty( $orderData['shipping']['country'] ) ? $orderData['shipping']['country'] : $orderData['billing']['country'],
                    'phone'     => get_option('phonenumber_from'),
                    'email'     => !empty( $orderData['shipping']['email'] ) ? $orderData['shipping']['email'] : $orderData['billing']['email'],
                )
            );

            if ( $data['Params']['CashOnDelivery'] ) {
                $data['Params']['CashOnDeliveryType'] = isset($args['cargo_cod_type']) ? $args['cargo_cod_type'] : 0;
            }

            if ( $shipping_method_id == 'woo-baldarp-pickup' ) {
                if ( $cargo_box_style !== 'cargo_automatic' ) {
                    $data['Params']['to_address'] = array(
                        'name'      => $name,
                        'company'   => get_option( 'website_name_cargo' ),
                        'street1'   => get_post_meta($order_id, 'StreetNum', TRUE),
                        'street2'   => get_post_meta($order_id, 'StreetName', TRUE),
                        'city'      => get_post_meta($order_id, 'CityName', TRUE),
                        'state'     => "",
                        'zip'       => "",
                        'country'   => "",
                        'phone'     =>  !empty( $orderData['shipping']['phone'] ) ? $orderData['shipping']['phone'] : $orderData['billing']['phone'],
                        'email'     =>  !empty( $orderData['shipping']['email'] ) ? $orderData['shipping']['email'] : $orderData['billing']['email'],
                    );

                    $data['Params']['boxPointId'] = get_post_meta($order_id, 'DistributionPointID', TRUE) ? get_post_meta($order_id, 'DistributionPointID', TRUE) :  get_post_meta($order_id, 'cargo_DistributionPointID', TRUE);
                } else {
                    $address = $data['Params']['to_address']['city'] . ',' . $data['Params']['to_address']['street2'] . ' ' . $data['Params']['to_address']['street1'];
                    $geocoding = $this->cargoAPI('https://api.carg0.co.il/Webservice/cargoGeocoding', array('address' => $address) );
                    if ( $geocoding->error === false ) {

                        if ( !empty($geocoding->data->results) ) {
                            $geocoding = $geocoding->data->results[0]->geometry->location;
                            $closest_point = $this->cargoAPI('https://api.carg0.co.il/Webservice/findClosestPoints', array('lat' => $geocoding->lat, 'long' => $geocoding->lng, 'distance' => 30) );

                            if ( $closest_point->error === false ) {
                                if ( !empty($closest_point->closest_points) ) {
                                    // THE SUCCESS FOR DETERMINE CARGO POINT ID IN AUTOMATIC MODE.
                                    $chosen_point = $closest_point->closest_points[0]->point_details;
                                    $data['Params']['boxPointId'] = $chosen_point->DistributionPointID;

                                    $order->update_meta_data( 'cargo_DistributionPointID', sanitize_text_field($chosen_point->DistributionPointID) );
                                    $order->update_meta_data( 'cargo_DistributionPointName', sanitize_text_field($chosen_point->DistributionPointName) );
                                    $order->update_meta_data( 'cargo_CityName', sanitize_text_field($chosen_point->CityName) );
                                    $order->update_meta_data( 'cargo_StreetName', sanitize_text_field($chosen_point->StreetName) );
                                    $order->update_meta_data( 'cargo_StreetNum', sanitize_text_field($chosen_point->StreetNum) );
                                    $order->update_meta_data( 'cargo_Comment', sanitize_text_field($chosen_point->Comment) );
                                    $order->update_meta_data( 'cargoPhone', sanitize_text_field($chosen_point->Phone) );
                                    $order->update_meta_data( 'cargo_Latitude', sanitize_text_field($chosen_point->Latitude) );
                                    $order->update_meta_data( 'cargo_Longitude', sanitize_text_field($chosen_point->Longitude) );
                                } else {
                                    $this->add_log_message("ERROR.FAIL: 'No closest points found by the radius." . PHP_EOL );
                                    return array('shipmentId' => "", 'error_msg' => 'No closest points found by the radius.');
                                }
                            } else {
                                $this->add_log_message("ERROR.FAIL: 'Failed to find closest points." . PHP_EOL );
                                return array('shipmentId' => "", 'error_msg' => 'Failed to find closest points. Contact support please.');
                            }
                        } else {
                            $this->add_log_message("ERROR.FAIL: Empty geocoding data." . PHP_EOL );
                            return array('shipmentId' => "", 'error_msg' => 'Empty geocoding data.');
                        }

                    } else {
                        $this->add_log_message("ERROR.FAIL: Address geocoding fail for address $address" . PHP_EOL );
                        return array('shipmentId' => "", 'error_msg' => 'Failed to create geocoding. Contact support please.');
                    }

                }
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

                $message .= "ORDER ID : $order_id | DELIVERY ID  : {$response['shipmentId']} | SENT TO CARGO ON : ".date('Y-m-d H:i:d')." SHIPMENT TYPE : $CarrierName | CUSTOMER CODE : $customerCode" . PHP_EOL;

                if( $shipping_method_id == 'woo-baldarp-pickup' ) {
                    $box_point_id = get_post_meta($order_id,'cargo_DistributionPointID',TRUE) ?? get_post_meta($order_id,'DistributionPointID',TRUE);
                    $message    .= "CARGO BOX POINT ID : $box_point_id". PHP_EOL;
				}
                $order->save();
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
            $cargo_box_style = get_option('cargo_box_style');

            if ( $chosen_method_id === 'woo-baldarp-pickup' && $cargo_box_style !== 'cargo_automatic') {
                if ( sanitize_text_field($_POST['DistributionPointID']) === '' ) {
                    wc_add_notice( __( 'Please select Shipping Collection Points' ), 'error' );
                }
            }

        }

        public function cslfw_admin_plugin_scripts() {
            $screen = get_current_screen();

            $screen_id    = $screen ? $screen->id : '';

            if($screen_id === 'toplevel_page_loaction_api_settings') {
                wp_enqueue_style( 'admin-baldarp-style', CSLFW_URL .'assets/css/admin-baldarp-style.css');
            }
            wp_enqueue_style( 'admin-baldarp-styles', CSLFW_URL .'assets/css/admin-baldarp-styles.css');
            	wp_enqueue_script( 'cargo-admin-script', CSLFW_URL .'assets/js/admin/admin-baldarp-script.js', array(), '', true);
            	wp_localize_script( 'cargo-admin-script', 'admin_cargo_obj',
                array(
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'ajax_nonce'    => wp_create_nonce( 'cslfw_shipping_nonce' ),
					'path' => CSLFW_URL,
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
            $shippingMethod = explode(':', sanitize_text_field($_POST['shipping_method'][0]) );
            if( reset($shippingMethod) == 'woo-baldarp-pickup') {

                if ( isset($_POST['DistributionPointID']) ) {
                    $order->update_meta_data( 'cargo_DistributionPointID', sanitize_text_field($_POST['DistributionPointID']) );
                }
                if ( isset( $_POST['DistributionPointName'] ) ) {
                    $order->update_meta_data( 'cargo_DistributionPointName', sanitize_text_field($_POST['DistributionPointName']) );
                }
                if ( isset( $_POST['CityName'] ) ) {
                    $order->update_meta_data( 'cargo_CityName', sanitize_text_field($_POST['CityName']) );
                }
                if( isset( $_POST['StreetName'] ) ) {
                    $order->update_meta_data( 'cargo_StreetName', sanitize_text_field($_POST['StreetName']) );
                }
                if ( isset( $_POST['StreetNum'] ) ) {
                    $order->update_meta_data( 'cargo_StreetNum', sanitize_text_field($_POST['StreetNum']) );
                }
                if ( isset( $_POST['Comment'] ) ) {
                    $order->update_meta_data( 'cargo_Comment', sanitize_text_field($_POST['Comment']) );
                }
                if ( isset( $_POST['Latitude'] ) ) {
                    $order->update_meta_data( 'cargo_Latitude', sanitize_text_field($_POST['Latitude']) );
                }
                if ( isset( $_POST['Longitude'] ) ) {
                    $order->update_meta_data( 'cargo_Longitude', sanitize_text_field($_POST['Longitude']) );
                }
            }
        }

        public function cslfw_plugin_scripts() {
			if ( is_cart() || is_checkout() ) {
				wp_enqueue_script( 'baldarp-script', CSLFW_URL .'assets/js/baldarp-script.js', array(), '', true);
				wp_localize_script( 'baldarp-script', 'baldarp_obj',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'ajax_nonce'    => wp_create_nonce( 'cslfw_shipping_nonce' ),
					)
				);

                if ( get_option('cslfw_google_api_key') ) {
                    $maps_key = get_option('cslfw_google_api_key');
                    wp_enqueue_script( 'baldarp-map-jquery', "https://maps.googleapis.com/maps/api/js?key=$maps_key&language=he&libraries=places&v=weekly", null, null, true );
                }
				wp_enqueue_style('badarp-front-css', CSLFW_URL.'assets/css/front.css');

				if ( get_option('bootstrap_enalble') == 1 ) {
					wp_enqueue_script( 'baldarp-bootstrap-jquery',  CSLFW_URL .'assets/js/boostrap_bundle.js', array(), '', false );
					wp_enqueue_style('badarp-bootstrap-css', CSLFW_URL .'assets/css/boostrap_min.css');
				}
			}

            if ( is_account_page() ) {
                wp_enqueue_style('badarp-front-css', CSLFW_URL.'assets/css/front.css');

                wp_enqueue_script( 'cargo-order', CSLFW_URL .'assets/js/cargo-order.js', array(), '', true );
                wp_localize_script( 'cargo-order', 'cargo_obj',
                    array(
                        'ajaxurl' => admin_url( 'admin-ajax.php' ),
                        'ajax_nonce'    => wp_create_nonce( 'cslfw_shipping_nonce' ),
                    )
                );
            }

        }

        public function cslfw_script_checkout() {
			if ( is_checkout() || is_cart() ){
			    $cargo_size = get_option('cslfw_map_size');
			    $cargo_size_custom = get_option('cslfw_custom_map_size');
			    $cargo_size_custom = $cargo_size === 'map_custom' ? "style=width:$cargo_size_custom" : '';
			?>
            <input type="hidden" id="default_markers" value="<?php echo CSLFW_URL.'assets/image/cargo-icon-svg.svg' ?>" >
        	<input type="hidden" id="selected_marker" value="<?php echo CSLFW_URL.'assets/image/selected_new.png' ?>" >
            <div class="modal" id="mapmodelcargo" tabindex="-1" role="dialog" style="display:none;">
                <div class="modal-dialog <?php echo esc_attr($cargo_size) ?>"  role="document">
                    <div class="modal-content " <?php echo esc_attr($cargo_size_custom) ?>>
                        <div class="modal-header">
                            <div class="cargo-logo">
                                <img src="<?php echo CSLFW_URL.'assets/image/howitworks.png'; ?>" alt="Cargo" width="60">
                            </div>

                            <div class="modal-search" style="direction: rtl;">
                                <a href="javascript:void(0);" class="open-how-it-works">?</a>
                                <div class="form-row">
                                    <div class='cargo_input_div'>
                                        <input type='text' id='cargo-location-input' placeholder='יש להזין את הכתובת שלך על מנת להציג נקודות חלוקה קרובות..''>
                                        <button class='cargo_address_check'>איתור</button>
                                    </div>
                                </div>
                            </div>


                            <button type="button" class="close js-modal-close" id="modal-close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class='cargo_content_main'>
                                <a class='js-toggle-locations-list' style='display: none'>סגירה</a>
                                <div class='cargo_location_list'>
                                    <div class='cargo_list_msg'>יש מלא את כתובת שלך בשדה החיפוש</div>
                                </div>
                                <div id="map" ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal descript" tabindex="-1" role="dialog" style="margin-top: 0px;display:none;    z-index: 2222222222;" >
                <div class="modal-dialog" role="document" style="max-width: 700px; width: 100%;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="cargo-logo">
                                <img src="<?php echo CSLFW_URL.'assets/image/howitworks.png'; ?>" alt="Cargo" width="60">
                            </div>
                            <h5 class="modal-title"><?php _e('CARGO BOX - איך זה עובד', 'cargo-shipping-location-for-woocommerce') ?></h5>
                            <button type="button" class="close js-modal-close" id="modal-close-desc" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="direction: rtl;">
                            <div><?php _e(' CARGO BOX ', 'cargo-shipping-location-for-woocommerce') ?></div>
                            <div><?php _e('נקודות החלוקה שלנו בפריסה ארצית לנוחיותכם,', 'cargo-shipping-location-for-woocommerce') ?></div>
                            <div><p><?php _e('אוספים את החבילה בדרך הקלה והמהירה ביותר!', 'cargo-shipping-location-for-woocommerce') ?></p></div>
							<div><p><?php _e('איסוף החבילה שלכם יתבצע בנקודת חלוקה הקרובה לביתכם או למקום עבודתכם, היכן שתבחרו, ללא המתנה לשליח, ללא צורך בזמינות, בצורה היעילה, הזולה והפשוטה ביותר', 'cargo-shipping-location-for-woocommerce') ?></p></div>
							<div><?php _e('כמה פשוט? ככה פשוט-', 'cargo-shipping-location-for-woocommerce') ?></div>
							<div><?php _e('בוחרים נקודת חלוקה שמתאימה לכם', 'cargo-shipping-location-for-woocommerce') ?></div>
							<div><?php _e('כאשר החבילה שלכם מגיעה ליעד אתם מקבלים SMS ומייל ', 'cargo-shipping-location-for-woocommerce') ?></div>
							<div><?php _e('ומגיעים לאסוף את החבילה ', 'cargo-shipping-location-for-woocommerce') ?></div>
                        </div>
                    </div>
                </div>
            </div>
           <?php }
        }

        public function cslfw_ajax_delivery_location() {
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

        function cargoAPI($url, $data = []) {

            $args = array(
                'method'      => 'POST',
                'timeout'     => 45,
                'httpversion' => '1.1',
                'blocking'    => true,
                'headers' => array(
                    'Content-Type: application/json'
                ),
            );
            if ( $data ) $args['body'] = json_encode($data);
            $response   = wp_remote_post($url, $args);
            $response   = wp_remote_retrieve_body($response) or die("Error: Cannot create object");
            return json_decode( $response );
        }

        public function cslfw_after_shipping_rate( $method, $index ) {
            if( is_cart() ) { return; }
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods')[ $index ];
            $chosen_method_id = explode(':', $chosen_shipping_methods);
            $chosen_method_id = reset($chosen_method_id);
            $chosen_method_ids = explode(':', $method->id);
            $chosen_method_ids = reset($chosen_method_ids);
            if ( $chosen_method_id != 'woo-baldarp-pickup' ) {
                return;
            } else {
                if ( $chosen_method_ids == 'woo-baldarp-pickup') {
                    $pointId    = isset($_COOKIE['cargoPointID']) ? sanitize_text_field($_COOKIE['cargoPointID']) : '';
                    $city       = isset($_COOKIE['CargoCityName']) ? sanitize_text_field($_COOKIE['CargoCityName']) : '';
                    $city_dd   = isset($_COOKIE['CargoCityName_dropdown']) ? sanitize_text_field($_COOKIE['CargoCityName_dropdown']) : '';
                    $lat       = isset($_COOKIE['cargoLatitude']) ? sanitize_text_field($_COOKIE['cargoLatitude']) : '';
                    $lng       = isset($_COOKIE['cargoLongitude']) ? sanitize_text_field($_COOKIE['cargoLongitude']) : '';

                    $cargo_box_style = get_option('cargo_box_style');
                    ?>
                    <div class="cargo-map-wrap">
                        <?php if ($cargo_box_style === 'cargo_map') : ?>
                        <a class='baldrap-btn btn button wp-element-button' id='mapbutton'><?php _e(' בחירת נקודה', 'cargo-shipping-location-for-woocommerce') ?></a>
                        <div id='selected_cargo'></div>
                    <?php elseif ( ($cargo_box_style === 'cargo_dropdowns') ) :
                        $cities = json_decode(json_encode( $this->cargoAPI("https://data.gov.il/api/3/action/datastore_search?resource_id=5c78e9fa-c2e2-4771-93ff-7f400a12f7ba&limit=1500") ), 1);

                            if ( $cities['success'] ) {
                                $cities = array_map(function($element){
                                    return $element['שם_ישוב'];
                                }, $cities['result']['records']);
                                sort($cities, SORT_STRING );
                            ?>
                                <p class="form-row form-row-wide">
                                    <label for="cargo_city">
                                        <span><?php _e('בחירת עיר', 'cargo-shipping-location-for-woocommerce') ?></span>
                                    </label>

                                    <select name="cargo_city" id="cargo_city" class="select2">
                                        <option><?php _e('נא לבחור עיר', 'cargo-shipping-location-for-woocommerce') ?></option>
                                        <?php foreach ($cities as $key => $value) : ?>
                                            <option value="<?php echo esc_attr($value) ?>" <?php if (trim($city_dd) === trim( $value ) ) echo 'selected="selected"'; ?>><?php echo esc_html($value) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                            <?php }
                                $points = $this->cargoAPI("https://api.carg0.co.il/Webservice/findClosestPoints", array('lat' => $lat, 'long' => $lng));
                                if ( $points->error === false && !empty($points->closest_points) ) {
                            ?>
                                <div class="form-row form-row-wide">
                                    <p class="select-wrap w-100">
                                        <label for="cargo_pickup_point">
                                            <span><?php _e('בחירת נקודת חלוקה', 'cargo-shipping-location-for-woocommerce') ?></span>
                                        </label>
                                        <select name="cargo_pickup_point" id="cargo_pickup_point" class="select2 w-100">
                                            <?php foreach ($points->closest_points as $key => $value) :
                                                $point = $value->point_details;
                                                ?>
                                                <option value="<?php echo esc_attr($point->DistributionPointID) ?>" <?php if ($pointId === $point->DistributionPointID) echo 'selected="selected"' ?>>
                                                    <?php echo esc_html($point->DistributionPointName) ?>, <?php echo esc_html($point->CityName) ?>, <?php echo esc_html($point->StreetName) ?> <?php echo esc_html($point->StreetNum) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </p>
                                </div>
                                <?php } else { ?>
                                    <p class="woocommerce-info 1">לא נמצאו כתובות ברדיוס של 5 ק״מ נא לבחור עיר אחרת</p>
                                <?php } ?>
                    <?php else : ?>
                    <?php endif; ?>
                        <?php
                            if ( !empty($pointId) ) {
                                $chosen_point = $this->cargoAPI("https://api.carg0.co.il/Webservice/getPickUpPoints", array('pointId' => $pointId));
                                $chosen_point = $chosen_point->PointsDetails[0];
                            }
                            if ($cargo_box_style !== 'cargo_automatic') :
                        ?>
                            <input type='hidden' id='DistributionPointID' name='DistributionPointID' value='<?php echo esc_attr( $chosen_point->DistributionPointID )?>'>
                            <input type='hidden' id='DistributionPointName' name='DistributionPointName' value='<?php echo esc_attr( $chosen_point->DistributionPointName ) ?>'>
                            <input type='hidden' id='CityName' name='CityName' value='<?php echo esc_attr( $chosen_point->CityName ) ?>'>
                            <input type='hidden' id='StreetName' name='StreetName' value='<?php echo esc_attr( $chosen_point->StreetName ) ?>'>
                            <input type='hidden' id='StreetNum' name='StreetNum' value='<?php echo esc_attr( $chosen_point->StreetNum ) ?>'>
                            <input type='hidden' id='Comment' name='Comment' value='<?php echo esc_attr( $chosen_point->Comment )?>'>
                            <input type='hidden' id='cargoPhone' name='cargoPhone' value='<?php echo esc_attr( $chosen_point->Phone )?>'>
                            <input type='hidden' id='Latitude' name='Latitude' value='<?php echo esc_attr( $chosen_point->Latitude )?>'>
                            <input type='hidden' id='Longitude' name='Longitude' value='<?php echo esc_attr( $chosen_point->Longitude ) ?>'>
                        <?php endif; ?>
                    </div>
                     <?php
                }
            }
        }

        public function cslfw_shipping_method() {
            require_once CSLFW_PATH . 'includes/woo-baldarp-shipping.php';
			require_once CSLFW_PATH . 'includes/woo-baldarp-express-shipping.php';
        }

        public function cslfw_add_Baldarp_shipping_method( $methods ) {
            $methods['woo-baldarp-pickup'] = 'CSLFW_Shipping_Method';
			$methods['cargo-express'] = 'Cargo_Express_Shipping_Method';
            return $methods;
        }

        public function cslfw_shipping_api_settings_init() {
            register_setting('cslfw_shipping_api_settings_fg', 'cargo_order_status');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_google_api_key');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_map_size');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_cod_check');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_custom_map_size');
            register_setting('cslfw_shipping_api_settings_fg', 'shipping_cargo_express');
            register_setting('cslfw_shipping_api_settings_fg', 'shipping_cargo_box');
            register_setting('cslfw_shipping_api_settings_fg', 'from_street');
            register_setting('cslfw_shipping_api_settings_fg', 'from_street_name');
            register_setting('cslfw_shipping_api_settings_fg', 'from_city');
            register_setting('cslfw_shipping_api_settings_fg', 'phonenumber_from');
            register_setting('cslfw_shipping_api_settings_fg', 'website_name_cargo');
			register_setting('cslfw_shipping_api_settings_fg', 'bootstrap_enalble');
			register_setting('cslfw_shipping_api_settings_fg', 'send_to_cargo_all');
			register_setting('cslfw_shipping_api_settings_fg', 'cargo_box_style');
			register_setting('cslfw_shipping_api_settings_fg', 'disable_order_status');
        }

        public function cargo_settings_link( $links_array ) {
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
        public function cslfw_deactivate() {
			//flush permalinks
			flush_rewrite_rules();
		}

        public function cslfw_uninstall() {
            //flush permalinks
			flush_rewrite_rules();
            delete_option('cargo_order_status');
            delete_option('cslfw_google_api_key');
            delete_option('cslfw_map_size');
            delete_option('cslfw_cod_check');
            delete_option('cslfw_custom_map_size');
            delete_option('shipping_cargo_express');
            delete_option('shipping_cargo_box');
            delete_option('website_name_cargo');
			delete_option('bootstrap_enalble');
			delete_option('send_to_cargo_all');
			delete_option('cargo_box_style');
			delete_option('disable_order_status');

        }

        public function InitPlugin(){
            add_action('admin_menu', array($this, 'PluginMenu'));
        }

        public function PluginMenu(){
            add_menu_page('Cargo Shipping Location', 'Cargo Shipping Location', 'manage_options', 'loaction_api_settings', array($this, 'settings'),plugin_dir_url( __FILE__ ) . 'assets/image/cargo-icon-with-white-bg-svg.svg');
            add_submenu_page('loaction_api_settings', 'Log Files', 'Log Files', 'manage_options', 'cargo_shipping_log', array($this, 'logs'));
            add_submenu_page('loaction_api_settings', 'Contact Us', 'Contact Us', 'manage_options', 'cargo_shipping_contact', array($this, 'contact'));
        }

        public function RenderPage(){
            $this->checkWooCommerce(); ?>
            <div class='wrap'>
                <h2><?php _e('Shipping Location API - Dashboard', 'cargo-shipping-location-for-woocommerce') ?></h2>
            </div>
            <?php
        }
        public function logs(){
			$this->checkWooCommerce();
			$this->loadTemplate('logs');
		}

        public function contact(){
            $this->checkWooCommerce();
            $this->loadTemplate('contact');
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
                require_once CSLFW_PATH . 'templates/'.$templateName.'.php';
            }
        }
    }
}

$cslfw_shipping = new CSLFW_Shipping();
$cslfw_shipping->InitPlugin();