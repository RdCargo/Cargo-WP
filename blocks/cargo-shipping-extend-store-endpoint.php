<?php
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;

/**
 * Shipping Workshop Extend Store API.
 */
class Cargo_Shipping_Extend_Store_Endpoint {
	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendRestApi
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cargo-shipping';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 */
	public static function init() {
		self::$extend = Automattic\WooCommerce\StoreApi\StoreApi::container()->get( Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class );
		self::extend_store();
	}

	/**
	 * Registers the actual data into each endpoint.
	 */
	public static function extend_store() {
		/**
		 * [backend-step-02]
		 * 📝 Once the `extend_checkout_schema` method is complete (see [backend-step-01]) you can
		 * uncomment the code below.
		 */
		if ( is_callable( [ self::$extend, 'register_endpoint_data' ] ) ) {
			self::$extend->register_endpoint_data(
				[
					'endpoint'        => CheckoutSchema::IDENTIFIER,
					'namespace'       => self::IDENTIFIER,
					'schema_callback' => [ 'Cargo_Shipping_Extend_Store_Endpoint', 'extend_checkout_schema' ],
					'schema_type'     => ARRAY_A,
				]
			);
		}
	}


	/**
	 * Register shipping workshop schema into the Checkout endpoint.
	 *
	 * @return array Registered schema.
	 *
	 */
	public static function extend_checkout_schema() {
        /**
         * [backend-step-01]
		 * 📝 Uncomment the code below and update the values in the array, following the instructions.
         *
         * We need to describe the shape of the data we're adding to the Checkout endpoint. Since we expect the shopper
         * to supply an option from the select box and MAYBE enter text into the `other` field, we need to describe two things.
         *
         * This function should return an array. Since we're adding two keys on the client, this function should
         * return an array with two keys. Each key describes the shape of the data for each field coming from the client.
         *
         */

        return [
            'otherShippingValue'   => [
                'description' => __('otherShippingValue description','cargo-shipping-location-for-woocommerce'), // Enter a description,
                'type'        => 'string', // Define the type, this should be a `string`,
                'context'     => ['view', 'edit'], // Define the contexts this should appear in This should be an array containing `view` and `edit`,
                'readonly'    => true, // Using a boolean value, make this field readonly,
                'optional'    => true,// Using a boolean value, make this field optional,
                'arg_options' => [
                    'validate_callback' => function( $value ) {
                        // Make this function return true if $value is a string, or false otherwise.
						return is_string($value);
                    },
                ]
            ],
            'alternateShippingInstruction'   => [
            	'description' => __('alternateShippingInstruction description','cargo-shipping-location-for-woocommerce'), // Enter a description,
                'type'        => 'string', // Define the type, this should be a `string`,
                'context'     => ['view', 'edit'], // Define the contexts this should appear in This should be an array containing `view` and `edit`,
                'readonly'    => true, // Using a boolean value, make this field readonly,
                'arg_options' => [
                    'validate_callback' => function( $value ) {
                        // Make this function return true if $value is a string, or false otherwise.
						return is_string($value);
					},
                ]
            ],
        ];
    }
}
