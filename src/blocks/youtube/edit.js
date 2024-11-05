import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';

/**
 * In order for a block to render HTML stored in a meta value, we need this
 * RawHTML function.
 * @link https://wordpress.stackexchange.com/a/409328/13090
 */
const htmlToElem = ( html ) => wp.element.RawHTML( { children: html } );

export default function Edit( { isSelected, context } ) {
	const { postType } = context;
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const blockProps = useBlockProps();
	// Two meta fields hold YouTube video ID and the iframe HTML.
	const metaValue      = meta[ invp_blocks.meta_prefix + 'youtube' ];
	const metaValueEmbed = meta[ invp_blocks.meta_prefix + 'youtube_embed' ];

	// Handle metaValue changes.
	useEffect( () => {
		// Avoid unnecessary API requests.
		if ( '' === metaValue || 11 > metaValue.length ) {
			return;
		}
		// Get iframe HTML from the YouTube API.
		wp.apiRequest( {
			url: wp.media.view.settings.oEmbedProxyUrl,
			data: {
				url: 'https://www.youtube.com/watch?v=' + metaValue,
			},
			type: 'GET',
			dataType: 'json',
			context: this,
		} )
		.done( youTubeApiCallbackSuccess ) // The new "success()".
		.fail( ( response ) => { // The new "error()".
			console.log( 'YouTube API error', response.responseJSON );
		} );
	}, [ metaValue ] );

	const youTubeApiCallbackSuccess = ( response ) => {
		// Save the iframe HTML in meta.
		setMeta( { ...meta, [invp_blocks.meta_prefix + 'youtube_embed']: response.html } );
		// Put the iframe HTML in the block.
		const container = document.getElementById( blockProps.id + '-oembed' );
		if ( container ) {
			container.innerHTML = response.html;
		}
	}

	return (
		<div { ...blockProps }>
			{
				/**
				 * Use isSelected to render the block differently if the user is
				 * editing it. Also, don't hide the TextControl if the iframe
				 * HTML is empty. Wait until the user inputs an ID and the
				 * YouTube API responds with HTML.
				 *
				 * @link https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#isselected
				 */
				isSelected || '' === metaValueEmbed ? (
					<TextControl
						label={ __( 'YouTube Video ID', 'inventory-presser' ) }
						value={ metaValue }
						onChange={ ( videoId ) =>
							setMeta( {
								...meta,
								[ invp_blocks.meta_prefix + 'youtube' ]: videoId,
								[ invp_blocks.meta_prefix + 'youtube_embed' ]: ''
							} )
						}
					/>
				) : (
					<div id={ blockProps.id + '-oembed' }>
						{ htmlToElem( metaValueEmbed ) }
					</div>
				)
			}
		</div>
	);
}
