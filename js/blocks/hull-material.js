wp.blocks.registerBlockType( 'inventory-presser/hull-material', {
	title: 'Hull Material',

	icon: 'admin-network', //it's a key

	category: 'inventory-presser',

	attributes: {
		hull_material: {
			type: 'string',
			source: 'meta',
			meta: 'inventory_presser_hull_material'
		},
	},

	edit: function( props ) {
		return props.attributes.hull_material;
	},

	save: function( props ) {
		return wp.element.createElement(
			'span',
			{
				className: 'invp_hull_material'
			},
			props.attributes.hull_material
		);
	}
} );