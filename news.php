<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

// Set default filter
if ($_GET['limit'] != 10 and $_GET['limit'] != 20 and $_GET['limit'] != 30) $_GET['limit'] = 10;
if ($_GET['sa'] > 0 and $sa_check['query'] = mysql_query('SELECT id, abbreviation FROM associations WHERE id = "'.$_GET['sa'].'" LIMIT 1') and mysql_num_rows($sa_check['query']) == 1) {
	$assoc_where_clause = 'a.id = "'.$_GET['sa'].'" AND ';
	$sa_check['results'] = mysql_fetch_assoc($sa_check['query']);
} else {
	$_GET['sa'] = 0;
}
if ($_GET['sort'] != 'DESC' and $_GET['sort'] != 'ASC') $_GET['sort'] = 'DESC';
if ($_GET['by'] != 'date' and $_GET['by'] != 'abbreviation' and $_GET['by'] != 'headline') $_GET['by'] = 'date';
if (!$_GET['page'] or !is_numeric($_GET['page'])) $_GET['page'] = 1;

// The news query
$news['sql'] = '(	
					SELECT	# news about NTCs this student has purchased
							a.name, a.abbreviation,
							w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w, courses AS c, purchases AS p
					WHERE	'.$assoc_where_clause.'
							a.id = w.association_id AND
							w.recipients = 0 AND
							w.association_id = c.association_id AND
							c.id = p.course_id AND
							p.student_id = "'.$_SESSION['student']['student_id'].'"
				) UNION (
					SELECT	# coordinator news about NTCs of which this student is a coordinator
							a.name, a.abbreviation,
							w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w, courses AS c, purchases AS p
					WHERE	'.$assoc_where_clause.'
							a.id = w.association_id AND
							w.recipients = 1 AND
							w.association_id = c.association_id AND
							c.id = p.course_id AND
							p.student_id = "'.$_SESSION['student']['student_id'].'" AND
							p.coordinator = true
				) UNION (
					SELECT	# news about associations of which this student is an administrator
							a.name, a.abbreviation,
							w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w
					WHERE	'.$assoc_where_clause.'
							a.administrator = "'.$_SESSION['student']['student_id'].'" AND
							a.id = w.association_id AND
							w.recipients < 0
				) UNION (
					SELECT	# news for students
							a.name, a.abbreviation,
							w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w
					WHERE	'.$assoc_where_clause.'
							a.id = w.association_id AND
							w.recipients = "0"
				) UNION (
					SELECT	# news for associations
							a.name, a.abbreviation,
							w.headline, w.story, UNIX_TIMESTAMP(w.created) AS date
					FROM	associations AS a, news AS w
					WHERE	"'.(int)$_SESSION['association']['association_id'].'" > 0 AND
							'.((isset($assoc_where_clause)) ? $assoc_where_clause : "(a.id = 1 OR a.id = '{$_SESSION['association']['association_id']}') AND").' 
							a.id = w.association_id
				) UNION (
					SELECT	# news of NTC updates
							a.name, a.abbreviation,
							CONCAT(c.prog_abbrev, c.course_code, " NTCs updated") AS headline, CONCAT("The ", a.name, " have updated [abbr=", c.course_name, "]", c.prog_abbrev, c.course_code, "[/abbr]", " NTCs.") AS story, UNIX_TIMESTAMP(n.created) AS date
					FROM	associations AS a, courses AS c, sets AS n
					WHERE	a.id > 1 AND
							'.$assoc_where_clause.'
							a.id = c.association_id AND
							c.id = n.course_id AND
							((n.distribution = 0 and n.received != 0) OR (n.distribution = 1))
				)';

// Setup pagination
$pagination['sql']					= $news['sql'];
$pagination['query']				= mysql_query($pagination['sql']) or die($layout->redirector('Athena | News Error', 'A problem occurred while attempting to access news. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persist, please contact the <a href="/contact.php?to=1">Administrators of Athena</a>.'));
$pagination['query_string']			= '?limit='.$_GET['limit'].'&amp;sa='.$_GET['sa'].'&amp;sort='.$_GET['sort'].'&amp;by='.$_GET['by'].'&amp;';
$pagination['total_items']			= mysql_num_rows($pagination['query']);
$pagination['total_pages']			= ceil($pagination['total_items']/$_GET['limit']);
$_GET['page']						= min($_GET['page'],$pagination['total_pages']);
$pagination['links']['first']		= $_SERVER['PHP_SELF'].$pagination['query_string'].'page=1';
$pagination['links']['previous']	= $_SERVER['PHP_SELF'].$pagination['query_string'].'page='.max(1,$_GET['page']-1);
$pagination['links']['next']		= $_SERVER['PHP_SELF'].$pagination['query_string'].'page='.min($pagination['total_pages'],$_GET['page']+1);
$pagination['links']['last']		= $_SERVER['PHP_SELF'].$pagination['query_string'].'page='.$pagination['total_pages'];

// Set selected options
$html['selected']['limit'][$_GET['limit']]	= ' selected="selected"';
$html['selected']['sa'][$_GET['sa']]		= ' selected="selected"';
$html['selected']['sort'][$_GET['sort']]	= ' selected="selected"';
$html['selected']['by'][$_GET['by']]		= ' selected="selected"';
$html['selected']['page'][$_GET['page']]	= ' selected="selected"';

// Get news with orders and limits
$news['sql'] = $news['sql'].' ORDER BY '.$_GET['by'].' '.$_GET['sort'].' LIMIT '.(max($_GET['page']-1, 0) * $_GET['limit']).', '.$_GET['limit'];
if ($news['query'] = mysql_query($news['sql'])) {
	$layout->output_header('News');
?>
<ul id="breadcrumb">
	<li>News</li>
<?php if (is_array($_SESSION['student'])) { ?>
	<li> &laquo; <a href="/students/">Student Home</a></li>
<?php } elseif (is_array($_SESSION['association'])) { ?>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
<?php } else { ?>
	<li> &laquo; <a href="/">Home</a></li>
<?php } ?>
</ul>
<h1>News for <?php print current_semester() == 0 ? date('Y').'/'.(date('Y')+1) : (date('Y')-1).'/'.date('Y') ?></h1>
<div id="latest" class="psuedo_p">
	<p>News and announcements from  Athena and all participating student associations.</p>
	<form method="get" action="<?php print $_SERVER['PHP_SELF'] ?>#content">
		<fieldset style="font-size:90%;">
			<p>
				<input type="submit" class="forward" value="Filter:" title="Filter news" /> 
				<select name="limit" style="width:40px;" title="Show this many items per page">
					<option<?php print $html['selected']['limit']['10'] ?>>10</option>
					<option<?php print $html['selected']['limit']['20'] ?>>20</option>
					<option<?php print $html['selected']['limit']['30'] ?>>30</option>
				</select>
				items/page from
				<select name="sa" title="Show items from this association">
					<option value="0" title="Any association">any assoc.</option>
<?php
		if ($associations['query'] = mysql_query('SELECT id, name, abbreviation FROM associations ORDER BY abbreviation', $info['sql_db_connect']) and mysql_num_rows($associations['query']) > 0) {
			while ($associations['results'] = mysql_fetch_assoc($associations['query'])) {
?>
					<option value="<?php print $associations['results']['id'] ?>"<?php print $html['selected']['sa'][$associations['results']['id']] ?> title="<?php print htmlentities($associations['results']['name']) ?>"><?php print $associations['results']['abbreviation'] ?></option>
<?php
			}
		}
?>
				</select><?php print $error['to'] ?> 
				&amp; sort
				<select name="sort" title="Sort items in this manner">
					<option value="DESC"<?php print $html['selected']['sort']['DESC'] ?> title="Descendingly">desc.</option>
					<option value="ASC"<?php print $html['selected']['sort']['ASC'] ?> title="Ascendingly">asc.</option>
				</select>
				by
				<select name="by" title="Sort by this column">
					<option<?php print $html['selected']['by']['date'] ?>>date</option>
					<option value="abbreviation" title="Association"<?php print $html['selected']['by']['association'] ?>>assoc.</option>
					<option<?php print $html['selected']['by']['headline'] ?>>headline</option>
				</select>
				<input type="hidden" name="page" value="1" />
				<a href="<?php print $_SERVER['PHP_SELF'] ?>#content" title="Reset news filter" class="char_button">&times;</a>
			</p>
		</fieldset>
	</form>
<?php
	if (mysql_num_rows($news['query']) > 0) {
?>
	<p><strong>Displaying items <?php print ($_GET['page']-1) * $_GET['limit'] + 1 ?>-<?php print min($pagination['total_items'],($_GET['page']-1) * $_GET['limit'] + $_GET['limit']) ?> of <?php print $pagination['total_items'] ?>.</strong></p>
	<ul id="news" class="no_first_highlight scrollable">
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
<?php
		if ($pagination['total_pages'] > 1) {
?>
	<div id="pagination">
		<ul>
<?php
			if ($_GET['page'] != 1) {
?>
			<li><a href="<?php print $pagination['links']['first'] ?>#content">&laquo; First</a></li>
<?php
				if ($_GET['page'] > 2) {
?>
				<li><a href="<?php print $pagination['links']['previous'] ?>#content">&lsaquo; Previous</a></li>
<?php
				}
			}
?>
			<li>
				<select onchange="location='<?php print $_SERVER['PHP_SELF'].$pagination['query_string'].'page=' ?>'+this.value+'#content'" style="width:70px;">
<?php
			for ($n = 1; $pagination['total_items'] > ($n-1) * $_GET['limit']; $n++) {
?>
					<option value="<?php print $n ?>"<?php print $html['selected']['page'][$n] ?>>Page <?php print $n ?></option>
<?php
			}
?>
				</select>
			</li>
<?php
			if ($_GET['page'] != $pagination['total_pages']) {
				if ($_GET['page'] < $pagination['total_pages']-1) {
?>
			<li><a href="<?php print $pagination['links']['next'] ?>#content">Next &rsaquo;</a></li>
<?php
				}
?>
			<li><a href="<?php print $pagination['links']['last'] ?>#content">Last &raquo;</a></li>
<?php
			}
?>
		</ul>
	</div>
<?php
		}
?>
<?php
	} else {
?>
	<p><strong>Displaying zero items.</strong></p>
	<p style="padding:25px;text-align:center;">There is currently no news to display. Try resetting (<a href="<?php print $_SERVER['PHP_SELF'] ?>#content" title="Reset news filter" class="char_button">&times;</a>) the news filter.</p>
<?php
	}
?>
</div>
<?php
	$layout->output_footer();
} else {
	$layout->redirector('Athena | News Error', 'A problem occurred while attempting to access news. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persist, please contact the <a href="/contact.php?to=1">Administrators of Athena</a>.');
}
?>
