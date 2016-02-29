(function($) {

	var template = '',
		cur_page = 0,
		per_page = 10,
		top_adjust = 100;

	function populate_inventory() {
		
		$.ajax({
			method: "POST",
			url: invp_options.ajax_url,
			dataType: "json",
			data: {
				action: 'get_simple_listing',
				per_page: per_page,
				cur_page: cur_page,
			}
		})
		.done(function(response) {
			if (response.status == 'ok') {
				$('.invp-reset').empty();

				// create paging
				if ($('.invp-pages').length && (response.total/1) > per_page) {
					$('.invp-pages').append('<span>Pages: </span>');
					page_count = Math.ceil(response.total/per_page);
					for (var i = 0; i < page_count; i++) {
						var page_link = (i == cur_page) ? '<a href="#" class="invp-page invp-page-active">' + (i + 1) + '</a>' : '<a href="#" class="invp-page">' + (i + 1) + '</a>';
						var pager_div = $('<div class="invp-page">' + page_link + '</div>');
						$('.invp-pages').append(page_link);
					}
					// bind links
					$('a.invp-page').on('click',function(e){
						e.preventDefault();
						cur_page = $(this).index()-1;
						populate_inventory();
						$('html, body').animate({
					        scrollTop: $(this).closest('.invp-wrapper').offset().top - top_adjust
					    }, 1000);
					});
				}

				// loop through inventory, replace string values and append
				$.each(response.inventory, function (index, item) {
					// set template html to a variable
					var html = template;
					// loop through each key value pair in the inventory item, replace {{key}} with value
					$.each(item, function(key, value) {
						var re = new RegExp('{{'+key+'}}', 'g');
						html = html.replace(re, value);
					});
			        // append to div wrapper
			        $('.invp-listing').append(html);
			    });
			} else {
				alert ('Error retrieving inventory, please try again!');
				console.log(response.message);
			}
		});

	}

	//on page load
	$(document).ready(function() {

		// if we're on the shortcode page, show listing
		if (invp_options.is_archive) {
			$.get(invp_options.template, function(response) {
			     template = response;
			     per_page = invp_options.per_page;
			     populate_inventory();
			});
		}

		// if we're on a singular inventory page, remove featured image from page, where added by the theme
		if (invp_options.is_singular) {
			$(invp_options.featured_image_urls).each(function(index,url){
				$("img[src='" + url + "']:not('.invp-image')").remove();
			});
		}

	});

	$(window).load(function() {
	  // The slider being synced must be initialized first
	  $('#carousel').flexslider({
	    animation: "slide",
	    controlNav: false,
	    animationLoop: false,
	    smoothHeight: true,
	    slideshow: false,
	    itemWidth: 150,
	    itemMargin: 10,
	    asNavFor: '#slider'
	  });
	 
	  $('#slider').flexslider({
	    animation: "fade",
	    controlNav: false,
	    animationLoop: false,
	   	smoothHeight: true,
	    slideshow: false,
	    sync: "#carousel"
	  });

	});

})(jQuery);