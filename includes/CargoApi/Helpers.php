<?php

namespace CSLFW\Includes\CargoAPI;

trait Helpers
{
    /**
     * @param $url
     * @param array $data
     * @param array $headers
     * @return mixed
     */
    public function post($url, $data = [], $headers = []) {
        $postHeaders = array_merge($headers, [
            'Content-Type' => 'application/json'
        ]);

        $args = [
            'method'      => 'POST',
            'timeout'     => 45,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers' => $postHeaders
        ];

        if ( $data ) $args['body'] = wp_json_encode($data);
        $response   = wp_remote_post($url, $args);
        $response   = wp_remote_retrieve_body($response) or die("Error: Cannot create object. <pre>" . $args['body']);
        if (get_option('cslfw_debug_mode')) {

            $logs = new \CSLFW_Logs();
            $message = "CARGO ARGS : " . wp_json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            $message .= "CARGO RESPONSE: " . wp_json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

            $logs->add_log_message($message . PHP_EOL);
        }
        return json_decode( $response );
    }

    /**
     * @param $url
     * @param array $data
     * @param array $headers
     * @return mixed
     */
    public function get($url, $data = [], $headers = []) {
        $postHeaders = array_merge($headers, [
            'Content-Type' => 'application/json'
        ]);

        $args = [
            'method'      => 'GET',
            'timeout'     => 45,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers' => $postHeaders
        ];

        if ( $data ) $args['body'] = wp_json_encode($data);
        $response   = wp_remote_post($url, $args);
        $response   = wp_remote_retrieve_body($response) or die("Error: Cannot create object. <pre>" . $args['body']);
        return json_decode( $response );
    }

    /**
     * @param $url
     * @param array $data
     * @param array $headers
     * @return mixed
     */
    public function delete($url, $data = [], $headers = []) {
        $postHeaders = array_merge($headers, [
            'Content-Type' => 'application/json'
        ]);

        $args = [
            'method'      => 'DELETE',
            'timeout'     => 45,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers' => $postHeaders
        ];

        if ( $data ) $args['body'] = wp_json_encode($data);
        $response   = wp_remote_post($url, $args);
        $response   = wp_remote_retrieve_body($response) or die("Error: Cannot create object. <pre>" . $args['body']);
        return json_decode( $response );
    }
}
