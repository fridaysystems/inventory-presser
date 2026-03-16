/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { PanelBody, CheckboxControl } from '@wordpress/components';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useEffect, useRef } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const { locationSlug = [] } = attributes;
	const blockProps = useBlockProps();
	const mapContainerRef = useRef( null );
	const mapInstanceRef = useRef( null );

	const locations = useSelect(
		( select ) => {
			const records = select( 'core' ).getEntityRecords( 'taxonomy', 'location', {
				per_page: -1,
				hide_empty: false,
			} );
			return records || [];
		},
		[]
	);

	function onToggleLocation( slug ) {
		const next = locationSlug.indexOf( slug ) === -1
			? [ ...locationSlug, slug ]
			: locationSlug.filter( ( s ) => s !== slug );
		setAttributes( { locationSlug: next } );
	}

	// Build popup data from selected locations that have coordinates.
	const popups = locations
		.filter(
			( term ) =>
				locationSlug.indexOf( term.slug ) !== -1 &&
				term.meta?.address_lat &&
				term.meta?.address_lon
		)
		.map( ( term ) => ( {
			name: term.name,
			lat: parseFloat( term.meta.address_lat ),
			lon: parseFloat( term.meta.address_lon ),
			address: term.description
				? term.description.replace( /\r/g, '' ).replace( /\n/g, '<br />' )
				: '',
		} ) );

	/* global invp_blocks */
	const mapboxToken = typeof invp_blocks !== 'undefined' ? invp_blocks.mapbox_public_token : '';
	const showMap = popups.length > 0 && !! mapboxToken;

	// Stable string keys for useEffect dependency comparison.
	const slugKey = locationSlug.join( ',' );
	const popupKey = popups.map( ( p ) => `${ p.lat },${ p.lon }` ).join( '|' );

	useEffect( () => {
		const container = mapContainerRef.current;
		const leaflet = window.L;

		// Tear down any existing map instance before re-building.
		if ( mapInstanceRef.current ) {
			mapInstanceRef.current.remove();
			mapInstanceRef.current = null;
		}

		if ( ! container || ! leaflet || ! mapboxToken || popups.length === 0 ) {
			return;
		}

		const map = leaflet.map( container, {
			dragging: false,
			touchZoom: false,
			doubleClickZoom: false,
			scrollWheelZoom: false,
			boxZoom: false,
			keyboard: false,
		} );
		mapInstanceRef.current = map;

		leaflet.tileLayer(
			'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=' + mapboxToken,
			{
				attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery \u00a9 <a href="https://www.mapbox.com/">Mapbox</a>',
				id: 'mapbox/streets-v11',
				tileSize: 512,
				zoomOffset: -1,
				accessToken: mapboxToken,
			}
		).addTo( map );

		const markers = popups.map( ( popup ) => {
			const marker = leaflet.marker( [ popup.lat, popup.lon ] ).addTo( map );
			marker.bindPopup( '<b>' + popup.name + '</b><br />' + popup.address );
			return marker;
		} );

		if ( markers.length === 1 ) {
			map.setView( [ popups[ 0 ].lat, popups[ 0 ].lon ], 13 );
		} else {
			const group = new leaflet.featureGroup( markers );
			map.fitBounds( group.getBounds() );
		}

		return () => {
			if ( mapInstanceRef.current ) {
				mapInstanceRef.current.remove();
				mapInstanceRef.current = null;
			}
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ slugKey, popupKey, mapboxToken ] );

	let summaryText;
	if ( ! locationSlug.length ) {
		summaryText = __( 'Select one or more locations in the block settings.', 'inventory-presser' );
	} else if ( ! mapboxToken ) {
		summaryText = __( 'A Mapbox API token is required. Add one in Inventory Presser settings.', 'inventory-presser' );
	} else if ( locations.length && ! popups.length ) {
		summaryText = __( 'No map coordinates found for the selected locations.', 'inventory-presser' );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Locations', 'inventory-presser' ) } initialOpen={ true }>
					{ locations?.length ? (
						locations.map( ( term ) => (
							<CheckboxControl
								key={ term.id }
								label={ term.name }
								checked={ locationSlug.indexOf( term.slug ) !== -1 }
								onChange={ () => onToggleLocation( term.slug ) }
							/>
						) )
					) : (
						<p>{ __( 'No locations found. Add Location terms in the admin screen.', 'inventory-presser' ) }</p>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ summaryText && <p>{ summaryText }</p> }
				<div
					ref={ mapContainerRef }
					className="invp-map"
					style={ {
						width: '100%',
						height: showMap ? '300px' : '0',
						display: showMap ? 'block' : 'none',
					} }
				/>
			</div>
		</>
	);
}
