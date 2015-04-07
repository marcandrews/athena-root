<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();

$courses['sql'] = 'SELECT * FROM courses WHERE association_id = '.$_SESSION['association']['association_id'].' AND semester = "'.current_semester().'" AND active = "1" ORDER BY prog_abbrev ASC, course_code ASC';
if ($courses['query'] = mysql_query($courses['sql']) and mysql_num_rows($courses['query']) > 0) {

	/* Validate input. */
	if ($_POST) {
		if (!preg_match('/\d{9}/',$_POST['mcgill_id']) or !preg_match('/\w+/',$_POST['last_name']) or !preg_match('/\w+/',$_POST['first_name'])) {
			$error['student'] = '<img src="/images/!.png" title="McGill ID, last name and first name are required before continuing." class="new_error" />';
		}
		if (!is_array($_POST['course_id'])) {
			$error['course'] = '<img src="/images/!.png" title="At least one NTC must be selected before continuing." class="new_error" />';
		}
		if ($error) {
			$step = 0;
		} else {
			if ($_POST['confirmed'] or $_POST['confirmed_another']) {
				$step = 2;
				$_POST['mcgill_id'] = htmlentities($_POST['mcgill_id']);
				$_POST['last_name'] = htmlentities($_POST['last_name']);
				$_POST['first_name'] = htmlentities($_POST['first_name']);
			}
			if ($_POST['submit_button']) {
				$step = 1;
				$_POST['mcgill_id'] = stripslashes(htmlentities($_POST['mcgill_id']));
				$_POST['last_name'] = stripslashes(htmlentities($_POST['last_name']));
				$_POST['first_name'] = stripslashes(htmlentities($_POST['first_name']));
			}
		}
	}

	/* Output. */
	switch ( $step) {
		case 2:

			/* Begin the query. */
			mysql_query('START TRANSACTION');
			
			/* Find or create the student. */
			$student['sql'] = 'SELECT id FROM students WHERE mcgill_id = "'.$_POST['mcgill_id'].'" LIMIT 1';
			if ($student['query'] = mysql_query($student['sql']) and mysql_num_rows($student['query']) == 1) {
				/* Student found. */
				$student['results'] = mysql_fetch_assoc($student['query']);
			} elseif ($student['query'] = mysql_query($student['sql']) and mysql_num_rows($student['query']) == 0) {				
				/* Create the student. */
				$students = '		INSERT	INTO students
											(mcgill_id, password, first_name, last_name, created)
									VALUES	("'.$_POST['mcgill_id'].'", "'.md5($_POST['mcgill_id']).'", "'.ucwords(trim($_POST['first_name'])).'", "'.ucwords(trim($_POST['last_name'])).'", NOW())';
				if (mysql_query($students)) {
					$student['results']['id'] = mysql_insert_id($info['sql_db_connect']);
				} else {
					$layout->redirector('Athena | Purchase Error', 'A problem occurred when attempted to add this student to Athena.<br /><br /><em>'.mysql_error().'</em>');
				}
				
				/* Add the student's validation bits. */
				$validations = '	INSERT	INTO students_validations
											(student_id, code, created)
									VALUES	("'.$student['results']['id'].'", "'.md5("{$student['results']['id']}_{$_POST['mcgill_id']}").'", NOW())';	
				if (!mysql_query($validations)) {
					$layout->redirector('Athena | Purchase Error', 'A problem occurred when attempted to add this student\'s validation bits to Athena.<br /><br /><em>'.mysql_error().'</em>');
				}
			} else {
				$layout->redirector('Athena | Purchase Error', 'A problem occurred when attempted to find this student.<br /><br /><em>'.mysql_error().'</em>');
			}
	
			/* Define purchases and pickups SQL. */
			$i=0; $j=0;
			while ( $courses['results'] = mysql_fetch_assoc($courses['query'])) {
				if (in_array($courses['results']['id'],$_POST['course_id'])) {
					$values_purchases .= '("'.$student['results']['id'].'", "'.$courses['results']['id'].'", NOW()),';
					$i++;
					$sets['sql'] =	'SELECT id FROM sets WHERE course_id = '.$courses['results']['id'].' AND distribution = "0" AND received != 0';
					if (is_array($_POST['pickups']) and $sets['query'] = mysql_query($sets['sql']) and mysql_num_rows($sets['query']) > 0) {
						while ( $sets['results'] = mysql_fetch_assoc($sets['query'])) {
							if (isset($_POST['pickups'][$sets['results']['id']])) {
								$values_pickup .= '((SELECT p.id FROM purchases AS p WHERE p.course_id = '.$courses['results']['id'].' AND p.student_id = '.$student['results']['id'].'), "'.$sets['results']['id'].'", NOW()),';
								$j++;
							}
						}
					}
				}
			}
			$purchases = 'INSERT INTO purchases (student_id, course_id, date) VALUES'.substr($values_purchases,0,-1).' ON DUPLICATE KEY UPDATE student_id = '.$student['results']['id'];
			$pickups = 'REPLACE INTO pickups (purchase_id, set_id, date) VALUES '.substr($values_pickup,0,-1);

			/* Add purchases and pickups and, if successful, commit the updates to the database. */
			if ($i > 0 and mysql_query($purchases) and ( ( $j > 0 and mysql_query($pickups) ) or $j == 0 ) and mysql_query('COMMIT')) {
				if ($_POST['confirmed_another']) {
					$layout->redirector('Purchase Successful ...', 'Athena has been updated. Now redirecting you to ...', $_SERVER['PHP_SELF']);
				} else {
					$layout->redirector('Purchase Successful ...', 'Athena has been updated. Now redirecting you to ...', '/associations/');
				}
			} elseif ($i == 0) {
				$layout->redirector('Athena | Purchase Error', 'There are no purchases to add to Athena. This student may have purchased the specified NTCs already; however, if this is not the case, you may want to <a href="'.$_SERVER['PHP_SELF'].'">try again</a>.');
			} else {
				$layout->redirector('Athena | Purchase Error', 'A problem occurred when attempting to add this student\'s purchases to Athena.<br /><br /><em>'.mysql_error().'</em>');
			}
			break;
		case 1:
			$layout->output_header('Purchases: Confirm', 'association');
?> 
<ul id="breadcrumb">
	<li>Purchases: Confirm</li>
	<li> &laquo; <a href="/associations/purchases.php">Purchases</a></li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Purchases: Confirm</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF']; ?>">
	<fieldset>
		<fieldset class="psuedo_p">
			<p>Confirm that this student:</p>
			<fieldset>
			<table>
				<tr>
					<td width="60">McGill ID<br />
						<input type="hidden" name="mcgill_id" value="<?php print $_POST['mcgill_id'] ?>" />
						<strong><?php print $_POST['mcgill_id'] ?></strong></td>
					<td width="130">Last Name<br />
						<input type="hidden" name="last_name" value="<?php print $_POST['last_name'] ?>" />
						<strong><?php print $_POST['last_name'] ?></strong></td>
					<td>First Name<br />
						<input type="hidden" name="first_name" value="<?php print $_POST['first_name'] ?>" />
						<strong><?php print $_POST['first_name'] ?></strong></td>
				</tr>
			</table>
			</fieldset>
			<p><br />&hellip; will be purchasing:</p>
			<fieldset id="purchase_list">
				<table>
					<col />
					<col />
					<col class="col_pickups" />
					<col class="col_price" />
					<thead>
						<tr>
							<th>Course</th>
							<th>Name</th>
							<th class="col_pickups">Set Pickups</th>
							<th class="col_price">Price</th>
						</tr>
					</thead>
<?php 		ob_start(); ?>
					<tbody>
<?php
			$i = 0;
			while ( $courses['results'] = mysql_fetch_assoc($courses['query'])) {
				if (in_array($courses['results']['id'], $_POST['course_id'])) {
?>
						<tr>
							<td><?php print $courses['results']['prog_abbrev'].$courses['results']['course_code'] ?></td>
							<td nowrap="nowrap"><?php print $courses['results']['course_name'] ?></td>
							<td class="col_pickups pickups">
<?php
					$sets['sql'] =	'	SELECT		n.id, n.num, u.id AS uid, u.date
										FROM		sets AS n
										LEFT JOIN	pickups AS u
										ON			n.id = u.set_id AND u.purchase_id = (SELECT p.id FROM purchases AS p, students AS s WHERE p.course_id = '.$courses['results']['id'].' AND p.student_id = s.id AND s.mcgill_id = "'.$_POST['mcgill_id'].'")
										WHERE		n.course_id = '.$courses['results']['id'].' AND
													n.distribution = "0" AND
													n.received != 0
										ORDER BY	n.num';
					if ($sets['query'] = mysql_query($sets['sql']) and mysql_num_rows($sets['query']) > 0) {
						while ( $sets['results'] = mysql_fetch_assoc($sets['query'])) {
							if ($sets['results']['uid'] != NULL )
								$p['html'] = ' checked="checked" disabled="disabled" title="This set was picked up on '.date('F jS, Y \a\t g:i a',strtotime($sets['results']['date'])).'"';
?>
								<label><?php printf('%02d',$sets['results']['num']) ?> <input name="pickups[<?php print $sets['results']['id']; ?>]" type="checkbox" value="<?php print $sets['results']['uid'] ?>"<?php print $p['html']; ?> /></label>
<?php
								unset($p['html']);
						}
					} else {
						print 'N/A';
					}
?>
							</td>
							<td class="col_price"><input type="hidden" name="course_id[]" value="<?php print $courses['results']['id'] ?>" /><strong>$<?php print $courses['results']['price'] ?></strong></td>
						</tr>
<?php
					$total += $courses['results']['price'];
				}
			}
?>
					</tbody>
<?php		
			$tbody = ob_get_clean();
			if ($i > 1) {
				ob_start();			
?>
					<tfoot>
						<tr>
							<td colspan="4" align="right"><strong>Total: $<?php print number_format($total,2) ?></strong></td>
						</tr>
					</tfoot>
<?php
				$tfoot = ob_get_clean();
				print $tfoot;
			}
			print $tbody;
?>
				</table>
			</fieldset>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="button" class="back" value="&lsaquo; Start over" onclick="location.href='<?php print $_SERVER['PHP_SELF']; ?>'" />
			<input type="submit" class="forward" name="confirmed" value="Confirm &rsaquo;" />
			<input type="submit" class="forward" name="confirmed_another" value="Confirm, and do another &raquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
			$layout->output_footer();
			break;
		default:
			$layout->output_header('Purchases', 'association');
?> 
<script type="text/javascript" src="/js/purchases.js"></script>
<ul id="breadcrumb">
	<li>Purchases</li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Purchases</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF']; ?>#content" class="disable_form_history"> 
<?php			general_error($error); ?>
	<fieldset>
		<fieldset class="psuedo_p">
			<p>To add NTC purchases to Athena, start by entering a McGill ID.</p>
			<fieldset class="student_selector">
				<table>
					<tr>
						<td class="mcgill_id"><label>McGill ID<br />
							<input type="text" name="mcgill_id" maxlength="9" class="text<?php if ($error['student']) { print ' error'; } ?>" value="<?php print $_POST['mcgill_id'] ?>" onkeyup="getStudent(event)" /></label></td>
						<td class="last_name"><label>Last Name<br />
							<input type="text" name="last_name" class="text<?php if ($error['student']) { print ' error'; } ?>" readonly="readonly" value="<?php print $_POST['last_name'] ?>" onkeyup="validate()" /></label></td>
						<td class="first_name"><label>First Name<br />
							<input type="text" name="first_name" class="text<?php if ($error['student']) { print ' error'; } ?>" readonly="readonly" value="<?php print $_POST['first_name'] ?>" onkeyup="validate()" /> <span class="char_button" onclick="full_reset()" title="Restart search">&times;</span><?php print $error['student'] ?></label></td>
					</tr>
				</table>
			</fieldset>
			<p id="status" style="display:none;"></p>
			<fieldset id="purchase_list" style="display:none;">
				<table>
					<col />
					<col class="col_name" />
					<col class="col_coordinators" />
					<col class="col_price" />
					<col class="col_buy" />
					<thead>
						<tr>
							<th>Course</th>
							<th class="col_name">Name</th>
							<th class="col_coordinators">Coordinator(s)</th>
							<th class="col_price">Price</th>
							<th class="col_buy"<?php if ($error['course']) { print ' class="buy_error"'; } ?>>Buy?</th>
						</tr>
					</thead>
<?php 			if ($error['course']) { ?>
					<tfoot style="border:none;">
						<tr>
							<td colspan="4">&nbsp;</td>
							<td class="buy_error"><?php print $error['course'] ?></td>
						</tr>
					</tfoot>
<?php			} ?>
					<tbody>
<?php
				while ( $courses['results'] = mysql_fetch_assoc($courses['query'])) {
					unset($coordinators);
					$coordinators['sql'] = 'SELECT s.id, s.last_name, s.first_name FROM students AS s, purchases AS p WHERE s.id = p.student_id AND p.course_id = '.$courses['results']['id'].' AND p.coordinator = true ORDER BY last_name, first_name';
					if ($coordinators['query'] = mysql_query($coordinators['sql']) and mysql_num_rows($coordinators['query']) > 0) {
						while ( $coordinators['results'] = mysql_fetch_assoc($coordinators['query'])) {
							$coordinators['html'] .= '<span class="small-caps">'.$coordinators['results']['last_name'].'</span> '.$coordinators['results']['first_name'].'<br />';
						}
						$coordinators['html'] = substr($coordinators['html'],0,-6);
					} else {
						$coordinators['html'] = '&infin;';
					}
?>
						<tr>
							<td><?php print $courses['results']['prog_abbrev'].$courses['results']['course_code'] ?></td>
							<td><?php print $courses['results']['course_name'] ?></td>
							<td class="col_coordinators"><?php print $coordinators['html'] ?></td>
							<td class="col_price">$<?php print $courses['results']['price']; ?></td>
							<td class="col_buy"<?php if ($error['course']) { print ' class="buy_error"'; } ?>><input type="checkbox" name="course_id[]" id="cid_<?php print $courses['results']['id']; ?>" disabled="disabled" value="<?php print $courses['results']['id']; ?>" onclick="validate()" /></td>
						</tr>
<?php
				}
?>
					</tbody>
				</table>
			</fieldset>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls"> 
			<input type="button" class="back" value="&lsaquo; Cancel" onclick="location.href='/associations/'" />
			<input type="submit" class="forward disabled" name="submit_button" value="Next &rsaquo;" disabled="disabled" />
		</fieldset>
	</fieldset>
</form>
<?php
			$layout->output_footer();
		break;
	}
} elseif (mysql_num_rows($courses['query']) == 0) {
	$layout->redirector('No Courses Available', 'Currently, there are no courses available for purchase. To add a course, please consult your student association\'s Athena administrator.');
} else {
	$layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
}



?>