function delete_all_data() {
	el.html( ' <img src="images/loading.gif" /> Deleting plugin data...' );
	jQuery( '#delete-all-data' ).submit();
}

function delete_all_post_attachments( ) {
	if ( confirm( 'Are you sure you want to delete all attachments?' ) ) {
		var data = {
			'_ajax_nonce': invp_classic_editor.delete_all_media_nonce,
			'action': 'delete_all_post_attachments',
			'post_ID': document.getElementById( 'post_ID' ).value
		};
		jQuery.post(
			ajaxurl,
			data,
			function ( response ) {
				update_add_media_button_annotation();
				// clobber the currently shown featured image
				WPSetThumbnailHTML(
					'<p class="hide-if-no-js"><a title="Set featured image" href="media-upload.php?post_id=' +
					document.getElementById( 'post_ID' ).value +
					'&amp;type=image&amp;TB_iframe=1" id="set-post-thumbnail" class="thickbox">Set featured image</a></p>'
				);
			}
		);
	}
}

function invp_vehicle_type_changed( type_slug ) {
	var is_boat = 'boat' == type_slug;

	// show or hide the boat-specific fields
	jQuery( 'tr.boat-postmeta' ).toggle( is_boat );

	// HIN or VIN?
	jQuery( 'label[for="' + invp.meta_prefix + 'vin"]' ).html( is_boat ? 'HIN' : 'VIN' );

	// Odometer units are usually miles, but can be changed.
	jQuery( '.invp_odometer_units' ).html( invp.odometer_units );

	if ( is_boat ) {

		// Change the interface for boats
		jQuery( 'select#' + invp.meta_prefix + 'body_style_hidden' )
			.attr( 'name', invp.meta_prefix + 'body_style' )
			.attr( 'id', invp.meta_prefix + 'body_style' );

		jQuery( 'input#' + invp.meta_prefix + 'body_style' )
			.attr( 'name', invp.meta_prefix + 'body_style_hidden' )
			.attr( 'id', invp.meta_prefix + 'body_style_hidden' );
	} else {

		// Reverse all those boat changes
		jQuery( 'input#' + invp.meta_prefix + 'body_style_hidden' )
			.attr( 'name', invp.meta_prefix + 'body_style' )
			.attr( 'id', invp.meta_prefix + 'body_style' );

		jQuery( 'select#' + invp.meta_prefix + 'body_style' )
			.attr( 'name', invp.meta_prefix + 'body_style_hidden' )
			.attr( 'id', invp.meta_prefix + 'body_style_hidden' );
	}
}

function is_number( evt ) {
	evt          = (evt) ? evt : window.event;
	var charCode = (evt.which) ? evt.which : evt.keyCode;
	return ! (charCode > 31 && ( charCode < 48 || charCode > 57 ) );
}

function update_add_media_button_annotation( ) {
	var data = {
		'action': 'output_add_media_button_annotation',
		'post_ID': document.getElementById( 'post_ID' ).value,
	};
	jQuery.post(
		ajaxurl,
		data,
		function ( response ) {
			jQuery( '#media-annotation' ).html( response );
		}
	);
}

/**
 * Update the attachment count after uploads
 * https://stackoverflow.com/questions/14279786/how-to-run-some-code-as-soon-as-new-image-gets-uploaded-in-wordpress-3-5-uploade#14515707
 */
jQuery( document ).ready(
	function () {

		// set up the edit screen for vehicle entry (hides boat fields)
		invp_vehicle_type_changed( jQuery( '#' + invp.meta_prefix + 'type' ).val() );

		// Hack for "Upload New Media" Page (old uploader)
		// Overriding the uploadSuccess function:
		if (typeof uploadSuccess !== 'undefined') {
				// First backup the function into a new variable.
				var uploadSuccess_original = uploadSuccess;
				// The original uploadSuccess function with has two arguments: fileObj, serverData
				// So we globally declare and override the function with two arguments (argument names shouldn't matter)
				uploadSuccess = function (fileObj, serverData) {
					// Fire the original procedure with the same arguments
					uploadSuccess_original( fileObj, serverData );
					// Execute whatever you want here:
					update_add_media_button_annotation();
				}
		}

		// Hack for "Insert Media" Dialog (new plupload uploader)
		// Hooking on the uploader queue (on reset):
		if (typeof wp !== 'undefined' && typeof wp.Uploader !== 'undefined' && typeof wp.Uploader.queue !== 'undefined') {
			wp.Uploader.queue.on(
				'reset',
				function () {
					update_add_media_button_annotation();
				}
			);
		}

		// Confirm Delete All Vehicles button presses
		jQuery( 'a#delete_all_vehicles' ).on(
			'click',
			function () {
				return confirm( 'Are you sure you want to delete all vehicles and vehicle photos?' );
			}
		);
	}
);
