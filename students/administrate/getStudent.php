<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$student['sql'] = '	SELECT id, last_name, first_name FROM students WHERE mcgill_id = "'.$_GET['mid'].'" LIMIT 1';
if	(	preg_match('/\d{9}/',$_GET['mid']) and
		$student['query'] = mysql_query($student['sql'], $info['sql_db_connect']) and mysql_num_rows($student['query']) == 1 and
		$user_authentication->validate_student() and 
		$user_authentication->validate_administrator($_GET['id'])
	) {
	$student['results'] = mysql_fetch_assoc($student['query']);
	if ( $student['results']['id'] != $_SESSION['student']['student_id'] ) {
		print '2;'.$student['results']['last_name'].';'.$student['results']['first_name'];
	} else {
		print '1;'.$student['results']['last_name'].';'.$student['results']['first_name'];
	}
} else {
	print '0';
}
?>