wp.blocks.registerBlockType(
	'inventory-presser/color',
	{
		title: 'Color',

		icon: 'admin-network', // it's a key

		category: 'inventory-presser',

		attributes: {
			color: {
				type: 'string',
				source: 'meta',
				meta: 'inventory_presser_color'
			},
		},

		edit: function ( props ) {
			return props.attributes.color;
		},

		save: function ( props ) {
			return wp.element.createElement(
				'span',
				{
					className: 'invp_color'
				},
				props.attributes.color
			);
		}
	}
);
