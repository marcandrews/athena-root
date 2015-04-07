<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();

$v[false] = 'checked="checked" ';
$v['display'] = 'display:none; ';
$vf[false] = 'checked="checked" ';

if ( $_POST && is_numeric($_GET['id']) ) {

	/* Validate the form. */

	if ( $_POST['protected'] != 1 ) {
		if ( $_POST['validated'] == 1 ) {
			if ( $_POST['first_name'] == '' ) {
				$error['first_name'] = error_inline('A first name is required.');
			}
			if ( $_POST['last_name'] == '' ) {
				$error['last_name'] = error_inline('A last name is required.');
			}
			if ( $_POST['email_alt'] != '' && !preg_match('/^\w+([-+.]\w+)*@(\w+([-+.]\w+)*\.)+\w{2,6}$/i', $_POST['email_alt']) ) {
				$error['email_alt'] = error_inline('A valid email address is required.');
			}
		} else {
			if ( !preg_match('/\d{9}/', $_POST['mcgill_id']) ) {
				$error['mcgill_id'] = error_inline('A valid 9-digit McGill ID is required.');
			}
			if ( $_POST['first_name'] == '' ) {
				$error['first_name'] = error_inline('A first name is required.');
			}
			if ( $_POST['last_name'] == '' ) {
				$error['last_name'] = error_inline('A last name is required.');
			}
			if ( $_POST['validate'] == 1 ) {
				$v['display'] = '';
				$v[false] = '';
				$v[true] = 'checked="checked" ';
				if ( !preg_match('/^\w+([-+.]\w+)*@(\w+([-+.]\w+)*\.)*mcgill\.ca$/i', $_POST['email']) ) {
					$error['email'] = error_inline('A valid McGill email address, ending with &quot;mcgill.ca&quot;, is required.');
				}
				if ( $_POST['email_alt'] != '' and !preg_match('/^\w+([-+.]\w+)*@(\w+([-+.]\w+)*\.)+\w{2,6}$/i', $_POST['email_alt']) ) {
					$error['email_alt'] = error_inline('Invalid email address.');
				}
				if ( !preg_match('/[A-z0-9]{6,9}/', $_POST['pw']) ) {
					$error['pw'] = error_inline('A password, 6-9 characters in length, is required.');
				}
				if ( $_POST['pw'] != $_POST['pw_confirm'] ) {
					$error['pw_confirm'] = error_inline('The passwords must match.');
				}
				if ( $_POST['force_validation'] == 1 ) {
					$vf[false] = '';
					$vf[true] = 'checked="checked" ';
				}
			}
		}
	}
	if ( $_POST['delete'] ) {
		foreach ( $_POST['delete'] as $key => $value ) {
			$html['pickups_display'][$key] = ' style="display:none;"';
		}
	}
	$_POST['modified'] = 0;
	if ( $error ) {
		$step = 1;
		$students['results'] = $_POST;
	} else {
		$step = 2;
		if ( $_POST['delete'] ) {
			foreach ( $_POST['delete'] as $key => $value ) {
				unset($_POST['pickups'][$key]);
			}
		}
	}
} elseif ( is_numeric($_GET['id']) and is_numeric($_GET['delete']) ) {

	/* Delete a duplicate student. */
	
	$cc['sql'] = 'SELECT id FROM purchases WHERE student_id = '.$_GET['delete'].' AND coordinator = true';
	if ( $cc['query'] = mysql_query($cc['sql'], $info['sql_db_connect']) and mysql_num_rows($cc['query']) == 0 ) {
		$students['sql'] = '	DELETE	s FROM associations AS d, courses AS c, purchases AS p, students AS s
								WHERE	d.id = '.$_SESSION['association']['association_id'].' AND
										d.id = c.association_id AND
										c.id = p.course_id AND
										p.student_id = s.id AND
										s.id = '.$_GET['delete'];
		if ( $students['query'] = mysql_query($students['sql'],$info['sql_db_connect']) ) {
			$layout->redirector('Successful ...', 'This duplicate student has been successfully deleted. Now redirecting you to ...', $_SERVER['PHP_SELF'].'?id='.$_GET['id']);
		} else {
			$layout->redirector('Error', 'There was an error:<br />' . mysql_error());
		}
	} elseif ( $cc['query'] = mysql_query($cc['sql'], $info['sql_db_connect']) and mysql_num_rows($cc['query']) > 0 ) {
		$cc['results'] = mysql_fetch_assoc($cc['query']);
		$i = mysql_num_rows($cc['query']);
		do {
			$j++;
			$html['courses'] .= '<strong><abbr title="'.$cc['results']['course_name'].'">'.$cc['results']['prog_abbrev'].$cc['results']['course_code'].'</abbr> (<a href="/contact.php?id='.$cc['results']['id'].'" target="_blank" title="Contact the '.$cc['results']['sa'].'">'.$cc['results']['sa_abbrev'].'</a>)</strong>'.( $i-$j>1 ? ', ' : '').( $i-$j==1 ? ' and ' : '');
		} while ( $cc['results'] = mysql_fetch_assoc($cc['query']) );
		$layout->redirector('Error', 'This duplicate student, <strong>'.mysql_result($cc['query'],0,6).' '.mysql_result($cc['query'],0,7).' ('.mysql_result($cc['query'],0,8).')</strong>, is the coordinator of '.( $i > 1 ? 'the following courses: ' : '' ).$html['courses'].'. Please contact the appropriate student association to have '.( $i > 1 ? 'new coordinators' : 'a new coordinator' ).' selected before attempted to delete this duplicate student.');
	}

} elseif ( is_numeric($_GET['id']) ) {

	// Get information on a single student.
	$students['sql'] =	'	SELECT		DISTINCT s.id, s.mcgill_id, s.first_name, s.last_name, s.email, s.email_alt, s.validated, s.protected, s.modified, s.created,
										v.student_id, v.email AS v_email, v.email_alt AS v_email_alt
							FROM		associations AS d, courses AS c, purchases AS p, students AS s
							LEFT JOIN	students_validations AS v ON v.student_id = s.id
							WHERE		s.id = '.$_GET['id'].' AND
										d.id = '.$_SESSION['association']['association_id'].' AND
										((d.id = c.association_id AND c.id = p.course_id AND s.id = p.student_id) OR s.id = d.administrator)
							LIMIT		1';
	$students['query'] = mysql_query($students['sql']) or die(mysql_error());
	if (mysql_num_rows($students['query']) == 1) {
		$students['results'] = mysql_fetch_assoc($students['query']);
		// Check this student's validation bits
		if ($students['results']['validated'] == 0 and $students['results']['student_id'] == NULL) {
			$validations['sql'] = '	INSERT	INTO students_validations
											(student_id, code, created)
									VALUES	("'.$students['results']['id'].'", "'.md5("{$student['results']['id']}_{$_POST['mcgill_id']}").'", "'.$students['results']['created'].'")';
			if (!mysql_query($validations['sql'])) {
				$layout->redirector('Athena | Edit Student Error', 'A problem occurred when attempting to correct this student\'s validation bits.');
			}
		}
		$step = 1;
	} else {
		header('Location: /associations/edit_students.php#content');
	}
} else {

	// Get the list of students.
	$students['sql'] =	'	(
								SELECT	s.id AS id, s.mcgill_id, s.first_name, s.last_name, s.validated, s.created
								FROM	associations AS d, courses AS c, purchases AS p, students AS s
								WHERE	d.id = '.$_SESSION['association']['association_id'].'
										AND d.id = c.association_id
										AND c.id = p.course_id
										AND p.student_id = s.id
							) UNION (
								SELECT	s.id AS id, s.mcgill_id, s.first_name, s.last_name, s.validated, s.created
								FROM	associations AS d, students AS s
								WHERE	d.id = '.$_SESSION['association']['association_id'].'
										AND d.administrator = s.id
							)
							ORDER BY last_name ASC, first_name ASC, mcgill_id ASC';
	$students['query'] = mysql_query($students['sql']) or die(mysql_error());
	$step = 0;
}

switch ( $step ) {
	case 2:

		// Begin the query.
		mysql_query('START TRANSACTION');

		// Update the students and validation tables.
		if ($_POST['protected'] != 1) {
			if ($_POST['validated'] == 1) {
				$sql['update'] = "	UPDATE	students
									SET		first_name = '{$_POST['first_name']}',
											last_name = '{$_POST['last_name']}',
											email_alt = '{$_POST['email_alt']}',
											modified = NOW()
									WHERE	id = {$_GET['id']}";
			} else {
				$sql['update'] = "	UPDATE	students AS s, students_validations AS v
									SET		s.mcgill_id = '{$_POST['mcgill_id']}',
											s.first_name = '{$_POST['first_name']}',
											s.last_name = '{$_POST['last_name']}',
											s.password = md5('{$_POST['mcgill_id']}'),
											s.modified = NOW(),
											v.code = md5('{$_GET['id']}_{$_POST['mcgill_id']}')
									WHERE	s.id = {$_GET['id']} AND
											s.id = v.student_id";
				if ($_POST['validate'] == 1) {
					$sql['update'] = "	UPDATE	students AS s, students_validations AS v
										SET		s.mcgill_id = '{$_POST['mcgill_id']}',
												s.first_name = '{$_POST['first_name']}',
												s.last_name = '{$_POST['last_name']}',
												s.validated = '0',
												s.modified = NOW(),
												v.code = md5('{$_GET['id']}_{$_POST['mcgill_id']}'),
												v.email = '{$_POST['email']}',
												v.email_alt = '{$_POST['email_alt']}',
												v.password = md5('{$_POST['pw']}')
										WHERE	s.id = {$_GET['id']} AND
												s.id = v.student_id";
					if ($_POST['force_validation'] != 1) {
						send_validation_email ($_GET['id'], $_POST['mcgill_id'], $_POST['first_name'], $_POST['last_name'], $_POST['email']);
					} else {
						$sql['update'] = "	UPDATE	students
											SET		mcgill_id = '{$_POST['mcgill_id']}',
													first_name = '{$_POST['first_name']}',
													last_name = '{$_POST['last_name']}',
													email = '{$_POST['email']}',
													password = md5('{$_POST['pw']}'),
													validated = '1',
													modified = NOW()
											WHERE	id = {$_GET['id']}";
						$sql['delete'] = "	DELETE FROM students_validations WHERE student_id = {$_GET['id']}";
					}
				}
			}
			if (!mysql_query($sql['update']) or (isset($sql['delete']) and !mysql_query($sql['delete']))) {
				if (mysql_errno() == 1062) {
					$layout->redirector('Athena | Edit Student Error', 'A problem occurred when attempting to edit this student. Given these edits, this student appears to be duplicated in the database.');
				} else {
					$layout->redirector('Athena | Edit Student Error', 'A problem occurred when attempting to edit this student.');
				}
			}
		}
		
		// Clear this student's set pickups.
		$sql['delete_pickups'] = '	DELETE	u
									FROM	associations AS d, courses AS c, purchases AS p, pickups AS u
									WHERE	d.id = '.$_SESSION['association']['association_id'].' AND
											d.id = c.association_id AND
											c.id = p.course_id AND
											p.student_id = '.$_GET['id'].' AND
											p.id = u.purchase_id';
		if (!mysql_query($sql['delete_pickups'])) {
			$layout->redirector('Athena | Edit Student Error', 'A problem occurred when attempting to edit this student\'s set pickups.');
		}

		// Add this student's set pickups.
		if (count($_POST['pickups'])) {
			$sql['insert_pickups'] = 'INSERT INTO pickups (purchase_id, set_id, date) VALUES ';
			foreach ($_POST['pickups'] as $pid => $p_values) {
				foreach ($p_values as $nid => $uid) {
					$sql['insert_pickups'] .= '('.$pid.', '.$nid.', NOW()), ';
				}
			}
			$sql['insert_pickups'] = substr($sql['insert_pickups'], 0, -2);
			if (!mysql_query($sql['insert_pickups'])) {
				$layout->redirector('Athena | Edit Student Error', 'A problem occurred when attempting to edit this student\'s set pickups.<br /><br /><em>'.mysql_error().'</em>');
			}
		}

		if ($_POST['delete']) {
			foreach ($_POST['delete'] as $cid => $pid) {
				$sql['delete_purchase'] = '	DELETE	p
											FROM	associations AS d, courses AS c, purchases AS p
											WHERE	d.id = '.$_SESSION['association']['association_id'].' AND
													d.id = c.association_id AND
													c.id = p.course_id AND
													p.course_id = '.$cid.' AND
													p.id = '.$pid.' AND
													p.student_id = '.$_GET['id'];
				if (!mysql_query($sql['delete_purchase'])) {
					$layout->redirector('Athena | Edit Student Error', 'A problem occurred when attempted to remove this student\'s purchase(s).<br /><br /><em>'.mysql_error().'</em>');
				}			
			}
		}

		if (mysql_query('COMMIT')) {
			$layout->redirector('Successful ...', 'This student has been successfully updated. Now redirecting you to ...', '/associations/');
		} else {
			$layout->redirector('Athena | Edit Student Error', 'A problem occurred when attempted to confirm the edits to this student.<br /><br /><em>'.mysql_error().'</em>');
		}
		break;
	case 1:
		$full_name = $students['results']['first_name'].' '.$students['results']['last_name'];
		$layout->output_header('Edit '.$full_name, 'association');
?>
<script type="text/javascript">
//<![CDATA[
function confirm_delete(pid,checkbox){
	if (checkbox.checked==true){
		q = confirm('Are you sure you want to delete this purchase?');
		if (q==true){
			$("#pid_"+pid+" input").attr("disabled","disabled");
			return true;
		} else {
			return false;
		}
	} else {
		$("#pid_"+pid+" input").attr("disabled","");
	}
}
//]]>
</script>
<ul id="breadcrumb">
	<li>Edit <?php print $full_name ?></li>
	<li> &laquo; <a href="/associations/edit_students.php">Edit Students</a></li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Edit <?php print $full_name ?></h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF'].'?id='.$_GET['id']; ?>" class="disable_form_history">
<?php 	general_error($error); ?>
	<fieldset>
		<input type="hidden" name="validated" value="<?php print $students['results']['validated'] ?>" />
		<input type="hidden" name="protected" value="<?php print $students['results']['protected'] ?>" />
		<fieldset class="psuedo_p top-down">
			<ul>
<?php 	if ( $students['results']['protected'] == '1' ) { ?>
				<li>
					<label>McGill ID</label>
					<strong><?php print $students['results']['mcgill_id']; ?></strong>
				</li>
				<li>
					<label>First name</label>
					<strong><?php print $students['results']['first_name']; ?></strong>
				</li>
				<li>
					<label>Last name</label>
					<strong><?php print $students['results']['last_name']; ?></strong>
				</li>
				<li>
					<label>Validation</label>		
					<strong>This student is validated.</strong>
				</li>
				<li>
					<label>McGill Email</label>
					<strong><?php print $students['results']['email']; ?></strong>
				</li>
				<li>
					<label>Alternative Email</label>
					<strong><?php $students['results']['email_alt'] == '' ? print 'None' : print $students['results']['email_alt']; ?></strong>
				</li>
<?php 	} else {?>
				<li>
					<label>McGill ID</label>
<?php		if ( $students['results']['validated'] == '1' ) { ?>
					<input type="hidden" name="mcgill_id" value="<?php print $students['results']['mcgill_id']; ?>" />
						<strong><?php print $students['results']['mcgill_id']; ?></strong>
<?php		} else { ?>
					<input name="mcgill_id" type="text" size="9" maxlength="9" value="<?php print $students['results']['mcgill_id']; ?>" class="text<?php if ( $error['mcgill_id'] ) print ' error'; ?>" /><?php print $error['mcgill_id'] ?>
<?php		} ?>
				</li>
				<li>
					<label>First name</label>
					<input name="first_name" type="text" size="15" value="<?php print $students['results']['first_name']; ?>" class="text<?php if ( $error['first_name'] ) print ' error'; ?>" /><?php print $error['first_name'] ?>
				</li>
				<li>
					<label>Last name</label>
					<input name="last_name" type="text" size="15" value="<?php print $students['results']['last_name']; ?>" class="text<?php if ( $error['last_name'] ) print ' error'; ?>" /><?php print $error['last_name'] ?>
				</li>
<?php		if ( $students['results']['validated'] == '1' ) { ?>
				<li>
					<label>Validation</label>		
					<strong>This student is validated.</strong>
				</li>
				<li>
					<label>McGill Email</label>
					<input type="hidden" name="email" value="<?php print $students['results']['email']; ?>" />
					<strong><?php print $students['results']['email']; ?></strong>
				</li>
				<li>
					<label>Alternative Email</label>
					<input name="email_alt" type="text" size="30" value="<?php print $students['results']['email_alt']; ?>" class="text<?php if ( $error['email_alt'] ) print ' error'; ?>" /><?php print $error['email_alt'] ?>
				</li>
<?php		} else { ?>
				<li>
					<label>Validation</label>		
					This student is <strong>not validated</strong>.
					<fieldset class="list">
						Would you like to validate this student?&nbsp;&nbsp;&nbsp;
						<label>Yes <input name="validate" type="radio" value="1"<?php print $v[true] ?> onclick="$('#validate').show('normal');" /></label>
						<label>No <input name="validate" type="radio" value="0"<?php print $v[false] ?> onclick="$('#validate').hide('normal');" /></label>
					</fieldset>
				</li>
				<li id="validate" style="<?print $v['display'] ?>">
					<fieldset class="large">
						<ul>
							<li>
								<label>McGill Email</label>
								<input name="email" type="text" size="30" value="<?php print $students['results']['email']; ?>" class="text<?php if ( $error['email'] ) print ' error'; ?>" /><?php print $error['email'] ?>
							</li>
							<li>
								<label>Alternative Email</label>
								<input name="email_alt" type="text" size="30" value="<?php print $students['results']['email_alt']; ?>" class="text<?php if ( $error['email_alt'] ) print ' error'; ?>" /><?php print $error['email_alt'] ?>
							</li>
							<li>
								<label>Password</label>		
								<input name="pw" type="password" size="9" maxlength="9" value="<?php print $_POST['pw'] ?>" class="text<?php if ( $error['pw'] ) print ' error'; ?>" /><?php print $error['pw'] ?>
							</li>
							<li>
								<label>Confirm Password</label>		
								<input name="pw_confirm" type="password" size="9" maxlength="9" value="<?php print $_POST['pw_confirm'] ?>" class="text<?php if ( $error['pw_confirm'] ) print ' error'; ?>" /><?php print $error['pw_confirm'] ?>
							</li>
							<li>
								<label>Force this validation?</label>
								<fieldset class="list">
									<label>Yes <input name="force_validation" type="radio" value="1"<?php print $vf[true] ?> /></label>
									<label>No (recommended) <input name="force_validation" type="radio" value="0"<?php print $vf[false] ?> /></label><br />
									<em>Only force this validation if you are certain that this student's McGill ID is correct.</em>
								</fieldset>
							</li>
						</ul>
					</fieldset>
				</li>
<?php
			}
		}
		if ( $students['results']['modified'] != 0 ) {
?>
				<li>
					<label>Last modified</label>
					<input type="hidden" name="modified" value="<?php print $students['results']['modified'] ?>" />
					<strong><?php print date('Y/m/d @ H:i',strtotime($students['results']['modified'])) ?></strong>
				</li>
<?php	} ?>
				<li>
					<label>Created</label>
					<input type="hidden" name="created" value="<?php print $students['results']['created']; ?>" />
					<strong><?php print date('Y/m/d @ H:i',strtotime($students['results']['created'])); ?></strong>
				</li>
			</ul>
		</fieldset>
<?php
		$purchases['sql'] =	'	SELECT		p.id AS pid, p.date,
											c.id AS cid, c.prog_abbrev, c.course_code, c.price, c.semester
								FROM		associations AS d, courses AS c, purchases AS p
								WHERE		d.id = c.association_id AND
											c.association_id = '.$_SESSION['association']['association_id'].' AND
											c.id = p.course_id AND
											p.student_id = '.$_GET['id'].' AND
											p.coordinator = false
								ORDER BY	c.prog_abbrev ASC, c.course_code ASC';
		if ( $purchases['query'] = mysql_query($purchases['sql'], $info['sql_db_connect']) and mysql_num_rows($purchases['query']) > 0 ) { ?>
		<h2>Purchases &amp; Pickups</h2>
		<fieldset class="psuedo_p">
			<p>This is the student's purchases and pickups for this year.</p>
			<table style="border-collapse:collapse;">
				<col />
				<col />
				<col />
				<col style="width:100%;" />
				<col />
				<thead>
					<tr>
						<th>Course</th>
						<th style="text-align:right;">Price</th>
						<th>Date Purchased</th>
						<th>Set Pickups</th>
						<th>Delete?</th>
					</tr>
				</thead>
				<tbody>
<?php		while ( $purchases['results'] = mysql_fetch_assoc($purchases['query']) ) {
				$i++;
				$total += $purchases['results']['price']; ?>
					<tr<?php if ( $i % 2 == 0 ) print ' class="odd"'; ?>>
						<td><strong><?php print $purchases['results']['prog_abbrev'].$purchases['results']['course_code']; ?></strong></td>
						<td align="right">$<?php print number_format($purchases['results']['price'],2); ?></td>
						<td nowrap="nowrap"><?php print date('Y/m/d @ H:i',strtotime($purchases['results']['date'])); ?></td>
						<td id="pid_<?php print $purchases['results']['pid'] ?>" class="pickups">
<?php
					$sets['sql'] =	'	SELECT		n.id, n.num, u.id AS uid, u.date
										FROM		(purchases AS p, sets AS n)
										LEFT JOIN	pickups AS u ON n.id = u.set_id AND u.purchase_id = p.id
										WHERE		p.student_id = '.$_GET['id'].' AND 
													p.course_id = '.$purchases['results']['cid'].' AND
													p.course_id = n.course_id AND
													n.distribution = "0" AND
													n.received != 0
										ORDER BY	n.num';
					if ( $sets['query'] = mysql_query($sets['sql'], $info['sql_db_connect']) and mysql_num_rows($sets['query']) > 0 ) {
						while ( $sets['results'] = mysql_fetch_assoc($sets['query']) ) {
							if ( $_POST and !$_POST['delete'][$purchases['results']['cid']] ) {
								if ( $_POST['pickups'][$purchases['results']['pid']][$sets['results']['id']] )
									$p['html'] = ' checked="checked"';
							} else {
								if ( $sets['results']['date'] != NULL )
									$p['html'] = ' checked="checked"'.' title="This set was picked up on '.date('F jS, Y \a\t g:i a',strtotime($sets['results']['date'])).'"';
							}
?>
							<label><?php printf('%02d',$sets['results']['num']) ?> <input type="checkbox" name="pickups[<?php print $purchases['results']['pid'] ?>][<?php print $sets['results']['id'] ?>]" value="<?php print $sets['results']['uid'] ?>"<?php print $p['html']; ?> /></label>
<?php
							unset($p['html']);
						}
					} else {
?>
							N/A
<?php
					}
?>
						</td>
						<td align="center"><?php if ( $_POST['delete'][$purchases['results']['cid']] ) { ?><script type="text/javascript">
//<![CDATA[
$("#pid_<?php print $purchases['results']['pid'] ?> input").attr("disabled","disabled");//]]> </script><?php } ?><input type="checkbox" name="delete[<?php print $purchases['results']['cid'] ?>]" value="<?php print $purchases['results']['pid'] ?>" <?php if ( $_POST['delete'][$purchases['results']['cid']] ) print 'checked="checked" ' ?>onclick="return confirm_delete(<?php print $purchases['results']['pid'] ?>,this);" /></td>
					</tr>
<?php
			}
			unset($i);
?>
				</tbody>
			</table>
		</fieldset>
<?php
		}
		if ($students['results']['validated'] == 1) {
			$duplicates['sql'] = 'SELECT * FROM students WHERE id != '.$_GET['id'].' AND validated = "0" AND ( mcgill_id = '.$students['results']['mcgill_id'].' OR first_name = "'.$students['results']['first_name'].'" OR last_name = "'.$students['results']['last_name'].'" )';
			$duplicates['query'] = mysql_query($duplicates['sql'], $info['sql_db_connect']) or print(mysql_error());
			if ( mysql_num_rows($duplicates['query']) > 0 ) {
?>
		<h2>Analysis of Possible Duplicates</h2>
		<fieldset class="psuedo_p">
			<p>This is a list of students that may be duplicates of <?php print $full_name ?>. The higher the Score, the more likely that the student is a duplicate of <?php print $full_name ?>; for instance, a score of 100 indicates a perfectly matching duplicate student. To remove the duplicate, click on the <strong>&times;</strong>.</p>
			<table>
				<thead>
					<tr>
						<th width="25%">McGill ID</th>
						<th width="25%">First Name</th>
						<th width="25%">Last Name</th>
						<th width="25%" style="text-align:center">Validated?</th>
						<th width="0%" align="right"><abbr title="The higher the score (100 being identical), the greater the likelihood that this student is duplicated.">Score</abbr></th>
						<td>&nbsp;</td>
					</tr>
				</thead>
				<tbody>
<?php
				$duplicates['results'] = mysql_fetch_assoc($duplicates['query']);
				do {
					foreach ( $duplicates['results'] as $key => $value ) {
						$i[$key] = similar_text(strtolower($students['results'][$key]),strtolower($value),&$p[$key]);
					}
?>
					<tr>
						<td><?php print $duplicates['results']['mcgill_id'].' ('.round($p['mcgill_id']).'%)' ?></td>
						<td><?php print $duplicates['results']['first_name'].' ('.round($p['first_name']).'%)' ?></td>
						<td><?php print $duplicates['results']['last_name'].' ('.round($p['last_name']).'%)' ?></td>
						<td align="center"><?php if ( $duplicates['results']['validated'] == 1 ) { print 'Yes'; } else { print 'No'; } ?></td>
						<td align="right"><?php print number_format(($p['mcgill_id']) * ($p['first_name']/100) * ($p['last_name']/100),2) ?></td>
						<td><a href="<?php print $_SERVER['PHP_SELF'] ?>?id=<?php print $_GET['id'] ?>&amp;delete=<?php print $duplicates['results']['id'] ?>" class="char_button" title="Delete this duplicate student?"  onclick="return confirm('Are you sure you want to delete this unvalidated duplicate of <?php print $students['results']['first_name'] ?> <?php print $students['results']['last_name'] ?> (<?php print $students['results']['mcgill_id'] ?>)?\nThis cannot be undone.')">&times;</a></td>
					</tr>
<?php
					unset($i,$p);
				} while ( $duplicates['results'] = mysql_fetch_assoc($duplicates['query']) );
?>
				</tbody>
			</table>
		</fieldset>
<?php
			}
		}
?>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="button" class="back" value="&lsaquo; Back" onclick="top.location.href='javascript:history.back(1)'" />
			<input type="submit" class="forward" value="Edit this student &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
		$layout->output_footer();
		break;
	default:
		if ( mysql_num_rows($students['query']) > 0 ) {
			$layout->output_header('Edit Students', 'association');
?>
<script type="text/javascript" src="/js/jqeury/webtoolkit.jscrollable.js"></script>
<script type="text/javascript">
//<![CDATA[

$(function(){
	$('table').Scrollable(350, 479);
	$('input[title]').each(function(){
		if (this.value == '') this.value = this.title;
		$(this).focus(function() { if (this.value == this.title && !this.readOnly) this.value = ''; }).blur(function() { if (this.value == '') this.value = this.title; });
	});
});

function filter (phrase, id, col) {
	var search_for = phrase.value.toLowerCase();
	var table = document.getElementById(id);
	var row;
	for (var r = 1; r < table.rows.length; r++){
		row = table.rows[r].cells[col].innerHTML.replace(/<[^>]+>/g,"");
		if (row.toLowerCase().indexOf(search_for)>=0 )
			table.rows[r].style.display = '';
		else table.rows[r].style.display = 'none';
	}
}
//]]>
</script>
<ul id="breadcrumb">
	<li>Edit Students</li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Edit Students</h1>
<p>Edit students who are associated with <?php print $_SESSION['association']['abbreviation'] ?>.</p>
<div id="edit_students_list" class="psuedo_p">
	<table id="student_list">
		<col class="col_name" />
		<col class="col_mcgill_id" />
		<col class="col_validated" />
		<col class="col_created" />
		<col />
		<thead>
			<tr>
				<th class="col_name"><input type="text" id="f_name" title="Name" onkeyup="filter(this,'student_list',0); $('#f_mcgill_id').val('McGill ID');" class="text disable_form_history" style="width:185px" /></th>
				<th class="col_mcgill_id"><input type="text" id="f_mcgill_id" title="McGill ID" maxlength="9" onkeyup="filter(this,'student_list',1); $('#f_name').val('Name');" class="text disable_form_history" style="width:63px" /></th>
				<th class="col_validated">Validated?</th>
				<th class="col_created">Created</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
<?php
			while ( $students['results'] = mysql_fetch_assoc($students['query']) ) {
?>
			<tr<?php if ($students['results']['validated'] == '0') { print ' class="unvalidated"'; } ?> onclick="window.location='edit_students.php?id=<?php print $students['results']['id'] ?>'">
				<td><strong><?php print $students['results']['last_name'] ?></strong>, <?php print $students['results']['first_name'] ?></td>
				<td><?php print $students['results']['mcgill_id'] ?></td>
				<td class="col_validated"><?php print ($students['results']['validated']) ? 'Yes' : 'No'; ?></td>
				<td><?php print date('Y/m/d',strtotime($students['results']['created'])) ?></td>
				<td><a href="edit_students.php?id=<?php print $students['results']['id'] ?>" title="Edit this student" style="font-weight:bold; text-decoration:none;">&Delta;</a></td>
			</tr>
<?php
			}
?>
		</tbody>
	</table>
	<div id="reminder">Please remind these students to validate their accounts.</div>
</div>
<?php
			$layout->output_footer();
		} else {
			$layout->redirector('No Students Available', 'There are no students who have purchased '.$_SESSION['association']['abbreviation'].' NTCs.');
		}
}
?>
