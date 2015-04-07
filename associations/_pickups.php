<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();

$courses['sql'] = '	SELECT		c.id, concat(c.prog_abbrev,c.course_code) AS code, c.course_name
					FROM		courses AS c
					WHERE		c.association_id = '.$_SESSION['association']['association_id'].' AND
								c.semester = "'.current_semester().'" AND
								c.year = "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'"
					ORDER BY	c.prog_abbrev, c.course_code';
if ($courses['query'] = mysql_query($courses['sql'],$info['sql_db_connect']) and mysql_num_rows($courses['query']) > 0) {
		

	if ($_GET) {
		$pickups['sql'] = "INSERT IGNORE INTO pickups (purchase_id, set_id, date) VALUES ({$_GET['pid']}, {$_GET['nid']}, NOW())";
		if ($pickups['query'] = mysql_query($pickups['sql'])) {
			print 1;
		} else {
			print 0;
		}
		exit;
	} elseif ($_POST) {
		if ( is_array($_POST['pickups']) ) {
			$pickups['sql'] .= 'INSERT IGNORE INTO pickups (purchase_id, set_id, date) VALUES ';
			foreach ( $_POST['pickups'] as $pid => $value ) {
				foreach ( $_POST['pickups'][$pid] as $set_id ) {
					$pickups['sql'] .= '('.$pid.', '.$set_id.', NOW()), ';
				}
			}
			$pickups['sql'] = substr($pickups['sql'],0,-2);
			$pickups['query'] = mysql_query($pickups['sql']);
		}
		if ( $_POST['done'] ) {
			if (is_array($_POST['pickups']) and $pickups['query']) {
				$layout->redirector('Pickup Successful ...', 'Athena has been updated. Now redirecting you to ...', '/associations/');
			} elseif (is_array($_POST['pickups']) and !$pickups['query']) {
				$layout->redirector('Error.', 'There was an error:<br />' . mysql_error());
			} else {
				$html['error'] = '<p id="error_paragraph">Sorry. At least one NTC set must be selected before Athena can continue.</p>';
			}
		}
		$html['selected_course'][$_REQUEST['cid']] = ' selected="selected"';
	} else {
		if ( !$_REQUEST['cid'] ) {
			$_REQUEST['cid'] = $courses['results']['id'];
		}
	}

	$pickup_list_header = $courses['results']['code'].': '.$courses['results']['course_name'];
	$layout->output_header('Pickups', 'association');
?>
<script type="text/javascript" src="/js/pickups.js"></script>
<ul id="breadcrumb">
	<li>Pickups</li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Pickups</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF'] ?>" class="disable_form_history" onsubmit="submit_form()">
<?php print $html['error'] ?>
	<fieldset>
		<p>To manage set pickups, select a course, select from the available sets, and then click on <strong>Done</strong>, to update Athena and return home, or <strong>Update, and continue</strong>, to update Athena and continue to manage set pickups.</p>
		<fieldset id="pickup_list" class="psuedo_p">
			<fieldset title="Course selection" style="padding-bottom:2em;">
				<label>
					Select a course:
					<select name="cid" onchange="switch_course($(this).val())">
<?php
	while ($courses['results'] = mysql_fetch_assoc($courses['query'])) {
?>
						<option value="<?php print $cids[] = $courses['results']['id'] ?>"<?php print $html['selected_course'][$courses['results']['id']] ?>><?php print $courses['results']['code'] ?>: <?php print $courses['results']['course_name'] ?></option>			
<?php
	}
?>
					</select>
				</label>
			</fieldset>
			<fieldset id="loading" style="text-align:center">
				<img src="/i/loading_32.gif" title="Loading; please wait &hellip;" /><br />
				Loading; please wait &hellip;
			</fieldset>
<?php
	foreach ($cids as $cid) {
		unset($html, $num_sets_printed);

		// Sort the sets for this course.
		$sets['sql'] = 'SELECT n.num, n.date, n.distribution FROM sets AS n WHERE n.course_id = '.$cid.' AND ((n.distribution = 0 AND n.received != "0000-00-00 00:00:00") OR n.distribution = 1) ORDER BY n.num, n.date';
		if ($sets['query'] = mysql_query($sets['sql']) and $sets['num_rows'] = mysql_num_rows($sets['query']) > 0) {
			while ($sets['results'] = mysql_fetch_assoc($sets['query'])) {
				switch ($sets['results']['distribution']) {
					case 1:
						$html['sets_online'][] = '<strong><abbr title="Released on '.date('Y/m/d',strtotime($sets['results']['date'])).'">#'.$sets['results']['num'].'</abbr></strong>';
					break;
					case 0:
					default:
						$html['sets_printed'] .= '<th class="col_n"><abbr title="Released on '.date('Y/m/d',strtotime($sets['results']['date'])).'">'.$sets['results']['num'].'</abbr></th>';
						$num_sets_printed++;
					break;
				}
			}
		} elseif ($sets['query'] and $sets['num_rows'] == 0) {
		} else {
		}

		if ($num_sets_printed == 0) {
			$html['sets_printed'] = '<th>N/A</th>';
		}
		
		
?>
			<fieldset id="course_<?php print $cid ?>" class="student_list_container">
<?php
		$pickups['sql'] = '	SELECT		p.id AS pid,
										s.last_name, s.first_name, s.mcgill_id, s.validated,
										n.id AS nid, n.num, n.date,
										u.date AS pickup_date
							FROM		(courses AS c, purchases AS p, students AS s)
							LEFT JOIN	sets AS n ON n.course_id = c.id AND n.distribution = "0" AND n.received != "0000-00-00 00:00:00"
							LEFT JOIN	pickups AS u ON u.set_id = n.id AND u.purchase_id = p.id
							WHERE		c.association_id = '.$_SESSION['association']['association_id'].' AND
										c.semester = "'.current_semester().'" AND
										c.year = "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'" AND
										c.id = '.$cid.' AND
										c.id = p.course_id AND
										p.student_id = s.id AND
										p.coordinator = false
							ORDER BY	s.last_name, s.first_name, s.mcgill_id, n.num, n.date';
		if ($pickups['query'] = mysql_query($pickups['sql']) and mysql_num_rows($pickups['query']) > 0) {
?>
				<table>
					<tr>
						<th class="col_name" style="vertical-align:bottom; line-height:11px;"><label for="n_<?php print $cid ?>">Name</label></th>
						<th class="col_mcgill_id" style="vertical-align:bottom; line-height:11px;"><label for="mid_<?php print $cid ?>">McGill ID</label></th>
						<th colspan="<?php print $num_sets_printed + 1 ?>" style="vertical-align:bottom; line-height:11px;"><label>Sets</label></th>
					</tr>
					<tr>
						<td><input type="text" id="n_<?php print $cid ?>" onkeyup="filter(this,'student_list_<?php print $cid ?>',0);document.getElementById('mid_<?php print $cid ?>').value='';" class="text" style="width:117px;" /></td>
						<td><input type="text" id="mid_<?php print $cid ?>" maxlength="9" onkeyup="filter(this,'student_list_<?php print $cid ?>',1);document.getElementById('n_<?php print $cid ?>').value='';" class="text" style="width:63px;" /></td>
						<?php print $html['sets_printed'] ?>
						<td>&nbsp;</td>
					</tr>
				</table>
				<div class="scrollable">
					<table id="student_list_<?php print $cid ?>">
						<col class="col_name" />
						<col class="col_mcgill_id" />
<?php
			for ($n = 1; $n <= $num_sets_printed; $n++) {
?>
						<col class="col_n" />
<?php
			}
?>
						<col />
						<tbody>
<?php
			$i = 0;
			while ($pickups['results'] = mysql_fetch_assoc($pickups['query']) ) {
				if ($i % ($num_sets_printed) == 0) {
?>
							<tr<?php if ( $pickups['results']['validated'] == 0 ) print ' class="unvalidated"'; ?>>
								<td><strong><?php print $pickups['results']['last_name']; ?></strong>, <?php print $pickups['results']['first_name'] ?></td>
								<td><?php print $pickups['results']['mcgill_id'] ?></td>
<?php
				}
				if ($num_sets_printed > 0) {
?>
								<td class="col_n"><input id="<?php print $pickups['results']['pid'] ?>_<?php print $pickups['results']['nid'] ?>" name="pickups[<?php print $pickups['results']['pid'] ?>][]" type="checkbox" value="<?php print $pickups['results']['nid'] ?>"<?php if ( $pickups['results']['pickup_date'] != NULL ) print ' checked="checked" disabled="disabled"'; ?>  onchange="if (this.checked) { pickup_queue(this.id); } else { clearTimeout(timer[this.id]) }" /></td>
<?php
				}
				$i++;
				if ($i % $num_sets_printed == 0) {
?>
								<td>&nbsp;</td>
							</tr>
<?php
				}
			}
?>
						</tbody>
					</table>
				</div>
				<p id="reminder">&bull; Please remind these students to validate their accounts.</p>
<?php
			$x = 0;
			if (is_array($html['sets_online'])) {
				$html['sets_online'] = array_unique($html['sets_online']);
				foreach ($html['sets_online'] as $value) { 
					$output .= $value.((count($html['sets_online']))-$x > 1 ? ', ' : ' and ');
					$x++;
				}
?>
				<p>Note that the following set<?php print $x == 1 ? ' is' : 's are'; ?> offered online: <?php print substr($output,0,-5) ?></p>
<?php
			}
		} elseif ($pickups['query'] and mysql_num_rows($pickups['query']) == 0) {
?>
				<div class="caution">As of yet, no one has purchased this set.</div>
<?php
		} else {
?>
				<div class="caution"><?php print mysql_error(); ?></div>
<?php
		}
?>
			</fieldset>
<?php
	}
?>
		</fieldset>
		<fieldset id="controls" class="psuedo_p screen_only" title="Form controls">
			<input type="button" value="&lsaquo; Cancel" onclick="top.location.href='/associations/'" class="back" />
			<input type="button" id="submit_buttons_wait" value="Please wait ..."  disabled="disabled" class="forward" style="display:none;" />
			<input type="submit" name="update" value="Update, and continue &raquo;" class="forward" />
			<input type="submit" name="done" value="Done &rsaquo;" class="forward" />
		</fieldset>
	</fieldset>
</form>
<?php
	$layout->output_footer();
} elseif ( mysql_num_rows($courses['query']) == 0 ) {
	$layout->redirector('No Courses Available', 'Currently, there are no courses available for pickup. To add a course, please consult your student association\'s Athena administrator.');
} else {
	$layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
}
?>