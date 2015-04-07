<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');



$user_authentication->validate_student();


$student['sql'] = "SELECT * FROM students WHERE id = {$_SESSION['student']['student_id']}";
if ($student['query'] = mysql_query($student['sql']) and mysql_num_rows($student['query']) == 1) {
	$student['results'] = mysql_fetch_assoc($student['query']);

	$html['cp_yes']		= '';
	$html['cp_no']		= 'checked="checked" ';
	$html['cp_display']	= 'display:none; ';

	if ($_POST) {
		if ($_POST['receive_emails']) {
			$_POST['receive_emails'] = 1;
		} else {
			$_POST['receive_emails'] = 0;
		}
		$student['results']['receive_emails'] = $_POST['receive_emails'];
		if ($_POST['change_password'] == 1) {
			$html['cp_yes']		= 'checked="checked" ';
			$html['cp_no']		= '';
			$html['cp_display']	= '';
	
			$password_check['sql'] = "SELECT password FROM students WHERE id = '{$student['results']['id']}' AND password = '".md5($_POST['password_old'])."' LIMIT 1";
			if (!$password_check['query'] = mysql_query($password_check['sql'], $info['sql_db_connect']) or !mysql_num_rows($password_check['query']) == 1 ) {
				$error['password_old'] = error_inline('Your old password is required before you can enter a new password.');
			}
			if (!preg_match('/[A-z0-9]{6,9}/', $_POST['password_new'])) {
				$error['password_new'] = error_inline('A new password, 6-9 characters in length, is required.');
			}
			if ($_POST['password_new'] != $_POST['password_new_confirm']) {
				$error['password_new_confirm'] = error_inline('The passwords need to match.');
			}
			$update_student['sql_password'] = "password = '".md5($_POST['password_new'])."', ";
		}	
		if ($error) {
			$stage = 1;
		} else {
			$stage = 2;
		}
	}

	switch ($stage) {
		case 2:
			$update_student['sql'] = "UPDATE students SET {$update_student['sql_password']} receive_emails = '{$_POST['receive_emails']}', modified = NOW() WHERE id = '{$student['results']['id']}' LIMIT 1";
			if ($update_student['query'] = mysql_query($update_student['sql']) and mysql_affected_rows() == 1) {
				$layout->redirector('Profile Successfully Updated ...', 'Your profile was successfully updated. Now redirecting you to ...', '/students/');
			} else {
				$layout->redirector('Athena | Error', 'A problem occurred while attempting to update your profile. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persist, please contact the <a href="/contact.php?to=1">Administrators of Athena</a>.');
			}
		break;
		case 1:
		default:
			$layout->output_header('Edit My Profile', 'student');
?>
<ul id="breadcrumb">
	<li>Edit My Profile</li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Edit My Profile</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF'] ?>" class="disable_form_history">
<?php
			general_error($error);
?>
	<fieldset>
		<fieldset class="psuedo_p top-down">
			<ul>
				<li>
					<label>McGill ID</label>
					<strong><?php print $student['results']['mcgill_id']; ?></strong>
				</li>
				<li>
					<label>First name</label>
					<strong><?php print $student['results']['first_name']; ?></strong>
				</li>
				<li>
					<label>Last name</label>
					<strong><?php print $student['results']['last_name']; ?></strong>
				</li>
				<li>
					<label>McGill Email</label>
					<strong><?php print $student['results']['email']; ?></strong>
				</li>
				<li>
					<label class="minor">
						Receive emails from coordinators, administrators, or student associations?
						<input type="checkbox" name="receive_emails" value="1"<?php if ($student['results']['receive_emails']) { ?> checked="checked" <?php } ?> />
					</label>
				</li>
				<li>
					<label>Change password</label>
					<fieldset class="list">
						<label>
							Yes
							<input type="radio" name="change_password"<?php print $html['cp_yes']; ?> value="1" onclick="$('#change_password').show('normal')" />
						</label>
						<label>
							No
							<input type="radio" name="change_password"<?php print $html['cp_no']; ?> value="0" onclick="$('#change_password').hide('normal')" />
						</label>
					</fieldset>
				</li>
				<li id="change_password" style="<?php print $html['cp_display']; ?>">
					<fieldset class="wide">
						Passwords must be 6-9 characters in length.
						<ul>
							<li>
								<label for="password_old">Enter your old password</label>
								<input type="password" name="password_old" id="password_old" class="text<?php if ( $error['password_old'] ) print ' error'; ?>" size="9" maxlength="9" /><?php print $error['password_old'] ?>
							</li>
							<li>
								<label for="password_new">Enter a new password</label>
								<input type="password" name="password_new" id="password_new" class="text<?php if ( $error['password_new'] ) print ' error'; ?>" size="9" maxlength="9" /><?php print $error['password_new'] ?>
							</li>
							<li>
								<label for="password_new_confirm">Confirm your new password</label>
								<input type="password" name="password_new_confirm" id="password_new_confirm" class="text<?php if ( $error['password_new_confirm'] ) print ' error'; ?>" size="9" maxlength="9" /><?php print $error['password_new_confirm'] ?>
							</li>
						</ul>
					</fieldset>
				</li>
<?php
			if ( $student['results']['modified'] != 0 ) {
?>
				<li>
					<label>Last modified</label>
					<strong><?php print date('Y/m/d @ H:i',strtotime($student['results']['modified'])) ?></strong>
				</li>
<?php
			}
?>
				<li>
					<label>Created</label>
					<strong><?php print date('Y/m/d @ H:i',strtotime($student['results']['created'])); ?></strong>
				</li>
			</ul>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="button" class="back" value="&lsaquo; Back" onclick="location.href='/students/'" />
			<input type="submit" class="forward" value="Edit my profile &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
			$layout->output_footer();
		break;
	}
} else {
	$layout->redirector('Athena | Error', 'A problem occurred while attempting to access your NTCs. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persist, please contact the <a href="/contact.php?to=1">Administrators of Athena</a>.');
}
?>