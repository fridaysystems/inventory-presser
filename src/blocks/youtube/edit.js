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
		wp.apiRequest({
			url: wp.media.view.settings.oEmbedProxyUrl,
			data: {
				url: 'https://www.youtube.com/watch?v=' + meta[ invp_blocks.meta_prefix + 'youtube' ],
			},
			type: 'GET',
			dataType: 'json',
			context: this
		})
		.done( function( response ) {
			document.getElementById( blockProps.id + '-oembed' ).innerHTML = response.html;
		} );
		return ( <div { ...blockProps }> <div id={ blockProps.id + '-oembed' } > { meta[ invp_blocks.meta_prefix + 'youtube' ] }</div></div> );
	}

	return (
		<div { ...blockProps }>
			<TextControl
				label    = { __( 'YouTube Video ID', 'inventory-presser' ) }
				value    = { meta[ invp_blocks.meta_prefix + 'youtube' ] }
				onChange = {(newValue) => setMeta( { ...meta, [invp_blocks.meta_prefix + 'youtube']: newValue } )}
			/>
		</div>
	);
}
