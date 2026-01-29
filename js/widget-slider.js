window.addEventListener( 'load', function(e) {
	/**
	 * Initialize all sliders on the page. Each slider can have its own configuration.
	 */
	var sliderContainers = document.querySelectorAll('.widget__invp_slick');
	
	if ( ! sliderContainers.length ) {
		return;
	}

	// Ensure the configs object exists.
	if ( typeof window.invpSliderConfigs === 'undefined' ) {
		window.invpSliderConfigs = {};
	}

	sliderContainers.forEach( function( container ) {
		var sliderId = container.getAttribute('data-slider-id');
		if ( ! sliderId ) {
			return;
		}

		var config = window.invpSliderConfigs[sliderId];
		if ( ! config ) {
			return;
		}

		/**
		 * The more slides shown at once, the shorter the slider is. Cap at 2 in
		 * frame at one time so the thumbnails do not appear smaller than postage
		 * stamps on smaller devices.
		 */
		var showcount = config.showcount;
		if( document.documentElement.clientWidth <= 480 
			&& showcount > 2 ) {
			showcount = 2;
		}

		var sliderWidthEl = container.querySelector('.slider-width');
		var flexslider = container.querySelector('.flexslider');
		
		if ( ! flexslider || ! sliderWidthEl ) {
			return;
		}

		var sliderWidth = jQuery(sliderWidthEl).width();
		if ( ! sliderWidth ) {
			return;
		}

		jQuery(flexslider).flexslider({
			animation: 'slide', /* Select your animation type, "fade" or "slide" */
			slideshow: true, /* Animate slider automatically */
			animationSpeed: 4000, /* Set the speed of animations, in milliseconds */
			slideshowSpeed: 8000, /* Set the speed of the slideshow cycling, in milliseconds */
			controlNav: false, /* clickable dots for each slide */
			directionNav: true, /* Create navigation for previous/next navigation? (true/false) */
			prevText: '', /* Set the text for the "previous" directionNav item */
			nextText: '', /* Set the text for the "next" directionNav item */
			itemWidth: (Math.round(sliderWidth / showcount + .5)), /* showcount is how many slides to show at one time*/
			smoothHeight: false, /* Allow height of the slider to animate smoothly in horizontal mode */
			start: function(){
				var current_image_height = jQuery(flexslider).css('height').replace( 'px', '' );
				//when the slide changes, reset the next/prev text line-height
				jQuery(container).find('.flexslider .flex-direction-nav a,.flexslider .flex-direction-nav li').css('line-height', current_image_height + 'px' );
				// Style previous and next buttons.
				var navLinks = flexslider.querySelectorAll('.flex-direction-nav a');
				if ( navLinks.length ) {
					navLinks.forEach( function(l) {
						var firstImage = l.parentNode.parentNode.parentNode.querySelector('.slides li:first-child img');
						if ( firstImage ) {
							var h = firstImage.style.height;
							if ( '' === h ) {
								h = firstImage.clientHeight + 'px';
							}
							if ( '0px' !== h ) {
								l.style.lineHeight = '' !== h ? h : '112.5px';
							}
						}
					});
				}
			}
		});
	});
});