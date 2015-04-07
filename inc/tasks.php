<?php
function log_task($task_id, $descr, $error = false) {
	global $info;
	if ($error == false) {
		$task['sql'] = "SELECT id FROM task_manager WHERE id = {$task_id} AND log = false";
		if ($task['query'] = mysql_query($task['sql']) and mysql_num_rows($task['query']) == 1) {
			return;
		}
	}
	$task['sql'] = "INSERT INTO task_logs SET task_id = '{$task_id}', descr = '$descr', ip = '{$_SERVER['REMOTE_ADDR']}', created = NOW()";
	return $task['query'] = mysql_query($task['sql']);
}

function set_next_runtime($task_id) {
	global $info;
	$hour	= date('H');
	$minute	= date('i') + 1;
	$day	= date('d');
	$month	= date('m');
	$year	= date('Y');
	$run_next = date('Y-m-d H:i:s', mktime($hour+1, $minute, 0, $month, $day, $year));
	if ($task['query'] = mysql_query("SELECT * FROM task_manager WHERE id = {$task_id}") and mysql_num_rows($task['query']) == 1) {
		$task['results'] = mysql_fetch_assoc($task['query']);
		if ($task['results']['run_minute'] > 0) {
			$minute	+= $task['results']['run_minute'];
		}
		if ($task['results']['run_hour'] > 0) {
			$hour	+= $task['results']['run_hour'];
		}
		if ($task['results']['run_day'] > 0) {
			$hour	 = $task['results']['run_hour'];
			$minute  = $task['results']['run_minute'];
			$day	 = $task['results']['run_day'];
			$month	+= 1;
		}
		if ($task['results']['run_month'] > 0) {
			$hour	 = $task['results']['run_hour'];
			$minute	 = $task['results']['run_minute'];
			$month	 = $task['results']['run_month'];
			$year	+= 1;
		}
		if ($task['results']['run_weekday'] > 0) {
			$hour	 = $task['results']['run_hour'];
			$minute	 = $task['results']['run_minute'];
			$day	+= 7 - (date('w') - $task['results']['run_weekday']);
			$month	 = date('m');
			$year	 = date('Y');
		}
		$run_next = date('Y-m-d H:i:s', mktime($hour, $minute, 0, $month, $day, $year));
	}
	return mysql_query("UPDATE task_manager SET run_next = '{$run_next}' WHERE id = '{$task_id}'") * mysql_affected_rows();
}

function try_again($task_id) {
	global $info;
	$hour	= date('H') + 1;
	$minute	= date('i') + 1;
	$day	= date('d');
	$month	= date('m');
	$year	= date('Y');
	$run_next = date('Y-m-d H:i:s', mktime($hour, $minute, 0, $month, $day, $year));
	return mysql_query("UPDATE task_manager SET run_next = '{$run_next}' WHERE id = '{$task_id}'") * mysql_affected_rows();
}

function lock_task($task_id) {
	global $info;
	return mysql_query("UPDATE task_manager SET locked = NOW() WHERE id = '{$task_id}'") * mysql_affected_rows();
}

function unlock_task($task_id) {
	global $info;
	return mysql_query("UPDATE task_manager SET locked = 0 WHERE id = '{$task_id}'") * mysql_affected_rows();
}

// Get the task.
$task['sql'] = 'SELECT * FROM task_manager WHERE run_next <= NOW() AND enabled = true ORDER BY run_next ASC LIMIT 1';
if ($task['query'] = mysql_query($task['sql']) and mysql_num_rows($task['query']) == 1) {
	$task['results'] = mysql_fetch_assoc($task['query']);

	// Is this task locked?
	if ($task['results']['locked'] != 0) {
		// This task is locked; do not run it.

		if (time() - strtotime($task['results']['locked']) > 1800) {
			// This task has been locked for too long and there may be a problem with it.
			// Set the next runtime and unlock the task.
			set_next_runtime($task['results']['id']);
			unlock_task($task['results']['id']);
			log_task($task['results']['id'], "Task was locked for longer than 30 minutes.", true);
		}
	} else {
		// This task is not locked; run it and lock the it while it runs.
		lock_task($task['results']['id']);

		// Run the task.
		$task_file = "{$info['site_path']}/inc/tasks/{$task['results']['file']}";
		if (is_file($task_file)) {
			require_once($task_file);
			if (function_exists('task') and mysql_query('START TRANSACTION') and ($task_result = task()) === true and mysql_query('COMMIT')) {
				// The task ran successfully, so set its next runtime, log it (if applicable) and unlock it.
				set_next_runtime($task['results']['id']);
				log_task($task['results']['id'], "Task successfully ran.");
				unlock_task($task['results']['id']);
			} else {
				// The task ran unsuccessfully, so try to run it again in an hour, log its error, and unlock it.
				mysql_query('ROLLBACK');
				try_again($task['results']['id']);
				log_task($task['results']['id'], "Task error occurred: {$task_result}.", true);
				unlock_task($task['results']['id']);
			}
		} else {
			// Log a "task not found" error.
			log_task($task['results']['id'], "Task {$task_file} was not found.", true);
		}
	}
}
?>