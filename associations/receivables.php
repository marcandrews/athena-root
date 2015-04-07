<?php
session_start();
header("Cache-control: private");
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();


$courses['sql'] = 'SELECT * FROM courses WHERE association_id = '.$_SESSION['association']['association_id'].' AND semester = "'.current_semester().'" AND active = "1" ORDER BY prog_abbrev ASC, course_code ASC';
if ( $courses['query'] = mysql_query($courses['sql']) and mysql_num_rows($courses['query']) > 0 ) {

	if ( $_POST['submit'] ) {
		if ( is_array($_POST['received']) ) {
			$step = 1;
		} else {
			$step = 0;
			$error = true;
		}
	} else {
		$step = 0;
	}

	switch ( $step ) {
		case 1:
			$ids = implode(' OR id = ', $_POST['received']);
			$received['sql'] = 'UPDATE sets SET received = NOW() WHERE id = '.$ids;
			
			if ( $received['query'] = mysql_query($received['sql']) ) {
				$layout->redirector('Athena has been updated ...', 'Athena has been updated and these NTC sets are now available for pickup. Now redirecting you to ...', '/associations/');
			} else {
				$layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
			}
			break;
		default:
			$layout->output_header('Receivables', 'association');
?>
<ul id="breadcrumb">
	<li>Receivables</li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Receivables</h1>
<?php
			if ($error) {
?>
		<p id="error_paragraph">In order to continue, at least one NTC set must marked as received.</p>
<?php
			}
?>
<p>This is a list of NTC sets waiting to be received from printing. Once the NTC set has been received, select it from the list below, and then click on <strong>Confirm</strong>. After, the selected set(s) will be available for pickup.</p>
<form method="post" action="<?php print $_SERVER['PHP_SELF']; ?>"> 
	<fieldset>
		<fieldset class="two-columns">
<?php
			while ( $courses['results'] = mysql_fetch_assoc($courses['query']) ) {
				$i++;
?>
			<fieldset class="psuedo_p<?php if (!is_int($i/2)) { print ' first'; } else { print ' second'; } ?>">
<?php
				$sets['sql'] = 'SELECT * FROM sets WHERE course_id = '.$courses['results']['id'].' AND distribution = "0" AND received = 0';
				if ( $sets['query'] = mysql_query($sets['sql'], $info['sql_db_connect']) and mysql_num_rows($sets['query']) > 0 ) {
					$show_submit = true;
?>
				<table>
					<caption><strong><?php print "{$courses['results']['prog_abbrev']}{$courses['results']['course_code']}: {$courses['results']['course_name']}"; ?></strong></caption>
					<thead> 
						<tr>
							<th>Set #</th> 
							<th>Date</th> 
							<th style="text-align:center">Pages</th>
							<th style="text-align:center">Received?</th> 
						</tr> 
					</thead> 
					<tbody>
<?php
					while ( $sets['results'] = mysql_fetch_assoc($sets['query']) ) {
?>
						<tr> 
							<td><?php print $sets['results']['num']; ?></td> 
							<td><?php print date('Y/m/d',strtotime($sets['results']['date'])); ?></td> 
							<td align="center"><?php print $sets['results']['pages']; ?></td> 
							<td align="center"><input name="received[]" type="checkbox" value="<?php print $sets['results']['id']; ?>" /></td>
						</tr> 
<?php
					}
?>
					</tbody> 
				</table>
<?php
				} else {
?>
				<p><strong><?php print "{$courses['results']['prog_abbrev']}{$courses['results']['course_code']}: {$courses['results']['course_name']}"; ?></strong></p>
				<div class="caution">There are no NTC sets waiting to be to received.</div> 
<?php
				}
?>
			</fieldset>
<?php
			}
?>
		</fieldset>
<?php
			if ( $show_submit ) {
?> 
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="button" class="back" value="&lsaquo; Cancel" onclick="top.location.href='/associations/'" />
			<input type="submit" class="forward" name="submit" value="Confirm &rsaquo;" />
		</fieldset>
<?php
			}
?>
	</fieldset>
</form>
<?php
			$layout->output_footer();
		break;
	}

} elseif ( mysql_num_rows($courses['query']) == 0 ) {
	$layout->redirector('No Courses Available', 'Currently, there are no courses available to be received. To add a course, please consult your student association\'s Athena administrator.');
} else {
	$layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
}
?> 
