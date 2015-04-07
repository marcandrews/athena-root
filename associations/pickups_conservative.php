<?php
session_start();
header("Cache-control: private");

require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();


$courses['sql'] = 'SELECT * FROM courses WHERE association_id = '.$_SESSION['association']['association_id'].' AND semester <= "'.current_semester().'" AND active = "1" ORDER BY prog_abbrev ASC, course_code ASC';
if ( $courses['query'] = mysql_query($courses['sql'],$info['sql_db_connect']) and mysql_num_rows($courses['query']) > 0 ) {


if ( $_GET['id'] > 0 ) {
	$students['sql'] = "SELECT * FROM students WHERE id={$_GET['id']}"; 
	$students['query'] = mysql_query($students['sql'], $info['sql_db_connect']) or die(mysql_error());
	$students['results'] = mysql_fetch_assoc($students['query']);
	if ( mysql_num_rows($students['query']) == 1 ) {
		$_POST['step1'] = true;
		$_POST['mcgill_id'] = $students['results']['mcgill_id'];
		unset($students);
	}
}

if ( $_POST['step2'] ) {
	if ( is_array($_POST['pickup']) ) {
		$step = 2;
	} else {
		$step = 1;
		$error = true;
	}
} elseif ( $_POST['step1'] ) {
	if ( preg_match("/\d{9}/", $_POST['mcgill_id']) ) {
		$step = 1;
	} else {
		$step = 0;
		$error['mcgill_id'] =  error_inline('A valid 9-digit McGill ID is required.');
	}
}





switch ( $step ) {
	case 2:
		foreach ( $_POST['pickup'] as $pid => $p_values ) {
			foreach ($p_values as $nid) {
				$pickups['sql'] = "INSERT INTO pickups (purchase_id, set_id, date) VALUES ({$pid}, {$nid}, NOW())";
				$pickups['query'] = mysql_query($pickups['sql'], $info['sql_db_connect']);
				if ( mysql_affected_rows() == 1 ) {
					$success = true;
				} else {
					$success = false;
					$layout->redirector("Error.", "There was an error:<br />".$pickups['sql'] . mysql_error());
					exit;
				}
			}
		}
		if ( $success == true ) {
			if ( $_POST['step2'] == 'Confirm, and do another »' ) {
				$layout->redirector("Pickup Successful ...", "Athena has been updated. Now redirecting you to ...", "/associations/{$_SERVER['PHP_SELF']}");
			} else {
				$layout->redirector("Pickup Successful ...", "Athena has been updated. Now redirecting you to ...", "/associations/");
			}
			exit;
		}
		break;
	case 1:
		$students['sql'] = 'SELECT * FROM students WHERE mcgill_id = "'.$_POST['mcgill_id'].'"'; 
		$students['query'] = mysql_query($students['sql'], $info['sql_db_connect']);
		if ($students['query'] and mysql_num_rows($students['query']) == 1) {
			$students['results'] = mysql_fetch_assoc($students['query']);
			$layout->output_header('Pickups (One by One) for '.$students['results']['first_name'].' '.$students['results']['last_name'], 'association');
?> 
<ul id="breadcrumb">
	<li>Pickups (One by One) for <?php print $students['results']['first_name'].' '.$students['results']['last_name'] ?></li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Pickups (Student-by-Student) for <?php print $students['results']['first_name'].' '.$students['results']['last_name'] ?></h1> 
<form method="post" action="<?php print $_SERVER['PHP_SELF']; ?>#content">
<?php 		if ($error == true ) { ?>
	<p id="error_paragraph">Atleast one NTC set must be selected in order to continue.</p>
<?php 		} ?>
	<fieldset>
		<input name="student_id" type="hidden" value="<?php print $students['results']['id']; ?>" /> 
		<input name="mcgill_id" type="hidden" value="<?php print $students['results']['mcgill_id']; ?>" />
		<p>Please confirm that this is the student that is picking up NTCs and then select from the list of available NTC sets.<br /><br />
			Student ID: <strong><?php print $students['results']['mcgill_id'] ?></strong><br />
			Name: <strong><?php print "{$students['results']['first_name']} {$students['results']['last_name']}" ?></strong></p>
<?php
			$courses['sql'] = '	SELECT	c.id AS cid, c.association_id, c.prog_abbrev, c.course_code, c.course_name, c.semester,
										p.id AS pid, p.student_id, p.course_id,
										s.id as sid, s.mcgill_id
								FROM	courses AS c, purchases AS p, students AS s
								WHERE	c.id = p.course_id AND
										p.student_id = s.id AND
										c.association_id = '.$_SESSION['association']['association_id'].' AND
										p.student_id = '.$students['results']['id'].' AND
										c.semester = "'.current_semester().'" AND
										c.active = "1"
								ORDER BY c.prog_abbrev ASC, c.course_code ASC';
			$courses['query'] = mysql_query($courses['sql'], $info['sql_db_connect']) or die(mysql_error());
			$courses['results'] = mysql_fetch_assoc($courses['query']);
			if ( mysql_num_rows($courses['query']) > 0 ) {
				do {
					$i++;
?> 
		<fieldset class="psuedo_p" style="float:left;<?php if (!is_int($i/2)) { print ' clear:left;'; } else { print ' clear:none;'; } ?> width:215px;">
			<table> 
				<caption><strong><?php print "{$courses['results']['prog_abbrev']}{$courses['results']['course_code']}: {$courses['results']['course_name']}"; ?></strong></caption>
				<thead>
					<tr>
						<td width="50%"><strong>Set #</strong></td> 
						<td width="50%"><strong>Date</strong></td> 
						<td align="center"><strong>Pickup?</strong></td> 
					</tr> 
				</thead> 
				<tbody>
<?php
					$sets['sql'] = '	SELECT	n.id, n.num, n.date, n.distribution, u.date AS pickup_date
										FROM	sets AS n
										LEFT	JOIN pickups AS u
												ON u.set_id = n.id AND u.purchase_id = '.$courses['results']['pid'].'
										WHERE	n.course_id = '.$courses['results']['cid'].' AND
												n.received != 0
										ORDER BY num DESC, date DESC';
					$sets['query'] = mysql_query($sets['sql'], $info['sql_db_connect']) or die(mysql_error());
					$sets['results'] = mysql_fetch_assoc($sets['query']);
					if ( mysql_num_rows($sets['query']) > 0 ) {
						do {
?>
					<tr> 
						<td><?php print $sets['results']['num']; ?></td> 
						<td><?php print date('Y/m/d',strtotime($sets['results']['date'])); ?></td>
<?php
							if ( $sets['results']['distribution'] == 0 ) {
								if ( $sets['results']['pickup_date'] != NULL ) {
									$html['checkbox'] = " checked=\"checked\" disabled=\"disabled\"";
								} 
?>
						<td align="center"><input name="pickup[<?php print $courses['results']['pid'] ?>][]" type="checkbox" value="<?php print $sets['results']['id']; ?>"<?php print $html['checkbox']; ?> /></td> 
<?php
							} else {
?>
						<td align="center">Online</td> 
<?php
							}
?>
					</tr> 
<?php
							unset($html['checkbox']);
						} while ( $sets['results'] = mysql_fetch_assoc($sets['query']) );
					} else {
?>
					<tr> 
						<td colspan="3" class="caution">There are no NTC sets to display.</td> 
					</tr> 
<?php
					}
?>
				</tbody> 
			</table>
		</fieldset>
<?php
				} while ( $courses['results'] = mysql_fetch_assoc($courses['query']) );
?>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="button" class="back" value="&lsaquo; Back" onclick="history.back(1)" />
			<input name="step2" type="submit" class="forward" value="Confirm &rsaquo;" /><br />
			<input name="step2" type="submit" class="forward" value="Confirm, and do another &raquo;" />
		</fieldset>
	</fieldset>
</form> 
<?php
			} else {
?>
<p id="warning_paragraph">This student has not purchased any NTCs this semester.</p>
<?php
			}
			$layout->output_footer();
		} else {
			$layout->redirector('Pickups (One by One) | Student Not Found', 'A student, with the McGill ID: '.$_POST['mcgill_id'].', was not found. Please <a href="javascript:history.go(-1)">go back</a> and try again.');
		}
		break;
	default:
		$layout->output_header('Pickups (One by One)', 'association');
?> 
<ul id="breadcrumb">
	<li>Pickups (One by One)</li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Pickups (Student-by-Student)</h1> 
<form method="post" action="<?php print $_SERVER['PHP_SELF']; ?>#content">
<?php
		general_error($error);
?>
	<fieldset>
		<fieldset class="psuedo_p">
			<p>This section will allow you to manage the set pickups for an individual student.</p>
			<label>
				Enter the student's 9-digit McGill ID:
				<input type="text" name="mcgill_id" class="text" size="9" maxlength="9" value="<?php print $_POST['mcgill_id']; ?>" /><?php print $error['mcgill_id']; ?>
			</label>
		</fieldset> 
		<fieldset id="controls" class="psuedo_p" title="Form controls"> 
			<input type="button" class="back" value="&lsaquo; Cancel" onclick="location.href='/associations/'" />
			<input type="submit" class="forward" name="step1" value="Next &rsaquo;" />
		</fieldset> 
	</fieldset>
</form> 
<?php
		$layout->output_footer();
}


} elseif ( mysql_num_rows($courses['query']) == 0 ) {
	$layout->redirector('No Courses Available', 'Currently, there are no courses available for pickup. To add a course, please consult your student association\'s Athena administrator.');
} else {
	$layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
}


?>