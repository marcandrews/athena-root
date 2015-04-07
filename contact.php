<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

if ($_POST) {
	$assoc['sql'] = 'SELECT abbreviation, email FROM associations WHERE id = "'.$_POST['to'].'" LIMIT 1';
	if ($assoc['query'] = mysql_query($assoc['sql']) and mysql_num_rows($assoc['query']) == 1) {
		$assoc['results'] = mysql_fetch_assoc($assoc['query']);
	} else {
		$error['to'] = error_inline('A recipient for your message is required.');
	}
	if (empty($_POST['name'])) {
		$error['name'] = error_inline('Your name is required.');
	}
	if (!preg_match("/^\w+([-+.]\w+)*@(\w+([-+.]\w+)*\.)+\w{2,6}$/i", $_POST['email'])) {
		$error['email'] = error_inline('A valid email address is required.');
	}
	if (str_word_count($_POST['subject']) < 1) {
		$error['subject'] = error_inline('A subject is required.');
	}
	if (str_word_count($_POST['message']) < 5) {
		$error['message'] = error_inline('Your message must be at least five words long.');
	}
	if (isset($error)) {
		$selected['to'][$_POST['to']] = ' selected="selected"';
		if ($_POST['cc']) $selected['cc'] = ' checked="checked"';
	} else {
		$send_mail = true;
	}
} else {
	$selected['to'][(int)$_GET['to']] = ' selected="selected"';
}

switch ($send_mail) {
	case true:
		if (get_magic_quotes_gpc()) {
			 $_POST = array_map('stripslashes', $_POST);
		}
		$to			=	"{$assoc['results']['abbreviation']} <{$assoc['results']['email']}>";
		$from		=	"{$_POST['name']} <{$_POST['email']}>";
		$message	=	wordwrap($_POST['message'], 70);
		$headers	=	"MIME-Version: 1.0\n";
		$headers	.=	"Content-type: text/plain; charset=iso-8859-1\n";
		$headers	.=	"X-Priority: 3\n";
		$headers	.=	"X-MSMail-Priority: Normal\n";
		$headers	.=	"X-SendersIP: ".$_SERVER['REMOTE_ADDR']."\n";
		$headers 	.=	"X-Mailer: php/".phpversion()."\n";
		if ( mail($to, $_POST['subject'], wordwrap("This email was sent to you from via Athena ({$info['site_url']}).\n\nSender name: {$_POST['name']}\nSender email: {$_POST['email']}\n\nMessage:\n$message",70), "From: {$from}\n{$headers}") ) {
			/* Mail was sent successfully */
			if ($_POST['cc']) {
				mail($from, "CC: {$_POST['subject']}", wordwrap("The following is a copy of the email that you sent from Athena ({$info['site_url']}).\n\nTo: {$assoc['results']['abbreviation']}\n\nMessage:\n{$message}",70), "From: {$to}\n{$headers}");
			}
			$layout->redirector('Success!', 'Your email has been sent. Now redirecting you to ...', '/');
		} else {
			/* Mail was sent unsuccessfully */
			$layout->redirector('Error!', 'There was an error. Please <a href="javascript:history.go(-1)">go back</a> and try again.', 'javascript:history.go(-1)');
		}
	break;
	default:
		$layout->output_header('Contact');
?>
<script type="text/javascript">
//<![CDATA[

	function contact_warning(id) {
		var id;
		if (id==1)
			alert('Please note that the Administrators of Athena are not involved with the sale, distribution or online access of NTCs. Should you have such a concern, please contact your appropriate student association.');
	}

	function form_reset() {
		if (confirm('Are you sure you want to clear the form?')) {
			$('p#error_paragraph,img.error').hide();
			$('.text, textarea, select').val('').removeClass('error');
			$('select#to').val(0);
			$('input#cc').removeAttr('checked');
		}
		return false;
	}
//]]>
</script>
<ul id="breadcrumb">
	<li>Contact</li>
<?php if (is_array($_SESSION['student'])) { ?>
	<li> &laquo; <a href="/students/">Student Home</a></li>
<?php } elseif (is_array($_SESSION['association'])) { ?>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
<?php } else { ?>
	<li> &laquo; <a href="/">Home</a></li>
<?php } ?>
</ul>
<h1>Contact</h1>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>#content" onreset="return form_reset()">
<?php
		general_error($error);
?>
	<p>To contact the Administrators of Athena or a participating student association, complete the following, and then click on <strong>Send</strong>.</p>
	<fieldset>
		<fieldset class="psuedo_p top-down">
			An asterisk (<span title="Required" class="required_field" >*</span>) indicates a required field.
			<ul>
				<li>
					<label for="to">To<span title="Required" class="required_field" >*</span></label>
<?php
		$assoc['sql'] = 'SELECT id, prog, name, abbreviation FROM associations ORDER BY abbreviation';
		if ( $assoc['query'] = mysql_query($assoc['sql']) and mysql_num_rows($assoc['query']) > 0 ) {
?>
					<select name="to" id="to" style="width:208px;"<?php if ( $error['to'] ) print ' class="error"'; ?> onchange="contact_warning(this.value)">
						<option value="0">Please select ...</option>
<?php
			while ($assoc['results'] = mysql_fetch_assoc($assoc['query'])) {
?>
						<option value="<?php print $assoc['results']['id'] ?>"<?php print $selected['to'][$assoc['results']['id']] ?>><?php print htmlentities($assoc['results']['name']) ?></option>
<?php
			}
?>
					</select><?php print $error['to'] ?>

<?php
		} else {
?>
					<input type="hidden" name="to" id="to" value="1" /><strong>Administrators of Athena</strong>
<?php
		}
?>
				</li>
<?php
		if ( $_SESSION['student']['validated'] == 1 ) {
?>
				<li>
					<label>Your name</label>
					<input type="hidden" name="name" value="<?php print $_SESSION['student']['first_name'].' '.$_SESSION['student']['last_name']; ?>" />
					<strong><?php print $_SESSION['student']['first_name'].' '.$_SESSION['student']['last_name']; ?></strong> ( <a href="/sessions.php?mode=sign_out">sign out</a> if this is not you )
				</li>
				<li>
					<label>Your email</label>
					<input type="hidden" name="email" value="<?php print $_SESSION['student']['email']; ?>" />
					<strong><?php print $_SESSION['student']['email']; ?></strong>
				</li>
<?php 
		} else {
?>
				<li>
					<label for="name">Your name<span title="Required" class="required_field" >*</span></label>
					<input type="text" name="name" id="name" value="<?php print $_POST['name']; ?>" class="text<?php if ( $error['name'] ) print ' error'; ?>" style="width:200px;" /><?php print $error['name']; ?>

				</li>
				<li>
					<label for="email">Your email<span title="Required" class="required_field" >*</span></label>
					<input type="text" name="email" id="email" value="<?php print $_POST['email']; ?>" class="text<?php if ( $error['email'] ) print ' error'; ?>" style="width:200px;" /><?php print $error['email']; ?>

				</li>
<?php	
		}
?>
				<li>
					<label for="subject">Subject<span title="Required" class="required_field" >*</span></label>
					<input type="text" name="subject" id="subject" value="<?php print $_POST['subject']; ?>" class="text<?php if ( $error['subject'] ) print ' error'; ?>" style="width:325px;" /><?php print $error['subject']; ?>

				</li>
				<li>
					<label for="message">Message<span title="Required" class="required_field" >*</span></label>
					<textarea name="message" id="message" rows="4" cols="30" style="width:325px;height:100px;"<?php if ( $error['message'] ) print ' class="error"'; ?>><?php print $_POST['message']; ?></textarea><?php print $error['message']; ?>

				</li>
				<li>
					<label class="major">Do you want to receive a copy of the email that will be sent?
						<input name="cc" id="cc" type="checkbox"<?php print $selected['cc'] ?> /></label>
				</li>
			</ul>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="button" class="back" value="&lsaquo; Cancel" onclick="history.go(-1)" />
			<input type="reset" class="back" value="Clear" />
			<input type="submit" class="forward" value="Send &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<?php
		$layout->output_footer();
}
?>
