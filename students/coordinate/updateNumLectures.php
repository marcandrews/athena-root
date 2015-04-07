<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

if	(
		$user_authentication->validate_student() and
		$user_authentication->validate_coordinator($_GET['cid']) and
		preg_match('/^[1-9][0-9]?$/',$_GET['tl']) and
		mysql_query('UPDATE courses SET total_lectures = '.(int)$_GET['tl'].' WHERE id = '.(int)$_GET['cid'],$info['sql_db_connect'])
	) {
	print '1;'.$_GET['tl'];
} else {
	print '0';
}
?>