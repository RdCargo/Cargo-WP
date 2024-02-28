<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use CSLFW\Includes\CargoAPI\Cargo;

/**
 * Class for integrating with WooCommerce Blocks
 */
    class Cargo_Shipping_Blocks_Integration implements IntegrationInterface {
		/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'cargo-shipping';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		require_once __DIR__ . '/cargo-shipping-extend-store-endpoint.php';
		$this->register_cargo_shipping_block_frontend_scripts();
		$this->register_cargo_shipping_block_editor_scripts();
		$this->register_cargo_shipping_block_editor_styles();
		$this->register_main_integration();
		$this->extend_store_api();
		$this->save_shipping_instructions();
		$this->show_shipping_instructions_in_order();
		/**
		 * [backend-step-09-extra-credit]
		 * 💰 Extra credit: Display the selection on the order confirmation page.
		 *
		 * To accomplish this, you'll need to add an action to the
		 * `woocommerce_order_details_after_customer_details` hook. Uncomment the function call in
		 * the next line, and implement the function body of `show_shipping_instructions_in_order_confirmation`.
		 *
		 * 💡 Similar to [backend-step-08] respectively [backend-step-08-extra-credit], you'll need
		 * to get the `cargo_shipping_alternate_shipping_instruction` and
		 * `cargo_shipping_alternate_shipping_instruction_other_text` and print them out.
		 */
		// $this->show_shipping_instructions_in_order_confirmation();

		/**
		 * [backend-step-10-extra-credit]
		 * 💰 Extra credit: Display the selection in the order confirmation email.
		 *
		 * To accomplish this, you'll need to add an action to the
		 * `woocommerce_email_customer_details` hook. Uncomment the function call in
		 * the next line, and implement the function body of `show_shipping_instructions_in_order_email`.
		 *
		 * 💡 Similar to [backend-step-08] respectively [backend-step-08-extra-credit], you'll need
		 * to get the `cargo_shipping_alternate_shipping_instruction` and
		 * `cargo_shipping_alternate_shipping_instruction_other_text` and print them out.
		 */
		// $this->show_shipping_instructions_in_order_email();
	}

	/**
	 * Extends the cart schema to include the cargo-shipping value.
	 */
	private function extend_store_api() {
		Cargo_Shipping_Extend_Store_Endpoint::init();
	}

    private function save_shipping_instructions() {
        /**
         * Complete the below `woocommerce_store_api_checkout_update_order_from_request` action.
		 * This will update the order metadata with the cargo-shipping alternate shipping instruction.
         *
         * The documentation for this hook is at: https://github.com/woocommerce/woocommerce-blocks/blob/b73fbcacb68cabfafd7c3e7557cf962483451dc1/docs/third-party-developers/extensibility/hooks/actions.md#woocommerce_store_api_checkout_update_order_from_request
         */
        add_action(
            'woocommerce_store_api_checkout_update_order_from_request',
            function( \WC_Order $order, \WP_REST_Request $request ) {
                $cargo_shipping_request_data = $request['extensions'][$this->get_name()];
                /**
				 * [backend-step-03]
                 * 📝 From the `$cargo_shipping_request_data` array, get the
				 * `alternateShippingInstruction` and `otherShippingValue` entries. Store them in
				 * their own variables, $alternate_shipping_instruction and $other_shipping_value.
                 */
				$alternate_shipping_instruction = $cargo_shipping_request_data['alternateShippingInstruction'];
				$other_shipping_value 			= $cargo_shipping_request_data['otherShippingValue'];
				/**
				 * [backend-step-04]
                 * 📝 Using `$order->update_meta_data` update the order metadata.
                 * Set the value of the `cargo_shipping_alternate_shipping_instruction` key to
				 * `$alternate_shipping_instruction`.
				 * Set the value of the `cargo_shipping_alternate_shipping_instruction_other_text`
				 * key to `$other_shipping_value`.
                 */
				$order->update_meta_data('cargo_shipping_alternate_shipping_instruction', $alternate_shipping_instruction);
				$order->update_meta_data('cargo_shipping_alternate_shipping_instruction_other_text', $other_shipping_value);
				/**
                 * [backend-step-04-extra-credit-1]
				 * 💰 Extra credit: Instead of the `cargo_shipping_alternate_shipping_instruction`
				 * key, show the translation-ready value.
                 */

				/**
                 * [backend-step-04-extra-credit-2]
				 * 💰 Extra credit: Avoid setting `cargo_shipping_alternate_shipping_instruction_other_text` if
                 * `$alternate_shipping_instruction_other_text` is not a string, or if it is empty.
                 */

				 /**
				  * [backend-step-05]
				  * 💡 Don't forget to save the order using `$order->save()`.
				  */
				 $order->save();
            },
            10,
            2
        );
    }

    /**
     * Adds the address in the order page in WordPress admin.
     */
    private function show_shipping_instructions_in_order() {
        add_action(
            'woocommerce_admin_order_data_after_shipping_address',
            function( \WC_Order $order ) {
                /**
                 * [backend-step-06]
				 * 📝 Get the `cargo_shipping_alternate_shipping_instruction` from the order metadata using `$order->get_meta`.
                 */
				$cargo_shipping_alternate_shipping_instruction = $order->get_meta('cargo_shipping_alternate_shipping_instruction');
                /**
				 * [backend-step-07]
                 * 📝 Get the `cargo_shipping_alternate_shipping_instruction_other_text` from the order metadata using `$order->get_meta`.
                 */
				$cargo_shipping_alternate_shipping_instruction_other_text = $order->get_meta('cargo_shipping_alternate_shipping_instruction_other_text');

				echo '<div>';
                echo '<strong>' . __( 'Shipping Instructions', 'cargo-shipping' ) . '</strong>';
                /**
				 * [backend-step-08]
				 * 📝 Output the alternate shipping instructions here!
				 */
                echo "<div>{$cargo_shipping_alternate_shipping_instruction}</div>";
                echo "<div>{$cargo_shipping_alternate_shipping_instruction_other_text}</div>";
				 /**
				 * [backend-step-08-extra-credit]
				 * 💰 Extra credit: Don't show the other value if the `cargo_shipping_alternate_shipping_instruction` is not `other`.
				 *
				 * Output the shipping instructions in the order admin by echoing them here.
				 */
                echo '</div>';
            }
        );
    }

	/**
     * Adds the address on the order confirmation page.
     */
	private function show_shipping_instructions_in_order_confirmation() {
		/**
		 * [backend-step-09-extra-credit]
		 * Add your code here …
		 */
	}

	/**
	 * Adds the address on the order confirmation email.
	 */
	public function show_shipping_instructions_in_order_email() {
		/**
		 * [backend-step-10-extra-credit]
		 * Add your code here …
		 */
	}

	/**
	 * Registers the main JS file required to add filters and Slot/Fills.
	 */
	private function register_main_integration() {
		$script_path = '/build/index.js';
		$style_path  = '/build/style-index.css';

		$script_url = plugins_url( $script_path, __FILE__ );
		$style_url  = plugins_url( $style_path, __FILE__ );

		$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_path ),
			];

		wp_enqueue_style(
			'cargo-shipping-blocks-integration',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);

		wp_register_script(
			'cargo-shipping-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'cargo-shipping-blocks-integration',
			'cargo-shipping',
			dirname( __FILE__ ) . '/languages'
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return [ 'cargo-shipping-blocks-integration', 'cargo-shipping-block-frontend' ];
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return [ 'cargo-shipping-blocks-integration', 'cargo-shipping-block-editor' ];
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
//		$chosenShippingMethod = WC()->session->get('chosen_shipping_methods')[0];
//		$chosenMethodId = explode(':', $chosenShippingMethod);
//		$chosenMethodId = reset($chosenMethodId);

		$boxPoints = $this->getBoxPoints();
		$boxCities = $this->getBoxCities($boxPoints);
		$chosenMethodId = '';
		return [
			'cargo-shipping-active' => true,
			'boxCities' => $boxCities,
			'shippingMethod' => $chosenMethodId,
			'boxPoints' => $boxPoints
		];
	}

	protected function getBoxPoints()
	{
		$cargo = new Cargo();

		return $cargo->getPickupPoints();
	}

	protected function getBoxCities($boxPoints)
	{
		if ($boxPoints) {
			$cities = array_unique(array_map(function($point) {
				return $point->CityName;
			}, $boxPoints));
			$boxCities = [
				[
					"label" => 'Pick the city',
					"value" => 0
				]
			];
			$cities = array_map(function($city) {
				return [
					"label" => $city,
					"value" => $city,
				];
			}, $cities);
			return array_merge($boxCities, $cities);
		} else {
			return [];
		}
	}

	public function register_cargo_shipping_block_editor_styles() {
		$style_path = '/build/style-cargo-shipping-block.css';

		$style_url = plugins_url( $style_path, __FILE__ );
		wp_enqueue_style(
			'cargo-shipping-block',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);
	}

	public function register_cargo_shipping_block_editor_scripts() {
		$script_path       = '/build/cargo-shipping-block.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/cargo-shipping-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'cargo-shipping-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'cargo-shipping-block-editor',
			'cargo-shipping',
			dirname( __FILE__ ) . '/languages'
		);
	}

	public function register_cargo_shipping_block_frontend_scripts() {
		$script_path       = '/build/cargo-shipping-block-frontend.js';
		$script_url        = plugins_url( $script_path, __FILE__ );
		$script_asset_path = dirname( __FILE__ ) . '/build/cargo-shipping-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => $this->get_file_version( $script_asset_path ),
			];

		wp_register_script(
			'cargo-shipping-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_localize_script( 'cargo-shipping-block-frontend', 'cargo_obj',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'    => wp_create_nonce( 'cslfw_shipping_nonce' ),
			)
		);
		wp_set_script_translations(
			'cargo-shipping-block-frontend',
			'cargo-shipping',
			dirname( __FILE__ ) . '/languages'
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return SHIPPING_WORKSHOP_VERSION;
	}
}
