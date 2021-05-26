jQuery(window).on('load', function() {
	jQuery(window).resize(function() {
		setTimeout(function() {
			adjustSlideHeight('#slider');
		}, 120);
	});
});

jQuery(document).ready(function() {
	jQuery('.flexslider').not('#carousel').flexslider({
		animation: 'slide',
		animationSpeed: invp.is_singular ? 200 : 300,
		slideshowSpeed: 3500,
		controlNav: false,
		prevText: '',
		nextText: '',
		slideshow: ! invp.is_singular,
		smoothHeight: true,
		after: function( slider ) { flexslider_maybe_resize_current_image(); },
		start: function( slider ) {
			adjustSlideHeight('#slider');
			jQuery(window).trigger('resize');
			flexslider_maybe_resize_current_image();
		}
	});

	jQuery('#carousel').flexslider({
		animation: "slide",
		controlNav: false,
		slideshow: false,
		smoothHeight: true,
		itemWidth: 150,
		asNavFor: '#slider',
		prevText: '',
		nextText: ''
	});

	jQuery('.flexslider:not(#carousel) .slides li:first-child img').each( function() {
		observer.observe(jQuery(this)[0], { attributes : true, attributeFilter : ['style'] });
	});
});

var observer = new MutationObserver(function(mutations) {
	mutations.forEach(function(mutationRecord) {
		flexslider_maybe_resize_current_image();
		jQuery('.flexslider:not(#carousel) .flex-direction-nav a').css('line-height', function() {
			return jQuery(this).parent().parent().parent().find('.slides li:first-child img').css('height');
		});
	});
});

function adjustSlideHeight(wrapper)
{
	var ratios = [];
	jQuery(wrapper + ' .slides li img').each(function() {
		ratios.push(jQuery(this).attr('height')/jQuery(this).attr('width'));
	});
	height = Math.ceil(jQuery('#slider-width').width() * Math.min.apply(Math,ratios));
	jQuery(wrapper + ' .slides li img').each(function() {
		jQuery(this).css('height', height);
		jQuery(this).css('width', 'auto');
	});
}

function flexslider_maybe_resize_current_image()
{
	var el = jQuery('.flexslider .flex-active-slide img')[0];

	if( typeof el === 'undefined' )
	{
		return;
	}

	var el_slider = jQuery('.flexslider');
	var slider_width = el_slider.css('width').replace(/[^0-9]/g, '')
		- parseInt( el_slider.css( 'border-left-width' ).replace(/[^0-9]/g, '') )
		- parseInt( el_slider.css( 'border-right-width' ).replace(/[^0-9]/g, '') );

	//if the photo isn't taking up the whole width of the slider, remove inline height so it does
	if( slider_width > el.width && jQuery(el).attr('srcset') )
	{
		jQuery(el).css('height','' );
		//set the inline width of the photo to be the real largest width or 100%
		var srcset = jQuery(el).attr('srcset');
		var pieces = srcset.split( ' ' );
		if( 1 < pieces.length )
		{
			var full_width = pieces[1].substring( 0, pieces[1].length-2 );
			if( full_width > el.width )
			{
				jQuery(el).removeAttr('height');
				jQuery(el).css( 'width', full_width ).css('max-width','100%' );
			}
		}
	}

	var current_image_height = el.height;
	//when the slide changes, reset the next/prev text line-height
	jQuery('.flexslider:not(#carousel) .flex-direction-nav a').css('line-height', current_image_height + 'px' );
	//and resize the whole slider based on the height of the current image
	jQuery('.flexslider:not(#carousel) .flex-viewport').css('height', current_image_height + 'px' );
}