/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, BlockControls, RichText } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
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
		return (
			<>
				<RichText { ...blockProps } value={ meta[ invp_blocks.meta_prefix + 'description' ] }></RichText>
			</>
		);
	}

	return (
		<>
			<BlockControls></BlockControls>
			<RichText 
				{ ...blockProps }
				tagName="p"
				onChange={ ( newValue ) => { setMeta( { ...meta, [invp_blocks.meta_prefix + 'description']: newValue } ); } }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link', 'core/text-color' ] }
				value={ meta[ invp_blocks.meta_prefix + 'description' ] }
			></RichText>
		</>
	);
}