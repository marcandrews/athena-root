<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');



if (is_array($_SESSION['student'])) {
	header('Location: /students/');
} elseif (is_array($_SESSION['association'])) {
	header('Location: /associations/');
}

$sas['sql'] = 'SELECT id FROM associations WHERE id > 1';
$sas['query'] = mysql_query($sas['sql']);
$html['dept'] = (int) mysql_num_rows($sas['query']);

$students['sql'] = 'SELECT DISTINCT s.id FROM purchases AS p, students AS s WHERE p.coordinator = false AND p.student_id = s.id';
$students['query'] = mysql_query($students['sql']);
$html['stud'] = (int) mysql_num_rows($students['query']);

$ntcs['sql'] = 'SELECT id FROM courses WHERE association_id > 1 AND semester <= '.current_semester().' AND year = "' .(current_semester() == 0 ? date('Y') : date('Y')-1).'"';
$ntcs['query'] = mysql_query($ntcs['sql']);
$html['ntcs'] = (int) mysql_num_rows($ntcs['query']);

$purchases['sql'] = 'SELECT p.id FROM courses AS c, purchases AS p WHERE c.association_id > 1 AND c.id = p.course_id AND p.coordinator = false';
$purchases['query'] = mysql_query($purchases['sql']);
$html['purchases'] = (int) mysql_num_rows($purchases['query']);

$sets['sql'] = 'SELECT n.id FROM courses AS c, sets AS n WHERE c.association_id > 1 AND c.id = n.course_id AND ((n.distribution = 0 AND n.received != 0) or n.distribution = 1)';
$sets['query'] = mysql_query($sets['sql']);
$html['sets'] = (int) mysql_num_rows($sets['query']);

$pickups['sql'] = 'SELECT u.id FROM courses AS c, purchases AS p, pickups AS u WHERE c.association_id > 1 AND c.id = p.course_id AND p.id = u.purchase_id';
$pickups['query'] = mysql_query($pickups['sql']);
$html['pickups'] = (int) mysql_num_rows($pickups['query']);

$downloads['sql'] = 'SELECT n.id FROM courses AS c, sets AS n WHERE c.association_id > 1 AND c.id = n.course_id AND n.distribution = 1';
$downloads['query'] = mysql_query($downloads['sql']);
$html['downloads'] = (int) mysql_num_rows($downloads['query']);

$news['sql'] = '(
					SELECT	a.name, a.abbreviation,
							w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w
					WHERE	a.id = w.association_id AND
							w.recipients = "0"
					ORDER BY w.created
				) UNION (
					SELECT	a.name, a.abbreviation,
							CONCAT(c.prog_abbrev, c.course_code, " NTCs updated") AS headline, CONCAT("The ", a.name, " have updated [abbr=", c.course_name, "]", c.prog_abbrev, c.course_code, "[/abbr]", " NTCs.") AS story, UNIX_TIMESTAMP(n.created) AS date
					FROM	associations AS a, courses AS c, sets AS n
					WHERE	a.id > 1 AND
							a.id = c.association_id AND
							c.id = n.course_id AND
							((n.distribution = 0 and n.received != 0) OR (n.distribution = 1))
					ORDER BY n.created DESC
				)
				ORDER BY date DESC
				LIMIT 7';

$layout->output_header();
?>
<h1>Welcome</h1>
<p>Welcome to Athena, the online NTC managing system for students and student
	associations of McGill University. Please sign into Athena for <strong><a href="/students/">
	students</a></strong>, or for <strong><a href="/associations/">student associations</a></strong>.</p>
<h2>What's New?</h2>
<div id="latest" class="psuedo_p">
	<div id="statistics">
		<p><strong>Statistics for <?php print current_semester() == 0 ? date('Y').'/'.(date('Y')+1) : (date('Y')-1).'/'.date('Y') ?></strong></p>
		<p>Athena is helping <strong><?php print $html['dept'] ?></strong> student associations serve <strong><?php print $html['stud'] ?></strong> students <strong><?php print $html['ntcs'] ?></strong> NTCs.</p>
		<p>There have been <strong><?php print $html['purchases'] ?></strong> NTC purchases.</p>
		<p>Of <strong><?php print $html['sets'] ?></strong> sets, <strong><?php print $html['sets']-$html['downloads'] ?></strong> are available for pickup, while <strong><?php print $html['downloads'] ?></strong> are available for download. There have also been <strong><?php print $html['pickups'] ?></strong> set pickups.</p>
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
			<div class="source">Posted by <abbr title="<?php print htmlentities($news['results']['name']) ?>" class="small-caps"><?php print ucwords(strtolower($news['results']['abbreviation'])) ?></abbr> on <?php print date($info['date']['medium'], $news['results']['date']) ?></div>
			<div class="story"><?php print parse_bbcode($news['results']['story']) ?></div>
		</li>
<?php
	}
?>
	</ul>
	<a href="/news.php">More news and announcements &raquo;</a>
</div>
<?php
} else {
?>
	<p>There is currently no news. Why don't you try the <a href="http://www.bbc.co.uk/">BBC</a>?</p>
<?php
}
$layout->output_footer();
?>
