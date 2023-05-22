wp.blocks.registerBlockType(
	'inventory-presser/engine',
	{
		title: 'Engine',

		icon: 'admin-network', // it's a key

		category: 'inventory-presser',

		attributes: {
			engine: {
				type: 'string',
				source: 'meta',
				meta: 'inventory_presser_engine'
			},
		},

		edit: function( props ) {
			return props.attributes.engine;
		},

		save: function( props ) {
			return wp.element.createElement(
				'span',
				{
					className: 'invp_engine'
				},
				props.attributes.engine
			);
		}
	}
);
