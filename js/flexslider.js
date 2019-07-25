jQuery('.flexslider').flexslider({
	animation: 'slide',
	controlNav: false,
	prevText: '',
	nextText: ''
});
var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutationRecord) {
        jQuery('#slider').data('flexslider').resize();
    });
});
jQuery(document).ready( function() {
	jQuery('#slider .slides li:first-child img').each( function() {
		observer.observe(jQuery(this)[0], { attributes : true, attributeFilter : ['style'] });
	});
});