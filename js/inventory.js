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
	if(typeof wp !== 'undefined' && null != wp.media ){
		var frame = wp.media.editor.add('content');
		frame.on('escape', function(){
			update_add_media_button_annotation();
		});
	}
});