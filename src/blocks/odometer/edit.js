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
		return ( <div { ...blockProps }> { Number( meta[ invp_blocks.meta_prefix + 'odometer' ] ).toLocaleString() + ' ' + invp_blocks.odometer_units } </div> );
	}

	return (
		<div { ...blockProps }>
			<TextControl
				label    = { __( 'Odometer', 'inventory-presser' ) }
				value    = { meta[ invp_blocks.meta_prefix + 'odometer' ] }
				onChange = {(newValue) => setMeta( { ...meta, [invp_blocks.meta_prefix + 'odometer']: newValue } )}
			/>
		</div>
	);
}
