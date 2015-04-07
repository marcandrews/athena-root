<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();

if ( $_GET['delete'] ) {
	$news['sql'] = 'DELETE FROM news WHERE id = '.$_GET['delete'].' AND association_id = '.$_SESSION['association']['association_id'].' LIMIT 1';
	if ($news['query'] = mysql_query($news['sql']) and mysql_affected_rows() == 1) {
		$layout->redirector('News Successfully Deleted ...', 'Athena has been updated. Now redirecting you to ...', '/associations/');
	}
}

$news['sql'] = 'SELECT * FROM news WHERE id = '.$_GET['edit'].' AND association_id = '.$_SESSION['association']['association_id'];
if ($_GET['edit'] and $news['query'] = mysql_query($news['sql']) and mysql_num_rows($news['query'])) {
	$mode				= 'edit';
	$news['results']	= mysql_fetch_assoc($news['query']);
	$html['title']		= 'Edit News';
	$html['descr']		= 'Use the following to edit your news.';
	$html['action']		= $_SERVER['PHP_SELF'].'?edit='.$_GET['edit'];
	$html['submit']		= 'Edit this news item';
	$html['success']	= 'News Successfully Edited ...';
} else {
	$mode				= 'add';
	$html['title']		= 'Add News';
	$html['descr']		= 'Use the following to add news to Athena.';
	$html['action']		= $_SERVER['PHP_SELF'];
	$html['submit']		= 'Add this news item';
	$html['success']	= 'News Successfully Added ...';
}

if ($_POST) {
	if (!preg_match('/^0$|^1$|^2$/',$_POST['recipients'])) {
		$error['recipients'] = error_inline('A recipient for your story is required.');
	}
	if (empty($_POST['headline'])) {
		$error['headline'] = error_inline('A headline is required.');
	}
	if (empty($_POST['story']) or strlen($_POST['story']) > 500) {
		$error['story'] = error_inline('Your story is either too short or longer than 500 characters.');
	}
	if (!is_array($error)) {
		if ( $mode == 'edit' ) {
			$news['sql'] = '	UPDATE	news
								SET		recipients = "'.$_POST['recipients'].'",
										headline = "'.trim($_POST['headline']).'",
										story = "'.trim($_POST['story']).'",
										modified = NOW()
								WHERE	id = "'.$_GET['edit'].'" AND
										association_id = "'.$_SESSION['association']['association_id'].'"';
		} else {
			$news['sql'] = '	INSERT INTO	news (association_id, recipients, headline, story, created)
								VALUES		("'.$_SESSION['association']['association_id'].'", "'.$_POST['recipients'].'", "'.$_POST['headline'].'", "'.trim(htmlentities($_POST['story'], ENT_QUOTES, 'UTF-8')).'", NOW())';
		}
		if ($news['query'] = mysql_query($news['sql'])) {
			$layout->redirector($html['success'], 'Athena has been updated. Now redirecting you to ...', '/associations/');
		} else {
			$layout->redirector('Error', 'There was an error:<br /><em>'.mysql_error().'</em>');
		}
	}
	$selected[$_POST['recipients']] = ' selected="selected"';
} elseif (!$_POST and $mode == 'edit') {
	$_POST = $news['results'];
	$selected[$_POST['recipients']] = ' selected="selected"';
}

$layout->output_header($html['title'], 'association');
?>
<ul id="breadcrumb">
	<li><?php print $html['title'] ?></li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1><?php print $html['title'] ?></h1>
<?php general_error($error); ?>
<form method="post" action="<?php print $html['action']; ?>"> 
	<fieldset>
		<p><?php print $html['descr'] ?></p>
		<fieldset class="psuedo_p top-down">
			<ul>
				<li>
					<label for="recipients">Recipients</label>
					<select name="recipients" id="recipients" style="width:50%;"<?php if ( $error['recipients'] ) print ' class="text_error"'; ?>>
						<option value="0"<?php print $selected[0] ?>>Your students</option>
						<option value="1"<?php print $selected[1] ?>>Your coordinators</option>
						<option value="2"<?php print $selected[2] ?>>Your student association</option>
					</select><?php print $error['recipients']; ?>
				</li>
				<li>
					<label for="headline">Headline</label>
					<input type="text" name="headline" id="headline" value="<?php print $_POST['headline']; ?>" maxlength="255" class="text<?php if ( $error['headline'] ) print ' error'; ?>" style="width:75%;" /><?php print $error['headline']; ?>
				</li>
				<li>
					<label for="story">Story</label>
					<textarea name="story" id="story" cols="50" rows="10" onkeyup="char_remaining(this,500,'char_remaining');" style="width:75%;height:125px;vertical-align:text-top;"<?php if ( $error['story'] ) print ' class="error"'; ?>><?php print stripslashes($_POST['story']) ?></textarea><?php print $error['story']; ?><br />
					(<span id="char_remaining">500</span> characters remaining)
				</li>
<?php
if ($news['query']) {
	if ($_POST['modified'] != 0) {
?>
				<li>
					<label>Modified</label>
					<strong><?php print date('Y/m/d @ H:i',strtotime($_POST['modified'])) ?></strong>
				</li>
<?php
	}
?>
				<li>
					<label>Created</label>
					<strong><?php print date('Y/m/d @ H:i',strtotime($_POST['created'])) ?></strong>
				</li>
<?php
}
?>
			</ul>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls"> 
			<input type="button" class="back" value="&lsaquo; Cancel" onclick="location.href='/associations/'" />
			<input type="submit" class="forward" name="step1" value="<?php print $html['submit'] ?> &rsaquo;" />
		</fieldset>
	</fieldset>
</form> 
<script type="text/javascript">
//<![CDATA[
char_remaining (document.getElementById('story'), 500, 'char_remaining');//]]>
</script>
<?php
$layout->output_footer();
?>