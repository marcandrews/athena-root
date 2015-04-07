<?php
function implode_headers($headers) {
	foreach ($headers as $key => $value) {
		$output .= "{$key}: {$value} \r\n";
	}
	return $output;
}

function task($bid = NULL) {

	global $info;

	$headers['MIME-Version']		= "1.0";
	$headers['Content-type']		= "text/plain; charset=iso-8859-1";
	$headers['X-Priority']			= "3";
	$headers['X-MSMail-Priority']	= "Normal";
	$headers['X-Mailer']			= "PHP ".phpversion();

	$i = 0;
	$mails_per_run = 50;

	if ($bid) {
		$bid_sql = "b.id = '{$bid}' AND b.student_id = '{$_SESSION['student']['student_id']}' AND ";
	}

	$bulkmail['sql'] = "	SELECT	b.id, b.subject, b.content, b.reply_to_student, b.honor_receive_emails, b.clear_when_complete,
									a.name, a.abbreviation, a.email,
									CONCAT(s.first_name, ' ', s.last_name) AS student, s.email AS student_email
							FROM	task_bulkmail AS b, associations AS a, students AS s
							WHERE	{$bid_sql}
									b.active = true AND
									b.association_id = a.id AND
									b.student_id = s.id";
	if ($bulkmail['query'] = mysql_query($bulkmail['sql']) and mysql_num_rows($bulkmail['query']) > 0) {

		while ($bulkmail['results'] = mysql_fetch_assoc($bulkmail['query'])) {
			$headers['From'] = "{$bulkmail['results']['abbreviation']} <{$bulkmail['results']['email']}>";
			if ($bulkmail['results']['reply_to_student'] == 1) {
				$headers['Reply-To'] = "{$bulkmail['results']['student']} <{$bulkmail['results']['student_email']}>";
			}

			if ($bulkmail['results']['honor_receive_emails'] == 1) {
				$receive_emails = 's.receive_emails = true AND';
			}

			$queue['sql'] = "	SELECT	s.first_name, s.last_name, s.email,
										r.id
								FROM	students AS s, task_bulkmail_queue AS r
								WHERE	s.validated = true AND
										{$receive_emails}
										s.id = r.student_id AND
										r.task_bulkmail_id = {$bulkmail['results']['id']}
								ORDER BY id ASC";
			if ($queue['query'] = mysql_query($queue['sql']) and mysql_num_rows($queue['query']) > 0) {
				while ($queue['results'] = mysql_fetch_assoc($queue['query'])) {
					$to			= "{$queue['results']['first_name']} {$queue['results']['last_name']} <{$queue['results']['email']}>";
					$subject	= 	str_replace (
										array('{first_name}', '{last_name}'),
										array($queue['results']['first_name'], $queue['results']['last_name']),
										html_entity_decode (
											$bulkmail['results']['subject'],
											ENT_QUOTES
										)
									);
					$message	= 	wordwrap (
										str_replace (
											array('{first_name}', '{last_name}'),
											array($queue['results']['first_name'], $queue['results']['last_name']),
											html_entity_decode (
												$bulkmail['results']['content'],
												ENT_QUOTES
											)
										),
										72
									);
					if (mail($to, $subject, $message, implode_headers($headers))) {
						mysql_query("DELETE FROM task_bulkmail_queue WHERE id = {$queue['results']['id']}");
						$i++;
					}
					if ($i >= $mails_per_run) {
						if ($bid and mysql_num_rows($queue['query']) != $mails_per_run) {
							return mysql_num_rows($queue['query'])-$i;
						} else {
							return true;
						}
					}
				}
			} elseif ($queue['query'] and mysql_num_rows($queue['query']) == 0) {
				if ($bulkmail['results']['clear_when_complete'] == 1) {
					mysql_query("DELETE FROM task_bulkmail WHERE id = {$bulkmail['results']['id']}");
				}
			} else {
				return 'Could not retrieve recipients from queue';
			}
		}
		return true;
	} elseif ($bulkmail['query'] and mysql_num_rows($bulkmail['query']) == 0) {
		return true;
	} else {
		return 'Could not retrieve bulk mails';
	}
}
?>