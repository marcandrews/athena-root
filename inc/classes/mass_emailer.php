<?php
class mass_emailer {

	private $aid = 0;
	private $cid = 0;
	private $bid = 0;
	private $query_string;

	function __construct() {
		if (isset($_GET['aid'])) {
			$this->aid = (int) $_GET['aid'];
			$this->query_string = 'aid='.$_GET['aid'];
		}
		if (isset($_GET['cid'])) {
			$this->cid = (int) $_GET['cid'];
			$this->query_string = 'cid='.$_GET['cid'];
		}
		if (isset($_GET['bid'])) $this->bid = (int) $_GET['bid'];
	}



	function display_queue() {
		$bulkmail['sql'] = "SELECT b.id, b.created, b.subject, b.content, (SELECT COUNT(id) FROM task_bulkmail_queue WHERE task_bulkmail_id = b.id) AS recipients FROM task_bulkmail AS b WHERE student_id = {$_SESSION['student']['student_id']}";
		if ($bulkmail['query'] = mysql_query($bulkmail['sql'])) {
?>
<script type="text/javascript">
$(function(){
	$('div.subject_content').hover(
		function () {
			$(this).css({ height: 'auto' });
		}, 
		function () {
			$(this).animate({ height: '1.4em' }, 500);
		}
	);
});
</script>
<div class="psuedo_p">
	<p>Use the following to send a mass email to everyone who has purchased <?php print ($this->aid ? 'NTCs from this association' : 'this NTC') ?>.
		To create, send or cancel a mass email, click on the plus sign (<strong>+</strong>),
		right-point double angle quotation mark (<strong>&raquo;</strong>), or multiplication
		sign (<strong>&times;</strong>), respectively.</p>
	<table>
		<thead>
			<tr>
				<th>Date</th>
				<th style="width:100%;">Email</th>
				<th style="text-align:center;">Remaining</th>
				<th>&nbsp;</th>
				<th><a href="/students/send_bulkmail.php?<?php print $this->query_string ?>" class="char_button" title="Create a bulk email">+</a></th>
			</tr>
		</thead>
		<tbody>
<?php
			if (mysql_num_rows($bulkmail['query']) > 0) {
				while ($bulkmail['results'] = mysql_fetch_assoc($bulkmail['query'])) {
?>
			<tr>
				<td><abbr title="<?php print date('Y/m/d @ H:i', strtotime($bulkmail['results']['created'])) ?>"><?php print date('Y/m/d', strtotime($bulkmail['results']['created'])) ?></abbr></td>
				<td><div class="subject_content" style="height:1.4em; overflow:hidden;"><?php print $bulkmail['results']['subject'] ?><span style="color:#BBB;"> &ndash; <?php print $bulkmail['results']['content'] ?></span></div></td>
				<td style="text-align:center;"><?php print $bulkmail['results']['recipients'] ?></td>
				<td><?php if ($bulkmail['results']['recipients'] > 0) { ?><a href="/students/send_bulkmail.php?cid=<?php print $this->cid ?>&amp;bid=<?php print $bulkmail['results']['id'] ?>&amp;send=1" class="char_button" title="Send this bulk email">&raquo;</a><?php } else { ?>&nbsp;<?php } ?></td>
				<td><a href="/students/send_bulkmail.php?cid=<?php print $this->cid ?>&amp;bid=<?php print $bulkmail['results']['id'] ?>&amp;delete=1" class="char_button" title="Cancel this bulk email" onclick="return confirm('Are you sure you want to delete the bulk email &ldquo;<?php print $bulkmail['results']['subject'] ?>?&rdquo;');">&times;</a></td>
			</tr>
<?php
				}
			} else {
?>
			<tr>
				<td colspan="5" style="padding:25px;text-align:center;">
					There are currently	no mass emails in your queue.<br />
					Would you like to <a href="/students/send_bulkmail.php?<?php print $this->query_string ?>">create a mass email</a>?
				</td>
			</tr>
<?php 
			}
?>
		</tbody>
	</table>
</div>
<?php
		} else {
?>
<p class="warning">A problem occurred while attempting to access your mass emailer queue. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persist, please contact the <a href="/contact.php?to=1">Administrators of Athena</a>.</p>
<?php
		}
	}	
	


	function create() {
		global $common_variables;
		global $layout;
	
		$bulkmail['sql'] = "SELECT id FROM task_bulkmail WHERE id = '{$this->bid}' AND student_id = '{$_SESSION['student']['student_id']}' LIMIT 1";
		if (is_int($this->bid) and $bulkmail['query'] = mysql_query($bulkmail['sql']) and mysql_num_rows($bulkmail['query']) == 1) {
			if ($_GET['send']) {
				$bulkmail['results'] = mysql_fetch_assoc($bulkmail['query']);
				$stage = 4;
			} elseif ($_GET['delete']) {
				$stage = 5;
			}
		} elseif ($_POST) {
			$_POST['subject'] = htmlentities(stripslashes($_POST['subject']), ENT_QUOTES);
			$_POST['message'] = htmlentities(stripslashes($_POST['message']), ENT_QUOTES);
			if (!is_array($_POST['courses'])) {
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
				if (strpos($_POST['submit'], 'Send this mass email') === 0) {
					$stage = 3;
				} else {
					$stage = 2;
				}
			}
		} else {
			$_POST['honour'] = 1;
		}
	
		$courses['sql'] = "	(
								SELECT	a.id AS aid, a.name, a.abbreviation, a.email, 
										c.id, CONCAT(c.prog_abbrev, c.course_code) AS course_code, c.course_name,
										1 AS mode
								FROM	associations AS a, courses AS c
								WHERE	a.id = {$this->aid} AND
										a.administrator = {$_SESSION['student']['student_id']} AND
										a.id = c.association_id AND
										c.year = ".$common_variables->current_school_year()." AND
										c.semester <= ".$common_variables->current_semester()."
							) UNION (
								SELECT	a.id AS aid, a.name, a.abbreviation, a.email, 
										c.id, CONCAT(c.prog_abbrev, c.course_code) AS course_code, c.course_name,
										0 AS mode
								FROM	associations AS a, courses AS c, purchases AS p
								WHERE	a.id = c.association_id AND
										c.id = '{$this->cid}' AND
										c.year = ".$common_variables->current_school_year()." AND
										c.semester = ".$common_variables->current_semester()." AND
										c.id = p.course_id AND
										p.student_id = {$_SESSION['student']['student_id']} AND
										p.coordinator = true
								LIMIT 1
							)
							ORDER BY course_code, course_name";
		if ($courses['query'] = mysql_query($courses['sql']) and mysql_num_rows($courses['query']) > 0) {
			$courses['results'] = mysql_fetch_assoc($courses['query']);	
			$mode = $courses['results']['mode'];
			if (mysql_num_rows($courses['query']) > 1) {
				$html['page_description'] = 'To send a bulk email to those who have purchased <span class="small-caps">'.ucwords(strtolower($courses['results']['abbreviation'])).'</span> NTCs, complete the following, and then click on <strong>Preview</strong>.';
				$html['origin'] = "/students/administrate/?id={$this->aid}";
				$html['origin_title'] = "Administrate {$courses['results']['abbreviation']}";
			} else {
				$html['page_description'] = 'To send a bulk email to those who have purchased <strong>'.$courses['results']['course_code'].'</strong> NTCs, complete the following, and then click on <strong>Preview</strong>.';
				$html['origin'] = "/students/coordinate/?cid={$this->cid}";
				$html['origin_title'] = "Coordinate {$courses['results']['prog_abbrev']}{$courses['results']['course_code']}";
			}
			$html['from'] = '<span class="small-caps">'.ucwords(strtolower($courses['results']['abbreviation'])).'</span> &lt;'.$courses['results']['email'].'&gt;';
		} elseif ($courses['query'] and mysql_num_rows($courses['query']) == 0) {
			$layout->redirector('Athena | Unauthorized', 'You are not authorized to access this section of Athena.', '/students/');
		} else {
			$layout->redirector('Athena | Error', 'A problem occurred while attempting to access the database. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.');
		}
		
		switch ($stage) {
			case 5:
				if (mysql_query("DELETE FROM task_bulkmail WHERE id = '{$this->bid}'") and mysql_affected_rows() == 1) {
					$layout->redirector('Bulk Email Deleted', 'Your bulk email was deleted successfully. Now redirecting you to ...', $html['origin']);
				} else {
					$layout->redirector('Athena | Error', 'A problem occurred while attempting to delete your bulk email. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.');
				}
			break;
			case 4:
				require($_SERVER['DOCUMENT_ROOT'].'inc/tasks/bulkmail.php');
				if (function_exists('task')) {
					$task_result = task($bulkmail['results']['id']);
					if ($task_result !== true and (int)$task_result > 0) {
						header("Refresh: 10; url={$_SERVER['PHP_SELF']}?".$this->query_string."&bid={$bulkmail['results']['id']}&send=1");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>Sending Bulk Email</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="Content-Language" content="en-ca" />
<link rel="shortcut icon" href="/i/favicon.ico" />
<link href="/css/athena.css" rel="stylesheet" type="text/css" media="screen" />
<link href="/redirector.css" rel="stylesheet" type="text/css" media="screen" />
</head>
<body>
<div id="wrapper">
	<div id="margin-top"></div>
	<div id="container">
		<h1>Sending Bulk Email</h1>
		<p>Given the number of recipients, Athena is currently sending your bulk email
			in batches. <strong><?php print $task_result ?> email(s) remain to be sent</strong>,
			so please be patient while Athena finishes sending your bulk email.<br />
			<br />
			&rsaquo; <a href="<?php print $html['origin'] ?>">Stop waiting, and let Athena's internal bulk mailer send these emails.</a><br />
			&rsaquo; <a href="<?php print "{$_SERVER['PHP_SELF']}?".$this->query_string."&amp;bid={$bulkmail['results']['id']}&amp;delete=1" ?>">Cancel this bulk email.</a></p>
	</div>
	<div id="margin-bottom"></div>
</div>
</body>
</html>
<?php
					} elseif ($task_result === true) {
						mysql_query("UPDATE task_manager SET locked = 0 WHERE id = 3 AND enabled = true LIMIT 1");
						mysql_query("DELETE FROM task_bulkmail WHERE id = {$bulkmail['results']['id']}");
						$layout->redirector('Bulk Email Sent', 'Your bulk email was sent successfully. Now redirecting you to ...', $html['origin']);
					} else {
						$layout->redirector('Athena | Error', 'A problem occurred while attempting to send your bulk email. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.');
					}
				} else {
					$layout->redirector('Athena | Error', 'A problem occurred while attempting to access the bulk emailer. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.');
				}
			break;
			case 3:
				/* Begin the query. */
				mysql_query('START TRANSACTION');
		
				/* Lock the bulk mailer task. */
				if (!mysql_query("UPDATE task_manager SET locked = NOW() WHERE id = 3 AND enabled = true LIMIT 1")) {
					die('Unable to lock bulk mailer task.');
				}
		
				/* Create the bulk email. */
				$bulkmail_insert['sql'] = "	INSERT INTO task_bulkmail
											SET	association_id = '{$courses['results']['aid']}',
												student_id = '{$_SESSION['student']['student_id']}',
												reply_to_student = '{$_POST['reply_to_student']}',
												subject = '{$_POST['subject']}',
												content = '{$_POST['message']}',
												honor_receive_emails = '{$_POST['honour']}',
												clear_when_complete = false,
												active = true,
												created = NOW()";
				if (!mysql_query($bulkmail_insert['sql']) or mysql_affected_rows() == 0) {
					die('Unable to insert the bulkmail.<br />'.mysql_error());
				}
				$bulkmail_insert['id'] = mysql_insert_id();
		
				/* Fill the queue with bulk mail recipients. */
				do {
					if (in_array($courses['results']['id'], $_POST['courses'])) {			
						$queue['sql'] = "	INSERT IGNORE INTO task_bulkmail_queue (task_bulkmail_id, student_id, delete_after_visit, created)
											SELECT	{$bulkmail_insert['id']}, s.id, false, NOW()
											FROM	students AS s, purchases AS p
											WHERE	p.course_id = {$courses['results']['id']} AND
													p.student_id = s.id AND
													{$honour_sql}
													s.validated = true";
						if (!mysql_query($queue['sql'])) {
							die('Unable to fill the queue<br />'.mysql_error());
						}
					}
				} while ($courses['results'] = mysql_fetch_assoc($courses['query']));
		
				/* Commit the changes to the database. */
				if (mysql_query('COMMIT')) {
					header("Location: {$_SERVER['PHP_SELF']}?".$this->query_string."&bid={$bulkmail_insert['id']}&send=1");
				} else {
					$layout->redirector('Athena | Error', 'A problem occurred while attempting to create your bulk email. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.');
				}
			break;
			case 2:
				ob_start();
				do {
					if (in_array($courses['results']['id'], $_POST['courses'])) {
						$true_courses[] = $courses['results']['id'];
?>
						<input type="hidden" name="courses[]" value="<?php print $courses['results']['id'] ?>" />
						<strong><?php print "{$courses['results']['course_code']}: {$courses['results']['course_name']}"; ?></strong><br />
<?php
					}
				} while ($courses['results'] = mysql_fetch_assoc($courses['query']));
				$c = ob_get_clean();
		
				$true_courses_ids = implode(' OR p.course_id = ', $true_courses);
				$h = "SELECT DISTINCT s.id FROM purchases AS p, students AS s WHERE (p.course_id = {$true_courses_ids}) AND p.student_id = s.id AND s.validated = true AND s.receive_emails = true";
				$d = "SELECT DISTINCT s.id FROM purchases AS p, students AS s WHERE (p.course_id = {$true_courses_ids}) AND p.student_id = s.id AND s.validated = true";
				if ($_POST['honour']) {
					$num_of_recipients['query'] = mysql_query($h);
				} else {
					$num_of_recipients['query'] = mysql_query($d);
				}
				if (mysql_num_rows($num_of_recipients['query']) == 0) {
					$error['num_of_recipients'] = error_inline('There are no recipients for this bulk email.');
				}
		
				$layout->output_header("{$html['origin_title']} | Send a Bulk Email | Preview Your Bulk Email", 'student');
?>
<ul id="breadcrumb">
	<li>Preview Your Bulk Email</li>
	<li> &laquo; <a href="javascript:history.go(-1)">Send a Bulk Email</a></li>
<?php
				if ($this->aid) {
?>
	<li> &laquo; <a href="/students/administrate/bulk_emails.php?id=<?php print $this->aid ?>">Bulk Emails</a></li>
<?php
				}
?>
	<li> &laquo; <a href="<?php print $html['origin'] ?>"><?php print $html['origin_title'] ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Preview Your Bulk Email</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF'].'?'.$this->query_string ?>#content">
<?php if ($error['num_of_recipients']) { ?>
	<p id="error_paragraph">There are no recipients for this bulk email.<br />
		Make changes to this bulk email and try again.</p>
<?php } ?>
	<fieldset>
		<p>This is a preview of your email that will be sent. If you are content with this email, click on <strong>Send a Bulk Email</strong> to send this bulk email; otherwise click on <strong>Make Changes</strong> to edit this bulk email.</p>
		<fieldset class="psuedo_p top-down">
			<ul>
				<li>
					<label>To</label>
					Everyone who has purchased:
					<p>
<?php print $c ?>
					</p>
				</li>
				<li>
					<label># of Recipients</label>
					<strong><?php print mysql_num_rows($num_of_recipients['query']) ?></strong><?php print $error['num_of_recipients']; ?>
				</li>
				<li>
					<label>From</label>
					<strong><?php print $html['from']; ?></strong>
				</li>
<?php
				if ($_POST['reply_to_student']) {
?>
				<li>
					<label>Reply to</label>
					<input type="hidden" name="reply_to_student" value="1" />
					<strong><?php print "{$_SESSION['student']['first_name']} {$_SESSION['student']['last_name']} &lt;{$_SESSION['student']['email']}&gt;"; ?></strong>
				</li>
<?php
				}
?>
				<li>
					<label>Subject</label>
					<input type="hidden" name="subject" value="<?php print $_POST['subject'] ?>" />
					<strong><?php print str_replace(array('{first_name}', '{last_name}'), array($_SESSION['student']['first_name'], $_SESSION['student']['last_name']), $_POST['subject']) ?></strong>
				</li>
				<li> 
					<label>Message</label>
					<input type="hidden" name="message" value="<?php print $_POST['message'] ?>" />
					<p><strong><?php print nl2br(str_replace(array('{first_name}', '{last_name}'), array($_SESSION['student']['first_name'], $_SESSION['student']['last_name']), $_POST['message'])) ?></strong><br />
						<em class="small">Please note that the first and last name tokens, <strong>{first_name}</strong> or <strong>{last_name}</strong>, have been substituted as if the email was sent to you.</em></p>
				</li>
				<li>
					<label class="major">
						<input type="hidden" name="honour" value="<?php print $_POST['honour'] ?>" />
						Honour your students' decision not to receive emails?
						<input type="checkbox" value="1"<?php if ($_POST['honour']) print ' checked="checked"'; ?> disabled="disabled" />
					</label>
				</li>
			</ul>
	</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls"> 
			<input type="button" class="back" value="&lsaquo; Make changes" onClick="history.go(-1);" />
<?php if (!$error['num_of_recipients']) { ?>
			<input type="submit" class="forward" name="submit" value="Send this mass email &rsaquo;" />
<?php } ?>
		</fieldset>
	</fieldset>
</form>
<?php
				$layout->output_footer();
			break;
			case 1:
			default:
				$layout->output_header("{$html['origin_title']} | Send a Bulk Email", 'student');
?>
<script type="text/javascript">
//<![CDATA[


function show_my_email () {
	if ($('input[@name=reply_to_student]:checked').val()) {
		$('#my_email').show('normal');
	} else {
		$('#my_email').hide('normal');
	}
}

$(document).ready(function() {
	show_my_email ();
});

//]]>
</script>
<ul id="breadcrumb">
	<li>Send a Bulk Email</li>
<?php
				if ($this->aid) {
?>
	<li> &laquo; <a href="/students/administrate/bulk_emails.php?id=<?php print $this->aid ?>">Bulk Emails</a></li>
<?php
				}
?>
	<li> &laquo; <a href="<?php print $html['origin'] ?>"><?php print $html['origin_title'] ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Send a Bulk Email</h1>
<?php general_error($error); ?>
<form method="post" action="<?php print $_SERVER['PHP_SELF'].'?'.$this->query_string ?>#content">
	<fieldset>
		<p><?php print $html['page_description'] ?> To personalize each email by including your recipients' first or last name, type <em>{first_name}</em> or <em>{last_name}</em>, respectively. These tokens will be automatically substituted when the email is sent.</p>
		<fieldset class="psuedo_p top-down">
			An asterisk (<span title="Required" class="required_field" >*</span>) indicates a required field.
			<ul>
				<li>
<?php
				if (mysql_num_rows($courses['query']) > 1) {
?>
					<label for="to">To<span title="Required" class="required_field" >*</span></label>
					Everyone who has purchased:<?php if ($error['courses']) print $error['courses'] ?>
<?php
					do {
?>
					<label class="minor">
						<input type="checkbox" name="courses[]" value="<?php print $courses['results']['id'] ?>"<?php if (@in_array($courses['results']['id'], $_POST['courses'])) print ' checked="checked"'; ?> />
						<strong><?php print "{$courses['results']['course_code']}: {$courses['results']['course_name']}" ?></strong>
					</label>
<?php
					} while ($courses['results'] = mysql_fetch_assoc($courses['query']));
				} else {
?>
					<label>To</label>
					Everyone who has purchased:<br />
					<input type="hidden" name="courses[]" value="<?php print $courses['results']['id'] ?>" />
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
<?php
				if ($mode == 1) {
?>
					<label class="minor">
						Use my email address? <input type="checkbox" name="reply_to_student" value="1" onClick="show_my_email();"<?php if ($_POST['reply_to_student']) print ' checked="checked"'; ?> /><br />
						<strong id="my_email"><?php print "{$_SESSION['student']['first_name']} {$_SESSION['student']['last_name']} &lt;{$_SESSION['student']['email']}&gt;"; ?></strong>
					</label>
<?php
				} else {
?>
					<input type="hidden" name="reply_to_student" value="1" />
					<strong><?php print "{$_SESSION['student']['first_name']} {$_SESSION['student']['last_name']} &lt;{$_SESSION['student']['email']}&gt;"; ?></strong>
<?php
				}
?>
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
						<input type="checkbox" name="honour" value="1"<?php if ($_POST['honour']) print ' checked="checked"'; ?> /></label>
				</li>
			</ul>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls"> 
			<input type="button" class="back" value="&lsaquo; Cancel" onClick="location.href='<?php print $html['origin'] ?>'" />
			<input type="submit" class="forward" name="submit" value="Preview &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
			$layout->output_footer();
		}
	}


	
	function send($bid) {
		$bulkmail['sql'] = "SELECT id FROM task_bulkmail WHERE id = '{$bid}' AND student_id = '{$_SESSION['student']['student_id']}' LIMIT 1";
		if ($bulkmail['query'] = mysql_query($bulkmail['sql']) and mysql_num_rows($bulkmail['query']) == 1) {
			if (require($_SERVER['DOCUMENT_ROOT'].'inc/tasks/bulkmail.php') and function_exists('task')) {
				$task_result = task($bulkmail['results']['id']);
				if ($task_result !== true and (int)$task_result > 0) {
					header("Refresh: 10; url={$_SERVER['PHP_SELF']}?".$this->query_string."&bid={$bulkmail['results']['id']}&send=1");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>Sending Bulk Email</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="Content-Language" content="en-ca" />
<link rel="shortcut icon" href="/i/favicon.ico" />
<link href="/css/athena.css" rel="stylesheet" type="text/css" media="screen" />
<link href="/redirector.css" rel="stylesheet" type="text/css" media="screen" />
</head>
<body>
<div id="wrapper">
	<div id="margin-top"></div>
	<div id="container">
		<h1>Sending Bulk Email</h1>
		<p>Given the number of recipients, Athena is currently sending your bulk email
			in batches. <strong><?php print $task_result ?> email(s) remain to be sent</strong>,
			so please be patient while Athena finishes sending your bulk email.<br />
			<br />
			&rsaquo; <a href="<?php print $html['origin'] ?>">Stop waiting, and let Athena's internal bulk mailer send these emails.</a><br />
			&rsaquo; <a href="<?php print "{$_SERVER['PHP_SELF']}?".$this->query_string."&amp;bid={$bulkmail['results']['id']}&amp;delete=1" ?>">Cancel this bulk email.</a></p>
	</div>
	<div id="margin-bottom"></div>
</div>
</body>
</html>
<?php
				} elseif ($task_result === true) {
					mysql_query("UPDATE task_manager SET locked = 0 WHERE id = 3 AND enabled = true LIMIT 1");
					mysql_query("DELETE FROM task_bulkmail WHERE id = {$bulkmail['results']['id']}");
					$layout->redirector('Bulk Email Sent', 'Your bulk email was sent successfully. Now redirecting you to ...', $html['origin']);
				} else {
					$layout->redirector('Athena | Error', 'A problem occurred while attempting to send your bulk email. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.');
				}
			} else {
				$layout->redirector('Athena | Error', 'A problem occurred while attempting to access the bulk emailer. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.');
			}
		} else {
				$layout->redirector('Athena | Error', 'A problem occurred while attempting to access the bulk emailer. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.', $html['origin']);
		}
	}
	


	function delete($bid) {
		$bulkmail['sql'] = "SELECT id FROM task_bulkmail WHERE id = '{$bid}' AND student_id = '{$_SESSION['student']['student_id']}' LIMIT 1";
		if ($bulkmail['query'] = mysql_query($bulkmail['sql']) and mysql_num_rows($bulkmail['query']) == 1) {
			if (mysql_query("DELETE FROM task_bulkmail WHERE id = '{$bid}'") and mysql_affected_rows() == 1) {
				$layout->redirector('Mass Email Deleted', 'Your mass email was successfully deleted and removed from your queue. Now redirecting you to ...', $html['origin']);
			} else {
				$layout->redirector('Athena | Error', 'A problem occurred while attempting to delete your mass email. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.');
			}
		}
	}
}
?>