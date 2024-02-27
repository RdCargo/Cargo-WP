/**
 * External dependencies
 */
import { useEffect, useState, useCallback } from '@wordpress/element';
import { SelectControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import attributes from './attributes';
export const Block = ({ checkoutExtensionData, extensions }) => {
	// TODO need to find the way to fetch shipping method to completely hide the block.
	/**
	 * setExtensionData will update the wc/store/checkout data store with the values supplied. It
	 * can be used to pass data from the client to the server when submitting the checkout form.
	 */

	const { setExtensionData } = checkoutExtensionData;
	/**
	 * Debounce the setExtensionData function to avoid multiple calls to the API when rapidly
	 * changing options.
	 */
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const debouncedSetExtensionData = useCallback(
		debounce((namespace, key, value) => {
			setExtensionData(namespace, key, value);
		}, 1000),
		[setExtensionData]
	);

	const validationErrorId = 'cargo-shipping-other-value';

	const { setValidationErrors, clearValidationError } = useDispatch(
		'wc/store/validation'
	);

	const validationError = useSelect((select) => {
		const store = select('wc/store/validation');
		/**
		 * [frontend-step-07]
		 * 📝 Write some code to get the validation error from the `wc/store/validation` data store.
		 * Using the `getValidationError` selector on the `store` object, get the validation error.
		 *
		 * The `validationErrorId` variable can be used to get the validation error. Documentation
		 * on the validation data store can be found here:
		 * https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/data-store/validation.md
		 */

		/**
		 * [frontend-step-07-extra-credit]
		 * 💰 Extra credit: In the `useEffect` that handles the "other" textarea, only call
		 * `clearValidationError` if the validation error is in the data store already.
		 */
	});
	const [
		boxPointCity,
		setBoxPointCity,
	] = useState('0');
	const [
		boxPointId,
		setBoxPoint
	] = useState('');

	const [
		boxPointFiltered,
		setBoxPointFiltered
	] = useState([]);
	const [
		shippingMethod,
		setShippingMethod
	] = useState(attributes.shippingMethod);

	useEffect(() => {
		setExtensionData( 'cargo-shipping', 'boxPointCity', boxPointCity )

		setBoxPointFiltered(attributes.boxPoints
			.filter(obj => obj.CityName === boxPointCity)
			.map(obj => ({
				label: `${obj.DistributionPointName}, ${obj.StreetName} ${obj.StreetNum}`,
				value: obj.DistributionPointID
			}))
		);

		setExtensionData('cargo-shipping', 'boxPointFiltered', boxPointFiltered);
	}, [setExtensionData, boxPointCity, attributes.boxPoints]);

	/**
	 * [frontend-step-02-extra-credit-2]
	 * 💰 Extra credit: Use a `useState` to track whether the user has interacted with the "other"
	 * textbox. If they have, then the validation error should not be hidden when the user changes
	 * the select's value. If it is "pristine" then we should keep the error hidden.
	 */

	/* Handle changing the "other" value */
	useEffect(() => {
		/**
		 * [frontend-step-03]
		 * 📝 Write some code in this useEffect that will run when the `boxPointId` value
		 * changes. This code should use `setExtensionData` to update the `boxPointId` key
		 * in the `cargo-shipping` namespace of the checkout data store.
		 */
		/**
		 * [frontend-step-03-extra-credit]
		 * 💰 Extra credit: Ensure the `setExtensionData` function is not called multiple times. We
		 * can use the `debouncedSetExtensionData` function for this. The API is the same.
		 */
		/**
		 * [frontend-step-04]
		 * 📝 Write some code that will use `setValidationErrors` to add an entry to the validation
		 * data store if `boxPointId` is empty.
		 *
		 * The API of this function is: `setValidationErrors( errors )`.
		 *
		 * `errors` is an object with the following shape:
		 * {
		 *     [ validationErrorId ]: {
		 *  		message: string,
		 *	    	hidden: boolean,
		 *     }
		 * }
		 *
		 * For now, the error should remain hidden until the user has interacted with the field.
		 *
		 * [frontend-step-04-extra-credit]
		 * 💰 Extra credit: If the `boxPointCity` is not `other` let's skip
		 * adding the validation error. Make sure to place this code before the
		 * `setValidationErrors` call, thus, the spoiler of [frontend-step-04] comes after the one
		 * of [frontend-step-04-extra-credit].
		 */
		/**
		 * [frontend-step-05]
		 * 📝 Update the above code so that it will use `clearValidationError` to remove the
		 * validation error from the data store if `boxPointCity` is not
		 * `other`, or if the `boxPointId` is not empty.
		 *
		 * The API of `clearValidationError` is: `clearValidationError( validationErrorId )`
		 *
		 * 💡Don't forget to update the dependencies of the `useEffect` when you reference new
		 * functions/variables!
		 */
	}, [
		/**
		 * [frontend-step-06]
		 * 💡 Don't forget to update the dependencies of the `useEffect` when you reference new
		 * functions/variables!
		 */
		boxPointId,
		setExtensionData,
	]);

	return (
		<div className="wp-block-cargo-shipping-not-at-home">
			{shippingMethod}
			{shippingMethod === 'woo-baldarp-pickup' &&  (
			<SelectControl
				label={__('Choose the city of delivery…', 'cargo-shipping')}
				value={boxPointCity}
				options={attributes.boxCities}
				onChange={setBoxPointCity}
			/>
			)}

			{boxPointCity !== '0' && (
				<>
					<SelectControl
						label={__('Choose box point…', 'cargo-shipping')}
						required={true}
						value={boxPointId}
						options={boxPointFiltered}
						onChange={setBoxPoint}
					/>
					{/**
					 * [frontend-step-08]
					 * 📝 Write some code in this block that will render a validation error if the
					 * validation error we're using in the wc/store/validation data store is not
					 * hidden. It's fine to just use a div, and display the `message` property of
					 * the validation error.
					 */}
				</>
			)}
		</div>
	);
};
