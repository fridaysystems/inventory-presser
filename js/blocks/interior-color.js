wp.blocks.registerBlockType( 'inventory-presser/interior-color', {
	title: 'Interior Color',

	icon: 'admin-network', //it's a key

	category: 'inventory-presser',

	attributes: {
		interior_color: {
			type: 'string',
			source: 'meta',
			meta: 'inventory_presser_interior_color'
		},
	},

	edit: function( props ) {
		return props.attributes.interior_color;
	},

	save: function( props ) {
		return wp.element.createElement(
			'span',
			{
				className: 'invp_interior_color'
			},
			props.attributes.interior_color
		);
	}
} );