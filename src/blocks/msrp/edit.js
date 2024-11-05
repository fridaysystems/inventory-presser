/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit( { isSelected, context } ) {
	const { postType } = context;
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const blockProps = useBlockProps();

	/**
	 * Use isSelected to render the lost focus output that mimics the front-end
	 *
	 * @link https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#isselected
	 */
	if ( ! isSelected ) {
		// Do not show the input field when the block is not selected.
		return ( <div { ...blockProps }> { invpFormatCurrency( meta[ invp_blocks.meta_prefix + 'msrp' ] ) } </div> );
	}

	return (
		<div { ...blockProps }>
			<TextControl
				label    = { __( 'MSRP', 'inventory-presser' ) }
				value    = { meta[ invp_blocks.meta_prefix + 'msrp' ] }
				onChange = {(newValue) => setMeta( { ...meta, [invp_blocks.meta_prefix + 'msrp']: newValue.replace( /[^0-9\.]/g, '' ) } )}
			/>
		</div>
	);
}
