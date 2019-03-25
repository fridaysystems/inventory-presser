wp.blocks.registerBlockType( 'inventory-presser/make', {
	title: 'Make',

	icon: 'admin-network', //it's a key

	category: 'inventory-presser',

	attributes: {
		make: {
			type: 'string',
			source: 'meta',
			meta: 'inventory_presser_make'
		},
	},

	edit: function( props ) {
		return props.attributes.make;
	},

	save: function( props ) {
		return wp.element.createElement(
			'span',
			{
				className: 'invp_make'
			},
			props.attributes.make
		);
	}
} );