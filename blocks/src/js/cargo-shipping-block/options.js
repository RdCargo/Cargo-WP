/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const options = [
	{
		label: __('Try again another day', 'cargo-shipping-location-for-woocommerce'),
		value: 'try-again',
	},
	{
		label: __('Leave in the shed', 'cargo-shipping-location-for-woocommerce'),
		value: 'leave-in-shed',
	},
	{
		label: __('Other', 'cargo-shipping-location-for-woocommerce'),
		value: 'other',
	},
	/**
	 * [frontend-step-01]
	 * 📝 Add more options using the same format as above. Ensure one option has the key "other".
	 */
];
