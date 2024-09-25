import { registerBlockType } from '@wordpress/blocks';
import { TextControl } from '@wordpress/components';

var simple_meta_keys = [
	'body_style',
	'color',
	'down_payment',
	'engine',
	'interior_color',
	'last_modified',
	'make',
	'model',
	'msrp',
	'odometer',
	'payment',
	'payment_frequency',
	'price',
	'stock_number',
	'title_status',
	'transmission_speeds',
	'trim',
	'vin',
	'year',
	'youtube'
];
for ( const key of invp_blocks.keys ) {
	if ( ! simple_meta_keys.includes( key['name'] ) ) {
		continue;
	}

	registerBlockType(
		'inventory-presser/' + key['name'].replace( '_', '-' ),
		{ // Block names must include only lowercase alphanumeric characters or dashes and start with a letter.
			title: key['label'], // what the user sees.
			icon: 'admin-network', // dashicon.
			category: 'inventory-presser', // block category.
			attributes: {
				blockValue: {
					type: key['type'],
					source: 'meta',
					meta: invp_blocks.meta_prefix + key['name'], // the meta key where the value gets saved
				},
			},

			example: {
				attributes: {
					blockValue: key['sample'],
				},
			},

			edit( { className, setAttributes, attributes } ) {
				return (
					<div className  = { className } >
						<TextControl
							label    = { key['label'] }
							value    = { attributes.blockValue }
							onChange = {(newtext) => setAttributes( { blockValue: newtext } )}
						/>
					</div>
				);
			},

			// No information saved to the block.
			// Data is saved to post meta via attributes.
			save() {
				return null;
			},
		}
	);
}
