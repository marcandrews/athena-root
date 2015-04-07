<?php
session_start();
header('Cache-control: private');

require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();

$courses['sql'] = '	SELECT		c.id AS cid, c.association_id, c.prog_abbrev, c.course_code, c.course_name, c.semester,
								d.id AS did, d.prog, d.name, d.abbreviation, d.website
					FROM		courses AS c, associations AS d
					WHERE		c.association_id = '.$_SESSION['association']['association_id'].' AND
								c.semester <= "'.current_semester().'" AND
								c.active = "1" AND
								c.association_id = d.id
					ORDER BY	c.prog_abbrev ASC, c.course_code ASC';
if ( $courses['query'] = mysql_query($courses['sql']) and mysql_num_rows($courses['query']) > 0 ) {
	$layout->output_header('NTC Summary', 'association');
?>
<ul id="breadcrumb">
	<li>NTC Summary</li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>NTC Summary</h1>
<p>View a summary of the NTCs offered by <?php print $_SESSION['association']['abbreviation']; ?> so far this year.</p>
<?php
	while ($courses['results'] = mysql_fetch_assoc($courses['query'])) {
		unset($coordinators, $i);
		$coordinators['sql'] = 'SELECT s.id, s.last_name, s.first_name FROM students AS s, purchases AS p WHERE s.id = p.student_id AND p.course_id = '.$courses['results']['cid'].' AND p.coordinator = true ORDER BY last_name, first_name';
		if ($coordinators['query'] = mysql_query($coordinators['sql']) and mysql_num_rows($coordinators['query'])) {
			$coordinators['results'] = mysql_fetch_assoc($coordinators['query']);
			do {
				$i++;
				$coordinators['html'] .= '<a href="/associations/edit_students.php?id='.$coordinators['results']['id'].'">'.$coordinators['results']['first_name'].' '.$coordinators['results']['last_name'].'</a>'.( mysql_num_rows($coordinators['query'])-$i==1 ? ' and ': ', ' );
			} while ( $coordinators['results'] = mysql_fetch_assoc($coordinators['query']) );
		} else {
			$coordinators['html'] = 'no one. To add a coordinator, please consult your student association\'s Athena administrator.  ';
		}
?>
	<div class="psuedo_p">
<?php
		$sets['sql'] = '	SELECT		n.id, n.num, n.date, n.pages, n.distribution, n.filename, n.received, (SELECT COUNT(id) FROM pickups WHERE set_id = n.id) AS pickups, (SELECT COUNT(id) FROM purchases WHERE course_id = '.$courses['results']['cid'].' AND coordinator = false) AS sold, ( SELECT COUNT(*) FROM sets_discussions WHERE set_id = n.id ) AS comments
							FROM		sets AS n
							WHERE		n.course_id = '.$courses['results']['cid'].'
							ORDER BY	n.num DESC, n.date DESC, n.id DESC';
		if ( $sets['query'] = mysql_query($sets['sql'], $info['sql_db_connect']) and mysql_num_rows($sets['query']) > 0 ) {
			$total_pages = 0;
			$total_filesize = 0;
?>
		<table id="course_<?php print $courses['results']['cid']; ?>">
			<caption><strong><?php print "{$courses['results']['prog_abbrev']}{$courses['results']['course_code']}: {$courses['results']['course_name']}"; ?></strong><br />
				This NTC is coordinated by <?php print substr($coordinators['html'],0,-2) ?></caption>
			<thead>
				<tr> 
					<td><strong>N&deg;</strong></td> 
					<td><strong>Date</strong></td> 
					<td class="full"><strong>Status</strong></td>
					<td><strong><abbr title="Comments">&ldquo;&rdquo;</abbr></strong></td>
					<td align="right"><strong>Size</strong></td>
					<td>&nbsp;</td>
				</tr> 
			</thead>
<?php
			ob_start();
?>
			<tbody>
<?php
			while ( $sets['results'] = mysql_fetch_assoc($sets['query']) ) {
				$total_pages += $sets['results']['pages'];
				$total_comments += $sets['results']['comments'];
?>
				<tr> 
					<td><?php print $sets['results']['num']; ?></td> 
					<td><?php print date('Y/m/d',strtotime($sets['results']['date'])); ?></td>
<?php
				if ( $sets['results']['distribution'] == 0 ) {
					if ( $sets['results']['received'] != 0 ) {
?>
					<td>Received and available for pickup (<?php print $sets['results']['pickups'] ?>/<?php print $sets['results']['sold'] ?>)</td> 
					<td class="tac"><?php print $sets['results']['comments']; ?></td> 
<?php
					} else {
?>
					<td>Waiting to be received</td> 
					<td>&nbsp;</td>
<?php
					}
?>
					<td class="nowrap tar" align="right"><?php print $sets['results']['pages'].( $sets['results']['pages'] == 1 ? ' page' : ' pages' ); ?></td> 
<?php
				} else {
					$total_filesize += filesize($info['download_path'].'/'.$sets['results']['id'].'_'.$sets['results']['filename'])/1024/1024;
					$filesize = round(filesize($info['download_path'].'/'.$sets['results']['id'].'_'.$sets['results']['filename'])/1024,0);
?>
					<td>Online and available for <a href="/students/downloader.php?id=<?php print $sets['results']['id'] ?>">download</a></td> 
					<td class="tac"><?php print $sets['results']['comments']; ?></td> 
					<td class="nowrap tar"><?php print $filesize ?> KB</td> 
<?php
				}
				if ( ($sets['results']['distribution'] == 0 and $sets['results']['received'] != 0) or $sets['results']['distribution'] == 1 ) {
?>
					<td><a href="/students/more_set_info.php?id=<?php print $sets['results']['id'] ?>" class="char_button" title="View more information on this set">&hellip;</a></td>
<?php
				} else {
?>
					<td>&nbsp;</td>
<?php
				} 
?>
				</tr>
<?php
			}
?>
			</tbody>
<?php		
			$tbody = ob_get_clean();
			ob_start();			
?>
			<tfoot>
				<tr>
					<td colspan="3" class="tar"><strong><abbr title="Totals">&sum;</abbr></strong></td>
					<td class="tac"><?php print (int) $total_comments ?></td>
					<td class="tar nowrap"><?php print $total_pages ?> pages<br />
						<?php print round($total_filesize,2) ?> MB</td>
					<td>&nbsp;</td>
				</tr>
			</tfoot>
<?php
			$tfoot = ob_get_clean();
			print $tfoot;
			print $tbody;
?>
		</table>
<?php
		} else {
?>
		<p><strong><?php print "{$courses['results']['prog_abbrev']}{$courses['results']['course_code']}: {$courses['results']['course_name']}"; ?></strong><br />
			This NTC is coordinated by <?php print substr($coordinators['html'],0,-2) ?></p>
		<div class="caution">Currently, there are no sets to summarize.</div> 

<?php
		}
?>
	</div>
<?php
		unset($total_comments, $total_pages, $total_filesize);
	}
	$layout->output_footer();
} elseif ( mysql_num_rows($courses['query']) == 0 ) {
	$layout->redirector('No Courses Available', 'Currently, there are no courses available. To add a course, please consult your student association\'s Athena administrator.');
} else {
	$layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
}
?>