<?php

namespace CSLFW\Includes\CargoAPI;

trait Helpers
{
    public function post($url, $data = []) {
        $args = [
            'method'      => 'POST',
            'timeout'     => 45,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers' => [
                'Content-Type: application/json',
            ],
        ];

        if ( $data ) $args['body'] = json_encode($data);
        $response   = wp_remote_post($url, $args);
        $response   = wp_remote_retrieve_body($response) or die("Error: Cannot create object. ORDERID = {$data['Params']['TransactionID']} <pre>" . $args['body']);
        return json_decode( $response );
    }
}
