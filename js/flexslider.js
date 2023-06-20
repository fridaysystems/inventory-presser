window.addEventListener( 'resize', function() {
	setTimeout(function() {
		// Copied this line here to fix Divi not showing first photo/slider during page loads in Chrome.
		flexsliderMaybeResizeCurrentImage();
		flexsliderStylePreviousNext();
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
			setTimeout(function() {
				window.dispatchEvent(new Event('resize'));
				flexsliderMaybeResizeCurrentImage();
			}, 120);
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

	var imgs = document.querySelectorAll('.flexslider:not(#carousel) .slides li:first-child img');
	if ( imgs.length ) {
		imgs.forEach( i => observer.observe(i, { attributes : true, attributeFilter : ['style'] }) );
	}
});

var observer = new MutationObserver(function(mutations) {
	mutations.forEach(function(mutationRecord) {
		flexsliderMaybeResizeCurrentImage();
		flexsliderStylePreviousNext();
	});
});

function flexsliderMaybeResizeCurrentImage() {
	var el = document.querySelector('.flexslider .flex-active-slide img');
	if( typeof el === 'undefined' ) {
		return;
	}

	var slider = document.querySelector('#slider.flexslider');
	if( slider.clientWidth > el.width && el.srcset ) {
		el.style.height = '';
		// Set the inline width of the photo to the real largest width or 100%.
		var pieces = el.srcset.split( ' ' );
		if( 1 < pieces.length ) {
			var fullWidth = pieces[1].substring( 0, pieces[1].length-2 );
			if( fullWidth > el.width ) {
				el.style.width = fullWidth;
				el.style.maxWidth = '100%';
				el.removeAttribute('height');
			}
		}
	}

	// If the photo is slow to load, it will have no height.
	if ( 0 === el.height ) {
		return;
	}

	// When the slide changes, reset the next/prev text line-height.
	document.querySelectorAll('.flexslider:not(#carousel) .flex-direction-nav a').forEach( a => a.style.lineHeight = el.height + 'px' );
	// Resize the whole slider based on the height of the current image.
	document.querySelector('.flexslider:not(#carousel) .flex-viewport').height = el.height + 'px';
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