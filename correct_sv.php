<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

if ( $_SESSION['student']['student_id'] != 1 ) header('Location: /students/');

print '<pre>';

/* Flag the database to not commit after each executed. */
$query = 'SET AUTOCOMMIT=0';
$result_query = mysql_query($query,$info['sql_db_connect']);

/* Begin the query. */
$query = 'BEGIN';
$result_query = mysql_query($query,$info['sql_db_connect']);

/* Get students with low integrity. */
$students['sql'] = 'SELECT s.id, s.mcgill_id, s.created FROM students AS s WHERE (email = "" OR email IS NULL) AND validated = "1" AND protected = "0"';
if ( $students['query'] = mysql_query($students['sql'],$info['sql_db_connect']) and $students['num_rows'] = mysql_num_rows($students['query']) > 0 ) {
	$success = true;
	$students['results'] = mysql_fetch_assoc($students['query']);
	
	/* Correct low integrity. */
	do {
		$validations['sql'] = '	REPLACE	validations
								SET		student_id = '.$students['results']['id'].',
										code = MD5("'.$students['results']['id'].'_'.$students['results']['mcgill_id'].'"),
										created = "'.$students['results']['created'].'"';
		$update['sql'] = '	UPDATE	students
							SET		password = MD5('.$students['results']['mcgill_id'].'),
									email = NULL,
									email_alt = NULL,
									validated = "0",
									modified = 0
							WHERE	id = '.$students['results']['id'];
		if ( !$validations['query'] = mysql_query($validations['sql'],$info['sql_db_connect']) or !$update['query'] = mysql_query($update['sql'],$info['sql_db_connect']) ) {
			$success = false;
			break;
		}
	} while ( $students['results'] = mysql_fetch_assoc($students['query']) );

	/* Commit integrity corrects to the database. */
	if ( $success != false ) {
		$query = 'COMMIT';
		if ( $result_query = mysql_query($query,$info['sql_db_connect']) ) {
			print 'Database integrity has been corrected (Number of corrected entries: '.$students['num_rows'].').';
		} else {
			print 'There was an error correcting students with low integrity.<br>'.mysql_error();
		}
	}
} elseif ( mysql_num_rows($students['query']) == 0 ) {
	print 'Database integrity is normal.';
} else {
	print 'There was an error getting students with low integrity.<br>'.mysql_error();
}

print '</pre>';
?>