/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';

const {
	boxPoints,
	boxCities,
	shippingMethod
} = getSetting( 'cargo-shipping_data', '' );

export default {
	boxCities: boxCities,
	boxPoints: boxPoints,
	shippingMethod: shippingMethod
};
