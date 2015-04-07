<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();
$layout->output_header('Home', 'association');

$purchases['sql'] = 'SELECT id FROM courses WHERE association_id = '.$_SESSION['association']['association_id'].' AND semester = "'.current_semester().'" AND active = "1"';
$purchases['query'] = mysql_query($purchases['sql']) or die(mysql_error());
$html['purchases'] = '<a href="/associations/purchases.php">'.mysql_num_rows($purchases['query']).'</a> NTC'.( mysql_num_rows($purchases['query']) == 1 ? ' is ' : 's are ' ).' available for purchase';

$received['sql'] = '	SELECT	s.id
						FROM	courses AS c, sets AS s
						WHERE	c.association_id = '.$_SESSION['association']['association_id'].' AND
								c.semester = "'.current_semester().'" AND
								c.active = "1" AND
								c.id = s.course_id AND
								s.distribution = false AND
								s.received = 0';
$received['query'] = mysql_query($received['sql']) or die(mysql_error());
$html['received'] = '<a href="/associations/receivables.php">'.mysql_num_rows($received['query']).'</a> NTC'.( mysql_num_rows($received['query']) == 1 ? ' is ' : 's are ' ).' waiting to be received';

$pickups['sql'] = '	SELECT	s.id
					FROM	courses AS c, sets AS s
					WHERE	c.association_id = '.$_SESSION['association']['association_id'].' AND
							c.semester = "'.current_semester().'" AND
							c.active = "1" AND
							c.id = s.course_id AND
							s.distribution = "0" AND
							s.received != 0';
$pickups['query'] = mysql_query($pickups['sql']) or die(mysql_error());
$html['pickups'] = '<a href="/associations/pickups.php">'.mysql_num_rows($pickups['query']).'</a> NTC'.( mysql_num_rows($pickups['query']) == 1 ? ' is ' : 's are ' ).' available for pickup';

$downloads['sql'] = '	SELECT	s.id
						FROM	courses AS c, sets AS s
						WHERE	c.association_id = '.$_SESSION['association']['association_id'].' AND
								c.semester = "'.current_semester().'" AND
								c.active = "1" AND
								c.id = s.course_id AND
								s.distribution = "1"';
$downloads['query'] = mysql_query($downloads['sql']) or die(mysql_error());
$html['downloads'] = '<a href="/associations/ntc_summary.php">'.mysql_num_rows($downloads['query']).'</a> NTC'.( mysql_num_rows($downloads['query']) == 1 ? ' is ' : 's are ' ).' available for download';

$sold['sql'] = '	SELECT	p.id
					FROM	purchases AS p, courses AS c
					WHERE	p.coordinator = false AND
							p.course_id = c.id AND
							c.association_id = '.$_SESSION['association']['association_id'];
$sold['query'] = mysql_query($sold['sql']) or die(mysql_error());
$html['sold'] = '<a href="/associations/accounts_summary.php">'.mysql_num_rows($sold['query']).'</a> NTC'.( mysql_num_rows($sold['query']) == 1 ? ' has ' : 's have ' ).' been sold';

$gross['sql'] = '	SELECT	SUM(c.price)
					FROM 	purchases AS p, courses AS c
					WHERE	p.coordinator = false AND
							p.course_id = c.id AND
							c.association_id = '.$_SESSION['association']['association_id'].' AND
							c.active = "1" AND
							c.semester <= "'.current_semester().'"';
$gross['query'] = mysql_query($gross['sql']) or die(mysql_error());

$w_salary['sql'] = '	SELECT	SUM(total_lectures*writer_salary)
						FROM 	courses
						WHERE	association_id = '.$_SESSION['association']['association_id'].' AND
								active = "1" AND
								semester <= "'.current_semester().'"';
$w_salary['query'] = mysql_query($w_salary['sql']) or die(mysql_error());

setlocale(LC_MONETARY, 'en_US');
$printing['sql'] = '	SELECT	SUM(s.pages * d.ds_printing_cost)
						FROM	purchases AS p, courses AS c, sets AS s, associations AS d
						WHERE	p.coordinator = false AND
								p.course_id = c.id AND
								s.course_id = c.id AND
								c.association_id = d.id AND
								s.distribution = "0" AND
								c.association_id = '.$_SESSION['association']['association_id'];
$printing['query'] = mysql_query($printing['sql']) or die(mysql_error());

?>
<ul id="breadcrumb">
	<li>Student Association Home</li>
</ul>
<h1>Welcome <?php print $_SESSION['association']['abbreviation']; ?></h1>
<p>Welcome to your <strong>Athena for Student Assoications</strong> home page, which allows you to manage your association's daily tasks and activities.</p>
<h2>What's New?</h2>
<div id="latest" class="psuedo_p">
	<div id="statistics">
		<p><strong>Your statistics for <?php print current_semester() == 0 ? date('Y').'/'.(date('Y')+1) : (date('Y')-1).'/'.date('Y') ?></strong></p>
		<p><?php print $html['purchases'] ?></p>
		<p><?php print $html['received'] ?></p>
		<p><?php print $html['pickups'] ?><br />
			<?php print $html['downloads'] ?></p>
		<p><?php print $html['sold'] ?><br />
			<a href="/associations/accounts_summary.php"><?php print money_format ('%(#1n', mysql_result($gross['query'],0) - mysql_result($w_salary['query'],0) - mysql_result($printing['query'],0)); ?></a> has been profited</p>
	</div>
<?php
$news['sql'] = '	SELECT	a.name, a.abbreviation,
							w.id, w.association_id, w.recipients, w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w
					WHERE	(a.id = 1 OR a.id = '.$_SESSION['association']['association_id'].') AND 
							a.id = w.association_id
					ORDER BY date DESC
					LIMIT 5';
if ($news['query'] = mysql_query($news['sql']) and mysql_num_rows($news['query']) > 0) {
	$first = true;
?>
	<ul id="news">
<?php
	while ($news['results'] = mysql_fetch_assoc($news['query'])) {
?>
		<li>
			<div class="headline"><?php print $news['results']['headline'] ?></div>
			<div class="source"><?php if ($news['results']['association_id'] == $_SESSION['association']['association_id']) { ?><div class="right"><a href="/associations/news.php?edit=<?php print $news['results']['id'] ?>" class="char_button" title="Edit this news item">&Delta;</a> <a href="/associations/news.php?delete=<?php print $news['results']['id'] ?>" class="char_button" title="Delete this news item" onclick="return confirm('Are you sure you want to delete this news item?')">&minus;</a></div><?php } ?>Posted by <abbr title="<?php print htmlentities($news['results']['name']) ?>" class="small-caps"><?php print ucfirst(strtolower($news['results']['abbreviation'])) ?></abbr> on <?php print date($info['date']['medium'], $news['results']['date']) ?></div>
			<div class="story"><?php print parse_bbcode($news['results']['story']) ?></div>
		</li>
<?php 
	}
?>
	</ul>
<?php
} else {
?>
	<p>There is currently no news. Why don't you try the <a href="http://www.bbc.co.uk/">BBC</a>?</p>
<?php
}
?>
	<strong><a href="/associations/news.php">Add your own news or announcement &raquo;</a></strong><br />
	<a href="/news.php">More news and announcements &raquo;</a>
</div>
<?php
$layout->output_footer();
?>