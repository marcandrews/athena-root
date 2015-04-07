<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$_GET['id'] = (int) $_GET['id'];
$user_authentication->validate_student();
$user_authentication->validate_administrator($_GET['id']);

$association['sql'] = 'SELECT * FROM associations WHERE id = '.$_GET['id'].' AND administrator = '.$_SESSION['student']['student_id'];
if ($association['query'] = mysql_query($association['sql']) and mysql_num_rows($association['query']) == 1) {
	$association['results'] = mysql_fetch_assoc($association['query']);

	// Delete a course.
	if (is_numeric($_GET['delete'])) {
		$d['sql'] = 'DELETE FROM courses WHERE association_id = "'.$_GET['id'].'" AND id = "'.$_GET['delete'].'" LIMIT 1';
		if ( $d['query'] = mysql_query($d['sql']) ) {
			$layout->redirector('Course Successfully Deleted ...', 'Athena has been updated. Now redirecting you to ...', $_SERVER['PHP_SELF'].'?id='.$_GET['id']);
		} else {
			$layout->redirector("Error.", "There was an error:<br />" . mysql_error($info['sql_db_connect']));
		}
	}

	// Duplicate a course.
	if (is_numeric($_GET['duplicate'])) {
		$source['sql'] = 'SELECT * FROM courses WHERE association_id = '.$_GET['id'].' AND id = '.$_GET['duplicate'].' LIMIT 1';
		if ( $source['query'] = mysql_query($source['sql']) ) {
			$source['results'] = mysql_fetch_assoc($source['query']);
			$destination['sql'] = '	INSERT	INTO courses
									SET		association_id	= "'.$source['results']['association_id'].'",
											prog_abbrev		= "'.$source['results']['prog_abbrev'].'",
											course_code		= "'.$source['results']['course_code'].'",
											course_name		= "'.$source['results']['course_name'].'",
											semester		= "'.$source['results']['semester'].'",
											year			= "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'",
											price			= "'.$source['results']['price'].'",
											writer_salary	= "'.$source['results']['writer_salary'].'",
											release_day		= "'.$source['results']['release_day'].'",
											active			= "1",
											created			= NOW()';
			if ($destination['query'] = mysql_query($destination['sql'])) {
				$layout->redirector('Course Successfully Duplicated ...', 'Athena has been updated. Now redirecting you to ...', $_SERVER['PHP_SELF'].'?id='.$_GET['id']);
			} else {
				$layout->redirector("Error.", "There was an error:<br />" . mysql_error($info['sql_db_connect']));
			}
		} else {
			$layout->redirector("Error.", "There was an error:<br />" . mysql_error($info['sql_db_connect']));
		}
		exit;
	}
	$layout->output_header('Administrate '.$association['results']['sa_abbrev'].' | Edit Courses', 'student');
?>
<script type="text/javascript">
//<![CDATA[
function confirmation (cid, desc) {
	var answer = confirm(desc);
	if (answer) {
		window.location = '<?php print $_SERVER['PHP_SELF'] ?>?id=<?php print $_GET['id'] ?>&amp;delete='+cid;
	}
}
//]]>
</script>
<ul id="breadcrumb">
	<li>Edit Courses</li>
	<li> &laquo; <a href="/students/administrate/?id=<?php print $_GET['id'] ?>">Administrate <?php print $association['results']['abbreviation'] ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Edit Courses</h1>
<div class="psuedo_p">
	<p>Use the following to edit courses for this school year, <strong><?php print (current_semester() == 0) ? date('Y') .'/'. (date('Y')+1) : (date('Y')-1) .'/'. date('Y') ?></strong>. To add, change
		or delete a course, click on the plus sign (<strong>+</strong>), delta (<strong>&Delta;</strong>) or minus
		sign (<strong>&minus;</strong>), respectively. For security, only courses without purchases
		can be deleted.</p>
	<table summary="List of courses currently offered this year.">
		<thead>
			<tr>
				<th scope="col">Code</th>
				<th scope="col" style="width:100%">Name</th>
				<th scope="col">Coordinator(s)</th>
				<th scope="col">Semester</th>
				<th scope="col" style="text-align:right">Price</th>		
				<th scope="col">&nbsp;</th>
				<th scope="col"><a href="/students/administrate/edit_course.php?id=<?php print $_GET['id'] ?>" class="char_button">+</a></th>
			</tr>
		</thead>
		<tbody>
<?php
	$courses['sql'] = '	SELECT		c.id, c.prog_abbrev, c.course_code, c.course_name, c.semester, c.price
						FROM		courses AS c
						WHERE		c.association_id = '.$_GET['id'].' AND
									c.year = "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'"
						ORDER BY	c.semester ASC, c.prog_abbrev ASC, c.course_code ASC';
	$courses['query'] = mysql_query($courses['sql']) or die(mysql_error($courses['query']));
	if ( mysql_num_rows($courses['query']) > 0 ) {
		$courses['results'] = mysql_fetch_assoc($courses['query']);
		do { ?>
			<tr>
				<td><?php print $courses['results']['prog_abbrev'] ?><?php print $courses['results']['course_code'] ?></td>
				<td align="left"><?php print $courses['results']['course_name'] ?></td>
<?php
	unset($coordinators);
	$coordinators['sql'] = 'SELECT s.last_name, s.first_name FROM students AS s, purchases AS p WHERE s.id = p.student_id AND p.course_id = '.$courses['results']['id'].' AND p.coordinator = true ORDER BY last_name, first_name';
	if ( $coordinators['query'] = mysql_query($coordinators['sql']) and mysql_num_rows($coordinators['query']) > 0 ) {
		$coordinators['results'] = mysql_fetch_assoc($coordinators['query']);
		do {
			$coordinators['html'] .= "<span style=\"font-variant:small-caps\">{$coordinators['results']['last_name']}</span> {$coordinators['results']['first_name']}<br />";
		} while ( $coordinators['results'] = mysql_fetch_assoc($coordinators['query']) );
		$coordinators['html'] = substr($coordinators['html'],0,-6);
	} else {
		$coordinators['html'] = 'N/A';
	}
?>
				<td class="nowrap"><?php print $coordinators['html'] ?></td>
				<td><?php print $info['semesters'][$courses['results']['semester']] ?></td>
				<td align="right"><?php print money_format('%(#1n',$courses['results']['price']) ?></td>
				<td><a href="/students/administrate/edit_course.php?id=<?php print $_GET['id'] ?>&amp;cid=<?php print $courses['results']['id'] ?>" class="char_button">&Delta;</a></td>
<?php
			$purchase_exist['sql'] = 'SELECT id FROM purchases WHERE course_id = '.$courses['results']['id'].' AND coordinator = false LIMIT 1';
			if ($purchase_exist['query'] = mysql_query($purchase_exist['sql']) and mysql_num_rows($purchase_exist['query']) == 0) {
?>
				<td><a href="<?php print $_SERVER['PHP_SELF'] ?>?id=<?php print $_GET['id'] ?>&amp;delete=<?php print $courses['results']['id'] ?>" class="char_button" onclick="confirmation('<?php print $courses['results']['id'] ?>','Are you sure you want to delete <?php print $courses['results']['prog_abbrev'] ?><?php print $courses['results']['course_code'] ?>: <?php print $courses['results']['course_name'] ?>?\nThis cannot be undone.')">&minus;</a></td>
<?php 		} else { ?>
				<td>&nbsp;</td>
<?php 		} ?>
			</tr>
<?php	
		} while ( $courses['results'] = mysql_fetch_assoc($courses['query']) );
	} else { ?>
			<tr>
				<td colspan="7" style="padding:25px;text-align:center;">There are currently no courses to display.<br />Would you like to <a href="/students/administrate/edit_course.php?id=<?php print $_GET['id'] ?>">add a course</a>?</td>
			</tr>
<?php } ?>
		</tbody>
	</table>
</div>
<?php
	$previous_courses['sql'] = '	SELECT	id, prog_abbrev, course_code, course_name, semester, year, price, sold, (price*sold - writer_salary*total_lectures - printing_cost) AS net_profit
									FROM	courses
									WHERE	association_id = '.$_GET['id'].' AND
											year < "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'"
									ORDER BY year ASC, semester ASC, prog_abbrev ASC, course_code ASC';
	$previous_courses['query'] = mysql_query($previous_courses['sql']) or die(mysql_error($courses['query']));
	if (mysql_num_rows($previous_courses['query']) > 0) {
?>
<h2>Courses from previous years</h2>
<div class="psuedo_p">
	<p>To copy a course from a previous year to the current school year,
		click on the <img src="/i/icon_course_copy.png" alt="Copy" />. Please note that you can only copy a
		course if it does not already exist in the current school year.</p>
	<table summary="List of courses from previous years.">
		<thead>
			<tr>
				<th scope="col">Code</th>
				<th scope="col" style="width:100%">Name</th>
				<th scope="col">Year</th>
				<th scope="col" style="text-align:right">Sold</th>		
				<th scope="col" style="text-align:right">Price</th>
				<th scope="col" style="text-align:right">Net</th>
				<th scope="col">&nbsp;</th>
			</tr>
		</thead>
<?php
		ob_start();
?>
		<tbody>
<?php	while ($previous_courses['results'] = mysql_fetch_assoc($previous_courses['query'])) { ?>
			<tr>
				<td><?php print $previous_courses['results']['prog_abbrev'] ?><?php print $previous_courses['results']['course_code'] ?></td>
				<td align="left"><?php if ( $previous_courses['results']['online_only'] == 1 ) { ?><img src="/i/icon_online_only.png" /> <?php } ?><?php print $previous_courses['results']['course_name'] ?></td>
				<td class="nowrap"><?php print $previous_courses['results']['semester'] == 0 ? $previous_courses['results']['year'] : $previous_courses['results']['year']+1; ?> <?php print $info['semesters'][$previous_courses['results']['semester']] ?></td>
				<td align="right"><?php print $previous_courses['results']['sold'] ?></td>
				<td align="right"><?php print money_format('%(#1n',$previous_courses['results']['price']) ?></td>
				<td align="right"><?php print money_format('%(#1n',$previous_courses['results']['net_profit']); $total_profit += $previous_courses['results']['net_profit']; ?></td>
<?php
			$course_exist['sql'] = '	SELECT	id
										FROM	courses
										WHERE	prog_abbrev = "'.$previous_courses['results']['prog_abbrev'].'" AND
												course_code = "'.$previous_courses['results']['course_code'].'" AND
												course_name = "'.$previous_courses['results']['course_name'].'" AND
												semester = "'.$previous_courses['results']['semester'].'" AND
												year = "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'"
										LIMIT 1';
			if ($course_exist['query'] = mysql_query($course_exist['sql']) and mysql_num_rows($course_exist['query']) == 0) {
?>
				<td><a href="<?php print $_SERVER['PHP_SELF'] ?>?id=<?php print $_GET['id'] ?>&amp;duplicate=<?php print $previous_courses['results']['id'] ?>" class="char_button"><img src="/i/icon_course_copy.png" alt="Copy" /></a></td>
<?php 		} else { ?>
				<td>&nbsp;</td>
<?php 		} ?>
			</tr>
<?php	} ?>
		</tbody>
<?php	
		$tbody = ob_get_clean();
		ob_start();
		if ( mysql_num_rows($previous_courses['query']) > 1 ) {
?>
		<tfoot>
			<tr>
				<td colspan="5" align="right"><abbr title="Total"><strong>&sum;</strong></abbr></td>
				<td align="right"><?php print money_format('%(#1n',$total_profit) ?></td>
				<td>&nbsp;</td>
			</tr>
		</tfoot>
<?php
	 	} 
		$tfoot = ob_get_clean();
		print $tfoot;
		print $tbody;
?>
	</table>
</div>
<?php
	}
	$layout->output_footer();
}
?>
