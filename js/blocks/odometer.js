wp.blocks.registerBlockType( 'inventory-presser/odometer', {
	title: 'Odometer',

	icon: 'admin-network', //it's a key

	category: 'inventory-presser',

	attributes: {
		odometer: {
			type: 'string',
			source: 'meta',
			meta: 'inventory_presser_odometer'
		},
	},

	edit: function( props ) {
		return props.attributes.odometer;
	},

	save: function( props ) {
		return wp.element.createElement(
			'span',
			{
				className: 'invp_odometer'
			},
			props.attributes.odometer
		);
	}
} );