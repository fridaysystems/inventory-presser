function delete_all_data() {
	//call delete_all_inventory(), then wait until #busy-notice is cleared to
	delete_all_inventory( 'delete-all-notice' );
	var timeoutID = setTimeout( maybe_finish_delete_all_data, 2000 );
}

function maybe_finish_delete_all_data() {
	//are the cars still deleting?
	var el = jQuery('#'+status_element_id);
	if( '' !== el.html()) {
		//yes? set another timeout
		var timeoutID = setTimeout( maybe_finish_delete_all_data, 2000 );
		console.log('setting another check');
	} else {
		//no, submit our form that will perform the rest of the data delete
		el.html(' <img src="images/loading.gif" /> Deleting other plugin data...');
		jQuery('#delete-all-data').submit();
	}
}

var status_element_id;

/**
 * Deletes all vehicles managed by this plugin via a looping AJAX call to
 * workaround script timeouts.
 *
 * @param string wait_notice_element_id An HTML element whose contents will show
 * the user a status while the deletion is taking place
 */
function delete_all_inventory( wait_notice_element_id ) {
	status_element_id = wait_notice_element_id;
	//Show the admin loading gif in the last run status
	jQuery('#'+status_element_id).html(' <img src="images/loading.gif" /> Deleting vehicles...');
	var data = {
		'action': 'delete_all_inventory'
	};
	jQuery.post( ajaxurl, data, function( response ) {
		var success_pos = response.indexOf( 'setting-error-settings_updated' );
		if( -1 >= success_pos ) {
			delete_all_inventory();
		} else {
			//Report to the user that the import is complete.
			var h1_elements = jQuery('div.wrap').find('h1');
			if( 1 == h1_elements.length ) {
				h1_elements.first().after( response );
				/**
				 * Create an event handler so the message goes away when the user
				 * clicks the button to dismiss. Since the message is added via
				 * javascript, this doesn't happen automatically.
				 */
				jQuery('.notice.is-dismissible').on('click', '.notice-dismiss', function(event){
					event.preventDefault();
					jQuery(this).parent().fadeTo(100,0,function(){
						jQuery(this).slideUp(100,function(){
							jQuery(this).remove();
						});
					});
				});
			}
			//Hide the swirly wait gif
			jQuery('#'+status_element_id).html('');
		}
	}).fail(function() {
		delete_all_inventory();
	});
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

//Execute some code when the media uploader is closed
jQuery(document).ready( function() {
	if(typeof wp !== 'undefined' && typeof wp.media !== 'undefined' && typeof wp.media.editor !== 'undefined' ){
		var frame = wp.media.editor.add('content');
		frame.on('escape', function(){
			update_add_media_button_annotation();
		});
	}
});