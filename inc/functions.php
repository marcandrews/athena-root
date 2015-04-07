<?php
function __autoload($name) {
	require_once($_SERVER['DOCUMENT_ROOT'].'inc/classes/'.$name.'.php');
}


function current_semester() {
//	return 1;
	if ( date('n') >= 9 and date('n') <= 12 ) {
		return 0;
	} elseif ( date('n') >= 1 and date('n') <= 4 ) {
		return 1;
	} elseif ( date('n') >= 5 and date('n') <= 8 ) {
		return 2;
	}
}

function current_school_year() {
	if (current_semester()==0) {
		return date('Y');
	} else {
		return date('Y')-1;
	}
}

function debug_array ($array) {
	print '<pre>';
	print_r($array);
	print '</pre>';
	exit;
}

function return_bytes($val) {
	$val = trim($val);
	$last = strtolower($val{strlen($val)-1});
	switch($last) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}
	return $val;
}

function error_inline ($message) {
	return '<img src="/i/!.png" alt="!" title="'.$message.'" class="error" />';
}

function general_error ( $error_array ) {
	if (count($error_array)>0) {
		print '	<p id="error_paragraph">There '.((count($error_array)==1) ? 'is an error that needs' : 'are errors that need').' to be resolved before Athena can continue.<br />Please hover over the <img src="/i/!.png" alt="!" /> for more details.</p>'."\r";
	}
}

function general_warning ( $warning_text ) {
	print '	<p id="warning_paragraph">'.$warning_text.'</p>';
}

function send_validation_email ( $student_id, $mcgill_id, $first_name, $last_name, $email ) {
	global $info;
	$validation_code = md5("{$student_id}_{$mcgill_id}");
	$to = "{$first_name} {$last_name} <{$email}>";
	$headers  = "From: {$info['email']}\n";
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Content-type: text/plain; charset=iso-8859-1\n";
	$headers .= "X-Priority: 3\n";
	$headers .= "X-MSMail-Priority: Normal\n";
	$headers .= "X-Mailer: php" . phpversion();
	$subject = "Athena Account Validation";
	$message = "{$first_name} {$last_name},

This email has been sent from {$info['site_url']}.

You have received this email because you requested to validate your Athena account.

------------------------------------------------
Validation Instructions Below
------------------------------------------------

We require that you validate your email address to ensure that you instigated this action. This protects against unwanted spam and malicious abuse.

To validate your Athena account, simply click on the following link:

{$info['site_url']}/students/validate.php?code={$validation_code}

You may be required to sign in; if so, please use your McGill ID as your password.

Regards,
The Athena Administration
{$info['site_url']}";
	$message = wordwrap($message, 70);
	return mail($to, $subject, $message, $headers);
}
















class layout {
	var $mode;
	var $title;
	var $message;
	var $success;
	var $log_event;
	var $redirect;
	var $redirect_delay = 2;

	function is_current($current_page) {
		if (strpos($_SERVER['PHP_SELF'], $current_page) === 0) {
			return 'class="current"';
		} else {
			return "href=\"{$current_page}\"";
		}
	}

	function output_header($title = NULL, $mode = NULL) {
		ob_start('ob_gzhandler');

//		header('Vary: Accept');
//		if (stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml')) {
//			header('Content-Type: application/xhtml+xml; charset=utf-8');
//		} else {
//			header('Content-Type: text/html; charset=utf-8');
//		}

		if ($mode == 'association') {
			$html['mode'] = 'for Student Associations ';
		}
		if ($mode == 'student') {
			$html['mode'] = 'for Students ';
		}
		if ($mode) {
			$html['mode_header'] = '<div id="subtitle">'.$html['mode'].'</div>';
		}
		if ($title != NULL) {
			$html['title'] = " {$html['mode']}| {$title}";
		}
		if (is_array($_SESSION['association'])) {
			$html['session'] = '<li title="Currently signed in as '.$_SESSION['association']['name'].'">Welcome '.$_SESSION['association']['abbreviation'].' <a href="/sessions.php?mode=sign_out" title="Sign out of Athena">( sign out )</a></li>';
			$html['home'] = '<li><a '.$this->is_current('/associations/index.php').'>student association home</a></li>';
		} elseif (is_array($_SESSION['student'])) {
			$html['session'] = '<li title="Currently signed in as '.$_SESSION['student']['first_name'].' '.$_SESSION['student']['last_name'].' ('.$_SESSION['student']['mcgill_id'].')">Welcome '.$_SESSION['student']['first_name'].' <a href="/sessions.php?mode=sign_out" title="Sign out of Athena">( sign out )</a></li>';
			if ($_SESSION['student']['validated']==1) {
				$html['home'] = '<li><a '.$this->is_current('/students/index.php').'>student home</a></li>';
			} else {
				$html['home'] = '<li><a '.$this->is_current('/students/validate.php').'>validate</a></li>';
			}
		} else {
			$html['session'] = '<li><a href="/sessions.php">Sign-in for Students</a></li><li><a href="/sessions.php?mode=association">Sign-in for Student Associations</a></li>';
			$html['home'] = '<li><a '.$this->is_current('/index.php').'>home</a></li>';
		}
		$home = $this->is_current("/index.php");
		$news = $this->is_current("/news.php");
		$help = $this->is_current("/help.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Athena<?php print $html['title'] ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="en-ca" />
<meta name="description" content="The online NTC manager for students and student associations of McGill University." />
<meta name="keywords" content="athena, mcgill, science, undergraduate, society, societies, council, councils, students, associations, notes, note taking club, ntc, ntcs" />
<meta name="robots" content="index,follow" />
<link rel="shortcut icon" href="/i/favicon.ico" />
<link type="text/css" rel="stylesheet" href="/css/screen.css" media="screen" />
<!--[if lt IE 7]><link type="text/css" rel="stylesheet" href="/css/screen_ie6.css" media="screen" /><![endif]-->
<!--[if gte IE 7]><link type="text/css" rel="stylesheet" href="/css/screen_ie7.css" media="screen" /><![endif]-->
<link type="text/css" rel="stylesheet" href="/css/print.css" media="print" />
<script type="text/javascript" src="/serve_js.php?file=/js/jqeury/jquery"></script>
<script type="text/javascript" src="/js/default.js"></script>
</head>
<body>
<div id="wrapper">
	<div id="margin-top"></div>
	<noscript><p>Some features of Athena may require JavaScript. However, it seems JavaScript is either disabled or not supported by your browser. To utilize all features, enable JavaScript by changing your browser options, and then <a href="<?php print $_SERVER['PHP_SELF'] ?>">try again</a>.</p></noscript>
	<div id="banner">
		<div id="title">Athena</div>
		<?php print $html['mode_header'] ?>	</div>
	<div id="nav-toplevel">
		<div id="nav-meta">
			<ul title="Top Level Navigation bar">
				<li><a href="#content">skip to content</a></li><?php print $html['session'] ?><li><a href="/contact.php" title="Contact the Administrators of Athena or a participating student association">contact</a></li>
			</ul>
		</div>
		<div id="nav-main">
			<ul title="Major Site Sections">
				<?php print $html['home'] ?><li><a <?php print $news ?>>News</a></li><li><a <?php print $this->is_current('/participants.php') ?>>Participants</a></li><li><a <?php print $help ?>>help</a></li>
			</ul>
		</div>
	</div>
	<div id="content">
<!-- Start of main content section -->


<?php
	}
	
	function output_footer() {
		global $info;
?>


<!-- End of main content section -->
<?php
		if (is_array($_SESSION['student']) and strpos($_SERVER['PHP_SELF'],'/students') === 0 and $_SERVER['PHP_SELF'] != '/students/validate.php') {
?>
	</div>
	<div id="nav-subs">
		<div id="nav-section">
			<ul title="Current Section Subsections">
				<li>My NTCs
					<ul>
<?php
			foreach ($info['semesters'] as $key => $value) {
				if ($key <= current_semester()) {
?>
						<li><a href="/students/?semester=<?php print $key ?>" title="My NTCs for the <?php print $value ?> Semester"><?php print $value ?></a></li>
<?php
				}
			}
?>
					</ul>
				</li>
<?php
			$administrate['sql'] = '	SELECT	id, name, abbreviation
										FROM	associations
										WHERE	administrator = '.$_SESSION['student']['student_id'].'
										ORDER BY abbreviation';
			if ( $administrate['query'] = mysql_query($administrate['sql']) and mysql_num_rows($administrate['query']) > 0 ) {
?>
				<li>Administrate
					<ul>
<?php
				 while ($administrate['results'] = mysql_fetch_assoc($administrate['query'])) {
?>
						<li><a href="/students/administrate/?id=<?php print $administrate['results']['id'] ?>" class="small-caps" title="<?php print $administrate['results']['name'] ?>"><?php print ucwords(strtolower($administrate['results']['abbreviation'])) ?></a></li>
<?php
				}
?>
					</ul>
				</li>
<?php
			}
			$coordinator['sql'] = '	SELECT		c.id, CONCAT(c.prog_abbrev,c.course_code) AS code, c.course_name, c.semester, c.year
									FROM		courses AS c, purchases AS p
									WHERE		(c.semester <= "'.current_semester().'" AND c.year <= "'.current_school_year().'") AND
												c.id = p.course_id AND
												p.student_id = '.$_SESSION['student']['student_id'].' AND
												p.coordinator = true
									ORDER BY	c.year DESC, c.semester DESC, c.prog_abbrev ASC, c.course_code ASC';			
			if ($coordinator['query'] = mysql_query($coordinator['sql']) and mysql_num_rows($coordinator['query']) > 0) {
?>
				<li>Coordinate
					<ul>
<?php
				while ($coordinator['results'] = mysql_fetch_assoc($coordinator['query'])) {
					if ($coordinator['results']['semester'] > 0) $coordinator['results']['year'] += 1;
?>
						<li><a href="/students/coordinate/?cid=<?php print $coordinator['results']['id'] ?>" title="<?php print $coordinator['results']['course_name'] ?> (<?php print $info['semesters'][$coordinator['results']['semester']] ?> <?php print $coordinator['results']['year'] ?>)"><?php print $coordinator['results']['code'] ?> <span style="font-size:75%;"><?php print $info['semesters'][$coordinator['results']['semester']] ?> &rsquo;<?php print substr($coordinator['results']['year'],-2) ?></span></a></li>
<?php
				}
?>
					</ul>
				</li>
<?php
			}
?>
				<li><a href="/students/edit_profile.php">Edit My Profile</a></li>
			</ul>
		</div>
<?php
		} elseif (is_array($_SESSION['association']) and strpos($_SERVER['PHP_SELF'],'/associations') === 0) {
?>
	</div>
	<div id="nav-subs">
		<div id="nav-section">
			<ul title="Current Section Subsections">
				<li><a href="/associations/purchases.php">Purchases</a></li>
				<li><a href="/associations/receivables.php">Receivables</a></li>
				<li><a href="/associations/pickups.php">Pickups</a></li>
				<li><a href="/associations/ntc_summary.php">NTC Summary</a></li>
				<li><a href="/associations/accounts_summary.php">Accounts Summary</a></li>
				<li><a href="/associations/accounts_history.php">Accounts History</a></li>
				<li><a href="/associations/edit_students.php">Edit Students</a></li>
			</ul>
		</div>
<?php
		}
?>
	</div>
	<div id="footer-spacer"></div>
</div>
<div id="footer">
	<div id="standards">
		<a href="http://validator.w3.org/check/referer"><abbr title="Extensible HyperText Markup Language">XHTML</abbr> 1.1</a>
		|
		<a href="http://jigsaw.w3.org/css-validator/check/referer"><abbr title="Cascading Style Sheets">CSS</abbr> 3.0</a>	</div>
	Copyright &copy; <?php print date('Y') ?> Marc Andrews. McGill University is neither affiliated with Athena nor responsible for its content.</div>
<div id="margin-bottom"></div>
<script type="text/javascript" src="http://www.google-analytics.com/urchin.js"></script>
<script type="text/javascript">_uacct="UA-498796-1";urchinTracker();</script>
</body>
</html>
<!-- Last updated on <?php print date("F jS, Y @ H:i:s T",getlastmod()) ?> -->
<?php
		ob_end_flush();
	}

	function redirector ($title, $message, $redirect = NULL, $delay = 2) {
		ob_start('ob_gzhandler');
		if ( $redirect != NULL ) {
			$html['meta_reditect'] = "<meta http-equiv=\"refresh\" content=\"$delay;url=$redirect\" />";
			$html['link_reditect'] = "( <a href=\"$redirect\">or click here if you do not wish to wait</a> )";
		} else {
			$html['link_reditect'] = "( <a href=\"javascript:history.back(1)\">click here to go back</a> )";
		}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php print $title ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="en-ca" />
<?php print $html['meta_reditect'] ?>
<link rel="shortcut icon" href="/images/favicon.ico" />
<link type="text/css" href="/css/screen.css" rel="stylesheet" media="screen" />
<!--[if lt IE 7]><link type="text/css" rel="stylesheet" href="/css/screen_ie6.css" media="screen" /><![endif]-->
<!--[if IE 7]><link type="text/css" rel="stylesheet" href="/css/screen_ie7.css" media="screen" /><![endif]-->
</head>
<body id="redirector">
<div id="wrapper">
	<div id="margin-top"></div>
	<div id="content">
		<h1><?php print $title ?></h1>
		<div class="psuedo_p">
			<p><?php print $message ?></p>
<?php
	if (mysql_errno()) {
?>
			<p>MySQL error #<?php print mysql_errno() ?>:<br />
				<?php print mysql_error() ?></p>
<?php
	}
?>
			<p><?php print $html['link_reditect'] ?></p>
		</div>
	</div>
	<div id="margin-bottom"></div>
</div>
</body>
</html>
<?php
		ob_end_flush();
		exit;
	}

} $layout = new layout;



class user_authentication {

	function sign_in ($username, $password, $type='student') {
		global $info;
		global $layout;
		$password = md5($password);
		if ( $type == 'association' ) {
			$signin['sql'] = 'SELECT id AS association_id, prog, prog_abbrev, name, abbreviation FROM associations WHERE sign_in = "'.$username.'" AND password = "'.$password.'"';
		} else {
			$signin['sql'] = 'SELECT id AS student_id, mcgill_id, first_name, last_name, email, validated FROM students WHERE mcgill_id = "'.$username.'" AND password = "'.$password.'"';
		}
		if ($signin['query'] = mysql_query($signin['sql']) and mysql_num_rows($signin['query']) == 1) {
			$signin['results'] = mysql_fetch_assoc($signin['query']);
			$_SESSION = array();
			$_SESSION[$type] = $signin['results'];
			if ($type == 'student')
				@mysql_query('UPDATE students SET last_visited = NOW(), last_session = "'.md5(session_id()).'" WHERE id = "'.$_SESSION['student']['student_id'].'" LIMIT 1');
			return true;
		} elseif ($signin['query'] and mysql_num_rows($signin['query']) == 0) {
			return false;
		} else {
			$layout->redirector('Athena | Error', 'An authentication error has occured. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
		}
	}

	function sign_out () {
		$_SESSION = array();
		session_destroy(); 
		session_start();
	}	

	function validate() {
		global $info;
		global $layout;
		if (is_array($_SESSION['association'])) {
			$validate['sql'] = 'SELECT id FROM associations WHERE id = "'.$_SESSION['association']['association_id']. '" LIMIT 1';
		} else {
			$validate['sql'] = 'SELECT	id
								FROM	students
								WHERE 	id = "'.$_SESSION['student']['student_id'].'" AND
										mcgill_id = "'.$_SESSION['student']['mcgill_id'].'" AND
										first_name = "'.$_SESSION['student']['first_name'].'" AND
										last_name = "'.$_SESSION['student']['last_name'].'" AND
										validated = "'.$_SESSION['student']['validated'].'"
								LIMIT 1';
		}
		if ($validate['query'] = mysql_query($validate['sql']) and mysql_num_rows($validate['query']) == 1) {
			return true;
		} elseif ($validate['query'] and mysql_num_rows($validate['query']) == 0) {
			$this->sign_out();
			$_SESSION['referer'] = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
			header('Location: /sessions.php');
		} else {
			$layout->redirector('Athena | Error', 'An authentication error has occured. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
		}
	}

	function validate_association() {
		global $info;
		global $layout;
		$assoc['sql'] = 'SELECT id FROM associations WHERE id = "'.$_SESSION['association']['association_id']. '" LIMIT 1';
		if ($assoc['query'] = mysql_query($assoc['sql']) and mysql_num_rows($assoc['query']) == 1) {
			return true;
		} elseif ($assoc['query'] and mysql_num_rows($assoc['query']) == 0) {
			$this->sign_out();
			$_SESSION['referer'] = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
			header('Location: /sessions.php?mode=association');
		} else {
			$layout->redirector('Athena | Error', 'An authentication error has occured. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
		}
	}

	function validate_student() {
		global $info;
		$student['sql'] = '	SELECT	id
							FROM	students
							WHERE 	id = "'.$_SESSION['student']['student_id'].'" AND
									mcgill_id = "'.$_SESSION['student']['mcgill_id'].'" AND
									first_name = "'.$_SESSION['student']['first_name'].'" AND
									last_name = "'.$_SESSION['student']['last_name'].'" AND
									validated = "'.$_SESSION['student']['validated'].'" AND
									last_session = "'.md5(session_id()).'"
							LIMIT 1';
		$student['query'] = mysql_query($student['sql']);
		$student['num_rows'] = mysql_num_rows($student['query']);
		if ($student['query'] and $student['num_rows'] == 1) { /* Student exists. */
			if ($_SESSION['student']['validated'] == 1) { /* Student is validated. */
				return true;
			} else { /* Student is NOT validated. */
				if ($_SERVER['PHP_SELF'] != '/students/validate.php') { /* Redirect the student to the validated page. */
					if ($_GET['code']) {
						header('Location: /students/validate.php?code='.$_GET['code']);
					} else {
						header('Location: /students/validate.php');
					}
				} else {
					return true;
				}
			}
		} elseif ($student['query'] and $student['num_rows'] == 0) { /* Student does not exist. */
			$this->sign_out();
			$_SESSION['referer'] = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
			header('Location: /sessions.php');
		} else { /* MySQL error. */
			$layout->redirector('Athena | Error', 'An authentication error has occured. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
		}
	}

	function validate_administrator($did) {
		global $info;
		global $layout;
		$student['sql'] = 'SELECT id FROM associations WHERE id = "'.$did.'" AND administrator = "'.$_SESSION['student']['student_id'].'"';
		if ($student['query'] = mysql_query($student['sql']) and mysql_num_rows($student['query']) == 1) {
			return true;
		} elseif ($student['query'] and mysql_num_rows($student['query']) == 0) {
			$layout->redirector('Unauthorized ...', 'You do not appear to be an administrator of this student association. Now redirecting you to ...', '/students/', 5);
		} else {
			$layout->redirector('Athena | Error', 'An authentication error has occured. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
		}
	}
	
	function validate_coordinator($pid) {
		global $info;
		global $layout;
		$student['sql'] = 'SELECT id FROM purchases WHERE course_id = "'.$pid.'" AND student_id = "'.$_SESSION['student']['student_id'].'" AND coordinator = true';
		if ($student['query'] = mysql_query($student['sql']) and mysql_num_rows($student['query']) == 1) {
			return true;
		} elseif ($student['query'] and mysql_num_rows($student['query']) == 0) {
			$layout->redirector('Unauthorized ...', 'You do not appear to be a coordinator of this course. Now redirecting you to ...', '/students/', 5);
		} else {
			$layout->redirector('Athena | Error', 'An authentication error has occured. Please <a href="javascript:location.reload();">refresh</a> this page and try again. If the problem persists, <a href="/contact.php">contact your student association</a>.');
		}
	}
} $user_authentication = new user_authentication;



class common_variables {

	function current_semester() {
		if ( date('n') >= 9 and date('n') <= 12 ) {
			return 0;
		} elseif ( date('n') >= 1 and date('n') <= 4 ) {
			return 1;
		} elseif ( date('n') >= 5 and date('n') <= 8 ) {
			return 2;
		}
	}
	
	function current_school_year() {
		if ($this->current_semester() == 0) {
			return date('Y');
		} else {
			return date('Y')-1;
		}
	}

} $common_variables = new common_variables;



class output_formatting {

	function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val{strlen($val)-1});
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

} $output_formatting = new output_formatting;



class error_handler {

	function error_inline ($message) {
		return '<img src="/i/!.png" alt="!" title="'.$message.'" class="error" />';
	}
	
	function general_error ($error_array) {
		if (count($error_array) > 0) {
			print '	<p id="error_paragraph">There '.((count($error_array)==1) ? 'is an error that needs' : 'are errors that need').' to be resolved before Athena can continue.<br />Please hover over the <img src="/i/!.png" alt="!" /> for more details.</p>'."\r";
		}
	}
	
	function general_warning ($warning_text) {
		print '	<p id="warning_paragraph">'.$warning_text.'</p>';
	}

} $error_handler = new error_handler;



function parse_bbcode ($str) {
	// Convert all applicable characters to HTML entities, while preventing "double" encoding
	$translation_table = get_html_translation_table (HTML_ENTITIES,ENT_QUOTES);
	$translation_table[chr(38)] = '&';
	$str = preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "&amp;", strtr($str, $translation_table));

	// Parse custom BBCode
	$search = array	(
						'/\[url\=(.*?)\](.*?)\[\/url\]/is',		// [url]
						'/\[abbr\=(.*?)\](.*?)\[\/abbr\]/is'	// [abbr]
					);
	$replace = array(
						'<a href="$1">$2</a>',					// [url]
						'<abbr title="$1">$2</abbr>'			// [abbr]
					);

	// Return the parsed string
	return preg_replace($search, $replace, $str);
}
?>