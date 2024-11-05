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
		return <div { ...blockProps }><h1> { meta[ invp_blocks.meta_prefix + 'year' ] + ' ' }
			{ meta[ invp_blocks.meta_prefix + 'make' ] + ' '}
			{ meta[ invp_blocks.meta_prefix + 'model' ] + ' ' }
			{ meta[ invp_blocks.meta_prefix + 'trim' ] } </h1></div>;
	}

	return (
		<div { ...blockProps }>
			<TextControl
				label    = { __( 'Year', 'inventory-presser' ) }
				value    = { meta[ invp_blocks.meta_prefix + 'year' ] }
				onChange = {(newValue) => setMeta( { ...meta, [invp_blocks.meta_prefix + 'year']: newValue.replace( /[^0-9]/g, '' ) } )}
			/>
			<TextControl
				label    = { __( 'Make', 'inventory-presser' ) }
				value    = { meta[ invp_blocks.meta_prefix + 'make' ] }
				onChange = {(newValue) => setMeta( { ...meta, [invp_blocks.meta_prefix + 'make']: newValue } )}
			/>
			<TextControl
				label    = { __( 'Model', 'inventory-presser' ) }
				value    = { meta[ invp_blocks.meta_prefix + 'model' ] }
				onChange = {(newValue) => setMeta( { ...meta, [invp_blocks.meta_prefix + 'model']: newValue } )}
			/>
			<TextControl
				label    = { __( 'Trim Level', 'inventory-presser' ) }
				value    = { meta[ invp_blocks.meta_prefix + 'trim' ] }
				onChange = {(newValue) => setMeta( { ...meta, [invp_blocks.meta_prefix + 'trim']: newValue } )}
			/>
		</div>
	);
}