<?php
namespace CSLFW\Includes\CargoAPI;

use CSLFW\Includes\CSLFW_Helpers;

class Webhook
{
    use Helpers;
    private $apiKey;
    private $headers;
    public function __construct()
    {
        $this->helpers = new CSLFW_Helpers();
        $this->apiKey = get_option('cslfw_cargo_api_key');

        $this->headers = [
            "Authorization" => "Bearer {$this->apiKey}",
        ];

        add_action('rest_api_init', [$this, 'cargo_status_update_webhook']);

        add_action('admin_menu', [$this, 'add_menu_link'], 100);
        add_action('wp_ajax_cslfw_save_cargo_api', [$this, 'save_cargo_api']);
        add_action('wp_ajax_cslfw_add_webhooks', [$this, 'send_webhooks_to_cargo']);
        add_action('wp_ajax_cslfw_delete_webhooks', [$this, 'delete_webhooks_from_cargo']);
        add_action('admin_enqueue_scripts', [$this, 'import_assets'] );
    }


    public function cargo_status_update_webhook()
    {
        register_rest_route( 'cargo-shipping-location-for-woocommerce/v1', '/update-status/',
            [
                'methods'  => 'POST',
                'callback' => [$this, 'cargo_update_shipment_status']
            ]
        );
    }

    /**
     * Webhook callback to update cargo shipment status.
     *
     * @param $request
     * @return mixed
     */
    public function cargo_update_shipment_status($request)
    {
        $data = $request->get_params();

        $args = [
            'meta_query' => [
                [
                    'key'     => 'cslfw_shipping', // meta_key to search
                    'value'   => "{$data['shipment_id']}", // part of the meta_value to match
                    'compare' => 'LIKE' // Perform a LIKE comparison
                ]
            ]
        ];

        if ( class_exists( \WC_Order_Query::class ) ) {
            $orders_query = new \WC_Order_Query( $args );
            $orders = $orders_query->get_orders();

            if ( $orders ) {
                foreach ( $orders as $order ) {
                    $deliveries = $order->get_meta('cslfw_shipping', true);

                    $deliveries[$data['shipment_id']]['status']['number'] = $data['new_status_code'];
                    $deliveries[$data['shipment_id']]['status']['text'] = $data['new_status'];

                    $order->update_meta_data('cslfw_shipping', $deliveries);
                    $order->save();
                }
            }

            $response = array(
                'success' => $orders ? true : false,
                'data' => $deliveries,
                'message' => $orders ? 'Database updated successfully' : 'Failed to update database',
            );

        } else {
            $response = [
                'success' => false,
                'data' => $args,
                'message' => 'WC_Order_Query not exist'
            ];
        }

        // Return the response
        return rest_ensure_response($response);
    }

    public function save_cargo_api()
    {
        parse_str(sanitize_text_field($_POST['form_data']), $data);

        if (!isset($data['_wpnonce']) && !wp_verify_nonce(sanitize_text_field($data['_wpnonce']), 'cslfw-save-api-key')) {
            echo wp_json_encode([
                'error' => true,
                'message' => 'Bad request, try again later.',
            ]);
            wp_die();
        }

        $headers = [
            "Authorization" => "Bearer {$data['cslfw_api_key']}",
        ];
        $response = $this->get("https://api-v2.cargo.co.il/api/token/auth", [], $headers);

        if ($response && !$response->errors) {
            if (empty($this->apiKey)) {
                add_option('cslfw_cargo_api_key', $data['cslfw_api_key']);
            } else {
                update_option('cslfw_cargo_api_key', $data['cslfw_api_key']);
            }
        } else {
            delete_option('cslfw_webhooks_installed');
        }

        echo wp_json_encode([
            'api_key' => $data['cslfw_api_key'],
            'error' => is_null($response) || $response->errors,
            'response' => $response,
            'message' => is_null($response) || $response->errors
                ? esc_html__('API key is not valid.', 'cargo-shipping-location-for-woocommerce')
                : esc_html__('Api key successfully saved. Reloading page.', 'cargo-shipping-location-for-woocommerce')
        ]);
        wp_die();
    }

    public function delete_webhooks_from_cargo()
    {
        if (!isset($_POST['_wpnonce']) && !wp_verify_nonce( sanitize_text_field($_POST['_wpnonce']), 'cslfw_cargo_update_or_remove_webhook')) {
            echo wp_json_encode([
                'error' => true,
                'message' => 'Bad request, try again later.',
            ]);
            wp_die();
        }

        $customerCodes = [
            'express' => get_option('shipping_cargo_express'),
            'box' => get_option('shipping_cargo_box'),
            'pickup' => get_option('shipping_pickup_code')
        ];
        $error = true;

        foreach ($customerCodes as $key => $customerCode) {
            if (!empty($customerCode)) {
                $optionKey = "cslfw_cargo_{$key}_webhook_id";

                if ($webhookId = get_option($optionKey)) {
                    $response = $this->deleteWebhook($customerCode, $webhookId);

                    if (!$response->errors) {
                        $error = false;
                        delete_option($optionKey);
                    }
                }
            }
        }

        echo wp_json_encode([
            'error' => $error,
            'message' => $error ? 'Something went wrong. Contact support.' : 'Webhooks successfully deleted.',
        ]);
        wp_die();
    }

    public function send_webhooks_to_cargo()
    {
        $customerCodes = [
            'express' => get_option('shipping_cargo_express'),
            'box' => get_option('shipping_cargo_box'),
            'pickup' => get_option('shipping_pickup_code')
        ];
        $error = true;

        foreach ($customerCodes as $key => $customerCode) {
            if (!empty($customerCode)) {
                $optionKey = "cslfw_cargo_{$key}_webhook_id";

                if ($webhook = get_option($optionKey)) {
                    $response = $this->updateCargoWebhook($customerCode, $webhook);
                } else {
                    $response = $this->addWebhookToCargo($customerCode);
                }

                if (!$response->errors) {
                    $error = false;
                    $webhookId = $response->data->id;
                    update_option($optionKey, $webhookId);
                }
            }
        }

        if ($error) {
            delete_option('cslfw_webhooks_installed');
        } else {
            add_option('cslfw_webhooks_installed', 'yes');
        }

        echo wp_json_encode([
            'error' => $error,
            'message' => $error ? 'Something went wrong. Contact support.' : 'Webhooks successfully updated.',
        ]);
        wp_die();
    }

    /**
     * @param $customerCode
     * @return array
     */
    protected function getWebhookArgs($customerCode)
    {
        $host = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $host.= sanitize_text_field($_SERVER['HTTP_HOST']);

        return [
            'type' => 'status-update',
            'customer_code' => (int) $customerCode,
            'webhook_url' => "{$host}/wp-json/cargo-shipping-location-for-woocommerce/v1/update-status/"
        ];
    }

    /**
     * @param $customerCode
     * @return mixed
     */
    public function updateCargoWebhook($customerCode, $webhookId)
    {
        $args = $this->getWebhookArgs($customerCode);
        $args['webhook_id'] = $webhookId;

        return $this->post("https://dashboard.cargo.co.il/api/webhooks/update", $args, $this->headers);
    }

    /**
     * @param $customerCode
     * @return mixed
     */
    public function addWebhookToCargo($customerCode)
    {
        $args = $this->getWebhookArgs($customerCode);

        return $this->post("https://dashboard.cargo.co.il/api/webhooks/create", $args, $this->headers);
    }

    /**
     * @param $customerCode
     * @param $webhookId
     * @return mixed
     */
    public function deleteWebhook($customerCode, $webhookId)
    {
        $args = [
            'webhook_id' => $webhookId,
            'customer_code' => $customerCode,
        ];

        return $this->delete("https://dashboard.cargo.co.il/api/webhooks/delete", $args, $this->headers);
    }

    public function add_menu_link()
    {
        add_submenu_page('loaction_api_settings', 'Status Webhook', 'Status Webhook', 'manage_options', 'cargo_shipping_webhook', [$this, 'render'] );
    }

    public function render()
    {
        $customerCodes = [
            'express' => get_option('shipping_cargo_express'),
            'box' => get_option('shipping_cargo_box'),
            'pickup' => get_option('shipping_pickup_code'),
        ];
        $webhooksInstalled = false;

        foreach ($customerCodes as $key => $customerCode) {
            if (!empty($customerCode)) {
                $optionKey = "cslfw_cargo_{$key}_webhook_id";

                if (get_option($optionKey)) {
                    $webhooksInstalled = true;
                }
            }
        }

        $data = [
            'apiKey' => $this->apiKey,
            'webhooksInstalled' => $webhooksInstalled,
        ];

        $this->helpers->load_template('webhook', $data);
    }

    public function import_assets() {
        wp_enqueue_script( 'cargo-webhook', CSLFW_URL . 'assets/js/admin/cslfw-webhook.js', ['jquery'], CSLFW_VERSION, true);
    }
}
