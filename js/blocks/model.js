wp.blocks.registerBlockType( 'inventory-presser/model', {
	title: 'Model',

	icon: 'admin-network', //it's a key

	category: 'inventory-presser',

	attributes: {
		make: {
			type: 'string',
			source: 'meta',
			meta: 'inventory_presser_model'
		},
	},

	edit: function( props ) {
		return props.attributes.model;
	},

	save: function( props ) {
		return wp.element.createElement(
			'span',
			{
				className: 'invp_model'
			},
			props.attributes.model
		);
	}
} );