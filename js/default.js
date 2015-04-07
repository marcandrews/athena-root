function isNumberKey(evt) {
var charCode = (evt.which) ? evt.which : event.keyCode
if ( charCode > 31 && (charCode < 48 || charCode > 57) )
	return false;
	return true;
}

function char_remaining (story, limit, counter) {
	var story;
	var limit;
	var counter;
	if (story) {
		if (story.value.length <= limit) {
			document.getElementById(counter).innerHTML = limit - story.value.length;
		} else {
			story.value = story.value.substring(0, limit);
			document.getElementById(counter).innerHTML = 0;
			alert('Sorry. There is a '+limit+' character limit.\n\rYou cannot add anymore characters.');
		}
	}
}

//	$(document).ready(function() {
//		$('form').submit(function() {
//			$('form :submit:first').attr('disabled', 'disabled').val('Please wait ...').fadeOut('slow');
//			$('form :submit:gt(0)').hide('fast');
//		});
//	});

$(function(){
	$('.disable_form_history').attr('autocomplete','off');
});
