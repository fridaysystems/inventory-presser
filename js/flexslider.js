jQuery(document).ready( function() {
	jQuery('#slider').flexslider({
		animation: 'slide',
		animationSpeed: 300,
		controlNav: false,
		prevText: '',
		nextText: '',
		slideshow: false,
		after: function( slider ) { flexslider_maybe_resize_current_image(); },
		start: function( slider ) { jQuery(window).trigger('resize'); }
	});

	jQuery('#slider .slides li:first-child img').each( function() {
		observer.observe(jQuery(this)[0], { attributes : true, attributeFilter : ['style'] });
	});
});

var observer = new MutationObserver(function(mutations) {
	mutations.forEach(function(mutationRecord) {
		flexslider_maybe_resize_current_image();
		jQuery('#slider .flex-direction-nav a').css('line-height', function() {
			return jQuery(this).parent().parent().parent().find('.slides li:first-child img').css('height');
		});
	});
});

function flexslider_maybe_resize_current_image()
{
	var slider_width = jQuery('#slider').css('width').replace(/[^0-9]/g, '');
	var current_image_width = jQuery('#slider .flex-active-slide img').css('width');

	if( slider_width - current_image_width.replace(/[^0-9]/g, '') > 10 ) {
		//if the photo isn't taking up the whole width of the slider, remove inline height so it does
		jQuery('#slider .flex-active-slide img').css('height','' );
	}

	var current_image_height = jQuery('#slider .flex-active-slide img').css('height');
	//when the slide changes, reset the next/prev text line-height
	jQuery('#slider .flex-direction-nav a').css('line-height', current_image_height );
	//and resize the whole slider based on the height of the current image
	jQuery('#slider .flex-viewport').css('height', current_image_height );
}