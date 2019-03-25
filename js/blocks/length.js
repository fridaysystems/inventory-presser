wp.blocks.registerBlockType( 'inventory-presser/length', {
	title: 'Length',

	icon: 'admin-network', //it's a key

	category: 'inventory-presser',

	attributes: {
		length: {
			type: 'string',
			source: 'meta',
			meta: 'inventory_presser_length'
		},
	},

	edit: function( props ) {
		return props.attributes.length;
	},

	save: function( props ) {
		return wp.element.createElement(
			'span',
			{
				className: 'invp_length'
			},
			props.attributes.length
		);
	}
} );