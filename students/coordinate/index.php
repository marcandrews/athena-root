<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');



$_GET['cid'] = (int) $_GET['cid'];

$user_authentication->validate_student();
$user_authentication->validate_coordinator($_GET['cid']);

$course['sql'] = '	SELECT	c.id, c.association_id, c.prog_abbrev, c.course_code, c.course_name, c.total_lectures, c.price, c.writer_salary, c.semester, c.year, c.active, c.sold, c.printing_cost, (SELECT COUNT(id) FROM purchases WHERE course_id = c.id) AS purchases
					FROM	courses AS c
					WHERE	c.id = '.$_GET['cid'].' AND
							(c.semester <= "'.current_semester().'" AND c.year <= "'.date('Y').'")
					LIMIT	1';

if ($course['query'] = mysql_query($course['sql']) and mysql_num_rows($course['query']) == 1) {
	$course['results'] = mysql_fetch_assoc($course['query']);
	$layout->output_header('Coordinate '.$course['results']['prog_abbrev'].$course['results']['course_code'], 'student');
?>
<ul id="breadcrumb">
	<li>Coordinate <?php print $course['results']['prog_abbrev'] . $course['results']['course_code']; ?></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Coordinate <?php print $course['results']['prog_abbrev'] . $course['results']['course_code']; ?></h1>
<?php
	if ($course['results']['semester'] >= current_semester() and $course['results']['year'] == current_school_year()) {

		/* This is an active course. */

?>
<script type="text/javascript">
//<![CDATA[

	function isNumberKey(evt) {
	var charCode = (evt.which) ? evt.which : event.keyCode
	if (charCode > 31 && (charCode < 48 || charCode > 57))
		return false;
		return true;
	}
	
	function updateNumLectures() {
		var tl = $('input#tl').val();
		var r_tl = /^[1-9][0-9]?$/;
		if (r_tl.test(tl)) {
			$.ajax({
				timeout:	10000,
				url:		'updateNumLectures.php', 
				data:		{ cid:<?php print $_GET['cid'] ?>, tl:tl },
				beforeSend:	function() {
								document.body.style.cursor = 'wait';
							},
				error:		function() {
								alert('An error occured when attempting to update the total number of lectures for this course. Please try again.');
							},						
				complete:	function() {
								document.body.style.cursor = 'auto';
							},
				success:	function(data) {
								var data = data.split(';');
								if (data[0]>0) {
									$('#n_lectures_edit').hide();
									$('#n_lectures').show();
									$('#n_html').html(data[1]);
									$('input#tl').val(data[1]);
								} else {
									alert('An error occured when attempting to update the total number of lectures for this course. Please try again.');
								}
							}
			});
		} else {
			alert('Please enter a number between 1 and 99, and then try again.');
		}
	}
//]]>
</script>
<?php
		if ($course['results']['total_lectures'] == 0) general_warning('The total number of lectures is currently set to zero.<br />Please set the total number of lectures under <em>NTC Summary</em>.');
?>
<p>You are the coordinator of <strong><?php print "{$course['results']['prog_abbrev']}{$course['results']['course_code']}: {$course['results']['course_name']}"; ?></strong>.</p>
<h2>NTC Summary</h2>
<div class="psuedo_p">
	<table>
		<tr style="text-align:center; font-weight:bold;">
			<td style="width:25%;"><abbr title="The total number of NTCs that will be written">Number of lectures:</abbr></td>
			<td style="width:25%;">Order quantity:</td>
			<td style="width:25%;">Sale price:</td>
			<td style="width:25%;">Writer salary:</td>
		</tr>
		<tr>
			<td align="center">
				<div id="n_lectures"<?php if ($course['results']['total_lectures']==0) print ' style="display:none;"'; ?>><span id="n_html"><?php print $course['results']['total_lectures']; ?></span> <span class="char_button" title="Change the total number of lectures for this course." onclick="$('#n_lectures').hide();$('#n_lectures_edit').show();">&Delta;</span></div>
				<div id="n_lectures_edit"<?php if ($course['results']['total_lectures']>0) print ' style="display:none;"'; ?>>
					<input type="text" id="tl" class="text" maxlength="2" style="width:14px; text-align:center;" value="<?php print $course['results']['total_lectures']; ?>" onkeypress="return isNumberKey(event)" />
					<input type="button" class="forward" style="float:none" value="&Delta;" title="Change the number of lectures" onclick="updateNumLectures();" />
<?php
		if ($course['results']['total_lectures'] > 0) {
?>
					<!--<input type="button" class="back" style="float:none" value="&times;" title="Cancel" onclick="display_swap('n_lectures'); display_swap('n_lectures_edit');" />-->
<?php
		}
?>
				</div>
			</td>
			<td align="center"><?php print $course['results']['purchases']; ?></td>
			<td align="center">$<?php print number_format($course['results']['price'],2); ?></td>
			<td align="center">$<?php print number_format($course['results']['writer_salary'],2); ?></td>
		</tr>
	</table>
</div>
<h2>Set Summary</h2>
<div class="psuedo_p">
		<p>Use the following to edit the sets for this NTC. To add, view more information, change or delete
			a set, click on the plus sign (<strong>+</strong>), ellipsis (<strong>&hellip;</strong>), delta (<strong>&Delta;</strong>)
			or minus sign (<strong>&minus;</strong>), respectively. For security, only
			sets that have not been received or sets that are online can be deleted.</p>
		<table>
		<thead>
			<tr>
				<th class="nowrap">Set #</th>
				<th class="nowrap">Date</th>
				<th class="full">Status</th>
				<th align="center"><abbr title="Comments">&ldquo;&rdquo;</abbr></th>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><a href="sets.php?cid=<?php print $course['results']['id'] ?>" title="Add a set" class="char_button">+</a></td>
			</tr>
		</thead>
		<tbody>
<?php
		$sets['sql'] = '	SELECT		n.id, n.num, n.date, n.distribution, n.received,
										( SELECT COUNT(id) FROM pickups WHERE set_id = n.id ) AS pickups,
										( SELECT COUNT(course_id) FROM purchases WHERE course_id = '.$course['results']['id'].' ) AS sold,
										( SELECT COUNT(id) FROM sets_discussions WHERE set_id = n.id ) AS comments
							FROM		sets AS n
							WHERE		course_id = '.$course['results']['id'].'
							ORDER BY	num DESC, date DESC';
		$sets['query'] = mysql_query($sets['sql']) or die(mysql_error());
		$sets['results'] = mysql_fetch_assoc($sets['query']);
		if ( mysql_num_rows($sets['query']) > 0 ) {
			do {
?>
			<tr>
				<td><?php print $sets['results']['num']; ?></td>
				<td><?php print date('Y/m/d',strtotime($sets['results']['date'])); ?></td>
<?php
				if ( $sets['results']['distribution'] == 0 ) {
					if ( $sets['results']['received'] != 0 ) {
?>
				<td>This set has been received (<?php print $sets['results']['pickups'] ?>/<?php print $sets['results']['sold'] ?>)</td>
				<td align="center"><?php print $sets['results']['comments'] ?></td>
				<td align="right" nowrap="nowrap"><a href="/students/more_set_info.php?id=<?php print $sets['results']['id'] ?>" class="char_button" title="View more information on this set">&hellip;</a></td>
				<td align="right" nowrap="nowrap"><a href="/students/coordinate/sets.php?cid=<?php print $course['results']['id'] ?>&amp;nid=<?php print $sets['results']['id']; ?>" title="Change this set" class="char_button">&Delta;</a></td>
				<td><!-- this set cannot be deleted --></td>
<?php
					} else {
?>
				<td>This set is waiting to be received</td>
				<td align="right" nowrap="nowrap">&nbsp;</td>
				<td align="right" nowrap="nowrap">&nbsp;</td>
				<td align="right" nowrap="nowrap"><a href="/students/coordinate/sets.php?cid=<?php print $course['results']['id'] ?>&amp;nid=<?php print $sets['results']['id']; ?>" title="Change this set" class="char_button">&Delta;</a></td>
				<td><a href="/students/coordinate/sets.php?cid=<?php print $course['results']['id'] ?>&amp;nid=<?php print $sets['results']['id']; ?>&amp;delete=1" title="Delete this set" class="char_button" onclick="return confirm('Are you sure you want to delete <?php print $course['results']['prog_abbrev'] . $course['results']['course_code']; ?> Set #<?php print $sets['results']['num']; ?> (<?php print date('Y/m/d',strtotime($sets['results']['date'])); ?>)?\nThis cannot be undone.')">&minus;</a></td>
<?php
					}
				} else {
?>
				<td>This set is available online (<a href="/students/downloader.php?id=<?php print $sets['results']['id']; ?>">download</a>)</td>
				<td align="center"><?php print $sets['results']['comments'] ?></td>
				<td align="right" nowrap="nowrap"><a href="/students/more_set_info.php?id=<?php print $sets['results']['id'] ?>" class="char_button" title="View more information on this set">&hellip;</a></td>
				<td align="right" nowrap="nowrap"><a href="/students/coordinate/sets.php?cid=<?php print $course['results']['id'] ?>&amp;nid=<?php print $sets['results']['id']; ?>" title="Change this set" class="char_button">&Delta;</a></td>
				<td><a href="/students/coordinate/sets.php?cid=<?php print $course['results']['id'] ?>&amp;nid=<?php print $sets['results']['id']; ?>&amp;delete=1" title="Delete this set" class="char_button" onclick="return confirm('Are you sure you want to delete <?php print $course['results']['prog_abbrev'] . $course['results']['course_code']; ?> Set #<?php print $sets['results']['num']; ?> (<?php print date('Y/m/d',strtotime($sets['results']['date'])); ?>)?\nThis cannot be undone.')">&minus;</a></td>
<?php
				}
?>
			</tr>
<?php
			} while ( $sets['results'] = mysql_fetch_assoc($sets['query']) );
		} else {
?>
			<tr>
				<td colspan="7" style="padding:25px;text-align:center;">
					There are currently	no sets to display.<br />
					Would you like to <a href="sets.php?cid=<?php print $course['results']['id'] ?>">add a set</a>?</td>
			</tr>
<?php
		}
?>
		</tbody>
	</table>
</div>
<h2>Mass Emailer</h2>
<?php
		$mass_emailer = new mass_emailer();
		$mass_emailer->display_queue();
	} else {

		/* This is an inactive course. */

		( $course['results']['semester'] == 0 ) ? $year = $course['results']['year'] : $year = $course['results']['year']+1;
		$html['semester'] = $info['semesters'][$course['results']['semester']].' '.$year;
?>
<p>You were the coordinator of <strong><?php print $course['results']['prog_abbrev'].$course['results']['course_code'] ?>: <?php print $course['results']['course_name'] ?></strong> for <?php print $html['semester'] ?>.</p>
<h2>NTC Summary</h2>
<div class="psuedo_p">
	<table>
		<tr style="text-align:center; font-weight:bold;">
			<td width="25%" align="center">Number of lectures:</td>
			<td width="25%" align="center">Number sold:</td>
			<td width="25%" align="center">Sale price: </td>
			<td width="25%" align="center">Writer salary: </td>
		</tr>
		<tr>
			<td align="center"><?php print $course['results']['total_lectures']; ?></td>
			<td align="center"><?php print max($course['results']['sold'],$course['results']['purchases']); ?></td>
			<td align="center">$<?php print number_format($course['results']['price'],2); ?></td>
			<td align="center">$<?php print number_format($course['results']['writer_salary'],2); ?></td>
		</tr>
	</table>
</div>
<?php
	}
	$layout->output_footer();
} else {
	header('Location: /students/');
}
?>
