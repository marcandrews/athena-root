<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');



$_GET['bid'] = (int) $_GET['bid'];
$_GET['aid'] = (int) $_GET['aid'];
$_GET['cid'] = (int) $_GET['cid'];

$user_authentication->validate_student();

$bulkmail['sql'] = "	SELECT	*
						FROM	tasks_bulkmail AS b, tasks_bulkmail_queue AS q, students AS s
						WHERE	b.id = {$_GET['bid']} AND
								b.id = q.task_bulkmail_id AND
								q.student_id = s.id AND
								s.id = {$_SESSION['student']['student_id']}";
if ($_GET['bid'] and $bulkmail['query'] = mysql_query($bulkmail['sql']) and mysql_num_rows($bulkmail['query']) == 1) {
	$bulkmail['results'] = mysql_fetch_assoc($bulkmail['query']);
	$stage = 4;
} elseif ($_POST) {
	$_POST['subject'] = htmlentities(stripslashes($_POST['subject']));
	$_POST['message'] = htmlentities(stripslashes($_POST['message']));
	if (is_array($_POST['courses'])) {
		$course_ids_sql = '(c.id = '.implode(' OR c.id = ', $_POST['courses']).') AND ';
	} else {
		$error['courses'] = error_inline('You must select at least one course.');
	}
	if (str_word_count($_POST['subject']) < 1) {
		$error['subject'] = error_inline('A subject is required.');
	}
	if (str_word_count($_POST['message']) < 5) {
		$error['message'] = error_inline('Your message must be at least five words long.');
	}
	if ($_POST['honour']) {
		$honour_sql = 'receive_emails = true AND ';
	}
	if (!is_array($error)) {
		if ($_POST['submit'] == 'Send a Bulk Email ›') {
			$stage = 3;
		} else {
			$stage = 2;
		}
	}
}

$courses['sql'] = "	(
						SELECT	a.id AS aid, a.name, a.abbreviation, a.email, 
								c.id, CONCAT(c.prog_abbrev, c.course_code) AS course_code, c.course_name
						FROM	associations AS a, courses AS c
						WHERE	a.id = {$_GET['aid']} AND
								a.administrator = {$_SESSION['student']['student_id']} AND
								a.id = c.association_id AND
								{$course_ids_sql}
								c.year = ".$common_variables->current_school_year()." AND
								c.semester <= ".$common_variables->current_semester()."
					) UNION (
						SELECT	a.id AS aid, a.name, a.abbreviation, a.email, 
								c.id, CONCAT(c.prog_abbrev, c.course_code) AS course_code, c.course_name
						FROM	associations AS a, courses AS c, purchases AS p
						WHERE	a.id = c.association_id AND
								c.id = '{$_GET['cid']}' AND
								c.year = ".$common_variables->current_school_year()." AND
								c.semester <= ".$common_variables->current_semester()." AND
								c.id = p.course_id AND
								p.student_id = {$_SESSION['student']['student_id']} AND
								p.coordinator = true
						LIMIT 1
					)
					ORDER BY course_code, course_name";
if ($courses['query'] = mysql_query($courses['sql']) and mysql_num_rows($courses['query']) > 0) {
	$courses['results'] = mysql_fetch_assoc($courses['query']);	
	if (mysql_num_rows($courses['query']) > 1) {
		$html['form_action'] = "{$_SERVER['PHP_SELF']}?aid={$_GET['aid']}";
		$html['page_description'] = 'To send a bulk email to those who have purchased <strong>'.$courses['results']['abbreviation'].'</strong> NTCs, complete the following, and then click on <strong>Preview</strong>.';
		$html['cancel_href'] = "/students/administrate/?id={$_GET['aid']}";
	} else {
		$html['form_action'] = "{$_SERVER['PHP_SELF']}?cid={$_GET['cid']}";
		$html['page_description'] = 'To send a bulk email to those who have purchased <strong>'.$courses['results']['course_code'].'</strong> NTCs, complete the following, and then click on <strong>Preview</strong>.';
		$html['cancel_href'] = "/students/coordinate/?cid={$_GET['cid']}";
	}
	$html['from'] = "{$courses['results']['abbreviation']} &lt;{$courses['results']['email']}&gt;";
} else {
	die(mysql_error());
}

switch ($stage) {
	case 4:
	break;
	case 3:
		/* Begin the query. */
		mysql_query('START TRANSACTION');

		/* Lock the bulk mailer task. */
		if (!mysql_query("UPDATE task_manager SET locked = NOW() WHERE id = 3")) {
			die('Unable to lock bulk mailer task.');
		}

		/* Create the bulk email. */
		$bulkmail_insert['sql'] = "	INSERT INTO task_bulkmail
									SET	association_id = {$courses['results']['aid']},
										student_id = {$_SESSION['student']['student_id']},
										subject = '{$_POST['subject']}',
										content = '{$_POST['message']}',
										honor_receive_emails = {$_POST['honour']},
										clear_when_complete = true,
										active = true";
		if (!mysql_query($bulkmail_insert['sql']) or mysql_affected_rows() == 0) {
			die('Unable to insert the bulkmail.');
		}
		$bulkmail_insert['id'] = mysql_insert_id();

		/* Fill the queue with bulk mail recipients. */
		do {
			$queue['sql'] = "	INSERT INTO task_bulkmail_queue (task_bulkmail_id, student_id, delete_after_visit, created)
								SELECT	{$bulkmail_insert['id']}, s.id, true, NOW()
								FROM	students AS s, purchases AS p
								WHERE	p.course_id = {$courses['results']['id']} AND
										p.student_id = s.id AND
										{$honour_sql}
										s.validated = true";
			if (!mysql_query($queue['sql'])) {
				die('Unable to fill the queue.');
			}
		} while ($courses['results'] = mysql_fetch_assoc($courses['query']));

//		/* Commit the changes to the database. */
//		if (mysql_query('COMMIT')) {
//			header("Location: {$_SERVER['PHP_SELF']}?bid={$bulkmail_insert['id']}");
//		} else {
//			die('Unable to commit changes to the database.');
//		}
	break;
	case 2:
		$layout->output_header("Coordinate {$courses['results']['course_code']} | Send a Bulk Email | Preview Your Bulk Email", 'student');
?>
<ul id="breadcrumb">
	<li>Preview Your Bulk Email</li>
	<li> &laquo; <a href="javascript:history.go(-1)">Send a Bulk Email</a></li>
	<li> &laquo; <a href="/students/coordinate/?cid=<?php print $_GET['cid'] ?>">Coordinate <?php print $courses['results']['prog_abbrev'].$courses['results']['course_code']; ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Preview Your Bulk Email</h1>
<form method="post" action="<?php print $html['form_action'] ?>">
	<fieldset>
		<p>This is a preview of your email that will be sent. If you are content with this email, click on <strong>Send a Bulk Email</strong> to send this bulk email; otherwise click on <strong>Make Changes</strong> to edit this bulk email.</p>
		<fieldset class="psuedo_p top-down">
			<ul>
				<li>
<?php
		if (mysql_num_rows($courses['query']) > 1) {
?>
					<label>To</label>
					Everyone who has purchased:<br />
<?php
			do {
?>
					<input type="hidden" name="courses[]" value="<?php print $courses['results']['id'] ?>" />
					<strong><?php print "{$courses['results']['course_code']}: {$courses['results']['course_name']}"; ?></strong><br />
<?php
			} while ($courses['results'] = mysql_fetch_assoc($courses['query']));
		} else {
?>
					<label>To</label>
					<input type="hidden" name="courses[]" value="<?php print $courses['results']['id'] ?>" />
					Everyone who has purchased:<br />
					<strong><?php print "{$courses['results']['course_code']}: {$courses['results']['course_name']}"; ?></strong>
<?php
		}
?>
				</li>
				<li>
					<label>From</label>
					<strong><?php print $html['from']; ?></strong>
				</li>
				<li>
					<label>Reply to</label>
					<strong><?php print "{$_SESSION['student']['first_name']} {$_SESSION['student']['last_name']} &lt;{$_SESSION['student']['email']}&gt;"; ?></strong>
				</li>
				<li>
					<label>Subject</label>
					<input type="hidden" name="subject" value="<?php print $_POST['subject'] ?>" />
					<strong><?php print $_POST['subject'] ?></strong>
				</li>
				<li> 
					<label>Message</label>
					<input type="hidden" name="message" value="<?php print $_POST['message'] ?>" />
					<strong><?php print nl2br($_POST['message']) ?></strong>
				</li>
				<li>
					<label class="major">Honour your students' decision not to receive emails?
						<input type="checkbox" name="honour" value="1"<?php if ($_POST['honour']) print ' checked="checked"'; ?> disabled="disabled" /></label>
				</li>
			</ul>
	</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls"> 
			<input type="button" class="back" value="&lsaquo; Make Changes" onclick="history.go(-1);" />
			<input type="submit" class="forward" name="submit" value="Send a Bulk Email &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
		$layout->output_footer();
	break;
	case 1:
	default:
		$layout->output_header("Coordinate {$courses['results']['course_code']} | Send a Bulk Email", 'student');
?>
<ul id="breadcrumb">
	<li>Send a Bulk Email</li>
	<li> &laquo; <a href="/students/coordinate/?cid=<?php print $_GET['cid'] ?>">Coordinate <?php print $courses['results']['prog_abbrev'].$courses['results']['course_code']; ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Send a Bulk Email</h1>
<?php general_error($error); ?>
<form method="post" action="<?php print $html['form_action'] ?>"> 
	<fieldset>
		<p><?php print $html['page_description'] ?></p>
		<fieldset class="psuedo_p top-down">
			An asterisk (<span title="Required" class="required_field" >*</span>) indicates a required field.
			<ul>
				<li>
<?php
		if (mysql_num_rows($courses['query']) > 1) {
?>
					<label for="to">To<span title="Required" class="required_field" >*</span></label>
					Everyone who has purchased:<br />
					<select name="courses[]" id="to" multiple="multiple"<?php if ($error['courses']) print ' class="error"'; ?>>
<?php
			do {
?>
						<option value="<?php print $courses['results']['id'] ?>" <?php if (@in_array($courses['results']['id'], $_POST['courses'])) print ' selected="selected"'; ?>><?php print "{$courses['results']['course_code']}: {$courses['results']['course_name']}" ?></option>
<?php
			} while ($courses['results'] = mysql_fetch_assoc($courses['query']));
?>
					</select><?php print $error['courses'] ?><br />
					CTRL-click to select multiple courses.
<?php
		} else {
?>
					<label>To</label>
					<input type="hidden" name="courses[]" value="<?php print $courses['results']['id'] ?>" />
					Everyone who has purchased:<br />
					<strong><?php print "{$courses['results']['course_code']}: {$courses['results']['course_name']}"; ?></strong>
<?php
		}
?>
				</li>
				<li>
					<label>From</label>
					<strong><?php print $html['from']; ?></strong>
				</li>
				<li>
					<label>Reply to</label>
					<strong><?php print "{$_SESSION['student']['first_name']} {$_SESSION['student']['last_name']} &lt;{$_SESSION['student']['email']}&gt;"; ?></strong>
				</li>
				<li>
					<label for="subject">Subject<span title="Required" class="required_field" >*</span></label>
					<input type="text" name="subject" id="subject" value="<?php print $_POST['subject'] ?>" maxlength="60" class="text<?php if ( $error['subject'] ) print ' error'; ?>" style="width:90%;" /><?php print $error['subject'] ?>
				</li>
				<li> 
					<label for="message">Message<span title="Required" class="required_field" >*</span></label>
					<textarea name="message" id="message" cols="50" rows="10"<?php if ( $error['message'] ) print ' class="error"'; ?> style="width:90%;height:200px;vertical-align:text-top;"><?php print $_POST['message'] ?></textarea><?php print $error['message']; ?>
				</li>
				<li>
					<label class="major">Honour your students' decision not to receive emails?
						<input type="checkbox" name="honour" value="1" /></label>
				</li>
			</ul>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls"> 
			<input type="button" class="back" value="&lsaquo; Cancel" onclick="location.href='<?php print $html['cancel_href'] ?>'" />
			<input type="submit" class="forward" name="submit" value="Preview &rsaquo;" />
		</fieldset>
		<h2>Quick tags</h2>
		<div class="psuedo_p" style="float:left; width:479px;">
			<p>You may use the following <em>Quick Tags</em> in your email; they will be converted when the email is sent.</p>
			<dl>
				<dt>{a_name}</dt>
				<dd>The student association's name.</dd>
				<dt>{a_abbreviation}</dt>
				<dd>The student association's abbreviation.</dd>
				<dt>{course_code}</dt>
				<dd>The course's code.</dd>
				<dt>{course_name}</dt>
				<dd>The course's name.</dd>
				<dt>{s_first_name}</dt>
				<dd>The student's first name.</dd>
				<dt>{s_last_name}</dt>
				<dd>The student's last name.</dd>
			</dl>
		</div>
	</fieldset>
</form>
<?php
		$layout->output_footer();
}
?>