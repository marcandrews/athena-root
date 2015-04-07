<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();

$courses['sql'] = '	SELECT		c.id, concat(c.prog_abbrev,c.course_code) AS code, c.course_name,
								n.num, n.date, n.distribution
					FROM		courses AS c
					LEFT JOIN	sets AS n ON n.course_id = c.id AND ((n.distribution = 0 AND n.received != "0000-00-00 00:00:00") OR n.distribution = 1)
					WHERE		c.association_id = '.$_SESSION['association']['association_id'].' AND
								c.semester = "'.current_semester().'" AND
								c.year = "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'"
					ORDER BY	c.prog_abbrev, c.course_code, n.num, n.date';
if ( $courses['query'] = mysql_query($courses['sql'],$info['sql_db_connect']) and mysql_num_rows($courses['query']) > 0 ) {
	$courses['results'] = mysql_fetch_assoc($courses['query']);
	
	if ( $_POST ) {
		if ( is_array($_POST['pickups']) ) {
			$pickups['sql'] .= 'INSERT IGNORE INTO pickups (purchase_id, set_id, date) VALUES ';
			foreach ( $_POST['pickups'] as $pid => $value ) {
				foreach ( $_POST['pickups'][$pid] as $set_id ) {
					$pickups['sql'] .= '('.$pid.', '.$set_id.', NOW()), ';
				}
			}
			$pickups['sql'] = substr($pickups['sql'],0,-2);
			$pickups['query'] = mysql_query($pickups['sql'],$info['sql_db_connect']);
		}
		if ( $_POST['confirm'] ) {
			if ( is_array($_POST['pickups']) and $pickups['query'] ) {
				$layout->redirector('Pickup Successful ...', 'Athena has been updated. Now redirecting you to ...', '/associations/');
			} elseif ( is_array($_POST['pickups']) and !$pickups['query']) {
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
<script type="text/javascript">
//<![CDATA[

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

function up() {
	$('#student_list').hide();
	$('#student_list_processing').show();
	$('form')[0].submit();
}

setTimeout('up()',30000);
//]]>
</script>
<ul id="breadcrumb">
	<li>Pickups</li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Pickups</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF']; ?>#content" class="disable_form_history" onsubmit="up()">
<?php print $html['error'] ?>
	<fieldset>
		<p class="no_print">To manage set pickups, select a course, select from the available sets, and then click on <strong>Confirm</strong>, to update Athena and return home, or <strong>Update, and Continue</strong>, to update Athena and continue to manage set pickups. Every five minutes and after switching courses, Athena will be automatically updated with the current pickups.</p>
		<fieldset id="pickup_list" class="psuedo_p">
			<div id="header_screen">
				<label>
					Course:
					<select name="cid" onchange="up()">
<?php
	do {
		if ( $courses['results']['id'] != $p ) {
?>
						<option value="<?php print $courses['results']['id'] ?>"<?php print $html['selected_course'][$courses['results']['id']] ?>><?php print $courses['results']['code'] ?>: <?php print $courses['results']['course_name'] ?></option>			
<?php
		}
		if ( $courses['results']['id'] == $_REQUEST['cid'] ) {
			$pickup_list_header = $courses['results']['code'].': '.$courses['results']['course_name'];
		}
		if ( $courses['results']['num'] != NULL ) {
			if ( $courses['results']['id'] == $_REQUEST['cid'] ) {
				if ( $courses['results']['distribution'] == 0 ) {
					$html['set_numbers'] .=
'							<th class="col_n"><abbr title="Released on '.date('Y/m/d',strtotime($courses['results']['date'])).'">'.$courses['results']['num'].'</abbr></th>
';
					$i++;
				} else {
					$html['online_sets'][] = '<strong><abbr title="Released on '.date('Y/m/d',strtotime($courses['results']['date'])).'">#'.$courses['results']['num'].'</abbr></strong>';
				}
			}
		}
		$p = $courses['results']['id'];
	} while ( $courses['results'] = mysql_fetch_assoc($courses['query']) );
	unset($p);
?>
					</select>
				</label>
				<br />
				<br />
<?php
	if ( $i > 0 ) {
?>
				<table>
					<tbody>
						<tr>
							<th class="col_name">
								<label>
									Name<br />
									<input type="text" id="n" onkeyup="filter(this,'student_list',0);document.getElementById('mid').value='';" class="text" style="width:117px;" />
								</label>
							</th>
							<th class="col_mcgill_id">
								<label>
									McGill ID<br />
									<input type="text" id="mid" maxlength="9" onkeyup="filter(this,'student_list',1);document.getElementById('n').value='';" class="text" style="width:63px;" />
								</label>
							</th>
<?php print $html['set_numbers'] ?>
							<th>&nbsp;</th>
						</tr>
					</tbody>
				</table>
			</div>
<?php
		$sets['sql'] = '	SELECT		p.id AS pid,
										s.last_name, s.first_name, s.mcgill_id, s.validated,
										n.id AS nid, n.num, n.date,
										u.date AS pickup_date
							FROM		(courses AS c, purchases AS p, students AS s)
							LEFT JOIN	sets AS n ON n.course_id = c.id AND n.distribution = "0" AND n.received != "0000-00-00 00:00:00"
							LEFT JOIN	pickups AS u ON u.set_id = n.id AND u.purchase_id = p.id
							WHERE		c.association_id = '.$_SESSION['association']['association_id'].' AND
										c.semester = "'.current_semester().'" AND
										c.year = "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'" AND
										c.id = '.$_REQUEST['cid'].' AND
										c.id = p.course_id AND
										p.student_id = s.id AND
										p.coordinator = false
							ORDER BY	s.last_name, s.first_name, s.mcgill_id, n.num, n.date';
		if ( $sets['query'] = mysql_query($sets['sql'],$info['sql_db_connect']) and mysql_num_rows($sets['query']) > 0 ) {
			ob_start();
?>
			<div class="scrollable">
				<table id="student_list">
					<caption><?php print $pickup_list_header ?> NTC Pickup Sheet</caption>
					<col class="col_name" />
					<col class="col_mcgill_id" />
<?php
			for ( $n = 1; $n <= $i; $n++ ) {
				$nthchild .= ($n+2).'),td:nth-child(';
?>
					<col class="col_n" />
<?php
			}
?>
					<col />
					<thead>
						<tr>
							<th>Name</th>
							<th>McGill ID</th>
<?php print $html['set_numbers'] ?>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
<?php			
			while ( $sets['results'] = mysql_fetch_assoc($sets['query']) ) {
				if ( $p and $sets['results']['pid'] != $p ) {
?>
							<td>&nbsp;</td>
						</tr>
						<tr<?php if ( $sets['results']['validated'] == 0 ) print ' class="unvalidated"'; ?>>
							<td><strong><?php print $sets['results']['last_name']; ?></strong>, <?php print $sets['results']['first_name'] ?></td>
							<td><?php print $sets['results']['mcgill_id'] ?></td>
<?php
				} elseif ( !$p ) {
?>
						<tr<?php if ( $sets['results']['validated'] == 0 ) print ' class="unvalidated"'; ?>>
							<td><strong><?php print $sets['results']['last_name']; ?></strong>, <?php print $sets['results']['first_name'] ?></td>
							<td><?php print $sets['results']['mcgill_id'] ?></td>
<?php
				}
?>
							<td class="col_n"><input name="pickups[<?php print $sets['results']['pid'] ?>][]" type="checkbox" value="<?php print $sets['results']['nid'] ?>"<?php if ( $sets['results']['pickup_date'] != NULL ) print ' checked="checked" disabled="disabled"'; ?> /></td>
<?php
				$p = $sets['results']['pid'];
			}
?>
							<td>&nbsp;</td>
						</tr>
					</tbody>
				</table>
				<div id="student_list_processing" class="caution" style="display:none;">Please wait while Athena is processing . . .</div>
			</div>
			<div id="reminder">&bull; Please remind these students to validate their accounts.</div>
<?php
			ob_end_flush();
		}
?>
		</fieldset>
		<fieldset id="controls" class="psuedo_p screen_only" title="Form controls">
			<input type="button" value="&lsaquo; Cancel" onclick="top.location.href='/associations/'" class="back" />
			<input type="button" id="submit_buttons_wait" value="Please wait ..."  disabled="disabled" class="forward" style="display:none;" />
			<input type="submit" name="confirm" value="Confirm &rsaquo;" class="forward" />
			<input type="submit" name="update" value="Update, and continue &raquo;" class="forward" />
		</fieldset> 
<?php
	} else {
?>
				<div class="caution">Currently, there are no sets available for pickup.</div>
			</div>
		</fieldset>
<?php
	}
?>
	</fieldset>
</form>
<?php
	if ( is_array($html['online_sets']) ) {
		$html['online_sets'] = array_unique($html['online_sets']);
		foreach ( $html['online_sets'] as $value ) { 
			$x++;
			$output .= $value.( (count($html['online_sets']))-$x > 1 ? ', ' : ' and ' );
		}
?>
<p>Note that the following set<?php print $x == 1 ? ' is' : 's are'; ?> offered online: <?php print substr($output,0,-5) ?></p>
<?php
	}
	$layout->output_footer();
} elseif ( mysql_num_rows($courses['query']) == 0 ) {
	$layout->redirector('No Courses Available', 'Currently, there are no courses available for pickup. To add a course, please consult your student association\'s Athena administrator.');
} else {
	$layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
}
?>