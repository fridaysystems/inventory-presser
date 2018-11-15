jQuery(document).ready(function() {
	jQuery('.slick-slider-element').slick({
		autoplay: true,
		responsive: [
			{
				breakpoint: 1028,
				settings: {
					slidesToShow: 3,
					slidesToScroll: 3,
					autoplaySpeed: 4000
				}
			},
			{
				breakpoint: 800,
				settings: {
					slidesToShow: 2,
					slidesToScroll: 2,
					autoplaySpeed: 3000
				}
			},
			{
				breakpoint: 480,
				settings: {
					slidesToShow: 1,
					slidesToScroll: 1,
					autoplaySpeed: 2000
				}
			},
		]
	});
});