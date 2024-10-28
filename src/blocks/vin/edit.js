/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit( { isSelected } ) {

	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const blockProps = useBlockProps();

	/**
	 * Use isSelected to render the lost focus output that mimics the front-end
	 *
	 * @link https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#isselected
	 */
	if ( ! isSelected ) {
		// Do not show the input field when the block is not selected.
		return ( <div { ...blockProps }> { meta[ invp_blocks.meta_prefix + 'vin' ] } </div> );
	}

	return (
		<div { ...blockProps }>
			<TextControl
				label    = { __( 'VIN', 'inventory-presser' ) }
				value    = { meta[ invp_blocks.meta_prefix + 'vin' ] }
				onChange = {(newValue) => setMeta( { ...meta, [invp_blocks.meta_prefix + 'vin']: newValue } )}
			/>
		</div>
	);
}
