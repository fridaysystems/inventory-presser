wp.blocks.registerBlockType( 'inventory-presser/beam', {
	title: 'Beam',

	icon: 'admin-network', //it's a key

	category: 'inventory-presser',

	attributes: {
		beam: {
			type: 'string',
			source: 'meta',
			meta: 'inventory_presser_beam'
		},
	},

	edit: function( props ) {
		return props.attributes.beam;
	},

	save: function( props ) {
		return wp.element.createElement(
			'span',
			{
				className: 'invp_beam'
			},
			props.attributes.beam
		);
	}
} );