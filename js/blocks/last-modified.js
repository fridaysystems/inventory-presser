wp.blocks.registerBlockType( 'inventory-presser/last-modified', {
	title: 'Last Modified',

	icon: 'admin-network', //it's a key

	category: 'inventory-presser',

	attributes: {
		last_modified: {
			type: 'string',
			source: 'meta',
			meta: 'inventory_presser_last_modified'
		},
	},

	edit: function( props ) {
		return props.attributes.last_modified;
	},

	save: function( props ) {
		return wp.element.createElement(
			'span',
			{
				className: 'invp_last_modified'
			},
			props.attributes.last_modified
		);
	}
} );