function delete_all_data() {
	el.html(' <img src="images/loading.gif" /> Deleting plugin data...');
	jQuery('#delete-all-data').submit();
}

function delete_all_post_attachments( ) {
	if( confirm('Are you sure you want to delete all attachments?') ) {
		var data = {
			'action': 'delete_all_post_attachments',
			'post_ID': document.getElementById('post_ID').value
		};
		jQuery.post( ajaxurl, data, function( response ) {
			update_add_media_button_annotation( );
			//clobber the currently shown featured image
			WPSetThumbnailHTML( '<p class="hide-if-no-js"><a title="Set featured image" href="media-upload.php?post_id=' +
				document.getElementById('post_ID').value +
				'&amp;type=image&amp;TB_iframe=1" id="set-post-thumbnail" class="thickbox">Set featured image</a></p>' );
		});
	}
}

function invp_vehicle_type_changed( type_slug ) {
	if( 'boat' == type_slug ) {

		/**
		 * Change the interface for boats
		 */

		//HIN instead of VIN
		jQuery('label[for="inventory_presser_vin"]').html('HIN');

		//odometer units are hours
		jQuery('.invp_odometer_units').html('hours');

		//show the propulsion type taxonomy meta box and boat-specific fields
		jQuery('#propulsion_typediv,tr.boat-postmeta').show();

		//hide the drive type taxonomy meta box
		jQuery('#drive_typediv').hide();

		jQuery('select#inventory_presser_body_style_hidden')
			.attr('name', 'inventory_presser_body_style')
			.attr('id', 'inventory_presser_body_style');

		jQuery('input#inventory_presser_body_style')
			.attr('name', 'inventory_presser_body_style_hidden')
			.attr('id', 'inventory_presser_body_style_hidden');
	} else {

		//Reverse all those boat changes

		//HIN instead of VIN
		jQuery('label[for="inventory_presser_vin"]').html('VIN');

		//odometer units are miles
		jQuery('.invp_odometer_units').html('miles');

		//hide the propulsion type taxonomy meta box and boat-specific fields
		jQuery('#propulsion_typediv,tr.boat-postmeta').hide();

		//show the drive type taxonomy meta box
		jQuery('#drive_typediv').show();

		jQuery('input#inventory_presser_body_style_hidden')
			.attr('name', 'inventory_presser_body_style')
			.attr('id', 'inventory_presser_body_style');

		jQuery('select#inventory_presser_body_style')
			.attr('name', 'inventory_presser_body_style_hidden')
			.attr('id', 'inventory_presser_body_style_hidden');
	}
}

function is_number( evt ) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    return ! (charCode > 31 && ( charCode < 48 || charCode > 57 ) );
}

function update_add_media_button_annotation( ) {
	var data = {
		'action': 'output_add_media_button_annotation',
		'post_ID': document.getElementById('post_ID').value,
	};
	jQuery.post( ajaxurl, data, function( response ) {
		jQuery('#media-annotation').html( response );
	});
}

/**
 * Update the attachment count after uploads
 * http://stackoverflow.com/questions/14279786/how-to-run-some-code-as-soon-as-new-image-gets-uploaded-in-wordpress-3-5-uploade#14515707
 */
jQuery(document).ready( function(){

	//set up the edit screen for vehicle entry (hides boat fields)
	invp_vehicle_type_changed( jQuery('#inventory_presser_type').val() );

	// Hack for "Upload New Media" Page (old uploader)
	// Overriding the uploadSuccess function:
	if (typeof uploadSuccess !== 'undefined') {
		// First backup the function into a new variable.
		var uploadSuccess_original = uploadSuccess;
		// The original uploadSuccess function with has two arguments: fileObj, serverData
		// So we globally declare and override the function with two arguments (argument names shouldn't matter)
		uploadSuccess = function(fileObj, serverData)
		{
			// Fire the original procedure with the same arguments
			uploadSuccess_original(fileObj, serverData);
			// Execute whatever you want here:
			update_add_media_button_annotation();
		}
	}

	// Hack for "Insert Media" Dialog (new plupload uploader)
	// Hooking on the uploader queue (on reset):
	if (typeof wp !== 'undefined' && typeof wp.Uploader !== 'undefined' && typeof wp.Uploader.queue !== 'undefined') {
		wp.Uploader.queue.on('reset', function() {
			update_add_media_button_annotation();
		});
	}
});