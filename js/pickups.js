//<![CDATA[
ajax_loader_image = new Image(13, 13); 
ajax_loader_image.src = '/i/loading_13.gif'; 

$(function(){
	$('#loading').fadeOut('normal', function(){
		$('#course_' + $('select[@name=cid]').val()).fadeIn('normal');
	});
	$('input[title]').each(function(){
		if (this.value == '') this.value = this.title;
		$(this).focus(function() { if (this.value == this.title && !this.readOnly) this.value = ''; }).blur(function() { if (this.value == '') this.value = this.title; });
	});
});

function switch_course(cid) {
	$('.student_list_container:visible').fadeOut('fast', function(){
		$('#n_'+cid).val('Name');
		$('#mid_'+cid).val('McGill ID');
		$('#student_list_'+cid+' tr').show();
		$('#course_'+cid).fadeIn('normal', function(){
			location.href = '#pickup_list';
		});
	});
}

function filter (phrase, id, col) {
	var search_for = phrase.value.toLowerCase();
	var table = document.getElementById(id);
	var row;
	for (var r = 0; r < table.rows.length; r++){
		row = table.rows[r].cells[col].innerHTML.replace(/<[^>]+>/g,"");
		if (row.toLowerCase().indexOf(search_for) >= 0) {
			table.rows[r].style.display = '';
		} else {
			table.rows[r].style.display = 'none';
		}
	}
}

var timer = new Array()
function pickup_queue(checkbox) {
	if (checkbox.checked) {
		timer[checkbox.id] = setTimeout('pickup_save("'+checkbox.id+'")', 10000);
	} else {
		clearTimeout(timer[checkbox.id]);
	}
}

function pickup_save(checkbox) {
	var checkbox;
	var checkbox_id = '#' + checkbox;
	var pickup = checkbox.split('_');
	if ($(checkbox_id + ':checked')) {
		$.ajax({
			timeout:	10000,
			url:		'/associations/pickups.php',
			data:		{ pid: pickup[1], nid: pickup[2] },
			beforeSend:	function() {
							$(checkbox_id).hide().after('<img id="l' + checkbox + '" src="/i/loading_13.gif" alt="Saving &hellip;" />');
						},
			error:		function() {
							
						},						
			complete:	function() {
							$('#l' + checkbox).remove();
							$(checkbox_id).show();
						},
			success:	function(data) {
							if (data > 0) {
								$(checkbox_id).attr('disabled', 'disabled');
							} else {
								alert(data);
							}
						}
		});
	}
}

function submit_form () {
	window.onbeforeunload = function () { };
	$('.student_list_container:visible').fadeOut('fast', function() { $('#loading').fadeIn('normal'); } );
	$('form')[0].submit();
}

window.onbeforeunload = function () {
	if ($('input:checkbox:enabled:checked').length > 0) {
		return 'There are pickups that have not been saved to Athena.';
	}
};
//]]>