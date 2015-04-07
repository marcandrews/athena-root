<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');



$assoc['sql'] = "	SELECT	id, name, abbreviation, address, website
					FROM	associations
					WHERE	id > 1
					ORDER BY name, abbreviation";
if ($assoc['query'] = mysql_query($assoc['sql']) and mysql_num_rows($assoc['query']) > 0) {
	$layout->output_header('Participants', NULL);
?>
<ul id="breadcrumb">
	<li>Participants</li>
<?php if (is_array($_SESSION['student'])) { ?>
	<li> &laquo; <a href="/students/">Student Home</a></li>
<?php } elseif (is_array($_SESSION['association'])) { ?>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
<?php } else { ?>
	<li> &laquo; <a href="/">Home</a></li>
<?php } ?>
</ul>
<h1>Participants</h1>
<p>Here is a list of the participating student associations and the NTCs they
	are offering for <strong><?php print current_semester() == 0 ? date('Y').'/'.(date('Y')+1) : (date('Y')-1).'/'.date('Y') ?></strong>.
	To access more information on any of these NTCs or the NTCs themselves, please
	contact or visit your student association.</p>
<?php
	while ($assoc['results'] = mysql_fetch_assoc($assoc['query'])) {
		$i++;
		$courses['sql'] = "	SELECT	CONCAT(prog_abbrev, course_code) AS course_code, course_name, year, semester, price
							FROM	courses
							WHERE	association_id = {$assoc['results']['id']} AND
									year = '".$common_variables->current_school_year()."'
							ORDER BY semester, course_code";
		if ($courses['query'] = mysql_query($courses['sql']) and mysql_num_rows($courses['query']) > 0) {
?>
	<div class="psuedo_p">
		<p class="left-align"><strong><?php print htmlentities($assoc['results']['name']) ?> (<?php print $assoc['results']['abbreviation'] ?>)</strong><br />
			<a href="/news.php?sa=<?php print $assoc['results']['id'] ?>">News</a> |
			<a href="/contact.php?to=<?php print $assoc['results']['id'] ?>">Email</a> |
			<a href="http://<?php print $assoc['results']['website'] ?>">Website</a><br />
			Location: <?php print $assoc['results']['address'] ?></p>
<?php
?>
		<table>
			<thead>
				<tr>
					<th>Course</th>
					<th style="width:100%;">Name</th>
					<th>Semester</th>
					<th style="text-align:right;">Price</th>
				</tr>
			</thead>
			<tbody>
<?php
			while ($courses['results'] = mysql_fetch_assoc($courses['query'])) {
?>
				<tr>
					<th><?php print $courses['results']['course_code'] ?></th>
					<td><?php print $courses['results']['course_name'] ?></td>
					<td class="nowrap"><?php print $info['semesters'][$courses['results']['semester']] ?> <?php print $courses['results']['semester'] == 0 ? $courses['results']['year'] : $courses['results']['year'] + 1 ?></td>
					<td style="text-align:right;"><?php print ($courses['results']['price'] > 0) ? '$'.$courses['results']['price'] : 'Free'; ?></td>
				</tr>	
<?php
			}
?>
			</tbody>
		</table>
	</div>
<?php
		}
	}
?>
<p style="clear:both;">Whether a member of a student association or not, if you
	distribute NTCs and would like to use Athena, please
	<a href="/contact.php?to=1">contact the Administrators of Athena</a>.</p>
<?php
	$layout->output_footer();
} else {
	$layout->redirector('Athena | Participants', 'There are currently no participants to display. Please try again later.');
}
?>
