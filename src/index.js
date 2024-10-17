import { registerBlockType } from '@wordpress/blocks';
import { TextControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * This script registers a block for every meta key in the array below. These
 * meta fields are text fields with minimal formatting for dollar amounts and
 * thousands separators in odometers.
 */

// This is an array of meta keys that are simple text fields.
var simple_meta_keys = [
	'beam', // For boats.
	'body_style',
	'color',
	'down_payment',
	'engine',
	'interior_color',
	'last_modified',
	'length', // For boats.
	'make',
	'model',
	'msrp',
	'odometer',
	'payment',
	'price',
	'stock_number',
	'title_status',
	'transmission_speeds',
	'trim',
	'vin',
	'year',
	'youtube'
];

/**
 * Tests if a passed string value is numeric.
 * @param n String to test.
 * @returns bool
 */
function isNumeric(n) {
	return !isNaN(parseFloat(n)) && isFinite(n);
}

function formatValue( key, value ) {
	if ( 'odometer' === key ) {
		if ( isNumeric( value ) ) {
			return Number( value ).toLocaleString() + ' ' + invp_blocks.odometer_word;
		}
		return value;
	}

	if ( 'price' === key || 'down_payment' === key || 'msrp' === key || 'payment' === key ) {
		if ( isNumeric( value ) ) {
			return invp_blocks.currency_symbol + Number( value ).toLocaleString();
		}
		return value;
	}
	return value;
}

for ( const key of invp_blocks.keys ) {
	if ( ! simple_meta_keys.includes( key['name'] ) ) {
		continue;
	}

	registerBlockType(
		// Block names must include only lowercase alphanumeric characters or dashes and start with a letter.
		'inventory-presser/' + key['name'].replace( '_', '-' ),
		{
			title: key['label'], // what the user sees.
			icon: 'admin-network', // A dashicon. This one is a key.
			category: 'inventory-presser', // block category.
			description: 'Displays and edits the ' + invp_blocks.meta_prefix + key['name'] + ' meta field.', // A description of the block.

			// Stash the meta key in an attribute so our PHP render_callback can find it.
			attributes: {
				key: {
					type: 'string',
				}
			},

			// Provide an example attribute set for block previews.
			example: {
				attributes: {
					key: key['name'],
				}
			},

			edit( { attributes, setAttributes, isSelected } ) {

				const postType = useSelect(
					( select ) => select( 'core/editor' ).getCurrentPostType(),
					[]
				);
				const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

				/**
				 * useEffect is a React hook that triggers during the component's life
				 * cycle parts, but when giving it an empty array as a second argument
				 * it will only trigger on mounting the component
				 *
				 * @link https://wordpress.stackexchange.com/a/393493/13090
				 */

				useEffect(() => {
					//This conditional is useful to only set the id attribute once
					//when the component mounts for the first time
					'undefined' === typeof attributes.key && setAttributes( { key: key['name'] } )
				}, [] );

				/**
				 * Use isSelected to render the lost focus output that mimics the front-end
				 *
				 * @link https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#isselected
				 */
				if ( ! isSelected ) {
					// Do not show the input field when the block is not selected.
					return ( <span> { formatValue( key['name'], meta[ invp_blocks.meta_prefix + key['name'] ] ) } </span> ); //
				}

				return (
					<div>
						<TextControl
							label    = { key['label'] }
							value    = { meta[ invp_blocks.meta_prefix + key['name'] ] }
							onChange = {(newValue) => setMeta( { ...meta, [invp_blocks.meta_prefix + key['name']]: newValue } )}
						/>
					</div>
				);
			},

			// No information saved to the block.
			// Data is saved to post meta.
			save() {
				return null;
			},
		}
	);
}
