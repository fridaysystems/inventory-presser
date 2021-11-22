(function($) {

	function add_block(group){
		var original = $(group).find('.repeat-this');
		var newgroup = original.clone(true).removeClass('repeat-this').addClass('repeated');
		var container = $(group).find('.repeat-container');
		$(container).append(newgroup);
	}

	//on page load
	$(document).ready(function() {

		// hijack the built in taxonomy description for the location address.
		$('.term-description-wrap label').text('Address');
		$('.term-description-wrap p').text('Use the fields below to edit the address.');
		$('#description').attr('readonly',true);
 
		$('.repeat-group').each(function(index,group){

			var group = $(this);

			// make sortable
			$(group).find('.repeat-container').sortable({
				handle: '.repeat-move',
				containment: 'parent'
			});

			// initial setup - add a repeat group
			if ($(group).find('.repeat-container').children().length == 0) {
				add_block(group);
			}

			// add buttons click event
			$(group).find('.repeat-add').on('click', function(e) {
				add_block(group);
			});

			// bind event - delete button
			$(group).find('.repeat-delete').on('click', function(e) {
				var group = $(this).closest('.repeat-group');
				var container = $(this).closest('.repeat-container');
				$(this).closest('.repeated').remove();
				// if there are no repeat groups, add a fresh one
				if ($(container).children().length == 0) {
					add_block(group);
				}
				$(container).sortable('refresh');
			});

		});

		$('table.tags tbody').sortable();

		$('.timepick').timepicker({ 'timeFormat': 'g:i A', 'scrollDefault': 'now' });

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