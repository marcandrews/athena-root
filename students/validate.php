<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');
// $user_authentication->validate_student();

$student['sql'] = '	SELECT	s.id, s.mcgill_id, s.password, s.first_name, s.last_name, s.email, s.email_alt, s.validated,
							v.code, v.password AS v_password, v.email AS v_email, v.email_alt AS v_email_alt, v.created AS v_created
					FROM	students AS s
					LEFT	JOIN students_validations AS v
							ON v.student_id = s.id
					WHERE 	s.id = "'.$_SESSION['student']['student_id'].'" AND
							s.mcgill_id = "'.$_SESSION['student']['mcgill_id'].'" AND
							s.first_name = "'.$_SESSION['student']['first_name'].'" AND
							s.last_name = "'.$_SESSION['student']['last_name'].'" AND
							s.validated = "'.$_SESSION['student']['validated'].'"
					LIMIT 1';
if ($student['query'] = mysql_query($student['sql']) and mysql_num_rows($student['query']) == 1) {
	$student['results'] = mysql_fetch_assoc($student['query']);
	
	// Determine validation status.
	if ($student['results']['validated'] == 1) {
		// This student is validated. Redirect them to the student home page.
		header('Location: /students/');
		exit;
	} else {
		// This student is NOT validated; begin the validation process.
		if ($student['results']['code'] == NULL) {
			// Restore this student's validation bits.
			$validations['sql'] = '	REPLACE INTO students_validations
									SET		student_id = "'.$student['results']['id'].'",
											code = "'.md5($student['results']['id'].'_'.$student['results']['mcgill_id']).'",
											created = NOW()';
			if ($validations['query'] = mysql_query($validations['sql'])) {
				$step = 1;
			} else {
				$layout->redirector('Athena | Validation Error', 'A problem occurred while attempting to restore your validation bits. Please <a href="/students/validate.php">go back</a> and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
			}
		} else {
			// Process this student's validation.
			if ($_GET['code'] == $student['results']['code']) {
				$step = 4;
			} elseif (!empty($student['results']['v_email']) and !empty($student['results']['v_password'])) {
				if ($_GET['action'] == 'restart') {
					// Reset this student's validation.
					$validations['sql'] = 'UPDATE students_validations SET email = "", email_alt = "", password = "" WHERE student_id = '.$student['results']['id'];
					if (mysql_query($validations['sql'])) {
						$layout->redirector('Athena | Validate your Athena Account', 'Restarting your validation...', '/students/validate.php');
					} else {
						$layout->redirector('Athena | Validation Error', 'A problem occurred while attempting to restart your validation. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
					}
				} elseif ($_GET['action'] == 'resend') {
					// Resend this student's validation email.
					if (send_validation_email($student['results']['id'], $student['results']['mcgill_id'], $student['results']['first_name'], $student['results']['last_name'], $student['results']['v_email'])) {
						$layout->redirector('Athena | Validate your Athena Account', 'Resending your validation email...', '/students/validate.php');
					} else {
						$layout->redirector('Athena | Validation Error', 'A problem occurred while attempting to resend your validation email. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
					}
				}
				$step = 3;
			} elseif ($_POST['validate']) {
				// Validate the student's input.
				unset($error);
				if (!preg_match('/^\w+([-+.]\w+)*@(\w+([-+.]\w+)*\.)*mcgill\.ca$/i', $_POST['email'])) {
					$error['email'] = error_inline('A valid McGill email address is required.');
				}
				if ($_POST['email'] != $_POST['email_cf']) {
					$error['email_cf'] = error_inline('To confirm, the McGill email address needs to match.');
				}
				if ($_POST['email_alt'] != '') {
					if ($_POST['email_alt'] == $_POST['email']) {
						$error['email_alt'] = error_inline('Your alternate email address cannot be your McGill email address.');
					}
					if (!preg_match('/^\w+([-+.]\w+)*@(\w+([-+.]\w+)*\.)+\w{2,6}$/i', $_POST['email_alt'])) {
						$error['email_alt'] = error_inline('A valid email address is required.');
					}
				}
				if (!preg_match('/[A-z0-9]{6,9}/', $_POST['password'])) {
					$error['password'] = error_inline('This password is invalid password.');
				}
				if ($_POST['password'] != $_POST['password_confirm']) {
					$error['password_confirm'] = error_inline('To confirm, the password needs to match.');
				}
				if (!isset($error)) {
					$step = 2;
				} else {
					$step = 1;
				}
			} else {
				$step = 1;
			}
		}
	}
	switch ($step) {
		case 4:
			// Begin the query.
			$result_query = mysql_query('START TRANSACTION');

			// Declare SQL statements.
			$validate['sql'] = '	UPDATE	students
									SET		email = "'.$student['results']['v_email'].'",
											email_alt = "'.$student['results']['v_email_alt'].'",
											password = "'.$student['results']['v_password'].'",
											validated = "1",
											modified = NOW()
									WHERE	id = '.$student['results']['id'];
			$delete['sql'] = 'DELETE FROM students_validations WHERE student_id = '.$student['results']['id'];
			
			// Runs SQL statements.
			if (mysql_query($validate['sql']) and mysql_query($delete['sql']) and mysql_query('COMMIT')) {
				$_SESSION = array();
				session_destroy();
				$layout->redirector('Account Validation Successful', 'Your Athena account has been validated. Please sign into Athena using your McGill ID and your chosen password.<script type="text/javascript" src="http://www.google-analytics.com/urchin.js"></script><script type="text/javascript">_uacct="UA-498796-1";urchinTracker();</script>', '/students/');
			} else {
				$layout->redirector('Athena | Validation Error', 'A problem occurred while attempting to validate your Athena account. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
			}
		break;
		case 3:
			$layout->output_header('Validate your Athena Account', 'student');
?>
<h1>Validate your Athena Account</h1>
<p>An email, with further instructions on validating your account, has been sent
	to <strong><?php print $student['results']['v_email'] ?></strong>. Please check
	your inbox, including any junk mail folders.<br />
	<br />
	If have not received this email, Athena can <a href="/students/validate.php?action=resend">resend</a> this
	email. If there was an error and <?php print $student['results']['v_email'] ?> <strong>is
	not your email address</strong>, you can <a href="/students/validate.php?action=restart">restart</a> the
	validation process.</p>
<?php
			$layout->output_footer();
		break;
		case 2:
			$sql = '	REPLACE INTO	students_validations
						SET				student_id = "'.$student['results']['id'].'",
										code = "'.md5($student['results']['id'].'_'.$student['results']['mcgill_id']).'",
										email = "'.$_POST['email'].'",
										email_alt = "'.$_POST['email_alt'].'",
										password = "'.md5($_POST['password']).'",
										ip = "'.$_SERVER['REMOTE_ADDR'].'",
										modified = NOW(),
										created = "'.$student['results']['v_created'].'"';
			if (mysql_query($sql) and send_validation_email($student['results']['id'], $student['results']['mcgill_id'], $student['results']['first_name'], $student['results']['last_name'], $_POST['email'])) {
				$layout->output_header('Validate your Athena Account', 'student');
?>
<h1>Validate your Athena Account</h1>
<p>In a few moments (usually instantly), you will receive an email in your <strong>McGill
	email inbox</strong> with further instructions on validating your Athena account.
	Please check your inbox, including any junk mail folders.<br />
	<br />
	If you fail to receive this validation email, log into Athena, using your
	McGill ID as your password. You will then have the option to resend the validation
	email, or begin the validation process from the beginning.</p>
<?php				
				$layout->output_footer();
			} else {
				$layout->redirector('Athena | Validation Error', 'A problem occurred while attempting to create your validation bits. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
			}
		break;
		default:
			$layout->output_header('Validate your Athena Account', 'student');
?>
<ul id="breadcrumb">
	<li>Validate your Athena Account</li>
</ul>
<h1>Validate your Athena Account</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF']; ?>" class="disable_form_history">
<?php
			general_error($error)
?>
	<p>Before you can use Athena, you will have to validate your account. Please complete the following and then click on <strong>Validate your account</strong>.</p>
	<fieldset>
		<fieldset class="psuedo_p over-label">
			<p>An asterisk (<span title="Required" class="required_field" >*</span>) indicates a required field.</p>
			<ul>
				<li>
					<label>Enter your McGill email address (this address <strong>must</strong> end with <em>mcgill.ca</em>)<span title="Required" class="required_field" >*</span><br />
						<input name="email" type="text" class="text<?php if ($error['email']) print ' error' ?>" value="<?php print $_POST['email']; ?>" size="40" /><?php print $error['email']; ?></label>
				</li>
				<li>
					<label>Confirm your McGill email address by entering it again<span title="Required" class="required_field" >*</span><br />
						<input name="email_cf" type="text" class="text<?php if ($error['email_cf']) print ' error' ?>" value="<?php print $_POST['email_cf']; ?>" size="40" /><?php print $error['email_cf']; ?></label>
				</li>
				<li>
					<label>Choose a password, 6-9 characters in length, that you will keep safe and remember<span title="Required" class="required_field" >*</span><br />
						<input name="password" type="password" class="text<?php if ($error['password']) print ' error' ?>" value="<?php print $_POST['password']; ?>" size="9" maxlength="9" /><?php print $error['password']; ?></label>
				</li>
				<li>
					<label>Confirm your password by entering it again<span title="Required" class="required_field" >*</span><br />
						<input name="password_confirm" type="password" class="text<?php if ($error['password_confirm']) print ' error' ?>" value="<?php print $_POST['password_confirm']; ?>" size="9" maxlength="9" /><?php print $error['password_confirm']; ?></label>
				</li>
			</ul>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input name="validate" type="submit" class="forward" value="Validate your account &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
			$layout->output_footer();
	}
} elseif ($student['query'] and mysql_num_rows($student['query']) == 0) {
	$user_authentication->sign_out();
	$_SESSION['referer'] = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
	header('Location: /sessions.php');
} else {
	$layout->redirector('Athena | Validation Error', 'A problem occurred while attempting to access your validation bits. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
}

?>
