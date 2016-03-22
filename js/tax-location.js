(function($) {

	//on page load
	$(document).ready(function() {

		$('#location-tax .repeat-group .repeat-add').each(function(index,group){
			$(this).on('click', function(e) {
				var original = $(this).closest('.repeat-group').find('.repeat-this');
				var newgroup = original.clone().removeClass('repeat-this');
				var container = $(this).closest('.repeat-group').find('.repeat-container');
				$(container).append(newgroup);
				$(original).find('input').val('');
			});
		});

	});

})(jQuery);