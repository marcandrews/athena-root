<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_student();
$user_authentication->validate_administrator($_GET['id']);

$association['sql'] = 'SELECT * FROM associations WHERE id = '.$_GET['id'].' AND administrator = '.$_SESSION['student']['student_id'];
$association['query'] = mysql_query($association['sql']) or die(mysql_error());
if ( mysql_num_rows($association['query']) == 1 ) {
	$association['results'] = mysql_fetch_assoc($association['query']);
}

if ( $_POST ) {
	if	(
			!preg_match('/\d{9}/', $_POST['mcgill_id']) or $_POST['mcgill_id'] == 'McGill ID' or
			!preg_match('/\w+/', $_POST['last_name']) or $_POST['last_name'] == 'Last Name' or
			!preg_match('/\w+/', $_POST['first_name']) or $_POST['first_name'] == 'First Name'
		)
	{
		$error['coordinator'] = error_inline('A first name, last name and McGill ID is required.');
		$course['results'] = $_POST;
	} else {
		$step = 2;
	}
}

switch ($step) {
	case 2:
		// Start the MySQL Transaction.
		mysql_query('START TRANSACTION');
		
		// Search for the new administrator.
		$student['sql'] = '	SELECT	id
							FROM	students
							WHERE	last_name = "'.$_POST['last_name'].'" AND
									first_name = "'.$_POST['first_name'].'" AND
									mcgill_id = "'.$_POST['mcgill_id'].'"
							LIMIT 	1';
		if ($student['query'] = mysql_query($student['sql']) and mysql_num_rows($student['query']) == 1) {
			// New administrator found.
			$student['results'] = mysql_fetch_assoc($student['query']);
		} else {
			// New administrator not found; add him/her to Athena.
			$student['sql'] = '	INSERT INTO	students
								SET			last_name = "'.$_POST['last_name'].'",
											first_name = "'.$_POST['first_name'].'",
											mcgill_id = "'.$_POST['mcgill_id'].'",
											password = "'.md5($_POST['mcgill_id']).'",
											created = NOW()';
			if ($student['query'] = mysql_query($student['sql'])) {
				$student['results']['id'] = mysql_insert_id($info['sql_db_connect']);
			} else {
				$layout->redirector('Athena | Error', "Athena was unable to add {$_POST['first_name']} {$_POST['last_name']} ({$_POST['mcgill_id']}). Please <a href=\"javascript:location.reload();\">refresh</a> this page and try again; however, if the problem persists, please <a href=\"/contact.php?to=1\">contact the administrators of Athena</a>.");
			}

			// Create his/her validation bits.
			$val['sql'] = '	INSERT INTO	students_validations
							SET			student_id = "'.$student['results']['id'].'",
										code = "'.md5($student['results']['id'].'_'.$_POST['mcgill_id']).'",
										created = NOW()';
			if (!($val['query'] = mysql_query($val['sql']))) {
				$layout->redirector('Athena | Error', "Athena was unable to add {$_POST['first_name']} {$_POST['last_name']} ({$_POST['mcgill_id']}). Please <a href=\"javascript:location.reload();\">refresh</a> this page and try again; however, if the problem persists, please <a href=\"/contact.php?to=1\">contact the administrators of Athena</a>.");
			}
		}
		
		// Set the new administrator.
		$course['sql'] = '	UPDATE	associations
							SET		administrator  = "'.$student['results']['id'].'",
									modified	= NOW()
							WHERE	id = '.$_GET['id'];
		if ($course['query'] = mysql_query($course['sql']) and mysql_query('COMMIT')) {
			$user_authentication->sign_out();
			$layout->redirector('Athena has been updated ...', 'You are no longer the administrator of '.$association['results']['abbreviation'].'. You will now be logged out ...', '/');
		} else {
				$layout->redirector('Athena | Error', "Athena was unable to set {$_POST['first_name']} {$_POST['last_name']} ({$_POST['mcgill_id']}) as the administrator of {$association['results']['abbreviation']}. Please <a href=\"javascript:location.reload();\">refresh</a> this page and try again; however, if the problem persists, please please <a href=\"/contact.php?to=1\">contact the administrators of Athena</a>.");
		}
		break;
	default:
		$layout->output_header('Administrate '.$association['results']['abbreviation'].' | Descend from Administrator Privileges', 'student');
?>
<script type="text/javascript">
//<![CDATA[

$(function(){
	$('input[title]').each(function(){
		if (this.value == '') this.value = this.title;
		$(this).focus(function() { if (this.value == this.title && !this.readOnly) this.value = ''; }).blur(function() { if (this.value == '') this.value = this.title; });
	});
});


function sf_reset() {
	$('p#error_paragraph,img.error').hide();
	$('.text, textarea, select').removeClass('error');
	$('input[@name=mcgill_id]').val('').focus();
	$('input[@name=last_name],input[@name=first_name]').attr('readOnly','readOnly').css('opacity', 0.5).each(function(){ $(this).val($(this).attr('title')); });
}

function getStudent(evt) {
	var k = evt.keyCode;
	var mcgill_id = $('input[@name=mcgill_id]').val();
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
								if (data[0]==2) {
									$('input[@name=mcgill_id]').blur();
									$('input[@name=last_name]').attr('readOnly','readOnly').css('opacity', 1).val(data[1]);
									$('input[@name=first_name]').attr('readOnly','readOnly').css('opacity', 1).val(data[2]);
								} else if (data[0]==1) {
									alert('Please specify a student other than yourself.');
									$('input[@name=mcgill_id]').val('');
									$('input[@name=last_name],input[@name=first_name]').attr('readOnly','readOnly').each(function(){ $(this).val($(this).attr('title')); });
								}
							} else {
								$('input[@name=last_name],input[@name=first_name]').removeAttr('readOnly').css('opacity', 1).each(function(){ $(this).val($(this).attr('title')); });
								$('input[@name=last_name]').focus();
							}
						}
		});
	} else {
		$('input[@name=last_name],input[@name=first_name]').attr('readOnly','readOnly').css('opacity', 0.5).each(function(){ $(this).val($(this).attr('title')); });
	}
}

function descend() {
	var mcgill_id = $('input[@name=mcgill_id]').val();
	var last_name = $('input[@name=last_name]').val();
	var first_name = $('input[@name=first_name]').val();

	var r_name = /\w+/;
	var r_mcgill_id = /\d{9}/;

	if ( r_name.test(last_name) && r_name.test(first_name) && r_mcgill_id.test(mcgill_id) ) {		
		return confirm('Are you sure you want to descend from administrator privileges, transfering these privileges to:\n'+first_name+' '+last_name+' ('+mcgill_id+')?\n\nIf you continue, you will be logged out and, upon logging in, you will no longer be able to administrate the <?php print addslashes($association['results']['name']) ?>.');
	}
}
//]]>
</script>
<ul id="breadcrumb">
	<li>Descend from Administrator Privileges</li>
	<li> &laquo; <a href="/students/administrate/?id=<?php print $_GET['id'] ?>">Administrate <?php print $association['results']['abbreviation'] ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Descend from Administrator Privileges</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF'].'?id='.$_GET['id'] ?>" class="disable_form_history">
<?php 	general_error($error); ?>
	<fieldset class="psuedo_p">
		<p>You are currently the administrator of the <strong><?php print $association['results']['name'] ?></strong>.</p>
		<p>Use the following to find a student to replace you as administrator.
			Please start by entering a McGill ID, a last name, and then a first name.</p>
		<fieldset class="student_selector">
			<table>
				<tr>
					<td class="mcgill_id"><input type="text" name="mcgill_id" title="McGill ID" maxlength="9" class="text<?php if ( $error['coordinator'] ) { print ' error'; } ?>" value="<?php print $_POST['mcgill_id'] ?>" onkeyup="getStudent(event)" /></td>
					<td class="last_name"><input type="text" name="last_name" title="Last Name" class="text<?php if ( $error['coordinator'] ) { print ' error'; } ?>" readonly="readonly" value="<?php print $_POST['last_name'] ?>" /></td>
					<td class="first_name"><input type="text" name="first_name" title="First Name" class="text<?php if ( $error['coordinator'] ) { print ' error'; } ?>" readonly="readonly" value="<?php print $_POST['first_name'] ?>" /> <span class="char_button" onclick="sf_reset()" title="Restart search">&times;</span><?php print $error['coordinator'] ?></td>
				</tr>
			</table>
		</fieldset>
	</fieldset>
	<fieldset id="controls" class="psuedo_p" title="Form controls">
		<input type="button" class="back" value="&lsaquo; Cancel" onclick="top.location.href='/students/administrate/?id=<?php print $_GET['id'] ?>'" />
		<input type="submit" class="forward" value="Descend &rsaquo;" onclick="return descend()" />
	</fieldset>
</form>
<?php
		$layout->output_footer();
}
?>
