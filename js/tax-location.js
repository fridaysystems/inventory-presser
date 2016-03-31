(function($) {

	function add_block(group){
		var original = $(group).find('.repeat-this');
		var newgroup = original.clone(true).removeClass('repeat-this').addClass('repeated');
		var container = $(group).find('.repeat-container');
		$(container).append(newgroup);
	}

	//on page load
	$(document).ready(function() {

		$('.term-description-wrap label').text('Address');
		$('.term-description-wrap p').text('Enter the address as you would like it to appear on your site.');

		$('.repeat-group').each(function(index,group){

			var group = $(this);

			$(group).find('.repeat-container').sortable({
				handle: '.repeat-move',
				containment: 'parent'
			});

			if ($(group).find('.repeat-container').children().length == 0) {
				add_block(group);
			}

			// add buttons click event
			$(group).find('.repeat-add').on('click', function(e) {
				add_block(group);
			});

			$(group).find('.repeat-delete').on('click', function(e) {
				var container = $(this).closest('.repeat-container');
				if ($(container).children().length > 1) {
					$(this).closest('.repeated').remove();
				} else {
					$(this).closest('.repeated').find('input:text').val('');
					$(this).closest('.repeated').find('input:checkbox').prop('checked', false);
				}
				$(container).sortable('refresh');
			});

		});

		$('.timepick').timepicker({ 'timeFormat': 'h:i A', 'scrollDefault': 'now' });

	});

	// listen for WP ajax call to add location tag, reset the term meta forms when it happens
	$(document).ajaxComplete(function( event, xhr, settings ) {
		if ((settings.data.indexOf('action=add-tag') >= 0) && (settings.data.indexOf('taxonomy=location') >= 0) && (settings.data.indexOf('post_type=inventory_vehicle') >= 0)) {

			$('.repeat-group').each(function(index,group){

				var group = $(this);
				$(group).find('.repeat-container').empty();
				add_block(group);

			});
		}
	});

})(jQuery);