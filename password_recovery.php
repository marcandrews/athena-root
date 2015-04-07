<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');



if (is_array($_SESSION['student']) or is_array($_SESSION['dept'])) {
	$layout->redirector('Athena | Password Recovery Error', 'Passwords cannot be recovered while logged into Athena. To continue, <a href="/sessions.php?mode=sign_out">log out</a> before attempting to recover your password.');
}

if (isset($_GET['code']) and strpos($_POST['submit'], 'Create your new password') === 0) {
	$_GET['code'] = $_POST['code'];
	$step = 3;
	if (!preg_match('/[A-z0-9]{6,9}/', $_POST['pw'])) {
		$error['pw'] = $error_handler->error_inline('A password, 6-9 characters in length, is required.');
		$step = 2;
	}
	if ($_POST['pw'] != $_POST['pw_confirm']) {
		$error['pw_confirm'] = $error_handler->error_inline('The passwords must match.');
		$step = 2;
	}
} elseif (isset($_GET['code'])) {
	$step = 2;
} elseif (strpos($_POST['submit'], 'Recover your password') === 0) {
	$students['sql'] = "SELECT id FROM students WHERE email = '{$_POST['email']}'";
	if (!preg_match('/^\w+([-+.]\w+)*@(\w+([-+.]\w+)*\.)*mcgill\.ca$/i', $_POST['email'])) {
		$error_description = 'Sorry. That email address is invalid.<br /><span style="font-weight:normal;">Please <a href="/help.php#question_4">see Athena\'s Help section</a>; otherwise, <a href="/contact.php">contact your student association</a>.</span>';
		$error['email'] = $error_handler->error_inline('A valid McGill email address that ends with &quot;mcgill.ca&quot; is required.');
	} elseif ($students['query'] = mysql_query($students['sql']) and !mysql_num_rows($students['query'])) {
		$error_description = 'Sorry. That email address is invalid.<br /><span style="font-weight:normal;">Please <a href="/help.php#question_4">see Athena\'s Help section</a>; otherwise, <a href="/contact.php">contact your student association</a>.</span>';
	} elseif (!$students['query']) {
		$error_description = 'Sorry. That email address is invalid.<br /><span style="font-weight:normal;">Please <a href="/help.php#question_4">see Athena\'s Help section</a>; otherwise, <a href="/contact.php">contact your student association</a>.</span>';
	} else {
		$step = 1;
	}
}

switch ($step) {
	case 3:
		$password_reset['sql'] = 'UPDATE students SET password = "'.md5($_POST['pw']).'" WHERE id = '.$_POST['student_id'];
		$pr_delete['sql'] = 'DELETE FROM students_password_recoveries WHERE student_id = '.$_POST['student_id'];
		if ( mysql_query($password_reset['sql']) and mysql_query($pr_delete['sql']) ) {
			$layout->redirector('Successful ...', 'You have created a new password. Please log into Athena with your new password ...', '/sessions.php');
		} else {
			$layout->redirector('Error', 'There was an error:<br />' . mysql_error());
		}
		break;
	case 2:
		$password_recovery['sql'] = ' 	SELECT	s.id, s.first_name, pr.created
										FROM	students AS s, students_password_recoveries AS pr
										WHERE	code = "'.$_GET['code'].'" AND
												s.id = pr.student_id
										LIMIT 1';
		$password_recovery['query'] = mysql_query($password_recovery['sql']) or print(mysql_error());
		if ( mysql_num_rows($password_recovery['query']) == 1 ) {
			$password_recovery['results'] = mysql_fetch_assoc($password_recovery['query']);
			$tminus = strtotime('now') - strtotime($password_recovery['results']['created']);
			if ( $tminus <= 65*60 ) {
				$layout->output_header('Password Recovery', 'student');
?>
<h1>Password Recovery</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF'] ?>?code=<?php print $_GET['code'] ?>" class="disable_form_history">
<?php
				general_error($error);
?>
	<input type="hidden" name="student_id" value="<?php print $password_recovery['results']['id'] ?>" />
	<input type="hidden" name="code" value="<?php print $_GET['code'] ?>" />
	<fieldset>
		<p><strong>Hello <?php print $password_recovery['results']['first_name'] ?></strong>. You can now create a new password for your Athena account. Please try to remember it this time.</p>
		<fieldset class="psuedo_p top-down wide">
			<p>An asterisk (<span title="Required" class="required_field" >*</span>) indicates a required field.</p>
			<ul>
				<li>
					<label>Enter your new password<span title="Required" class="required_field" >*</span></label>
					<input type="password" name="pw" class="text<?php if ($error['pw']) print ' error'; ?>" size="9" maxlength="9" /><?php print $error['pw'] ?>

				</li>
				<li>
					<label>Confirm your new password<span title="Required" class="required_field" >*</span></label>
					<input type="password" name="pw_confirm" class="text<?php if ($error['pw_confirm']) print ' error'; ?>" size="9" maxlength="9" /><?php print $error['pw_confirm'] ?>

				</li>
			</ul>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="submit" name="submit" class="forward" value="Create your new password &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
				$layout->output_footer();
				exit;
			} else {
				$layout->output_header('Password Recovery', 'student', $_SESSION);
?>
<h1>Password Recovery</h1>
<p>Sorry. You requested to reset your password but failed to complete the process
	within 60 minutes from your initial request. In addition, for security reasons,
	you may only request to reset a password once every several hours. Please try
	again in a few hours or <a href="/contact.php">contact your student association</a> for
	further assistance.</p>
<?php
				$layout->output_footer();
				exit;
			}
		} else {
		}
		break;
	case 1:
		$pr_delete['sql'] = 'DELETE FROM students_password_recoveries WHERE TIMEDIFF(NOW(),created) > "06:00:00"';
		$pr_delete['query'] = mysql_query($pr_delete['sql']);

		$students['sql'] = 'SELECT * FROM students WHERE email = "'.$_POST['email'].'"';
		$students['query'] = mysql_query($students['sql']) or print(mysql_error());
		if (mysql_num_rows($students['query']) == 1) {
			$students['results'] = mysql_fetch_assoc($students['query']);

			$rip['sql'] = 'SELECT * FROM students_password_recoveries WHERE student_id = '.$students['results']['id'].' OR ip = "'.$_SERVER['REMOTE_ADDR'].'"';
			$rip['query'] = mysql_query($rip['sql']) or print(mysql_error());
			$rip['results'] = mysql_fetch_assoc($rip['query']);
			if ( mysql_num_rows($rip['query']) == 1 ) {
				$tminus = strtotime('now') - strtotime($rip['results']['created']);
				if ( $tminus <= 65*60 ) {
					$layout->output_header('Password Recovery', 'student');
?>
<h1>Password Recovery</h1>
<p>You have already requested to reset your password. An email, containing further
	instructions, has been sent to <strong><?php print $_POST['email'] ?></strong>. Please check
	your inbox, including any junk mail folders. You have <strong><?php print round(65 - $tminus/60) ?> minutes</strong> to
	complete the instructions contained in this email and successfully reset your
	password.<br />
	<br />
	For further assistance, please contact your <a href="/contact.php">contact your
	student association</a>.</p>
<?php
					$layout->output_footer();
					exit;
				} else {
					$layout->output_header('Password Recovery', 'student');
?>
<h1>Password Recovery</h1>
<p>Sorry. You requested to reset your password but failed to complete the process
	within 60 minutes from your initial request. For security reasons, you may only
	request to reset a password once every several hours. Please try again in a
	few hours or <a href="/contact.php">contact your student association</a> for
	further assistance.</p>
<?php
					$layout->output_footer();
					exit;
				}
			} else {
				$created = date('Y-m-d H:i:s');
				$expiration = date('F jS, Y \a\t H:i', mktime(date('H'), date('i')+60, date('s'), date('m'),date('d'),date('Y')));
				$code = md5($students['results']['id'].' '.date('Y-m-d H:i:s'));
				$pr['sql'] = '	INSERT INTO	students_password_recoveries
									    	(student_id, code, ip, created)
								VALUES		('.$students['results']['id'].', "'.$code.'", "'.$_SERVER['REMOTE_ADDR'].'", "'.$created.'")';
				$to = "{$students['results']['first_name']} {$students['results']['last_name']} <{$students['results']['email']}>";
				$from = "Athena <marc.andrews@mail.mcgill.ca>";
				$headers  = "From: $from\n";
				$headers .= "Reply-To: $from\n";
				$headers .= "MIME-Version: 1.0\n";
				$headers .= "Content-type: text/plain; charset=iso-8859-1\n";
				$headers .= "X-Priority: 3\n";
				$headers .= "X-MSMail-Priority: Normal\n";
				$headers .= "X-Mailer: php/" . phpversion();
				$subject = "Password recovery information from Athena ({$info['site_url']})";
				$message = <<<EMAIL
{$students['results']['first_name']},

This email has been sent from {$info['site_url']}.
 
You have received this email because a request was made to recover your Athena password.
 
------------------------------------------------
IMPORTANT!
------------------------------------------------
 
If you did not request this password recovery, please IGNORE and DELETE this email immediately. Only continue if you wish your password to be recovered!
 
------------------------------------------------
Password Recovery Instructions Below
------------------------------------------------
 
We require that you "validate" your password recovery to ensure that you requested this action. This protects against unwanted spam and malicious abuse.
 
To do so, simply click on the link below and complete the form
 
{$info['site_url']}{$_SERVER['PHP_SELF']}?code={$code}

This link will stay active for 60 minutes following the initial request. After {$expiration}, you will have to start the password recovery process from the beginning.
 
IP address of sender: {$_SERVER['REMOTE_ADDR']}
 
 
Regards,
The Athena Administration
{$info['site_url']}
EMAIL;
				if ( ($pr['query'] = mysql_query($pr['sql'])) and mail($to, $subject, wordwrap($message,70), $headers) ) {
					$layout->output_header('Password Recovery', 'student', $_SESSION);
?>
<h1>Password Recovery</h1>
<p>An email, containing further instructions, has been sent to <strong><?php print $_POST['email'] ?></strong>.
	You should receive this email within the next few moments (usually instantly).
	Please check your inbox, including any junk mail folders. You will mave <strong>60 minutes</strong>
	to complete the instructions contained in this email and recover your password.</p>
<?php
					$layout->output_footer();
					exit;
				} else {
					print 'ERROR! Cannot save PR to database or send PR email.';
				}
			}
		} else {
			$layout->output_header('Password Recovery', 'student', $_SESSION);
?>
<h1>Password Recovery</h1>
<p>Sorry. The email address, <strong><?php print $_POST['email'] ?></strong>,
	was not found. Please <a href="javascript:history.back(1)">go back</a> and try
	again or <a href="/contact.php">contact your student association</a> for further
	assistance.</p>
<?php
			$layout->output_footer();
			exit;
		}
	break;
	default:
		$layout->output_header('Password Recovery', 'student');
?>
<h1>Password Recovery</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF'] ?>" class="disable_form_history">
	<fieldset>
<?php
		if ($error_description) {
?>
		<p id="error_paragraph"><?php print $error_description ?></p>
<?php
		}
?>
		<fieldset class="psuedo_p">
		<p>Enter your McGill email address in the field below. Once you have submitted
			the form, you will receive an email asking for validation of this request to
			ensure that no malicious use has occured. This email will also contain a link
			that you must click. After clicking on the link, you will be presented with
			a form that will allow you to enter a new password to use for your account.</p>
		<p>For best results, please use your computer at home (i.e. not a campus computer).</p>
		<label>Enter your McGill email address:
			<input type="text" name="email" class="text<?php if ($error['email']) print ' error' ?>" size="30" value="<?php print $_POST['email'] ?>" /><?php print $error['email'] ?></label>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="submit" name="submit" class="forward" value="Recover your password &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
		$layout->output_footer();
	break;
}
?>
