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

        if ( $data ) $args['body'] = json_encode($data);
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

        if ( $data ) $args['body'] = json_encode($data);
        $response   = wp_remote_post($url, $args);
        $response   = wp_remote_retrieve_body($response) or die("Error: Cannot create object. <pre>" . $args['body']);
        return json_decode( $response );
    }
}
