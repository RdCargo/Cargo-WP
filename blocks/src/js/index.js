/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

const render = () => {};

registerPlugin('cargo-shipping', {
	render,
	scope: 'woocommerce-checkout',
});
