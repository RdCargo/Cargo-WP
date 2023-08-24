<?php
/**
 * Admin adjustments.
 *
 */
if ( class_exists( 'CSLFW_Admin', false ) ) {
    return new CSLFW_Admin();
}
require CSLFW_PATH . '/includes/cslfw-cargo.php';

if( !class_exists('CSLFW_Admin') ) {
    class CSLFW_Admin
    {
        function __construct()
        {
            $this->helpers = new CSLFW_Helpers();
            add_action( 'admin_enqueue_scripts', array( $this, 'import_assets' ) );

            add_action( 'init', array( $this, 'register_order_status_for_cargo' ) );
            add_filter( 'wc_order_statuses', array( $this, 'custom_order_status') );
            add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
            add_action( 'add_meta_boxes', array( $this, 'remove_shop_order_meta_box' ), 90  );
            add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'show_shipping_info' ) );
            add_action( 'admin_notices', array( $this, 'cargo_bulk_action_admin_notice') );
            add_action( 'woocommerce_shipping_init', array( $this,'shipping_method_classes' ) );
            add_action( 'manage_shop_order_posts_custom_column', array( $this,'add_custom_column_content') );
            add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'bulk_order_cargo_shipment' ), 10, 3);

            add_filter( 'bulk_actions-edit-shop_order', array( $this, 'custom_dropdown_bulk_actions_shop_order' ), 20, 1 );
            add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_delivery_status_column_header' ), 2000 );
            add_filter( 'woocommerce_shipping_methods', array( $this,'cargo_shipping_methods' ) );

        }

        public function import_assets() {
            $screen       = get_current_screen();
            $screen_id    = $screen ? $screen->id : '';


            if( $screen_id === 'toplevel_page_loaction_api_settings' ||  $screen_id === 'cargo-shipping-location_page_cargo_shipping_contact' || $screen_id === 'cargo-shipping-location_page_cargo_orders_reindex' ) {
                wp_enqueue_script( 'cargo-libs', CSLFW_URL . 'assets/js/libs.js', array('jquery'), '', true);
            }
            wp_enqueue_style( 'admin-baldarp-styles', CSLFW_URL . 'assets/css/admin-baldarp-styles.css' );
            wp_enqueue_script( 'cargo-global', CSLFW_URL . 'assets/js/global.js', array('jquery'), '', true);

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
         * Single order cargo view.
         *
         * @param $post
         */
        public function render_meta_box_content( $post ) {
            // Use get_post_meta to retrieve an existing value from the database.
            $order              = wc_get_order( $post->ID );
            $shipping_method    = @array_shift($order->get_shipping_methods());
            $payment_method     = $order->get_payment_method();
            $payment_method_check = get_option( 'cslfw_cod_check' ) ?  get_option( 'cslfw_cod_check' ) : 'cod';
            $cargo_debug_mode   = get_option( 'cslfw_debug_mode' );
            $cslfw_fulfill_all  = get_option('cslfw_fulfill_all');
            $cargo_shipping     = new CSLFW_Cargo_Shipping($post->ID);
            $deliveries         = $cargo_shipping->get_shipment_ids();
            $cslfw_shiping_methods = get_option('cslfw_shipping_methods') ? get_option('cslfw_shipping_methods') : [];
            $cslfw_shipping_methods_all = get_option('cslfw_shipping_methods_all');

            if ( $order->get_status() !== 'cancelled' && $order->get_status() !== 'refunded' && $order->get_status() !== 'pending' ) {
                if ($cargo_debug_mode) {
                    if ($shipping_method) var_dump($shipping_method['method_id']);
                    var_dump($cargo_shipping->deliveries);
                }

                if ( ($shipping_method && $shipping_method['method_id'] === 'cargo-express')
                    || ($shipping_method && $shipping_method['method_id'] === 'woo-baldarp-pickup')
                    || ( $shipping_method && in_array($shipping_method['method_id'], $cslfw_shiping_methods) )
                    || (bool)$cslfw_shipping_methods_all
                ) { ?>
                    <div class="cargo-submit-form-wrap" <?php if ( $deliveries ) echo 'style="display: none;"'; ?> >
                        <?php if (!$cslfw_fulfill_all) { ?>
                            <div class="cargo-button">
                                <strong><?php _e('Fulfillment (SKU * Quantity in Notes)', 'cargo-shipping-location-for-woocommerce') ?></strong>
                                <label for="cslfw_fulfillment">
                                    <input type="checkbox" name="cslfw_fulfillment" id="cslfw_fulfillment" />
                                    <span><?php _e('Yes', 'cargo-shipping-location-for-woocommerce') ?></span>
                                </label>
                            </div>
                        <?php } ?>
                        <?php if ( ($shipping_method && $shipping_method['method_id'] !== 'woo-baldarp-pickup') || (!$shipping_method && (bool)$cslfw_shipping_methods_all) ) : ?>
                            <div class="cargo-button">
                                <strong><?php _e('Double Delivery', 'cargo-shipping-location-for-woocommerce') ?></strong>
                                <label for="cargo_double-delivery">
                                    <input type="checkbox" name="cargo_double_delivery" id="cargo_double-delivery" />
                                    <span><?php _e('Yes', 'cargo-shipping-location-for-woocommerce') ?></span>
                                </label>
                            </div>
                            <div class="cargo-button">
                                <strong><?php _e('Cash on delivery', 'cargo-shipping-location-for-woocommerce') ?> (<?php echo $order->get_formatted_order_total() ?>)</strong>
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
                        <?php endif; ?>
                        <?php if ($shipping_method && $shipping_method['method_id'] === 'woo-baldarp-pickup') : ?>
                            <?php
                            $shipment_data = $cargo_shipping->get_shipment_data();
                            $DistributionPointID = $shipment_data ? end($shipment_data)['box_id'] : false;
                            $DistributionPointID = !$DistributionPointID ? get_post_meta($post->ID, 'cargo_DistributionPointID', true) : $DistributionPointID;

                            if ( $DistributionPointID ) :
                                $cities              = json_decode(json_encode( $this->helpers->cargoAPI("https://api.cargo.co.il/Webservice/getPickupCities") ), 1);
                                $points = $this->helpers->cargoAPI("https://api.cargo.co.il/Webservice/getPickUpPoints", ['pointId' => $DistributionPointID]);
                                $selected_city       = $points->PointsDetails[0]->CityName;
                                if ( count($cities['PointsDetails'] ) ) { ?>
                                    <p class="form-row form-row-wide">
                                        <label for="cargo_city">
                                            <span><?php _e('בחירת עיר', 'cargo-shipping-location-for-woocommerce') ?></span>
                                        </label>

                                        <select name="cargo_city" id="cargo_city" class="">
                                            <option><?php _e('נא לבחור עיר', 'cargo-shipping-location-for-woocommerce') ?></option>
                                            <?php foreach ($cities['PointsDetails'] as $key => $value) : ?>
                                                <option value="<?php echo esc_attr($value['CityName']) ?>" <?php if (trim($selected_city) === trim( $value['CityName'] ) ) echo 'selected="selected"'; ?>><?php echo esc_html($value['CityName']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </p>
                                <?php }
                                $points = $this->helpers->cargoAPI("https://api.cargo.co.il/Webservice/getPickUpPoints", ['city' => $selected_city]);
                                ?>
                                <div class="form-row form-row-wide">
                                    <p class="select-wrap w-100">
                                        <label for="cargo_pickup_point">
                                            <span><?php _e('בחירת נקודת חלוקה', 'cargo-shipping-location-for-woocommerce') ?></span>
                                        </label>
                                        <select name="cargo_pickup_point" id="cargo_pickup_point" class=" w-100" style="display: <?php echo esc_attr($points ? 'block' : 'none'); ?>" >
                                            <?php foreach ($points->PointsDetails as $key => $point) : ?>
                                                <option value="<?php echo esc_attr($point->DistributionPointID) ?>" <?php if ($DistributionPointID === $point->DistributionPointID) echo 'selected="selected"' ?>>
                                                    <?php echo esc_html($point->DistributionPointName) ?>, <?php echo esc_html($point->CityName) ?>, <?php echo esc_html($point->StreetName) ?> <?php echo esc_html($point->StreetNum) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </p>
                                </div>
                            <?php
                            endif; ?>
                        <?php endif; // End express check ?>
                        <div class="cargo-radio">
                            <strong><?php _e('Shipment Type', 'cargo-shipping-location-for-woocommerce') ?></strong>
                            <label for="cargo_shipment_type_regular">
                                <input type="radio" name="cargo_shipment_type" id="cargo_shipment_type_regular" checked value="1" />
                                <span><?php _e('Regular', 'cargo-shipping-location-for-woocommerce') ?></span>
                            </label>
                            <?php if ( $shipping_method &&$shipping_method['method_id'] !== 'woo-baldarp-pickup' || (bool)$cslfw_shipping_methods_all ) : ?>
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
                               class="submit-cargo-shipping  btn btn-success"
                               data-id="<?php echo esc_attr($post->ID); ?>"><?php _e('שלח ל CARGO', 'cargo-shipping-location-for-woocommerce') ?></a>
                        </div>
                    </div>
                    <?php if ( $deliveries ) :
                        $cargo_shippings =  implode(', ', $deliveries);
                        ?>

                        <div class="cargo-button">
                            <div><strong><?php _e('Shipping ID\'s: ', 'cargo-shipping-location-for-woocommerce' ) ?></strong><?php echo esc_html($cargo_shippings) ?></div>
                            <a href="#" class="label-cargo-shipping button"  data-order-id="<?php echo esc_attr($post->ID); ?>"><?php _e('הדפס תווית', 'cargo-shipping-location-for-woocommerce') ?></a>
                        </div>

                        <div class="checkstatus-section">
                            <?php
                            if ( is_array($deliveries) ) {
                                foreach ($deliveries as $key => $value) {
                                    echo wp_kses_post("<a href='#' class='btn btn-success send-status button' style='margin-bottom: 10px;' data-id=" . $post->ID . " data-deliveryid='$value'>" . __('בקש סטטוס משלוח', 'cargo-shipping-location-for-woocommerce') . " $value</a>");
                                }
                            } else {
                                echo wp_kses_post("<a href='#' class='btn btn-success send-status button' data-id=" . $post->ID . " >בקש סטטוס משלוח</a>");
                            }
                            ?>
                        </div>

                        <div class="cargo-button">
                            <a href="#" class="cslfw-create-new-shipment button button-primary"><?php _e('יצירת משלוח חדש', 'cargo-shipping-location-for-woocommerce') ?></a>
                            <p style="font-size: 12px;"><?php _e('פעולה זו לא תבטל את המשלוח הקודם (יש לפנות לשירות הלקוחות) אלא תיצור משלוח חדש', 'cargo-shipping-location-for-woocommerce') ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ( $shipping_method && $shipping_method['method_id'] == 'woo-baldarp-pickup' && $deliveries ) {
                        $box_shipment_type = get_post_meta($post->ID, 'cslfw_box_shipment_type', true);

                        foreach ($cargo_shipping->get_shipment_data() as $shipping_id => $data) {
                            $point = $this->helpers->cargoAPI("https://api.cargo.co.il/Webservice/getPickUpPoints", ['pointId' => intval( $data['box_id'] )]);

                            if ( count($point->PointsDetails) ) {
                                $chosen_point = $point->PointsDetails[0];
                                ?>
                                <div>
                                    <h3>SHIPPING <?php esc_html_e($shipping_id) ?></h3>
                                    <h4 style="margin-bottom: 5px;"><?php _e('Cargo Point Details', 'cargo-shipping-location-for-woocommerce') ?></h4>
                                    <?php if ($box_shipment_type === 'cargo_automatic' && !$chosen_point) { ?>
                                        <p><?php _e('Details will appear after sending to cargo.', 'cargo-shipping-location-for-woocommerce') ?></p>
                                    <?php } else { ?>
                                        <h2 style="padding:0;">
                                            <strong><?php echo wp_kses_post( $chosen_point->DistributionPointName ) ?> : <?php echo wp_kses_post($chosen_point->DistributionPointID ); ?></strong>
                                        </h2>
                                        <h4 style="margin:0;"><?php echo wp_kses_post( $chosen_point->StreetNum.' '.$chosen_point->StreetName.' '. $chosen_point->CityName ) ?></h4>
                                        <h4 style="margin:0;"><?php echo wp_kses_post( $chosen_point->Comment ) ?></h4>
                                        <h4 style="margin:0;"><?php echo wp_kses_post( $chosen_point->Phone ) ?></h4>
                                    <?php } ?>
                                </div>
                            <?php }
                        } ?>

                    <?php }
                } else {
                    esc_html_e('Shipping type is not related to cargo', 'cargo-shipping-location-for-woocommerce');
                }
            }
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
         * @param $order_statuses
         * @return mixed
         *
         * Add order status in Array
         */
        function custom_order_status( $order_statuses ) {
            $order_statuses['wc-send-cargo'] = _x( 'Send to CARGO', 'Order status', 'cargo-shipping-location-for-woocommerce' );
            return $order_statuses;
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
                $shipping_method = @array_shift($order->get_shipping_methods() );
                $cslfw_shiping_methods = get_option('cslfw_shipping_methods') ? get_option('cslfw_shipping_methods') : [];
                $cslfw_shipping_methods_all = get_option('cslfw_shipping_methods_all');

                if ($order->get_status() !== 'cancelled' && $order->get_status() !== 'refunded' && $order->get_status() !== 'pending') {
                    if ( ($shipping_method && $shipping_method['method_id'] === 'cargo-express')
                        || ($shipping_method && $shipping_method['method_id'] === 'woo-baldarp-pickup')
                        || ( $shipping_method && in_array($shipping_method['method_id'], $cslfw_shiping_methods) )
                        || $cslfw_shipping_methods_all
                    ) {
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
        }

        /**
         * remove meta box from the admin order page
         */
        public function remove_shop_order_meta_box() {
            remove_meta_box( 'postcustom', 'shop_order', 'normal' );
        }

        /**
         * @param $actions
         * @return array
         *
         * Add Order Status to Admin Order list
         */
        function custom_dropdown_bulk_actions_shop_order($actions ){
            $new_actions = array();

            foreach ($actions as $key => $action) {
                $new_actions[$key] = $action;

                if ('mark_processing' === $key) {
                    $new_actions['mark_send-cargo-shipping'] = __( 'Send to CARGO', 'cargo-shipping-location-for-woocommerce' );
                    $new_actions['mark_send-cargo-dd'] = __( 'Send to CARGO with double delivery', 'cargo-shipping-location-for-woocommerce' );
                    $new_actions['mark_cargo-print-label'] = __( 'Print CARGO labels', 'cargo-shipping-location-for-woocommerce' );
                }
            }

            return $new_actions;
        }

        /**
         * @param $column
         *
         * Add Custom Column in Admin Order List
         */
        public function add_custom_column_content( $column ) {
            global $post;
            $order              = wc_get_order($post->ID);
            $shipping_method    = @array_shift($order->get_shipping_methods());
            $cslfw_shiping_methods = get_option('cslfw_shipping_methods') ? get_option('cslfw_shipping_methods') : [];
            $cslfw_shipping_methods_all = get_option('cslfw_shipping_methods_all');


            if (  $order->get_status() !== 'cancelled' && $order->get_status() !== 'refunded' && $order->get_status() !== 'pending' ) {

                if ( ($shipping_method && $shipping_method['method_id'] === 'cargo-express')
                    || ($shipping_method && $shipping_method['method_id'] === 'woo-baldarp-pickup')
                    || ($shipping_method && in_array($shipping_method['method_id'], $cslfw_shiping_methods) )
                    || (bool) $cslfw_shipping_methods_all
                ) {

                    $cargo_shipping = new CSLFW_Cargo_Shipping($post->ID);
                    $deliveries = $cargo_shipping->get_shipment_data();

                    $box_point_id = get_post_meta($post->ID, 'cargo_DistributionPointID', true);
                    if ( 'cslfw_delivery_status' === $column ) {
                        if ( $deliveries ) {
                            foreach ($deliveries as $key => $value) {
                                echo wp_kses_post('<p>Status - ' . $value['status']['text'] . '</p>');
                                echo wp_kses_post("<a href='#' class='btn btn-success send-status' style='margin-bottom: 5px;' data-id=" . $post->ID.  " data-deliveryid='$key'>$key  בדוק מצב הזמנה</a>");
                            }
                        }
                    }

                    if ( 'send_to_cargo' === $column ) {
                        if ( $deliveries ) {
                            $deliveries = implode(',', $cargo_shipping->get_shipment_ids()) ;
                            echo wp_kses_post("<p>". $deliveries . "</p>");
                            echo wp_kses_post('<a  href="#" class="btn btn-success label-cargo-shipping" data-order-id="'.$post->ID.'">הדפס תווית</a>');
                        } else {
                            if ( $box_point_id ) {
                                echo wp_kses_post("<a href='#' class='btn btn-success submit-cargo-shipping' data-box-point-id='$box_point_id' data-id=".$post->ID." >שלח  לCARGO</a>");
                            } else {
                                echo wp_kses_post("<a href='#' class='btn btn-success submit-cargo-shipping' data-id=".$post->ID." >שלח  לCARGO</a>");
                            }
                        }
                    }
                }
            }
        }

        /**
         * Show Shipping Info in Admin Edit Order
         *
         * @param $order
         */
        function show_shipping_info($order ) {
            $cargo_shipping = new CSLFW_Cargo_Shipping($order->get_id());
            $deliveries = $cargo_shipping->get_shipment_ids();

            if ( ! empty($deliveries) ) {
                $deliveries = is_array( $deliveries ) ? implode(',', $deliveries) : $deliveries;
                echo wp_kses_post('<p><strong>'.__('מזהה משלוח', 'cargo-shipping-location-for-woocommerce').':</strong> ' . $deliveries . '</p>');
            }
        }

        /**
         * @param $columns
         * @return array
         */
        public function add_order_delivery_status_column_header($columns) {
            $new_columns = array();

            foreach ( $columns as $column_name => $column_info ) {
                $new_columns[ $column_name ] = $column_info;

                if ( 'order_status' === $column_name ) {
                    $new_columns['send_to_cargo'] = __( 'שלח משלוח לCARGO', 'cargo-shipping-location-for-woocommerce' );
                    $new_columns['cslfw_delivery_status'] = __( 'סטטוס משלוח', 'cargo-shipping-location-for-woocommerce' );
                }
            }

            return $new_columns;
        }

        /**
         * Add Bulk Action in admin panel.
         */
        function cargo_bulk_action_admin_notice() {
            global $pagenow;
            if ( 'edit.php' === $pagenow ) {
                $args = array(
                    'posts_per_page' => -1,
                    'meta_key'      => 'cargo_shipping_id', // Postmeta key field
                    'meta_value'    => ['', '0'],
                    'meta_compare'  => 'NOT IN', // Possible values are ‘=’, ‘!=’, ‘>’, ‘>=’, ‘<‘, ‘<=’, ‘LIKE’, ‘NOT LIKE’, ‘IN’, ‘NOT IN’, ‘BETWEEN’, ‘NOT BETWEEN’, ‘EXISTS’ (only in WP >= 3.5), and ‘NOT EXISTS’ (also only in WP >= 3.5). Values ‘REGEXP’, ‘NOT REGEXP’ and ‘RLIKE’ were added in WordPress 3.7. Default value is ‘=’.
                    'return'        => 'ids' // Accepts a string: 'ids' or 'objects'. Default: 'objects'.
                );

                $orders = wc_get_orders( $args );
                if ( count($orders) > 0) {
                    echo wp_kses_post( printf( '<div class="notice notice-error fade is-dismissible"><p>' .
                        _n( '%s Order require reindex',
                            '%s Orders require reindex',
                            count($orders),
                            'woocommerce'
                        ) . '<a class="button button-primary" style="margin-left: 10px;" href="%s">REINDEX</a></p></div>', count($orders), admin_url('admin.php?page=cargo_orders_reindex')
                    ) );
                }
            }

            if ( 'edit.php' === $pagenow
                && isset($_GET['post_type'])
                && 'shop_order' === $_GET['post_type']
                && isset( $_GET['cargo_send']) ) {

                $processed_count = intval( sanitize_text_field($_REQUEST['processed_count']) );

                if ( isset($_REQUEST['processed_count']) ) {
                    echo wp_kses_post( printf( '<div class="notice notice-success fade is-dismissible"><p>' .
                        _n( '%s Order Sent for Shipment',
                            '%s Orders Sent For Shipment',
                            $processed_count,
                            'woocommerce'
                        ) . '</p></div>', $processed_count ) );
                }

                $skipped_count = intval( sanitize_text_field($_REQUEST['skipped_count']) );

                if ( isset($_REQUEST['skipped_count']) ) {
                    echo wp_kses_post( printf( '<div class="notice notice-success fade is-dismissible"><p>' .
                        _n( '%s Were skipped because they already have shipment created.',
                            '%s Were skipped because they already have shipment created.',
                            $skipped_count,
                            'woocommerce'
                        ) . '</p></div>', $skipped_count ) );
                }
            }
        }

        /**
         * Init Cargo shipping methods.
         */
        public function shipping_method_classes() {
            require_once CSLFW_PATH . 'includes/woo-baldarp-shipping.php';
            require_once CSLFW_PATH . 'includes/woo-baldarp-express-shipping.php';
        }

        /**
         * Add shipping methods.
         *
         * @param $methods
         * @return mixed
         */
        public function cargo_shipping_methods( $methods ) {
            $methods['woo-baldarp-pickup'] = 'CSLFW_Shipping_Method';
            $methods['cargo-express'] = 'Cargo_Express_Shipping_Method';
            return $methods;
        }

        /**
         * Add bulk actions in order list
         *
         * @param $redirect_to
         * @param $action
         * @param $ids
         * @return mixed
         */
        public function bulk_order_cargo_shipment($redirect_to, $action, $ids) {
            $is_cargo = 0;

            $processed_count = 0;
            $skipped_count = 0;

            if ( false !== strpos( $action, 'mark_' ) ) {
                $action_name     = substr( $action, 5 ); // Get the status name from action.
                if ($action_name === 'send-cargo-shipping') {
                    foreach ($ids as $key => $order_id) {
                        $cargo_shipping = new CSLFW_Cargo_Shipping($order_id);
                        if (!$cargo_shipping->get_shipment_data()) {

                            $distribution_point = get_post_meta($order_id, 'cargo_DistributionPointID', true);
                            if ( intval($distribution_point) ) {
                                $point = $this->helpers->cargoAPI("https://api.cargo.co.il/Webservice/getPickUpPoints", ['pointId' => intval( $distribution_point )]);
                                if ( count($point->PointsDetails) ) {
                                    $chosen_point      = $point->PointsDetails[0];
                                    $args['box_point'] = $chosen_point;
                                    $cargo_shipping->createShipment($args);

                                }
                            } else {
                                $cargo_shipping->createShipment();
                            }

                            $processed_count++;
                        } else {
                            $skipped_count++;
                        }
                    }
                } elseif ($action_name === 'send-cargo-dd') {
                    foreach ($ids as $key => $order_id) {
                        $cargo_shipping = new CSLFW_Cargo_Shipping($order_id);
                        if (!$cargo_shipping->get_shipment_data()) {
                            $distribution_point = get_post_meta($order_id, 'cargo_DistributionPointID', true);
                            if ( intval($distribution_point) ) {
                                $point = $this->helpers->cargoAPI("https://api.cargo.co.il/Webservice/getPickUpPoints", ['pointId' => intval( $distribution_point )]);
                                if ( count($point->PointsDetails) ) {
                                    $chosen_point      = $point->PointsDetails[0];
                                    $args['box_point'] = $chosen_point;
                                    $args['double_delivery'] = 2;
                                    $cargo_shipping->createShipment($args);
                                }
                            } else {
                                $cargo_shipping->createShipment(array('double_delivery' => 2));
                            }
                            $processed_count++;
                        } else {
                            $skipped_count++;
                        }
                    }
                } elseif ($action_name === 'cargo-print-label') {
                    $cargo_shipping     = new CSLFW_Cargo_Shipping();
                    $shipment_ids   = $cargo_shipping->order_ids_to_shipment_ids($ids);
                    $pdf_label      = $cargo_shipping->getShipmentLabel( implode( ',', $shipment_ids ) );
                    $processed_count++;
                    $redirect_to    = $pdf_label->pdfLink;

                    return $redirect_to;
                } elseif ($action_name === 'cargo-print-label') {
                    $cargo_shipping     = new CSLFW_Cargo_Shipping();
                    $shipment_ids   = $cargo_shipping->order_ids_to_shipment_ids($ids);
                    $pdf_label      = $cargo_shipping->getShipmentLabel( implode( ',', $shipment_ids ) );
                    $processed_count++;
                    $redirect_to    = $pdf_label->pdfLink;

                    return $redirect_to;
                }
            }

            $redirect_to = add_query_arg(
                array(
                    'cargo_send'     => $is_cargo,
                    'old_stutus'     => '',
                    'processed_count' => $processed_count,
                    'skipped_count' => $skipped_count,
                    'processed_ids'  => implode( ',', $ids ),
                ), $redirect_to );
            return $redirect_to;
        }
    }
}

$cslfw_admin = new CSLFW_Admin();
