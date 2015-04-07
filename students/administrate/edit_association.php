<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');


$_GET['id'] = (int)$_GET['id'];
$user_authentication->validate_student();
$user_authentication->validate_administrator($_GET['id']);

$html['cp_yes']		= '';
$html['cp_no']		= 'checked="checked" ';
$html['cp_display']	= 'display:none; ';

if ($_POST and is_numeric($_GET['id'])) {
	if (!preg_match('/\w+/', $_POST['prog'])) {
		$error['prog'] = error_inline('A program name is required.');
	}
	if (!preg_match('/\D{4}/', $_POST['prog_abbrev'])) {
		$error['prog_abbrev'] = error_inline('A program abbreviation is required.');
	}
	if (!preg_match('/\w+/', $_POST['name'])) {
		$error['name'] = error_inline('A student association name is required.');
	}
	if (!preg_match('/[A-Z]{1,6}/', $_POST['abbreviation'])) {
		$error['abbreviation'] = error_inline('A student association abbreviation is required.');
	}
	if (empty($_POST['address'])) {
		$error['address'] = error_inline('A student association address is required.');
	}
	if (!preg_match('/^\w+([-+.]\w+)*@(\w+([-+.]\w+)*\.)+\w{2,6}$/i', $_POST['email'])) {
		$error['email'] = error_inline('A student association email is required.');
	}
	if (empty($_POST['website'])) {
		$error['website'] = error_inline('A student association website is required.');
	}
	if (!is_numeric($_POST['ss_printing_cost'])) {
		$error['ss_printing_cost'] = error_inline('A single-sided printing cost is required.');
	}
	if (!is_numeric($_POST['ds_printing_cost'])) {
		$error['ds_printing_cost'] = error_inline('A double-sided printing cost is required.');
	}
	if (!preg_match('/\w+/',$_POST['sign_in'])) {
		$error['sign_in'] = error_inline('A log in required.');
	}
	if ($_POST['change_password'] == 1) {
		$html['cp_yes'] = 'checked="checked" ';
		$html['cp_no'] = '';
		$html['cp_display'] = '';

		$association['sql'] = 'SELECT password FROM associations WHERE id = '.$_GET['id'].' AND administrator = '.$_SESSION['student']['student_id'].' AND password = "'.md5($_POST['password_old']).'" LIMIT 1';
		if (!$association['query'] = mysql_query($association['sql']) or !mysql_num_rows($association['query']) == 1) {
			$error['password_old'] = error_inline('Your old password is required before you can enter a new password.');
		}
		if (!preg_match('/[A-z0-9]{6,9}/', $_POST['password_new'])) {
			$error['password_new'] = error_inline('A new password, 6-9 characters in length, is required.');
		}
		if ($_POST['password_new'] != $_POST['password_new_confirm']) {
			$error['password_new_confirm'] = error_inline('The passwords need to match.');
		}
	}

	if ($error) {
		$step = 1;
		$association['results'] = $_POST;
	} else {
		$step = 2;
	}
} else {
	$association['sql'] = "SELECT * FROM associations WHERE id = {$_GET['id']} AND administrator = {$_SESSION['student']['student_id']} LIMIT 1";
	if ($association['query'] = mysql_query($association['sql']) and mysql_num_rows($association['query']) == 1) {
		$association['results'] = mysql_fetch_assoc($association['query']);
	} else {
		$layout->redirector("Oop!", "There was an error");
	}
}

switch ($step) {
	case 2:
		if ($_POST['change_password'] == 1) $sql['password'] = "password = '".md5($_POST['password_new'])."',";

		$association['sql'] = "	UPDATE	associations
								SET		prog = '{$_POST['prog']}',
										prog_abbrev = '".strtoupper($_POST['prog_abbrev'])."',
										name = '{$_POST['name']}',
										abbreviation = '".strtoupper($_POST['abbreviation'])."',
										address = '{$_POST['address']}',
										email = '{$_POST['email']}',
										website = '{$_POST['website']}',
										show_website = '".(int)$_POST['show_website']."',
										ss_printing_cost = {$_POST['ss_printing_cost']},
										ds_printing_cost = {$_POST['ds_printing_cost']},
										sign_in = '{$_POST['sign_in']}',
										{$sql['password']}
										modified = NOW()
								WHERE	id = {$_GET['id']} AND
										administrator = {$_SESSION['student']['student_id']}";
		if ($association['query'] = mysql_query($association['sql'])) {
			$layout->redirector('Athena has been updated ...', 'Your student association has been updated. Now redirecting you to ...', '/students/administrate/?id='.$_GET['id']);
		} else {
			$layout->redirector('Error', 'There was an error:<br />'.mysql_error());
		}
		break;
	default:
		$layout->output_header('Administrate '.$association['results']['abbreviation'].' | Edit This Student Association', 'student');
?>
<ul id="breadcrumb">
	<li>Edit This Student Association</li>
	<li> &laquo; <a href="/students/administrate/?id=<?php print $_GET['id'] ?>">Administrate <?php print $association['results']['abbreviation'] ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Edit This Student Association</h1>
<?php 	general_error($error); ?>
<form method="post" action="<?php print $_SERVER['PHP_SELF'].'?id='.$_GET['id'] ?>">
	<fieldset>
		<fieldset class="psuedo_p">
			<p>Use the following to edit this student association's information.</p>
			<p>All fields are required.</p>
			<fieldset class="top-down">
				<legend>Program</legend>
				<ul>
					<li>
						<label for="prog">Name</label>
						<input type="text" name="prog" id="prog" class="text<?php if ($error['prog']) print ' error'; ?>" value="<?php print $association['results']['prog'] ?>" style="width:150px;" /><?php print $error['prog'] ?>
					</li>
					<li>
						<label for="prog_abbrev">Abbreviation</label>
						<input type="text" name="prog_abbrev" id="prog_abbrev" class="text<?php if ($error['prog_abbrev']) print ' error'; ?>" value="<?php print $association['results']['prog_abbrev'] ?>" size="4" maxlength="4" /><?php print $error['prog_abbrev'] ?>
					</li>
				</ul>
			</fieldset>
			<fieldset class="top-down">
				<legend>Student Association</legend>
				<ul>
					<li>
						<label for="name">Name</label>
						<input type="text" name="name" id="name" class="text<?php if ($error['name']) print ' error'; ?>" value="<?php print $association['results']['name'] ?>" style="width:300px;" /><?php print $error['name'] ?>
					</li>
					<li>
						<label for="abbreviation">Abbreviation</label>
						<input type="text" name="abbreviation" id="abbreviation" class="text<?php if ($error['abbreviation']) print ' error'; ?>" value="<?php print $association['results']['abbreviation'] ?>" size="6" maxlength="6" /><?php print $error['abbreviation'] ?>
					</li>
					<li>
						<label for="address">Address</label>
						<input type="text" name="address" id="address" class="text<?php if ($error['address']) print ' error'; ?>" value="<?php print $association['results']['address'] ?>" style="width:300px;" /><?php print $error['address'] ?>
					</li>
					<li>
						<label for="email">Email</label>
						<input type="text" name="email" id="email" class="text<?php if ($error['email']) print ' error'; ?>" value="<?php print $association['results']['email'] ?>" style="width:150px;" /><?php print $error['email'] ?>
					</li>
					<li>
						<label for="website">Website</label>
						<div class="text<?php if ($error['website']) print ' error'; ?>" style="width:200px;" onclick="$('#website').focus()">http://
							<input type="text" name="website" id="website" value="<?php print $association['results']['website'] ?>" style="width:150px;" /></div><?php print $error['website'] ?>
						<label class="minor">Do you want to drive hits to your website by displaying it when a student downloads one of your NTCs?
							<input type="checkbox" name="show_website" id="show_website" <?php if ($association['results']['show_website'] == 1) print 'checked="checked" '; ?>value="1" /></label>
					</li>
				</ul>
			</fieldset>
			<fieldset class="top-down">
				<legend>Printing Costs</legend>
				<ul>
					<li>
						<label for="ss_printing_cost">Single-sided</label>
						<div class="text<?php if ($error['ss_printing_cost']) print ' error'; ?>" style="width:58px;" onclick="$('#ss_printing_cost').focus()">$
							<input type="text" name="ss_printing_cost" id="ss_printing_cost" value="<?php print $association['results']['ss_printing_cost'] ?>" size="2" maxlength="5" /></div><?php print $error['ss_printing_cost'] ?>
					</li>
					<li>
						<label for="ds_printing_cost">Double-sided</label>
						<div class="text<?php if ($error['ds_printing_cost']) print ' error'; ?>" style="width:58px;" onclick="$('#ds_printing_cost').focus()">$
							<input type="text" name="ds_printing_cost" id="ds_printing_cost" value="<?php print $association['results']['ds_printing_cost'] ?>" size="2" maxlength="5" /></div><?php print $error['ds_printing_cost'] ?>
					</li>
				</ul>
			</fieldset>
			<fieldset class="top-down">
				<legend>Student Association Access</legend>
				<ul>
					<li>
						<label for="sign_in">Sign in</label>
						<input type="text" name="sign_in" id="sign_in" class="text<?php if ($error['sign_in']) print ' error'; ?>" value="<?php print $association['results']['sign_in'] ?>" size="9" maxlength="9" /><?php print $error['sign_in'] ?>
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
						<fieldset class="top-down wide">
							Passwords must be 6-9 characters in length.
							<ul>
								<li>
									<label for="password_old">Enter your old password</label>
									<input type="password" name="password_old" id="password_old" class="text<?php if ($error['password_old']) print ' error'; ?>" size="9" maxlength="9" /><?php print $error['password_old'] ?>
								</li>
								<li>
									<label for="password_new">Enter a new password</label>
									<input type="password" name="password_new" id="password_new" class="text<?php if ($error['password_new']) print ' error'; ?>" size="9" maxlength="9" /><?php print $error['password_new'] ?>
								</li>
								<li>
									<label for="password_new_confirm">Confirm your new password</label>
									<input type="password" name="password_new_confirm" id="password_new_confirm" class="text<?php if ($error['password_new_confirm']) print ' error'; ?>" size="9" maxlength="9" /><?php print $error['password_new_confirm'] ?>
								</li>
							</ul>
						</fieldset>
					</li>
				</ul>
			</fieldset>
			<fieldset class="top-down">
				<legend>Dates and Times</legend>
				<ul>
					<li>
						<label>Last modified</label>
						<input type="hidden" name="modified" value="<?php print $association['results']['modified'] ?>" />
						<strong><?php print date('Y/m/d \a\t H:i',strtotime($association['results']['modified'])) ?></strong>
					</li>
					<li>
						<label>Created</label>
						<input type="hidden" name="created" value="<?php print $association['results']['created'] ?>" />
						<strong><?php print date('Y/m/d \a\t H:i',strtotime($association['results']['created'])) ?></strong>
					</li>
				</ul>
			</fieldset>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="button" class="back" value="&lsaquo; Cancel" onclick="location.href='/students/administrate/?id=<?php print $_GET['id'] ?>'" />
			<input type="submit" name="submit" class="forward" value="Edit this association &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
		$layout->output_footer();
	break;
}
?>
