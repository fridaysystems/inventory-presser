window.addEventListener( 'resize', function() {
	setTimeout(function() {
		// Copied this line here to fix Divi not showing first photo/slider during page loads in Chrome.
		flexsliderMaybeResizeCurrentImage();
		flexsliderStylePreviousNext();
		window.dispatchEvent( new Event('afterDelayedResize' ) );
	}, 120);
});

window.addEventListener( 'load', function() {
	jQuery('.flexslider').not('#carousel').flexslider({
		animation: 'slide',
		animationSpeed: invp.is_singular ? 200 : 300,
		slideshowSpeed: 3500,
		controlNav: false,
		prevText: '',
		nextText: '',
		slideshow: ! invp.is_singular,
		smoothHeight: true,
		after: function( slider ) { flexsliderMaybeResizeCurrentImage(); },
		start: function( slider ) {
			// Wait for the first image to load and call flexsliderLoaded().
			var img = document.querySelector('.flex-active-slide img');
			if (img.complete) {
				flexsliderLoaded();
			} else {
				img.addEventListener( 'load', flexsliderLoaded );
			}
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
		nextText: '',
		start: function( slider ) {
			flexsliderStylePreviousNext();
		}
	});

	window.invp.resizeObserver = new ResizeObserver(entries => {
		window.dispatchEvent(new Event('resize'));
	});
	window.invp.resizeObserver.observe(document.querySelector('.flex-active-slide img'));
});
function flexsliderMaybeShrinkFrame() {
	// Is the slider wider than the width of the first image?
	if ( document.querySelector( '#slider' ).clientWidth > document.querySelector('#slider.flexslider .flex-active-slide img').clientWidth ) {
		document.querySelector( '#slider.flexslider' ).style.width =
		document.querySelector( '#carousel.flexslider' ).style.width = document.querySelector('#slider.flexslider .flex-active-slide img').clientWidth + 'px';
	}
	window.removeEventListener( 'afterDelayedResize', flexsliderMaybeShrinkFrame );
}
function flexsliderLoaded() {
	flexsliderMaybeShrinkFrame();
	window.dispatchEvent(new Event('resize'));
	flexsliderMaybeResizeCurrentImage();
	// When a full size photo is clicked, go "full screen".
	document.querySelectorAll( '#slider .invp-image' ).forEach( i => i.addEventListener( 'click', flexsliderPopOut ) );
}
function flexsliderMaybeResizeCurrentImage() {
	var activeSlide = document.querySelector('#slider.flexslider .flex-active-slide img');
	if( typeof activeSlide === 'undefined' ) {
		return;
	}

	var slider = document.querySelector('#slider.flexslider');
	if( slider.clientWidth > activeSlide.width && activeSlide.srcset ) {
		activeSlide.style.height = '';
		// Set the inline width of the photo to the real largest width or 100%.
		var largestWidth = getLargestWidthFromSrcset(activeSlide);
		if( largestWidth > activeSlide.width ) {
			activeSlide.style.width = largestWidth;
			activeSlide.style.maxWidth = '100%';
			activeSlide.removeAttribute('height');
		}
	}

	// If the photo is slow to load, it will have no height.
	if ( 0 === activeSlide.height ) {
		return;
	}

	// When the slide changes, reset the next/prev text line-height.
	document.querySelectorAll('.flexslider:not(#carousel) .flex-direction-nav a').forEach( a => a.style.lineHeight = activeSlide.height + 'px' );
	// Resize the whole slider based on the height of the current image.
	document.querySelector('.flexslider:not(#carousel) .flex-viewport').height = activeSlide.height + 'px';
}

function flexsliderNavigateToImage( evt ) {
	location.href = evt.target.parentNode.dataset.href;
}

// When a slide is clicked, grow the gallery on top of the page to show bigger images.
function flexsliderPopOut() {
	var activeSlide = document.querySelector( '#slider .flex-active-slide img' );
	var largestWidth = getLargestWidthFromSrcset( activeSlide );
	// If the current photo is already being shown at full width, abort.
	// If the photo is already 85% of the viewport, we can't get much bigger.
	if ( activeSlide.clientWidth >= largestWidth
		|| .85 < activeSlide.clientWidth / document.documentElement.clientWidth ) {
		// Open the photo directly, this was the behavior before we added the pop out gallery.
		location.href = document.querySelector('#slider .flex-active-slide a').dataset.href;
		return;
	}

	var container = document.querySelector( '.vehicle-images' );
	// Save properties before we change them so we can revert/close the gallery.
	if( 'undefined' === typeof window.invp.flexsliderPreviousProps ) {
		window.invp.flexsliderPreviousProps = {};
	}
	window.invp.flexsliderPreviousProps.position = container.style.position;
	window.invp.flexsliderPreviousProps.paddingLeft = container.style.paddingLeft;
	window.invp.flexsliderPreviousProps.zIndex = container.style.zIndex;
	window.invp.flexsliderPreviousProps.width = container.style.width;
	window.invp.flexsliderPreviousProps.top = container.style.top;
	window.invp.flexsliderPreviousProps.left = container.style.left;
	window.invp.flexsliderPreviousProps.sizes = activeSlide.sizes;

	// Change properties to make the slider go fullscreen.
	container.style.position = 'fixed';
	container.style.paddingLeft = '0';
	container.style.zIndex = '100000'; // Admin bar is 99999.
	container.style.width = largestWidth.toString() + 'px';
	document.querySelectorAll( '#slider,#slider-width,#slider .slides li,#slider .flex-active-slide, #carousel' ).forEach( l => {
		l.style.width = largestWidth.toString() + 'px';
	});
	container.style.left = ( ( document.documentElement.clientWidth - container.clientWidth ) / 2 ).toString() + 'px';
	// Watch for the changing height of #slider to reposition current image.
	window.invp.resizeObserver = new ResizeObserver(entries => {
		document.querySelector( '.vehicle-images' ).style.top = ( ( document.documentElement.clientHeight - entries[0].target.clientHeight ) / 2 ).toString() + 'px';
	});
	window.invp.resizeObserver.observe(document.getElementById('slider'));

	window.addEventListener( 'afterDelayedResize', flexsliderInflateSizes );
	function flexsliderInflateSizes( evt ) {
		document.querySelectorAll( '#slider .slides li img' ).forEach( l => {
			if ( 'undefined' !== typeof l.sizes ) {
				var sizeToReplace = l.sizes.split( /[ px]/ )[2];
				// Replace sizes attribute in slide.
				l.setAttribute( 'sizes', l.sizes.replaceAll( sizeToReplace, largestWidth ) );
			}
		});
		window.removeEventListener( 'afterDelayedResize', flexsliderInflateSizes );
	}
	window.dispatchEvent(new Event('resize'));

	// Create an overlay to dim the rest of the page behind the slider.
	var overlay = document.createElement('div');
	overlay.classList.add('flexsliderPopOut');
	overlay.style.position = 'fixed';
	overlay.style.top = overlay.style.left = '0';
	overlay.style.width = overlay.style.height = overlay.style.right = '100%'
	overlay.style.backgroundColor = 'rgba( 0, 0, 0, .8 )';
	overlay.style.zIndex = '99999';
	overlay.addEventListener( 'click', flexsliderPopIn );
	document.body.append(overlay);

	document.querySelectorAll( '#slider .invp-image' ).forEach( i => {
		// Remove click handler so more clicks on full size images don't call this function.
		i.removeEventListener( 'click', flexsliderPopOut );

		// Add a click handler that opens the image file directly, this was the previous behavior.
		i.addEventListener( 'click', flexsliderNavigateToImage );
	});

	// Prevent the thumbnails from sliding to the left out of .flex-viewport.
	document.querySelector( '#carousel .slides' ).style.transform = 'translate3d(0px, 0px, 0px)';
}

function flexsliderPopIn() {
	var container = document.querySelector( '.vehicle-images' );
	// Restore the saved properties.
	if ( 'undefined' !== window.invp.flexsliderPreviousProps ) {
		container.style.position = window.invp.flexsliderPreviousProps.position;
		container.style.zIndex = window.invp.flexsliderPreviousProps.zIndex;
		container.style.width = window.invp.flexsliderPreviousProps.width;
		container.style.top = window.invp.flexsliderPreviousProps.top;
		container.style.left = window.invp.flexsliderPreviousProps.left;
		container.style.paddingLeft = window.invp.flexsliderPreviousProps.paddingLeft;
		document.querySelectorAll( '#slider .slides li img' ).forEach( l => {
			if ( 'undefined' !== typeof l.sizes ) {
				// Replace sizes attribute in slide.
				l.setAttribute( 'sizes', window.invp.flexsliderPreviousProps.sizes );
			}
		});
	}
	document.querySelectorAll( '#slider,#slider-width,#slider .slides li,#slider .flex-active-slide' ).forEach( l => l.style.width = null );
	window.addEventListener( 'afterDelayedResize', flexsliderMaybeShrinkFrame );
	window.dispatchEvent(new Event('resize'));
	// Remove the overlay element.
	document.querySelectorAll( '.flexsliderPopOut' ).forEach( el => el.remove() );
	// Remove the explicit height set on the slider viewport so it readjusts.
	document.querySelector('.flexslider:not(#carousel) .flex-viewport').style.height = 'auto';
	// Restore click handler to re-enter the gallery.
	// When a full size photo is clicked, go "full screen".
	document.querySelectorAll( '#slider .invp-image' ).forEach( i => {
		i.removeEventListener( 'click', flexsliderNavigateToImage );
		i.addEventListener( 'click', flexsliderPopOut );
	});
	// Remove our resizeObserver on #slider
	window.invp.resizeObserver.disconnect();
}

function flexsliderStylePreviousNext() {
	var navLinks = document.querySelectorAll( '.flexslider .flex-direction-nav a' );
	if ( navLinks.length ) {
		navLinks.forEach( l => {
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

//https://stackoverflow.com/a/60487971/338432
function getLargestWidthFromSrcset(element) {
	if( ! element ) {
		return 2048;
	}
	if (element.getAttribute("srcset")) {
		return element
			.getAttribute("srcset")
			.split(",")
			.reduce(
				(acc, item) => {
					let [url, width] = item.trim().split(" ");
					width = parseInt(width);
					if (width > acc.width) return { width, url };
					return acc;
				},
				{ width: 0, url: "" }
			).width;
	}
  
	return element.getAttribute("width");
}