<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$student['sql'] = '	SELECT id, last_name, first_name FROM students WHERE mcgill_id = "'.$_GET['mid'].'" LIMIT 1';
if	(	$user_authentication->validate_association() and
		preg_match('/\d{9}/',$_GET['mid']) and
		$student['query'] = mysql_query($student['sql'], $info['sql_db_connect']) and mysql_num_rows($student['query']) == 1 ) {
	$student['results'] = mysql_fetch_assoc($student['query']);
	print '2;'.$student['results']['last_name'].';'.$student['results']['first_name'];
	$check['sql'] = '	SELECT	c.id
						FROM	courses AS c, purchases AS p, students AS s
						WHERE	c.id = p.course_id AND
								p.student_id = s.id AND
								s.mcgill_id = "'.$_GET['mid'].'"';
	if (	$check['query'] = mysql_query($check['sql'],$info['sql_db_connect']) and
			($check['num_rows'] = mysql_num_rows($check['query'])) > 0 ) {
		print ';';
		while ( $check['results'] = mysql_fetch_assoc($check['query']) ) {
			$n++;
			print '#cid_'.$check['results']['id'].( $check['num_rows'] > $n ? ',' : '');
		} 
	}
} elseif ( $student['query'] = mysql_query($student['sql'], $info['sql_db_connect']) and mysql_num_rows($student['query']) == 0 ) {
	print '1';
} else {
	print '0';
}
?>