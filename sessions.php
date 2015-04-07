<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$mode = $_GET['mode'];

if ($mode == 'sign_out') {
	$user_authentication->sign_out();
	$layout->redirector('Signed out ...', 'You have successfully signed out of Athena. Now redirecting you to ...', '/');
} else {
	if ($mode != 'association' and $mode != 'student') $mode = 'student';
	if ((is_array($_SESSION['association']) and $mode == 'association') or (is_array($_SESSION['student']) and $mode == 'student')) {
		header('Location: /');
	} elseif ($mode == 'association') {
		$html['intro'] = 'To sign into Athena for Student Associations, enter your sign-in and password, and then click on <strong>Sign into Athena</strong>.';	
		$html['sign_in'] = 'Sign-in:';
		$html['error_str'] = 'Sorry. That sign-in and/or password is invalid.<br /><span style="font-weight:normal;">Are you trying to <a href="/sessions.php">sign into Athena for students</a>? Otherwise, <a href="/contact.php?to=1">contact the Administrators of Athena</a>.</span>';
		$redirect = '/associations/';
	} else {
		$html['intro'] = 'To sign into Athena for Students, enter your McGill ID and your password, and then click on <strong>Sign into Athena</strong>.';	
		$html['sign_in'] = 'McGill ID:';
		$html['error_str'] = 'Sorry. That McGill ID and/or password is invalid.<br /><span style="font-weight:normal;">If forgotten, you can <a href="/password_recovery.php">recover your password</a>; otherwise, <a href="/help.php#question_4">see Athena\'s Help section</a>.</span>';
		$html['maxlength'] = ' maxlength="9"';
		if ($redirect == NULL) {
			$redirect = '/students/';
		}
	}
}

if (!is_null($_POST['sign_in']) and !is_null($_POST['password'])) {
	if ($user_authentication->sign_in($_POST['sign_in'],$_POST['password'],$mode)) {
		$layout->redirector('Signed in ...', 'You have successfully signed into Athena. Now redirecting you to ...', ($_POST['referer']) ? $_POST['referer'] : $redirect);
	} else {
		$html['error'] = $html['error_str'];
	}
}

$layout->output_header('Sign into Athena', $mode);
?>
<h1>Sign into Athena</h1>
<form method="post" action="<?php print $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']; ?>">
	<fieldset>
<?php
		if (isset($_SESSION['referer']) or isset($_POST['referer'])) {
?>
		<input type="hidden" name="referer" value="<?php print ($_SESSION['referer']) ? ($_SESSION['referer']) : $_POST['referer']; ?>" />
<?php
			if (!$_POST) {
?>
		<p id="warning_paragraph">You must sign in to see this page.</p>
<?php 
			}
		}
		unset($_SESSION['referer']);
		if ( $html['error'] ) {
?>
		<p id="error_paragraph"><?php print $html['error_str'] ?></p>
<?php 
		}
?>
		<fieldset class="psuedo_p left-right">
			<p><?php print $html['intro']; ?></p>
			<label><?php print $html['sign_in']; ?><br />
				<input type="text" name="sign_in" size="9"<?php print $html['maxlength']; ?> value="<?php print $_POST['sign_in']; ?>" class="text" /></label>
			<label>Password:<br /> 
				<input type="password" name="password" size="9" maxlength="9" value="<?php print $_POST['password']; ?>" class="text" /><?php if ($mode == 'student') { ?>&nbsp;&nbsp;&nbsp;<a href="/password_recovery.php">I forgot my password</a><?php } ?></label>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="submit" class="forward" value="Sign into Athena &rsaquo;" />
		</fieldset>
	</fieldset>
</form> 
<?php
$layout->output_footer();
?>