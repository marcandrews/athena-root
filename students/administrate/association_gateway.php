<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$_GET['id'] = (int)$_GET['id'];
$user_authentication->validate_student();
$user_authentication->validate_administrator($_GET['id']);

$association['sql'] = 'SELECT id AS association_id, prog, prog_abbrev, name, abbreviation FROM associations WHERE id = '.$_GET['id'].' AND administrator = '.$_SESSION['student']['student_id'].' LIMIT 1';
if ($association['query'] = mysql_query($association['sql'],$info['sql_db_connect']) and mysql_num_rows($association['query']) == 1) {
	$_SESSION = array();
	session_destroy();
	session_start();
	$_SESSION['association'] = mysql_fetch_assoc($association['query']);
	header('Location: /associations/');
} else {
	header('Location: /sessions.php?mode=association');
}
?>