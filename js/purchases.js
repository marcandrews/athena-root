function full_reset() {
	$('input[@name=mcgill_id]').val('').focus();
	partial_reset();
}

function partial_reset() {
	$('#status,#purchase_list,#error_paragraph,img.new_error').hide('fast');
	$('input[@type=submit]').attr('disabled','disabled').addClass('disabled');
	$('.buy_error').removeClass();
	$('input[@name=mcgill_id],input[@name=last_name],input[@name=first_name]').removeClass().addClass('text')
	$('input[@name=last_name],input[@name=first_name]').attr('readOnly','readOnly').val('');
}

function getStudent(evt) {
	var k = evt.keyCode; 
	var mcgill_id = $('input[@name=mcgill_id]').val();
	var r_mcgill_id = /\d{9}/;
	if (((k>=48 && k<=58) || (k>=96 && k<=105) || k==8 || k==46 || (evt.ctrlKey && (k==86 || k==88 || k==90)) || (evt.shiftKey && k==45)) && r_mcgill_id.test(mcgill_id)) {
		$.ajax({
			timeout:	10000,
			url:		'getStudent.php?mid='+mcgill_id,
			beforeSend:	function() {
							$('#status').html('<br />Searching...').show('normal');
							document.body.style.cursor = 'wait';
						},
			error:		function() {
							$('#status').html('<br />Searching for this McGill ID failed. Please <a href="javascript:getStudent()">try again</a>.').show();
						},						
			complete:	function() {
							document.body.style.cursor = 'auto';
						},
			success:	function(data) {
							var data = data.split(';');
							if (data[0]>0) {
								$('#purchase_list input').removeAttr('disabled').removeAttr('checked');
								if (data[0]==2) {
									$('input[@name=mcgill_id]').blur();
									$('input[@name=last_name]').attr('readOnly','readOnly').val(data[1]);
									$('input[@name=first_name]').attr('readOnly','readOnly').val(data[2]);
									$('#status').html('<br />Now, select the courses that will be purchased.');
									if (data[3]) {
										$(data[3]).attr('disabled','disabled').attr('checked','checked');
									}
								} else if (data[0]==1) {
									$('input[@name=last_name],input[@name=first_name]').removeAttr('readOnly').val('');
									$('input[@name=last_name]').focus();
									$('#status').html('<br />Next, enter his/her last and first name above.');
								}
								validate();
							} else {
								$('#status').html('<br />Searching for this McGill ID failed. Please <a href="javascript:getStudent()">try again</a>.');
							}
						}
		});
	} else if (!(r_mcgill_id.test(mcgill_id))) {
		partial_reset();
		if (mcgill_id.length == 9) {
			alert('Please enter a valid 9-digit McGill ID.');
		}
	}
}

function validate() {
	if ($('input[@name=last_name]').val() && $('input[@name=first_name]').val() && $('input:checkbox:enabled:checked').val()) {
		$('#status').html('<br />Now, select the courses that will be purchased, and then click on <strong>Next</strong>.');
		$('#purchase_list').show('normal');
		$('input[@type=submit]').removeAttr('disabled').removeClass('disabled');
	} else if ($('input[@name=last_name]').val() && $('input[@name=first_name]').val()) {
		$('#status').html('<br />Now, select the courses that will be purchased, and then click on <strong>Next</strong>.');
		$('#purchase_list').show('normal');
		$('input[@type=submit]').attr('disabled','disabled').addClass('disabled');
	} else {
		$('#status').html('<br />Next, enter his/her last and first name above.');
		$('#purchase_list').hide('fast');
		$('input[@type=submit]').attr('disabled','disabled').addClass('disabled');
	}
}