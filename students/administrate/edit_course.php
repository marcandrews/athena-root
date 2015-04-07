<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$_GET['id'] = (int)$_GET['id'];
$user_authentication->validate_student();
$user_authentication->validate_administrator($_GET['id']);

$association['sql'] = 'SELECT id, name, abbreviation FROM associations WHERE id = '.$_GET['id'].' AND administrator = '.$_SESSION['student']['student_id'];
if ( $association['query'] = mysql_query($association['sql']) and mysql_num_rows($association['query']) == 1 ) {
	$association['results'] = mysql_fetch_assoc($association['query']);
}

$course['sql'] = '	SELECT	a.abbreviation,
							c.id, c.prog_abbrev, c.course_code, c.course_name, c.semester, c.distribution, c.release_day, c.price, c.writer_salary, c.modified, c.created
					FROM	associations AS a
					LEFT	JOIN courses AS c ON a.id = c.association_id AND c.id = "'.$_GET['cid'].'"
					WHERE	a.id = '.$_GET['id'].' AND 
							a.administrator = "'.$_SESSION['student']['student_id'].'"
					LIMIT 1';
if ($course['query'] = mysql_query($course['sql']) and mysql_num_rows($course['query']) == 1) {
	$course['results'] = mysql_fetch_assoc($course['query']);
	if ($course['results']['id']) {
		$html['semester'][$course['results']['semester']] = ' selected="selected"';
		$html['distribution'][$course['results']['distribution']] = ' selected="selected"';
		$html['release_day'][$course['results']['release_day']] = ' selected="selected"';
		$title = 'Edit '.$course['results']['prog_abbrev'].$course['results']['course_code'].': '.$course['results']['course_name'];
		$description = 'Use the following to edit <strong>'.$course['results']['prog_abbrev'].$course['results']['course_code'].': '.$course['results']['course_name'].'</strong>.';
		$submit = 'Edit';
	} else {
		$course['query'] = false;
		$title = 'Add A Course';
		$submit = 'Add';
		$description = 'Complete the following to add a course.';
		$course['results']['prog_abbrev'] = $association['results']['prog_abbrev'];
		$html['semester'][current_semester()] = ' selected="selected"';
	}
} else {
	$layout->redirector('Oops!', 'A problem occurred while attempting to retrieve course information. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Athena Administrators</a>.');
}

if ( $_POST ) {
	if (!preg_match('/\D{4}/', $_POST['prog_abbrev']) ) {
		$error['prog_abbrev'] = error_inline('A 4-letter program abbreviation is required.');
	}
	if (!preg_match('/\d{3}/', $_POST['course_code']) ) {
		$error['course_code'] = error_inline('A 3-digit course code is required.');
	}
	if (!preg_match('/\w+/', $_POST['course_name']) ) {
		$error['course_name'] = error_inline('A course name is required.');
	}
	if (!is_numeric($_POST['price']) ) {
		$error['price'] = error_inline('A sale price required.');
	}
	if (!is_numeric($_POST['writer_salary']) ) {
		$error['writer_salary'] = error_inline('A writer salary is required.');
	}
	if (is_array($_POST['coordinators']) ) {
		foreach ($_POST['coordinators'] as $mcgill_id => $names) {
			if (preg_match('/\d{9}/',$mcgill_id)) {
				foreach ($names as $value) {
					if ($value == '' or $value == 'Last Name' or $value == 'First Name') {
						$error['coordinators'] = true;
						break;
					}
				}
			} else {
				$error['coordinators'] = true;
				break;
			}
		}
	} else {
		$error['coordinators'] = true;
	}

	if ( $error ) {
		$step = 1;
		$course['results'] = $_POST;
		$html['semester'][$_POST['semester']] = ' selected="selected"';
		$html['distribution'][$_POST['distribution']] = ' selected="selected"';
		$html['release_day'][$_POST['release_day']] = ' selected="selected"';
	} else {
		$step = 2;
	}
}

switch ($step) {
	case 2:
		// Start transaction.
		mysql_query('START TRANSACTION');

		// Insert or update the course.
		if ($course['query']) {
			$course['sql'] = '	UPDATE	courses
								SET		association_id	= '.$association['results']['id'].',
										prog_abbrev		= "'.strtoupper($_POST['prog_abbrev']).'",
										course_code		= "'.$_POST['course_code'].'",
										course_name		= "'.$_POST['course_name'].'",
										semester		= "'.$_POST['semester'].'",
										year			= "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'",
										distribution	= "'.$_POST['distribution'].'",
										price			= "'.$_POST['price'].'",
										writer_salary	= "'.$_POST['writer_salary'].'",
										release_day		= "'.$_POST['release_day'].'",
										active			= "1",
										modified		= NOW()
								WHERE	id				= '.$_GET['cid'];
		} else {
			$course['sql'] = '	INSERT 	INTO courses
								SET		association_id	= '.$association['results']['id'].',
										prog_abbrev		= "'.strtoupper($_POST['prog_abbrev']).'",
										course_code		= "'.$_POST['course_code'].'",
										course_name		= "'.$_POST['course_name'].'",
										semester		= "'.$_POST['semester'].'",
										year			= "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'",
										distribution	= "'.$_POST['distribution'].'",
										price			= "'.$_POST['price'].'",
										writer_salary	= "'.$_POST['writer_salary'].'",
										release_day		= "'.$_POST['release_day'].'",
										active			= "1",
										created			= NOW()';
		}
		mysql_query($course['sql']) or die($layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect'])));
		if (mysql_insert_id()) $_GET['cid'] = mysql_insert_id();

		// Delete previous coordinators for this course.
		if ($course['query']) {
			$coordinators['sql'] = 'DELETE FROM purchases WHERE course_id = '.$_GET['cid'].' AND coordinator = true';
			if ( !mysql_query($coordinators['sql']) ) {
				$layout->redirector('Error3', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
			}
		}

		// Add coordinators.
		foreach ($_POST['coordinators'] as $mcgill_id => $name) {
			$student['sql'] = '	SELECT	id
								FROM	students
								WHERE	last_name = "'.$name['last_name'].'" AND
										first_name = "'.$name['first_name'].'" AND
										mcgill_id = "'.$mcgill_id.'"
								LIMIT 	1';
			if ($student['query'] = mysql_query($student['sql']) and mysql_num_rows($student['query']) == 1) {
				$student['results'] = mysql_fetch_assoc($student['query']);
			} else {
				$student['sql'] = '	INSERT INTO	students
									SET			last_name = "'.$name['last_name'].'",
												first_name = "'.$name['first_name'].'",
												mcgill_id = "'.$mcgill_id.'",
												password = "'.md5($mcgill_id).'",
												created = NOW()';
				if ( $student['query'] = mysql_query($student['sql']) ) {
					$student['results']['id'] = mysql_insert_id($info['sql_db_connect']);
					$val['sql'] = '	INSERT INTO	students_validations
									SET			student_id = "'.$student['results']['id'].'",
												code = "'.md5($student['results']['id'].'_'.$mcgill_id).'",
												created = NOW()';
					$val['query'] = mysql_query($val['sql']) or die($layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect'])));
				} else {
					$layout->redirector('Error2', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
				}
			}
			$coordinators['sql'] = 'REPLACE purchases (course_id, student_id, coordinator, date) VALUES ('.$_GET['cid'].', '.$student['results']['id'].', 1, NOW())';
			if ( !mysql_query($coordinators['sql']) ) {
				$layout->redirector('Error3', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
			}
		}

		if (mysql_query('COMMIT')) {
			$layout->redirector('Athena has been updated ...', 'The course has been successfully '.strtolower($submit).'ed. Now redirecting you to ...', '/students/administrate/edit_courses.php?id='.$_GET['id']);
		} else {
			$layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
		}
	break;
	default:
		$layout->output_header('Administrate '.$association['results']['sa_abbrev'].' | Edit Courses | '.$title, 'student');
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
//]]>
</script>
<ul id="breadcrumb">
	<li><?php print $title ?></li>
	<li> &laquo; <a href="/students/administrate/edit_courses.php?id=<?php print $_GET['id'] ?>">Edit Courses</a></li>
	<li> &laquo; <a href="/students/administrate/?id=<?php print $_GET['id'] ?>">Administrate <?php print $association['results']['abbreviation'] ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1><?php print $title ?></h1>
<?php
		general_error($error);
?>
<form method="post" action="<?php print $_SERVER['PHP_SELF'].'?id='.$_GET['id'].'&amp;cid='.$_GET['cid'] ?>">
	<fieldset>
		<fieldset class="psuedo_p">
			<p><?php print $description ?></p>
			<fieldset class="top-down">
				<ul>
					<li>
						<label for="prog_abbrev">Program <abbr title="Abbreviation">Abbrev.</abbr></label>
						<input type="text" name="prog_abbrev" id="prog_abbrev" class="text<?php if ( $error['prog_abbrev'] ) print ' error'; ?>" value="<?php print $course['results']['prog_abbrev'] ?>" size="4" maxlength="4" /><?php print $error['prog_abbrev'] ?> 
					</li>
					<li>
						<label for="course_code">Course Code</label>
						<input type="text" name="course_code" id="course_code" class="text<?php if ( $error['course_code'] ) print ' error'; ?>" value="<?php print $course['results']['course_code'] ?>" size="4" maxlength="3" /><?php print $error['course_code'] ?> 
					</li>
					<li>
						<label for="course_name">Course Name</label>
						<input type="text" name="course_name" id="course_name" class="text<?php if ( $error['course_name'] ) print ' error'; ?>" value="<?php print $course['results']['course_name'] ?>" style="width:320px;" maxlength="256" /><?php print $error['course_name'] ?> 
					</li>
					<li>
						<label for="semester">Semester</label>
						<select name="semester" id="semester">
<?php
		foreach ($info['semesters'] as $key => $value) {
?>
							<option value="<?php print $key ?>"<?php print $html['semester'][$key] ?>><?php print $value ?></option>
<?php
		}
?>
						</select>
					</li>
					<li>
						<label for="distribution">Distribution</label>
						<select name="distribution" id="distribution">
<?php
		foreach ($info['distribution_methods'] as $key => $value) {
?>
							<option value="<?php print $key ?>"<?php print $html['distribution'][$key] ?>><?php print $value ?></option>
<?php
		}
?>
						</select>
					</li>
					<li>
						<label for="release_day">Release Day</label>
						<select name="release_day" id="release_day">
							<option value="0"<?php print $html['release_day'][0] ?>>Sunday</option>
							<option value="1"<?php print $html['release_day'][1] ?>>Monday</option>
							<option value="2"<?php print $html['release_day'][2] ?>>Tuesday</option>
							<option value="3"<?php print $html['release_day'][3] ?>>Wednesday</option>
							<option value="4"<?php print $html['release_day'][4] ?>>Thursday</option>
							<option value="5"<?php print $html['release_day'][5] ?>>Friday</option>
							<option value="6"<?php print $html['release_day'][6] ?>>Saturday</option>
						</select>
					</li>
					<li>
						<label for="price">Sale Price</label>
						<div class="text<?php if ( $error['price'] ) print ' error'; ?>" style="width:65px;">$
							<input type="text" name="price" id="price" value="<?php print $course['results']['price'] ?>" size="5" maxlength="5" /></div><?php print $error['price'] ?> 
					</li>
					<li>
						<label for="writer_salary">Writer Salery</label>
						<div class="text<?php if ( $error['writer_salary'] ) print ' error'; ?>" style="width:65px;">$
							<input type="text" name="writer_salary" id="writer_salary" value="<?php print $course['results']['writer_salary'] ?>" size="5" maxlength="5" /></div><?php print $error['writer_salary'] ?> 
					</li>
				</ul>
			</fieldset>
			<fieldset class="student_selector">
				<label for="mcgill_id">Coordinator(s)</label>
				<table>
					<thead>
						<tr>
							<td class="mcgill_id"><input type="text" id="mcgill_id" title="McGill ID" maxlength="9" class="text disable_form_history<?php if ( $error['coordinators'] ) { print ' error'; } ?>" value="<?php print $_POST['mcgill_id'] ?>" onkeyup="getStudent(event)" /></td>
							<td class="last_name"><input type="text" id="last_name" title="Last Name" class="text disable_form_history<?php if ( $error['coordinators'] ) { print ' error'; } ?>" readonly="readonly" value="<?php print $_POST['last_name'] ?>" /></td>
							<td class="first_name"><input type="text" id="first_name" title="First Name" class="text disable_form_history<?php if ( $error['coordinators'] ) { print ' error'; } ?>" readonly="readonly" value="<?php print $_POST['first_name'] ?>" />
								<span class="char_button" onclick="sf_reset()" title="Restart search">&times;</span> <span class="char_button" onclick="addCoordinator()" title="Add this student as a coordinator">+</span></td>
						</tr>
					</thead>
					<tbody id="coordinator_list">
						<tr>
							<td colspan="4" id="please">Please add at least one coordinator.</td>
						</tr>
<?php
		$coordinators['sql'] = 'SELECT s.mcgill_id, s.last_name, s.first_name FROM students AS s, purchases AS p WHERE s.id = p.student_id AND p.course_id = '.$_GET['cid'].' AND p.coordinator = true ORDER BY last_name, first_name';
		if ( $coordinators['query'] = mysql_query($coordinators['sql']) and mysql_num_rows($coordinators['query']) > 0 ) {
			$coordinators['results'] = mysql_fetch_assoc($coordinators['query']);
			do {
				print <<<EOT
						<tr id="o_{$coordinators['results']['mcgill_id']}">
							<td><input type="hidden" name="coordinators[{$coordinators['results']['mcgill_id']}][last_name]" value="{$coordinators['results']['last_name']}" />
								<input type="hidden" name="coordinators[{$coordinators['results']['mcgill_id']}][first_name]" value="{$coordinators['results']['first_name']}" />
								{$coordinators['results']['mcgill_id']}</td>
							<td>{$coordinators['results']['last_name']}</td>
							<td><span class="char_button" onclick="removeCoordinator('{$coordinators['results']['mcgill_id']}');" title="Remove this coordinator" style="float:right;clear:none;">&minus;</span>{$coordinators['results']['first_name']}</td>
						</tr>

EOT;
			} while ( $coordinators['results'] = mysql_fetch_assoc($coordinators['query']) );
		}
?>
					</tbody>
				</table>
			</fieldset>
<?php
		if ( $course['query'] ) {
?>
			<fieldset class="top-down">
				<ul>
<?php
			if ( $course['results']['modified'] != 0 ) { ?>
					<li>
						<label>Last modified</label>
						<strong><?php print date('Y/m/d \a\t H:i',strtotime($course['results']['modified'])) ?></strong>
					</li>
<?php
			}
?>
					<li>
						<label>Created</label>
						<strong><?php print date('Y/m/d \a\t H:i',strtotime($course['results']['created'])) ?></strong>
					</li>
				</ul>
			</fieldset>
<?php
		}
?>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="button" class="back" value="&lsaquo; Cancel" onclick="location.href='/students/administrate/edit_courses.php?id=<?php print $_GET['id'] ?>'" />
			<input type="submit" class="forward" value="<?php print $submit ?> this course &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<script type="text/javascript">
//<![CDATA[

var container = document.getElementById('coordinator_list');
var items = container.getElementsByTagName('tr');
if ( items.length > 1 ) {
	document.getElementById('please').style.display = 'none';
} else {
	document.getElementById('please').style.display = '';
}
//]]>
</script>
<?php
		$layout->output_footer();
}
?>
