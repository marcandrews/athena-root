<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');



$user_authentication->validate_student();

if (!array_key_exists($_GET['semester'], $info['semesters'])) $_GET['semester'] = current_semester();

$purchases['sql'] = 'SELECT p.id FROM courses AS c, purchases AS p WHERE c.association_id > 1 AND c.id = p.course_id AND p.student_id = '.$_SESSION['student']['student_id'].' AND p.coordinator = false';
$purchases['query'] = mysql_query($purchases['sql']);
$html['purchases'] = (int) mysql_num_rows($purchases['query']);

$sets['sql'] = 'SELECT n.id FROM purchases AS p, courses AS c, sets AS n WHERE p.course_id AND p.student_id = '.$_SESSION['student']['student_id'].' AND p.coordinator = false AND p.course_id = c.id AND c.association_id > 1 AND c.id = n.course_id AND ((n.distribution = 0 AND n.received != 0) or n.distribution = 1)';
$sets['query'] = mysql_query($sets['sql']);
$html['sets'] = (int) mysql_num_rows($sets['query']);

$pickups['sql'] = 'SELECT u.id FROM courses AS c, purchases AS p, pickups AS u WHERE c.association_id > 1 AND c.id = p.course_id AND p.student_id = '.$_SESSION['student']['student_id'].' AND p.coordinator = false AND p.id = u.purchase_id';
$pickups['query'] = mysql_query($pickups['sql']);
$html['pickups'] = (int) mysql_num_rows($pickups['query']);

$downloads['sql'] = 'SELECT n.id FROM purchases AS p, courses AS c, sets AS n WHERE p.course_id AND p.student_id = '.$_SESSION['student']['student_id'].' AND p.coordinator = false AND p.course_id = c.id AND c.association_id > 1 AND c.id = n.course_id AND n.distribution = 1';
$downloads['query'] = mysql_query($downloads['sql']);
$html['downloads'] = (int) mysql_num_rows($downloads['query']);


$news['sql'] = '(
					SELECT	# news about NTCs this student has purchased
							a.name, a.abbreviation,
							w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w, courses AS c, purchases AS p
					WHERE	a.id = w.association_id AND
							w.recipients = 0 AND
							w.association_id = c.association_id AND
							c.id = p.course_id AND
							p.student_id = '.$_SESSION['student']['student_id'].'
				) UNION (
					SELECT	# coordinator news about NTCs of which this student is a coordinator
							a.name, a.abbreviation,
							w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w, courses AS c, purchases AS p
					WHERE	a.id = w.association_id AND
							w.recipients = 1 AND
							w.association_id = c.association_id AND
							c.id = p.course_id AND
							p.student_id = '.$_SESSION['student']['student_id'].' AND
							p.coordinator = true
				) UNION (
					SELECT	# news about associations of which this student is an administrator
							a.name, a.abbreviation,
							w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w
					WHERE	a.administrator = '.$_SESSION['student']['student_id'].' AND
							a.id = w.association_id AND
							w.recipients < 0
				) UNION (
					SELECT	a.name, a.abbreviation,
							CONCAT(c.prog_abbrev, c.course_code, " NTCs updated") AS headline, CONCAT("The ", a.name, " have updated [abbr=", c.course_name, "]", c.prog_abbrev, c.course_code, "[/abbr]", " NTCs.") AS story, UNIX_TIMESTAMP(n.created) AS date
					FROM	associations AS a, courses AS c, purchases AS p, sets AS n
					WHERE	a.id = c.association_id AND
							c.id = p.course_id AND
							p.student_id = '.$_SESSION['student']['student_id'].' AND
							p.coordinator = false AND
							p.course_id = n.course_id AND
							((n.distribution = 0 and n.received != 0) OR (n.distribution = 1))
				)
				ORDER BY date DESC
				LIMIT 3';

$courses['sql'] = '	SELECT	n.id, n.num, n.date, n.pages, n.distribution, n.filename,
							c.id AS cid, concat(c.prog_abbrev,c.course_code) AS course_code, c.course_name,
							a.name, a.abbreviation, a.website,
							u.date AS pickup_date,
							( SELECT COUNT(*) FROM sets_discussions WHERE set_id = n.id ) AS comments
					FROM	(courses AS c, associations AS a, purchases AS p)
							LEFT JOIN	sets AS n
							ON			n.course_id = c.id AND (n.received != 0 OR n.distribution = 1)
							LEFT JOIN	pickups AS u
							ON			u.set_id = n.id AND u.purchase_id = p.id
					WHERE	c.semester = "'.$_GET['semester'].'" AND
							c.year = "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'" AND
							c.association_id = a.id AND
							c.id = p.course_id AND
							p.student_id = '.$_SESSION['student']['student_id'].' AND
							p.coordinator = false
					ORDER BY c.prog_abbrev, c.course_code, n.num DESC, n.date';
if ($courses['query'] = mysql_query($courses['sql'])) {
	$layout->output_header('Home', 'student');
?>
<ul id="breadcrumb">
	<li>Student Home</li>
</ul>
<h1>Welcome <?php print $_SESSION['student']['first_name'] ?></h1>
<p>Welcome to your <strong>Athena for Students</strong> home page.</p>
<h2>What's New?</h2>
<div id="latest" class="psuedo_p">
	<div id="statistics">
		<p><strong>Your statistics for <?php print current_semester() == 0 ? date('Y').'/'.(date('Y')+1) : (date('Y')-1).'/'.date('Y') ?></strong></p>
		<p>You have purchased <strong><?php print $html['purchases'] ?></strong> NTC<?php if ($html['purchases'] != 1) print 's'; ?>.</p>
		<p>You have <strong><?php print $html['sets'] ?></strong> set<?php if ($html['sets'] != 1) print 's'; ?> available in print, <strong><?php print $html['pickups'] ?></strong> of which you have picked up.</p>
		<p>You have <strong><?php print $html['downloads'] ?></strong> set<?php if ($html['downloads'] != 1) print 's'; ?> available online.</p>
	</div>
<?php
	if ($news['query'] = mysql_query($news['sql']) and mysql_num_rows($news['query']) > 0) {
?>
	<ul id="news">
<?php
		while ($news['results'] = mysql_fetch_assoc($news['query'])) {
?>
		<li>
			<div class="headline"><?php print $news['results']['headline'] ?></div>
			<div class="source">Posted by <abbr title="<?php print htmlentities($news['results']['name']) ?>" class="small-caps"><?php print ucfirst(strtolower($news['results']['abbreviation'])) ?></abbr> on <?php print date($info['date']['medium'], $news['results']['date']) ?></div>
			<div class="story"><?php print parse_bbcode($news['results']['story']) ?></div>
		</li>
<?php
		}
?>
	</ul>
	<a href="/news.php">More news and announcements &raquo;</a>
<?php
	} else {
?>
	<p>There is currently no news. Why don't you try the <a href="http://www.bbc.co.uk/">BBC</a>?</p>
<?php
	}
?>
</div>
<h2>My <?php print $info['semesters'][$_GET['semester']] ?> NTCs</h2>
<?php
	if (mysql_num_rows($courses['query']) > 0) {
?>
<p>Here are the NTCs you have purchased this <strong><?php print $info['semesters'][$_GET['semester']] ?> Semester</strong>.</p>
<?php
		while ($courses['results'] = mysql_fetch_assoc($courses['query'])) {
			if ( $prev and $prev != $courses['results']['cid'] ) {
?>
		</tbody> 
	</table>
</div>
<div class="psuedo_p">
	<p><strong><?php print $courses['results']['course_code'] ?>: <?php print $courses['results']['course_name'] ?></strong><br />
		Offered by the <a href="http://<?php print $courses['results']['website'] ?>"><?php print $courses['results']['name'] ?> (<?php print $courses['results']['abbreviation'] ?>)</a></p>
	<table>
		<thead>
			<tr> 
				<td class="nowrap"><strong>Set #</strong></td> 
				<td><strong>Date</strong></td> 
				<td style="width:50%;"><strong>Status</strong></td> 
				<td style="text-align:center"><strong><abbr title="Comments">&ldquo;&rdquo;</abbr></strong></td>
				<td>&nbsp;</td>
			</tr> 
		</thead>
		<tbody>
<?php
			} elseif ( !$prev ) {
?>
<div class="psuedo_p">
	<p><strong><?php print $courses['results']['course_code'] ?>: <?php print $courses['results']['course_name'] ?></strong><br />
		Offered by the <a href="http://<?php print $courses['results']['website'] ?>"><?php print $courses['results']['name'] ?> (<?php print $courses['results']['abbreviation'] ?>)</a></p>
	<table>
		<thead>
			<tr> 
				<td class="nowrap"><strong>Set #</strong></td> 
				<td><strong>Date</strong></td> 
				<td style="width:50%;"><strong>Status</strong></td> 
				<td style="text-align:center"><strong><abbr title="Comments">&ldquo;&rdquo;</abbr></strong></td>
				<td>&nbsp;</td>
			</tr> 
		</thead>
		<tbody>
<?php
			}
			if ( $courses['results']['id'] != NULL ) {
?>
			<tr> 
				<td><?php print $courses['results']['num']; ?></td> 
				<td><?php print date('Y/m/d',strtotime($courses['results']['date'])) ?></td>
<?php
				if ( $courses['results']['distribution'] == 0 ) {
					if ( $courses['results']['pickup_date'] != NULL ) {
?>
				<td>I have this set</td> 
<?php
					} else {
?>
				<td>This set is available for pickup</td> 
<?php
					}
				} else {
?>
				<td>This set is available online <a href="/students/more_set_info.php?id=<?php print $courses['results']['id']; ?>#content">(download)</a></td>
<?php
				}
?>
				<td style="text-align:center"><?php print $courses['results']['comments']; ?></td>
				<td style="text-align:right"><a href="/students/more_set_info.php?id=<?php print $courses['results']['id'] ?>#content" class="char_button" title="More information on this set">&hellip;</a></td>
			</tr>
<?php
			} else {
?>
			<tr>
				<td colspan="5" class="caution" style="padding:10px;text-align:center;">Currently, there are no sets available.</td>
			</tr>
<?php
			}
			$prev = $courses['results']['cid'];
		}
?>
		</tbody> 
	</table>
</div>
<?php
	} else {
		if ($_GET['semester'] == current_semester()) {
?>
<p class="warning">You have not purchased any NTCs this <?php print $info['semesters'][$_GET['semester']] ?>.<br />
	To purchase an NTC, please visit your <a href="/participants.php">participating student association</a>.</p>
<?php
		} else {
?>
<p class="warning">You have not purchased any NTCs this <?php print $info['semesters'][$_GET['semester']] ?>.</p>
<?php
		}
	}
	$layout->output_footer();
} else {
	$layout->redirector('Athena | Error', 'A problem occurred while attempting to access your NTCs. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persist, please contact the <a href="/contact.php?to=1">Administrators of Athena</a>.');
}
?>