<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$_GET['id'] = (int) $_GET['id'];

	$download['sql'] = '	(
								SELECT	a.website, 0 AS show_website,
										CONCAT(c.prog_abbrev,c.course_code) AS course_code,
										n.num
								FROM	associations AS a, courses AS c, sets AS n
								WHERE	a.id = "'.$_SESSION['association']['association_id'].'" AND
										a.id = c.association_id AND
										c.id = n.course_id AND
										n.id = "'.$_GET['id'].'" AND
										n.distribution = "1"
								LIMIT 1
							) UNION (
								SELECT	a.website, 0 AS show_website,
										CONCAT(c.prog_abbrev,c.course_code) AS course_code,
										n.num
								FROM	associations AS a, courses AS c, purchases AS p, sets AS n
								WHERE	a.id = c.association_id AND
										c.id = n.course_id AND
										n.id = "'.$_GET['id'].'" AND
										n.distribution = "1" AND
										c.id = p.course_id AND
										p.student_id = "'.$_SESSION['student']['student_id'].'" AND
										p.coordinator = true
								LIMIT 1
							) UNION (
								SELECT	a.website, a.show_website,
										CONCAT(c.prog_abbrev,c.course_code) AS course_code,
										n.num
								FROM	associations AS a, courses AS c, purchases AS p, sets AS n
								WHERE	a.id = c.association_id AND
										c.id = n.course_id AND
										n.id = "'.$_GET['id'].'" AND
										n.distribution = "1" AND
										c.id = p.course_id AND
										p.student_id = "'.$_SESSION['student']['student_id'].'" AND
										p.coordinator = false
								LIMIT 1
							)
							LIMIT 1';
	if ( $download['query'] = mysql_query($download['sql'], $info['sql_db_connect']) and mysql_num_rows($download['query']) == 1 ) {
		$download['results'] = mysql_fetch_assoc($download['query']);
		if ( $download['results']['show_website'] == '1' ) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Athena for Students | Now processing your download of <?php print $download['results']['course_code'] ?> Set #<?php print $download['results']['num'] ?></title>
<link rel="shortcut icon" href="/i/favicon.ico" />
<link href="/css/athena.css" rel="stylesheet" type="text/css" media="screen" />
<link href="/redirector.css" rel="stylesheet" type="text/css" media="screen" />
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript"></script>
<script type="text/javascript">
//<![CDATA[

	_uacct = "UA-498796-1";
	urchinTracker();
//]]>
</script>
</head>

<frameset rows="110,*">
	<frame src="/students/download_frame.php?id=<?php print $_GET['id'] ?>" frameborder="0" noresize="noresize" scrolling="no" />
	<frame src="http://<?php print $download['results']['website'] ?>" frameborder="0" noresize="noresize" />
	<noframes>
		<body onload="setTimeout(window.location='/students/downloader.php?id=<?php print $_GET['id'] ?>',3000)">
		<div id="wrapper">
			<div id="margin-top"></div>
			<div id="container">
				<h1>Now processing your download</h1>
				<p>Now processing your download of <a href="/students/downloader.php?id=<?php print $_GET['id'] ?>"><?php print $download['results']['course_code'] ?> Set #<?php print $download['results']['num'] ?></a>.<br />
					<br />
					<a href="javascript:history.back(1)">click here to go back</a></p>
			</div>
			<div id="margin-bottom"></div>
		</div>
		</body>
	</noframes>
</frameset>

</html>
<?php
		} else {
			header('Location: /students/downloader.php?id='.$_GET['id']);
			exit;
		}
	} else {
		print mysql_error();
		$layout->redirector('Error.', 'Sorry. That file was not found.');
	}
?>