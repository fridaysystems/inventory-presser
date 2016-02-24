function delete_all_inventory() {
	//Show the admin loading gif in the last run status
	jQuery('#busy-notice').html(' <img src="images/loading.gif" /> Deleting vehicles...');
	var data = {
		'action': 'delete_all_inventory',
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
			jQuery('#busy-notice').html('');
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