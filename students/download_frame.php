<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$_GET['id'] = (int) $_GET['id'];

	$download['sql'] = '	(
								SELECT	a.website, 0 AS show_website,
										CONCAT(c.prog_abbrev,c.course_code) AS course_code,
										n.num, n.date, n.filename
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
										n.num, n.date, n.filename
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
										n.num, n.date, n.filename
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
	if	(
		 	$download['query'] = mysql_query($download['sql'], $info['sql_db_connect'])
			and mysql_num_rows($download['query']) == 1
			and $download['results'] = mysql_fetch_assoc($download['query']) and
			file_exists($path = $info['download_path'].'/'.$_GET['id'].'_'.$download['results']['filename'])
		) {
?>
<html>
<head>
<title>Athena for Students | Now processing your download of <?php print $download['results']['course_code'] ?> Set #<?php print $download['results']['num'] ?></title>
<style type="text/css">
html {
font: 11px Tahoma, Verdana, Arial, sans-serif;
height:100%;
}
body {
height:107px;
margin:0;
padding:0;
background-color:#f8fbff;
border-bottom:3px solid #ECECEC;
}
table {
font-size:100%;
width:100%;
height:100px;
}
h1,h2 {
font-family:serif;
font-style:italic;
text-align:right;
letter-spacing:0.04em;
font-size:2.75em;
color:#555;
margin:0;
padding:0;
}
h2 {
font-size:1.5em;
color:#666;
}
h1:first-letter {
font-size:1.7em;
text-transform:uppercase;
}
a:link {
color:#888;
text-decoration:underline;
}
a:active {
color:#000;
text-decoration:none;
}
a:visited {
color:#888;
text-decoration:underline;
}
a:hover {
color:#ccc;
text-decoration:none;
}
#controls {
position:absolute;
top:5px;
right:5px;
text-align:right;
}
</style>
</head>
<script type="text/javascript">
//<![CDATA[

setTimeout("document.location='/students/downloader.php?id=<?php print $_GET['id'] ?>'", 5000)

//]]>
</script>
<body>

<table>
	<tr>
		<td width="49%" nowrap="nowrap"><h1>Athena</h1>
			<h2>Now processing your download</h2>
		</td>
		<td>&nbsp;</td>
		<td width="49%" nowrap="nowrap">Course: <strong><?php print $download['results']['course_code'] ?></strong><br>
			Set number: <strong><?php print $download['results']['num']; ?></strong><br>
			Set date: <strong><?php print date('F jS, Y',strtotime($download['results']['date'])); ?></strong><br>
			Filename: <strong><?php print $download['results']['filename']; ?></strong> (<?php print round(filesize($path)/1024,0); ?>KB) </td>
	</tr>
	<tr>
		<td colspan="3" align="center" nowrap="nowrap">Your download will begin in <strong>5 seconds</strong>. Manually download <a href="/students/downloader.php?id=<?php print $_GET['id'] ?>">this set</a>.<br />
			While you wait, why not visit your <a href="http://<?php print $download['results']['website'] ?>" target="_blank">student association's website</a>?</td>
	</tr>
</table>
<div id="controls"><a href="/students/more_set_info.php?id=<?php print $_GET['id'] ?>#content" target="_top">Go back to Athena &laquo;</a><br />
	<a href="http://<?php print $download['results']['website'] ?>" target="_top">Remove frame &times;</a></div>
</body>
</html>
<?php
	}
?>