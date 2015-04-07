<?php
ini_set('error_reporting', E_ERROR | E_PARSE);
ini_set('display_errors', true);

$info['sql_host']		=	'localhost';
$info['sql_db']			=	'athena';
$info['sql_user']		=	'athena';
$info['sql_pass']		=	'tasAjet3';
$info['email']			=	'Athena <athena@sus.mcgill.ca>';
$info['site_url']		=	'http://'.$_SERVER['HTTP_HOST'];
$info['site_path']		=	$_SERVER['DOCUMENT_ROOT'];
$info['download_path']	=	$info['site_path'].'d';

$info['semesters']				= array('Fall','Winter','Summer');
$info['distribution_methods']	= array('Print','Online','External');
$info['news_recipients']		= array('student','coordinator','association','administrator');

setlocale(LC_MONETARY, 'en_CA');

$info['date']['short']	= 'Y/m/d';
$info['date']['medium']	= 'Y/m/d @ H:i';
$info['date']['long']	= 'l, F dS, Y, H:i';

require_once($_SERVER['DOCUMENT_ROOT'].'inc/functions.php');

if ($info['sql_db_connect'] = mysql_connect($info['sql_host'], $info['sql_user'], $info['sql_pass'])) {
	mysql_select_db($info['sql_db']);
} else {
	$layout->redirector('Athena is unavailable', 'Sorry for the inconvenience, but Athena is currently unavailable. Please <a href="javascript:location.reload(true)">refresh this page</a> and try again in a few moments.<br /><br />Error: '.mysql_error());
}

require_once($_SERVER['DOCUMENT_ROOT'].'inc/tasks.php');
?>