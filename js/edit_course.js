$(function(){
	$('input[title]').each(function(){
		if (this.value == '') this.value = this.title;
		$(this).focus(function() { if (this.value == this.title && !this.readOnly) this.value = ''; }).blur(function() { if (this.value == '') this.value = this.title; });
	});
});

function sf_reset() {
	$('input#mcgill_id').val('').focus();
	$('input#last_name,input#first_name').attr('readOnly','readOnly').css('opacity', 0.5).each(function(){ $(this).val($(this).attr('title')); });
}

function getStudent(evt) {
	var k = evt.keyCode;
	var mcgill_id = $('input#mcgill_id').val();
	var r_mcgill_id = /\d{9}/;
	if (r_mcgill_id.test(mcgill_id)) {
		$.ajax({
			timeout:	10000,
			url:		'getStudent.php',
			data:		{ id:'<?php print $_GET['id'] ?>', mid:mcgill_id },
			beforeSend:	function() {
							document.body.style.cursor = 'wait';
						},
			complete:	function() {
							document.body.style.cursor = 'auto';
						},
			success:	function(data) {
							var data = data.split(';');
							if (data[0]>0) {
								$('input#mcgill_id').blur();
								$('input#last_name').attr('readOnly','readOnly').css('opacity', 1).val(data[1]);
								$('input#first_name').attr('readOnly','readOnly').css('opacity', 1).val(data[2]);
							} else {
								$('input#last_name,input#first_name').removeAttr('readOnly').css('opacity', 1);
								$('input#last_name').val('').focus();
							}
						}
		});
	} else {
		$('input#last_name,input#first_name').attr('readOnly','readOnly').css('opacity', 0.5).each(function(){ $(this).val($(this).attr('title')); });
	}
}

function addCoordinator() {
	var mcgill_id = $('input#mcgill_id').val();
	var last_name = $('input#last_name').val();
	var first_name = $('input#first_name').val();
	var r_mcgill_id = /\d{9}/;
	var r_name = /\w+/;
	if (r_mcgill_id.test(mcgill_id) && r_name.test(last_name) && r_name.test(first_name) && last_name != 'Last Name' && first_name != 'First Name') {
		if ($('tr#o_'+mcgill_id).size()==0) {
			$('#please').hide();
			$('#coordinator_list').append('<tr id="o_'+mcgill_id+'"><td>'+mcgill_id+'</td><td><input type="hidden" name="coordinators['+mcgill_id+'][last_name]" value="'+last_name+'" />'+last_name+'</td><td><span class="char_button" onclick="removeCoordinator(\''+mcgill_id+'\');" title="Remove this coordinator" style="float:right;clear:none;">&minus;</span><input type="hidden" name="coordinators['+mcgill_id+'][first_name]" value="'+first_name+'" />'+first_name+'</td></tr>');
			$('p#error_paragraph,img.error').hide();
			$('.text, textarea, select').removeClass('error');
		} else {
			alert('This student is already a coordinator.');
		}
		sf_reset();
	} else {
		alert('To add a coordinator, please enter a valid McGill ID, a last name and a first name.');
	}
}

function removeCoordinator(mcgill_id) {
	$('tr#o_'+mcgill_id).remove();
	if ($('#coordinator_list tr').size()==1) {
		$('#please').show();
	}
}