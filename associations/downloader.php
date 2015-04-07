<?php
session_start();
header('Cache-control: private');

require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$set_id = $_GET['id'];
if ( is_numeric($set_id) && $user_authentication->validate_association() ) {

	//----------------------------------------------------------
	// If the visitor has requested a download, download the
	// file if it exist.
	//----------------------------------------------------------

	mysql_select_db($info['sql_db'], $info['sql_db_connect']);
	$download['sql'] = '	SELECT
									n.filename
								FROM
									courses AS c, sets AS n
								WHERE
									c.association_id = '.$_SESSION['association']['association_id'].' AND
									n.id = '.$set_id.' AND
									c.id = n.course_id AND
									n.distribution = "1" AND
									n.filename IS NOT NULL
								LIMIT 1';
	$download['query'] = mysql_query($download['sql'], $info['sql_db_connect']);
	$download['results'] = mysql_fetch_assoc($download['query']);

	$path = $info['download_path'].'/'.$set_id.'_'.$download['results']['filename'];

	if ( file_exists($path) && mysql_num_rows($download['query']) == 1 ) {
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
}
?>