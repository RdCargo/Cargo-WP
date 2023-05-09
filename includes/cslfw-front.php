<?php
/**
 * Admin adjustments.
 *
 */
if ( class_exists( 'CSLFW_Front', false ) ) {
    return new CSLFW_Front();
}

if( !class_exists('CSLFW_Front') ) {
    class CSLFW_Front
    {
        function __construct()
        {
            $this->helpers = new CSLFW_Helpers();
            add_action( 'wp_enqueue_scripts', array( $this, 'import_assets' ) );

            add_filter( 'woocommerce_account_orders_columns', array( $this, 'add_account_orders_column'), 10, 1 );
            add_filter( 'woocommerce_locate_template', array( $this, 'intercept_wc_template' ), 10, 3 );

            add_action( 'wp_head', array( $this, 'checkout_popups' ) );
            add_action( 'wp_footer', array( $this, 'add_model_footer' ) );
            add_action( 'woocommerce_order_details_after_order_table', array( $this, 'tracking_button' ) );
            add_action( 'wp_ajax_get_order_tracking_details', array( $this,'get_order_tracking_details') );
            add_action( 'woocommerce_after_shipping_rate', array( $this, 'checkout_cargo_actions' ), 20, 2) ;
            add_action( 'woocommerce_my_account_my_orders_column_order-track', array( $this, 'add_account_orders_column_rows' ) );
            add_action( 'woocommerce_checkout_process', array( $this, 'action_woocommerce_checkout_process' ),10,1);
        }

        function import_assets() {
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
        }

        /**
         * Display fields for checkout page, and cargo box selector..
         *
         * @param $method
         * @param $index
         */
        public function checkout_cargo_actions( $method, $index ) {
            if( is_cart() ) { return; }
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods')[ $index ];
            $chosen_method_id = explode(':', $chosen_shipping_methods);
            $chosen_method_id = reset($chosen_method_id);
            $loop_method = explode(':', $method->id);
            $loop_method = reset($loop_method);

            if ( $chosen_method_id == 'woo-baldarp-pickup' && $method->method_id == 'woo-baldarp-pickup' ) {
                $pointId    = isset($_COOKIE['cargoPointID']) ? sanitize_text_field($_COOKIE['cargoPointID']) : '';
                $city       = isset($_COOKIE['CargoCityName']) ? sanitize_text_field($_COOKIE['CargoCityName']) : '';
                $city_dd    = isset($_COOKIE['CargoCityName_dropdown']) ? sanitize_text_field($_COOKIE['CargoCityName_dropdown']) : '';
                $lat        = isset($_COOKIE['cargoLatitude']) ? sanitize_text_field($_COOKIE['cargoLatitude']) : '';
                $lng        = isset($_COOKIE['cargoLongitude']) ? sanitize_text_field($_COOKIE['cargoLongitude']) : '';

                $cargo_box_style = get_option('cargo_box_style');
                ?>
                <div class="cargo-map-wrap">
                    <?php if ($cargo_box_style === 'cargo_map' ) : ?>
                        <a class="baldrap-btn btn button wp-element-button" id="mapbutton">
                            <?php _e(' בחירת נקודה', 'cargo-shipping-location-for-woocommerce') ?>
                        </a>
                        <div id="selected_cargo"></div>
                    <?php
                    elseif ( ($cargo_box_style === 'cargo_dropdowns') ) :
                        $cities = json_decode(json_encode( $this->helpers->cargoAPI("https://api.carg0.co.il/Webservice/getCitiesForPlugin") ), 1);
                        if ( $cities['success'] ) { ?>
                            <p class="form-row form-row-wide">
                                <label for="cargo_city">
                                    <span><?php _e('בחירת עיר', 'cargo-shipping-location-for-woocommerce') ?></span>
                                </label>

                                <select name="cargo_city" id="cargo_city" class="">
                                    <option><?php _e('נא לבחור עיר', 'cargo-shipping-location-for-woocommerce') ?></option>
                                    <?php foreach ($cities['data'] as $key => $value) : ?>
                                        <option value="<?php echo esc_attr($value['city_name']) ?>" <?php if (trim($city_dd) === trim( $value['city_name'] ) ) echo 'selected="selected"'; ?>><?php echo esc_html($value['city_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </p>
                        <?php }
                        $points = $this->helpers->cargoAPI("https://api.carg0.co.il/Webservice/findClosestPoints", array('lat' => $lat, 'long' => $lng, 'distance' => 10));
                        if ( $points->error === false && !empty($points->closest_points) ) {
                            ?>
                            <div class="form-row form-row-wide">
                                <p class="select-wrap w-100">
                                    <label for="cargo_pickup_point">
                                        <span><?php _e('בחירת נקודת חלוקה', 'cargo-shipping-location-for-woocommerce') ?></span>
                                    </label>
                                    <select name="cargo_pickup_point" id="cargo_pickup_point" class=" w-100">
                                        <?php foreach ($points->closest_points as $key => $value) :
                                            $point = $value->point_details; ?>
                                            <option value="<?php echo esc_attr($point->DistributionPointID) ?>" <?php if ($pointId === $point->DistributionPointID) echo 'selected="selected"' ?>>
                                                <?php echo esc_html($point->DistributionPointName) ?>, <?php echo esc_html($point->CityName) ?>, <?php echo esc_html($point->StreetName) ?> <?php echo esc_html($point->StreetNum) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                            </div>
                        <?php } else { ?>
                            <p class="woocommerce-info"><?php _e('לא נמצאו כתובות ברדיוס של 10 ק״מ נא לבחור עיר אחרת', 'cargo-shipping-location-for-woocommerce') ?></p>
                        <?php } ?>
                    <?php endif; ?>
                    <?php
                    if ( !empty($pointId) ) {
                        $chosen_point = $this->helpers->cargoAPI("https://api.carg0.co.il/Webservice/getPickUpPoints", array('pointId' => $pointId));
                        $chosen_point = $chosen_point->PointsDetails[0];
                    }
                    if ($cargo_box_style !== 'cargo_automatic') :
                        ?>
                        <input type="hidden" id="DistributionPointID" name="DistributionPointID" value="<?php echo esc_attr( $chosen_point->DistributionPointID ?? '' )?>">
                        <input type="hidden" id="CityName" name="CityName" value="<?php echo esc_attr( $chosen_point->CityName ?? '' ) ?>">
                    <?php endif; ?>
                </div>
                <?php
            }
        }

        /**
         * @param $order
         *
         * Add Track order link in my account page in order tab
         */
        function add_account_orders_column_rows( $order ) {
            $order_id           = $order->get_id();
            $cargo_shipping     = new CSLFW_Cargo_Shipping($order_id);
            $deliveries  = $cargo_shipping->get_shipment_ids();

            if( $deliveries ) {
                foreach ( $deliveries as $key => $value ) {
                    echo wp_kses_post('<a href="#" class="btn woocommerce-button js-cargo-track" data-delivery="' . $value . '">' . __('Track Order', 'cargo-shipping-location-for-woocommerce') . '</a>');
                }
            }
        }

        /**
         * @param $columns
         * @return mixed
         *
         * Add New column Track order in the my account order tab
         */
        function add_account_orders_column( $columns ) {
            $columns['order-track'] = __( 'Track order', 'woocommerce' );

            return $columns;
        }

        /**
         * Add popup to account page.
         */
        function add_model_footer() {
            if( is_account_page() ) {
                $this->helpers->load_template('account-tracking-popup');
            }
        }

        /**
         * Function for `woocommerce_order_details_after_order_table` action-hook.
         *
         * @param  $order
         *
         * @return void
         */
        function tracking_button( $order ) {
            $order_id           = $order->get_id();
            $cargo_shipping     = new CSLFW_Cargo_Shipping($order_id);
            $deliveries  = $cargo_shipping->get_shipment_ids();

            if ( $deliveries ) {
                foreach($deliveries as $key => $value) {
                    echo wp_kses_post('<a href="#" class="btn wp-element-button button woocommerce-button js-cargo-track" data-delivery="'. $value .'">' . __('Track shipment', 'cargo-shipping-location-for-woocommerce') . " $value</a>");
                }
            }
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
            $result = $this->helpers->cargoAPI('https://api.carg0.co.il/Webservice/CheckShipmentStatusAndTime', $data);
            echo json_encode( $result );
            die();
        }

        /**
         * Display map and info popup.
         */
        public function checkout_popups() {
            if ( is_checkout() || is_cart() ){
                $this->helpers->load_template('checkout-popups');
            }
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
    }
}

$CSLFW_Front = new CSLFW_Front();
