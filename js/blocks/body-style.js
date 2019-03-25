wp.blocks.registerBlockType( 'inventory-presser/body-style', {
	title: 'Body Style',

	icon: 'admin-network', //it's a key

	category: 'inventory-presser',

	attributes: {
		body_style: {
			type: 'string',
			source: 'meta',
			meta: 'inventory_presser_body_style'
		},
	},

	edit: function( props ) {
		return props.attributes.body_style;
	},

	save: function( props ) {
		return wp.element.createElement(
			'span',
			{
				className: 'invp_body_style'
			},
			props.attributes.body_style
		);
	}
} );