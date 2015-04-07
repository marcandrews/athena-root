<?php
session_start();
header('Cache-control: private');
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
 		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$download['results']['filename'].'"');
		header('Content-length: '.filesize($path));
		header('Expires: '.gmdate('D, d M Y H:i:s', mktime(date('H')+2, date('i'), date('s'), date('m'), date('d'), date('Y'))).' GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		readfile($path);
	} else {
		$layout->redirector('Unauthorized ...', 'You do not appear to have access.');
		exit;
	}
?>