<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate();

$set['sql'] = '	(
					SELECT	DISTINCT
							2 AS mode,
							c.id AS cid, c.prog_abbrev, c.course_code, c.course_name,
							n.id, n.num, n.date, n.descr, n.pages, n.distribution, n.filename,
							NULL AS coordinator,
							NULL AS pickup
					FROM	courses AS c, sets AS n
					WHERE	c.association_id = "'.$_SESSION['association']['association_id'].'" AND
							c.id = n.course_id AND
							n.id = "'.$_GET['id'].'" AND
							(n.received != "0" OR n.distribution = "1")
					LIMIT 1
				) UNION (
					SELECT	DISTINCT
							1 AS mode,
							c.id AS cid, c.prog_abbrev, c.course_code, c.course_name,
							n.id, n.num, n.date, n.descr, n.pages, n.distribution, n.filename,
							p.coordinator,
							u.date AS pickup
					FROM	(courses AS c, sets AS n, purchases AS p)
					LEFT	JOIN pickups AS u
							ON n.id = u.set_id AND u.purchase_id = p.id
					WHERE	c.id = n.course_id AND
							n.id = "'.$_GET['id'].'" AND
							n.course_id = p.course_id AND
							p.student_id = "'.$_SESSION['student']['student_id'].'" AND
							p.coordinator = true AND
							(n.received != "0" OR n.distribution = "1")
					LIMIT 1
				) UNION (
					SELECT	DISTINCT
							0 AS mode,
							c.id AS cid, c.prog_abbrev, c.course_code, c.course_name,
							n.id, n.num, n.date, n.descr, n.pages, n.distribution, n.filename,
							p.coordinator,
							u.date AS pickup
					FROM	(courses AS c, sets AS n, purchases AS p)
					LEFT	JOIN pickups AS u
							ON n.id = u.set_id AND u.purchase_id = p.id
					WHERE	c.id = n.course_id AND
							n.id = "'.$_GET['id'].'" AND
							n.course_id = p.course_id AND
							p.student_id = "'.$_SESSION['student']['student_id'].'" AND
							p.coordinator = false AND
							(n.received != "0" OR n.distribution = "1")
					LIMIT 1
				)
				LIMIT 1';
if ( is_numeric($_GET['id']) and $set['query'] = mysql_query($set['sql']) and mysql_num_rows($set['query']) == 1 ) {
	$set['results'] = mysql_fetch_assoc($set['query']);
	
	// Delete a comment.
	if ( is_numeric($_GET['did']) ) {
		$delete_comment['sql'] = 'DELETE FROM sets_discussions WHERE id = '.$_GET['did'].' AND student_id = '.$_SESSION['student']['student_id'];
		if ( $delete_comment['query'] = mysql_query($delete_comment['sql'], $info['sql_db_connect']) ) {
			$layout->redirector('Successful ...', 'Your comment has been succussfully deleted. Now redirecting you to ...', $_SERVER['PHP_SELF'].'?id='.$_GET['id'].'#discuss');
		} else {
			$layout->redirector('Sorry ...', 'There was an error and your comment could not be deleted. Please go back and try again.<br />' . mysql_error());
		}
	}

	// Post a comment.
	if ( $_POST ) {
		if ( str_replace(array("\r\n","\n","\r"),'',$_POST['post']) != '' and strlen($_POST['post']) <= 1000 ) {
			$discuss['sql'] = '	INSERT	INTO sets_discussions
										(set_id, student_id, type, post, created)
								VALUES	('.$_GET['id'].', '.$_SESSION['student']['student_id'].', "'.$_POST['type'].'", "'.trim(htmlentities($_POST['post'])).'", NOW())';
			if ( $discuss['query'] = mysql_query($discuss['sql'], $info['sql_db_connect']) ) {
				$layout->redirector('Successful ...', 'Your comment has been succussfully posted. Now redirecting you to ...', $_SERVER['PHP_SELF'].'?id='.$_GET['id'].'#discuss');
			} else {
				$layout->redirector('Sorry ...', 'There was an error and your comment could not be posted. Please go back and try again.<br />' . mysql_error());
			}
		} else {
			$error = true;
			$selected[$_POST['type']] = ' selected="selected"';
		}
	}

	$title = 'More Information on '.$set['results']['prog_abbrev'].$set['results']['course_code'].' Set #'.$set['results']['num'];
	if ($set['results']['mode'] == 2) {
		$layout->output_header($title, 'association');
	} else {
		$layout->output_header($title, 'student');
	}
?>
<ul id="breadcrumb">
	<li><?php print $title ?></li>
<?php
	if ($set['results']['mode'] == 2) {
?>
	<li> &laquo; <a href="/associations/ntc_summary.php">NTC Summary</a></li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
<?php
	} else {
		if ($set['results']['coordinator'] == 1) {
?>
	<li> &laquo; <a href="/students/coordinate/?cid=<?php print $set['results']['cid'] ?>">Coordinate <?php print $set['results']['prog_abbrev'].$set['results']['course_code']; ?></a></li>
<?php
		}
?>
	<li> &laquo; <a href="/students/">Student Home</a></li>
<?php
	}
?>
</ul>
<h1><?php print $title ?></h1>
<div class="psuedo_p top-down">
	<ul>
		<li>
			<label>Course name</label>
			<strong><?php print $set['results']['course_name'] ?></strong>
		</li>
		<li>
			<label>Set number</label>
			<strong><?php print $set['results']['num'] ?></strong>
		</li>
		<li>
			<label>Set date</label>
			<strong><?php print date('F jS, Y',strtotime($set['results']['date'])) ?></strong>
		</li>
<?php
	if ( !empty($set['results']['descr']) ) {
?>
		<li>
			<label>Description</label>
			<strong><?php print nl2br($set['results']['descr']) ?></strong>
		</li>
<?php
	}
	if ( $set['results']['distribution'] == 0 ) {
?>
		<li>
			<label>Pages</label>
			<strong><?php print $set['results']['pages'] ?></strong>
		</li>
		<li>
			<label>Status</label>
			<strong><?php if ( is_null($set['results']['pickup']) ) { print 'This set is available for pickup'; } else {print 'I picked up this set on '.date('Y/m/d \a\t H:i',strtotime($set['results']['pickup'])); } ?></strong>
		</li>
<?php
	} else {
?>
		<li>
			<label>Filename</label>
			<strong><?php print $set['results']['filename'] ?>  </strong>
		</li>
		<li>
			<label>Filesize</label>
			<strong><?php print round(filesize($info['download_path'].'/'.$_GET['id'].'_'.$set['results']['filename'])/1024,0); ?> KB</strong>
		</li>
		<li>
			<label>Status</label>
			<strong><a href="/students/download.php?id=<?php print $set['results']['id']; ?>"><strong>This set is available online</strong> (click here to download)</a></strong>
		</li>
<?php
	}
?>
	</ul>
</div>
<h2 id="discuss">Discuss</h2>
<?php
	if ($set['results']['mode'] < 2) {
		if ( $error ) {
?>
<p id="error_paragraph">Sorry. Your post is either too short, or longer than 1000 characters.<br />
	Please alter your post and try again.</p>
<?php
		}
?>
<form method="post" action="<?php print $_SERVER['PHP_SELF'].'?id='.$_GET['id'].'#discuss'; ?>">
	<fieldset>
		<fieldset class="psuedo_p top-down">
			<p>To discuss this set and post your comment, question, addition or correction, complete the following, and then click on <strong>Discuss</strong>.</p>
			<ul>
				<li>
					<label for="post">Discuss</label>
					<textarea name="post" id="post" rows="5" cols="30" onkeyup="char_remaining(this,1000,'char_remaining');" style="width:290px;height:75px;margin:0 0 2px 0;"><?php print stripslashes(htmlentities($_POST['post'])) ?></textarea><br />
					(<span id="char_remaining">1000</span> characters remaining)
				</li>
				<li>
					<label for="type">Type</label>
					<select name="type" id="type">
						<option value="0"<?php print $selected[0] ?>>General comment or question</option>
						<option value="1"<?php print $selected[1] ?>>Suggest an addition</option>
						<option value="2"<?php print $selected[2] ?>>Suggest a correction</option>
					</select>
				</li>
			</ul>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="submit" value="Discuss &raquo;" class="forward" />
		</fieldset>
	</fieldset>
</form>
<?php
	}
?>
<div id="discussions">
<?php
	$discussions['sql'] = '	SELECT	s.id AS sid, s.first_name, s.last_name,
									sd.id, sd.type, sd.post, sd.created
							FROM	sets_discussions AS sd, students AS s
							WHERE	sd.set_id = '.$_GET['id'].' AND
									sd.student_id = s.id
							ORDER	BY sd.created DESC';
	if ( $discussions['query'] = mysql_query($discussions['sql'], $info['sql_db_connect']) and mysql_num_rows($discussions['query']) > 0 ) {
?>
<?php
		$discussions['results'] = mysql_fetch_assoc($discussions['query']);
		$type = array('Comment', 'Addition', 'Suggestion');
		do {
?>
	<p class="p<?php print $discussions['results']['type'] ?>">
		<span class="posted_by"><?php print $type[$discussions['results']['type']] ?> posted by <strong><?php  if ( $discussions['results']['sid'] == $_SESSION['student']['student_id'] ) { print 'you'; } else { print $discussions['results']['first_name'].' '.$discussions['results']['last_name']; } ?></strong> on <?php print date('Y/m/d \a\t H:i',strtotime($discussions['results']['created'])) ?><?php if ( $discussions['results']['sid'] == $_SESSION['student']['student_id'] ) { ?> <a href="<?php print $_SERVER['PHP_SELF'].'?id='.$_GET['id'].'&did='.$discussions['results']['id'].'#discuss'; ?>" onclick="return confirm('Are you sure you want to delete your comment?');">(delete your comment)</a><?php } ?></span>
		<?php print nl2br($discussions['results']['post']) ?><br /><br />
	</p>
<?php
		} while ( $discussions['results'] = mysql_fetch_assoc($discussions['query']) );
	} else {
?>
	<p id="warning_paragraph">So far, there have been no comments posted.<?php if ($set['results']['mode'] < 2) { ?><br />Use the above to post your comment. <?php } ?></p>
<?php
	}
?>
</div>
<script type="text/javascript">
//<![CDATA[
char_remaining (document.getElementById('post'),1000,'char_remaining');
//]]>
</script>
<?php
	$layout->output_footer();
} else {
	$layout->redirector('Unauthorized ...', 'You do not appear to have access to view this page.');
}
?>