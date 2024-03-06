<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Cargo_Express_Shipping_Method' ) ) {
    class Cargo_Express_Shipping_Method extends WC_Shipping_Method {
		/**
         * Constructor for your shipping class
         */
        public function __construct($instance_id = 0) {

            $this->id                 = 'cargo-express';
            $this->instance_id 		  = absint( $instance_id );
        	$this->method_title       = esc_html__( 'CARGO EXPRESS', 'cargo-shipping-location-for-woocommerce' );
            $this->method_description = esc_html__( 'Custom Shipping method CARGO Express for Home Delivery', 'cargo-shipping-location-for-woocommerce' );
            $this->supports           = ['shipping-zones','instance-settings','instance-settings-modal','settings'];
			$this->enabled            = 'yes';
			$this->title       		  = esc_html__( 'CARGO EXPRESS SHIPPING', 'cargo-shipping-location-for-woocommerce');
			$this->init();

			$this->title = $this->get_option('title');
			$this->shipping_cost = $this->get_option('shipping_cost');
            $this->weight_limit = $this->get_option('weight_limit');
            $this->free_shipping_amount = $this->get_option('free_shipping_amount');
		}

		public function init() {
            // Load the settings API
            $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
            $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
        }

		public function init_form_fields() {
			$this->instance_form_fields = [
				'title' => [
					'title' => esc_html__( 'Title', 'cargo-shipping-location-for-woocommerce' ),
					'type' => 'text',
					'description' => esc_html__( 'Title to be display on site', 'cargo-shipping-location-for-woocommerce' ),
					'default' => esc_html__( 'שילוח עד הבית CARGO Express', 'cargo-shipping-location-for-woocommerce' )
				],
				'shipping_cost' => [
					'title' => esc_html__( 'Shipping cost', 'cargo-shipping-location-for-woocommerce' ),
					'type' => 'text',
					'description' => esc_html__( '', 'cargo-shipping-location-for-woocommerce' ),
					'default' => esc_html__( '', 'cargo-shipping-location-for-woocommerce' )
				],
                'weight_limit' => [
                    'title' => esc_html__( 'Cart Weight limit', 'cargo-shipping-location-for-woocommerce' ),
                    'type' => 'number',
                    'description' => esc_html__( 'Set here the weight limit with the dot. e.g. "3.5"', 'cargo-shipping-location-for-woocommerce' ),
                    'default' => esc_html__( '', 'cargo-shipping-location-for-woocommerce' )
                ],
				'free_shipping_amount' => [
					'title' => esc_html__( 'Free shipping from an amount', 'cargo-shipping-location-for-woocommerce' ),
					'type' => 'text',
					'description' => esc_html__( '', 'cargo-shipping-location-for-woocommerce' ),
					'default' => esc_html__( '', 'cargo-shipping-location-for-woocommerce' )
				]
			];
		}

        /**
         * Set the availability based on cart weight.
         *
         * @param $package
         * @return bool
         */
        public function is_available( $package )
        {
            if ($this->weight_limit > 0) {
                $max_weight = 10; // Change this to your desired weight

                $cart_weight = WC()->cart->get_cart_contents_weight();
                return $cart_weight < $max_weight;
            } else {
                return true;
            }

        }

        /**
         * @param array $package
         */
		public function calculate_shipping( $package = [] ) {
			if(!empty($this->shipping_cost)) {
				$this->add_rate([
					'id'    => $this->id .":" .$this->instance_id,
					'label' => $this->title,
					'cost'  => $this->shipping_cost,
				]);
			}

			$total_price = 0;
			foreach ( $package['contents'] as $item_id => $values ) {
				$total_price += floatval($values['line_total']);
			}
			if($total_price > $this->free_shipping_amount) {
				if(!empty($this->free_shipping_amount)) {
					$this->add_rate([
						'id'    => $this->id .":" .$this->instance_id,
						'label' => $this->title,
						'cost'  => 0
					]);
				}
			}

		}
	}
}
