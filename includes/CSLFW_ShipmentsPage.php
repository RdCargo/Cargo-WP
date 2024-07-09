<?php

namespace CSLFW\Includes;
use CSLFW\Includes\CargoAPI\Cargo;
use CSLFW\Includes\CargoAPI\CargoAPIV2;
use CSLFW\Includes\CSLFW_Helpers;
use CSLFW_Logs;


class CSLFW_ShipmentsPage
{

    public function __construct()
    {
        $this->helpers = new CSLFW_Helpers();
        add_action('admin_menu', [$this, 'add_menu_link'], 100);
        add_action('admin_enqueue_scripts', [$this, 'import_assets'] );
        add_action('wp_ajax_get_multiple_shipment_labels', [$this, 'getMultipleShipmentLabels']);

    }

    public function add_menu_link()
    {
        add_submenu_page('loaction_api_settings', 'Shipments', 'Shipments', 'manage_options', 'cargo_shipments_table', [$this, 'render'] );
    }

    private function getOrders()
    {
        $paged = isset($_GET['paged']) ? sanitize_text_field($_GET['paged']) : 1;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : null;
        $metaQuery = [
            'relation' => 'AND',
            [
                'key' => 'cslfw_shipping',
                'compare' => 'EXISTS',
            ]
        ];

        if ($search) {
            $metaQuery[] = [
                'key' => 'cslfw_shipping',
                'value' => "{$search}",
                'compare' => 'LIKE'
            ];
        }

        $args = [
            'meta_key' => 'cslfw_shipping',
            'paginate' => true,
            'posts_per_page' => 10,
            'page' => $paged,
        ];

        $orders = $this->helpers->astra_wc_get_orders($args, $search);

        if ($orders) {
            return [
                'orders' => $orders->orders,
                'total_orders' => $orders->total,
                'total_pages' =>  $orders->max_num_pages,
                'current_page' => $paged
            ];
        } else {
            return [
                'orders' => [],
                'total_orders' => 0,
                'total_pages' =>  1,
                'current_page' => 1
            ];
        }
    }

    public function getMultipleShipmentLabels()
    {
        $api_key = get_option('cslfw_cargo_api_key');

        if ($api_key) {
            $cargo = new CargoAPIV2();
        } else {
            $cargo = new Cargo();
        }
        parse_str(sanitize_text_field($_POST['form_data']), $data);
        parse_str($_POST['shipments'], $shipmentIds);

        $orderIds = array_map('sanitize_text_field', $_POST['orderIds']);

        if ($data['printType'] === 'A4') {
            $response = $cargo->generateMultipleLabelsA4($shipmentIds['shipments'], $data['startingPoint']);
        } else {
            $orderIds = $orderIds ? $orderIds : [];
            $shipmentsData = $this->helpers->getProductsForLabels($shipmentIds['shipments'], $orderIds);
            $withProducts = get_option('cslfw_products_in_label');

            if ($withProducts && $shipmentsData) {
                $response = $cargo->generateMultipleLabel($shipmentIds['shipments'], $shipmentsData);
            } else {
                $response = $cargo->generateMultipleLabel($shipmentIds['shipments']);
            }
        }


        echo wp_json_encode($response);
        exit;
    }

    public function render()
    {
        $data =  $this->getOrders();

        $this->helpers->load_template('shipments-table', $data);
    }

    public function import_assets() {
        $screen     = get_current_screen();
        $screen_id  = $screen ? $screen->id : '';


        if( $screen_id === 'cargo-shipping-location_page_cargo_shipments_table' ) {
            wp_enqueue_style( 'admin-baldarp-styles', CSLFW_URL . 'assets/css/admin-baldarp-styles.css', [], CSLFW_VERSION );
            wp_enqueue_style( 'shipments-table', CSLFW_URL . 'assets/css/shipments-table.css', [], CSLFW_VERSION );

            wp_enqueue_script( 'cargo-shipments-table', CSLFW_URL . 'assets/js/admin/shipments-table.js', ['jquery'], '1.0.0', true);
            wp_localize_script( 'cargo-shipments-table', 'admin_shipments_obj',
                [
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'ajax_nonce'    => wp_create_nonce( 'cslfw_shipping_nonce' ),
                    'path' => CSLFW_URL,
                ]
            );
        }
    }
}
