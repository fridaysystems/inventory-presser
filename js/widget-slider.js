jQuery(document).ready(function(){
	/**
	 * The more slides shown at once, the shorter the slider is. Cap at 2 in
	 * frame at one time so the thumbnails do not appear smaller than postage
	 * stamps on smaller devices.
	 */
	if( document.documentElement.clientWidth <= 480 
		&& widget_slider.showcount > 2 )
	{
		widget_slider.showcount = 2;
	}
	var flexslider = jQuery('.widget__invp_slick .flexslider');
	flexslider.flexslider({
		animation: 'slide', /* Select your animation type, "fade" or "slide" */
		slideshow: true, /* Animate slider automatically */
		animationSpeed: 4000, /* Set the speed of animations, in milliseconds */
		slideshowSpeed: 8000, /* Set the speed of the slideshow cycling, in milliseconds */
		controlNav: false, /* clickable dots for each slide */
		directionNav: true, /* Create navigation for previous/next navigation? (true/false) */
		prevText: '', /* Set the text for the "previous" directionNav item */
		nextText: '', /* Set the text for the "next" directionNav item */
		itemWidth: (Math.round(jQuery('.widget__invp_slick #slider-width').width() / widget_slider.showcount + .5)), /* widget_slider.showcount is how many slides to show at one time*/
		smoothHeight: false, /* Allow height of the slider to animate smoothly in horizontal mode */
		start: function(){
			var current_image_height = jQuery('.widget__invp_slick .flexslider').css('height').replace( 'px', '' );
			//when the slide changes, reset the next/prev text line-height
			jQuery('.widget__invp_slick .flexslider .flex-direction-nav a,.widget__invp_slick .flexslider .flex-direction-nav li').css('line-height', current_image_height + 'px' );
		}
	});

	//resize the slider when the browser is resized
	jQuery(window).bind('resize', function () {
		setTimeout(function () {
		   var slider = jQuery(flexslider).data('flexslider');
		   slider.resize();
		}, 1000);
	});
});