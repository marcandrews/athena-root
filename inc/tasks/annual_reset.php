<?php
function task () {
	global $info;
	
	// Get course information.
	$courses['sql'] = '	SELECT	c.id, c.association_id,
								(SELECT COUNT(id) FROM purchases WHERE course_id = c.id AND coordinator = true) AS coordinators,
								(SELECT COUNT(id) FROM purchases WHERE course_id = c.id AND coordinator = false) AS sold,
								(SELECT SUM(pages) FROM sets WHERE course_id = c.id AND distribution = 0 AND pages > 0) AS pages,
								a.ds_printing_cost AS printing_cost
						FROM	courses AS c, associations AS a
						WHERE	c.year = "'.(date('Y') - 1).'" AND
								c.association_id = a.id';
	if ($courses['query'] = mysql_query($courses['sql']) and mysql_num_rows($courses['query']) > 0) {
		while ($courses['results'] = mysql_fetch_assoc($courses['query'])) {
			// Record end-year statistics.
			$course['sql'] = '	UPDATE	courses
								SET		active = false,
										sold = '.$courses['results']['sold'].',
										printing_cost = '.($courses['results']['sold'] + $courses['results']['coordinators']) * $courses['results']['pages'] * $courses['results']['printing_cost'].',
										modified = NOW()
								WHERE	id = '.$courses['results']['id'];
			if (!$course['query'] = mysql_query($course['sql']) or mysql_affected_rows() == 0) {
				return 'Unable to record the end-of-year statistics';
			}
	
			// Delete set downloads.
			$sets['sql'] = 'SELECT id, filename FROM sets WHERE course_id = '.$courses['results']['id'].' AND distribution = 1';
			if ($sets['query'] = mysql_query($sets['sql']) and mysql_num_rows($sets['query']) > 0) {
				while ($sets['results'] = mysql_fetch_assoc($sets['query'])) {					
					// Delete set download, if applicable.
					$file = $info['download_path'].'/'.$sets['results']['id'].'_'.$sets['results']['filename'];
					if (file_exists($file) and !unlink($file)) {
						return 'Unable to delete '.$file;
					}
				}
			}
			
			// Delete sets.
			$sets['sql'] = 'DELETE FROM sets WHERE course_id = '.$courses['results']['id'];
			if (!$sets['query'] = mysql_query($sets['sql'])) {
				return 'Unable to delete sets'; 
			}
	
			// Delete purchases.
			$purchases['sql'] = 'DELETE FROM purchases WHERE course_id = '.$courses['results']['id'].' AND coordinator = false';
			if (!$purchases['query'] = mysql_query($purchases['sql'])) {
				return 'Unable to delete purchases';
			}
		}
	} else {
		return 'Unable to retrieve courses';
	}

	// Duplicate courses for next year.
	$course['sql'] = '	INSERT INTO courses (association_id, prog_abbrev, course_code, course_name, semester, `year`, distribution, total_lectures, price, writer_salary, release_day, active, created)
						SELECT	association_id, prog_abbrev, course_code, course_name, semester, "'.date('Y').'", distribution, 0, price, writer_salary, release_day, true, NOW()
						FROM	courses
						WHERE	year = "'.(date('Y') - 1).'"';
	if (!$course['query'] = mysql_query($course['sql'])) {
		return 'Unable to duplicate courses for next year';
	}

	// Prune students.
	$students['sql'] = 'DELETE FROM students WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(last_visited) > 126227704 AND protected = false';
	if (!$students['query'] = mysql_query($students['sql'])) {
		return 'Unable to prune students';
	}

	return true;
}
?>