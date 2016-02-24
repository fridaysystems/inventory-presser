(function($) {

	var template = '',
		cur_page = 0,
		per_page = 10,
		top_adjust = 100;

	function populate_inventory(cur_page) {
		
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
					page_count = Math.ceil(response.total/per_page);
					for (var i = 0; i < page_count; i++) {
						var page_link = (i == cur_page) ? i + 1 : '<a href="#">' + (i + 1) + '</a>';
						var pager_div = $('<div class="invp-page">' + page_link + '</div>');
						$('.invp-pages').append(pager_div);
					}
					// bind links
					$('.invp-page a').on('click',function(e){
						e.preventDefault();
						cur_page = $(this).parent().index();
						populate_inventory(cur_page);
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
	$( document ).ready(function() {

		$.get(invp_options.template, function(response) {
		     template = response;
		     per_page = invp_options.per_page;
		     populate_inventory(cur_page);
		});

	});

})(jQuery);