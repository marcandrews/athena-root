<?php
session_start();
header("Cache-control: private");

require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();

$dept['sql'] = 'SELECT * FROM associations WHERE id = '.$_SESSION['association']['association_id'];
$dept['query'] = mysql_query($dept['sql'],$info['sql_db_connect']) or die(mysql_error());
$dept['results'] = mysql_fetch_assoc($dept['query']);
$courses['sql'] = 'SELECT * FROM courses WHERE association_id = '.$_SESSION['association']['association_id'].' AND semester <= "'.current_semester().'" AND active = "1" ORDER BY prog_abbrev ASC, course_code ASC';
if ( $courses['query'] = mysql_query($courses['sql'],$info['sql_db_connect']) and mysql_num_rows($courses['query']) > 0 ) {
	$courses['results'] = mysql_fetch_assoc($courses['query']);
	$layout->output_header('Accounts Summary', 'association');
?>
<ul id="breadcrumb">
	<li>Accounts Summary</li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Accounts Summary</h1>
<p>View a summary of the accounts for <?php print $_SESSION['association']['abbreviation']; ?>.</p>
<div class="psuedo_p">
	<table style="border-collapse:collapse">
		<thead>
			<tr style="font-weight:bold; white-space:nowrap;">
				<td>Course</td>
				<td align="right"><abbr title="Number of lectures">Lect.</abbr></td>
				<td align="right">Sold</td>
				<td align="right">Price</td>
				<td align="right">Gross</td>
				<td align="right"><abbr title="Writer">W.</abbr> salary</td>
				<td align="right"><abbr title="Writer">W.</abbr> payroll</td>
				<td align="right"><abbr title="This is an estimation of the cost to print only the NTC sets that are currently available.">Printing</abbr></td>
				<td align="right">Net</td>
			</tr>
		</thead>
<?php	ob_start(); ?>
		<tbody>
<?php
	do {
		$sold['sql'] = 'SELECT course_id FROM purchases WHERE course_id = '.$courses['results']['id'].' AND coordinator = false';
		$sold['query'] = mysql_query($sold['sql'], $info['sql_db_connect']) or die(mysql_error());
		$sold['value'] = mysql_num_rows($sold['query']);
?>
			<tr>
				<td><?php print "<abbr title=\"{$courses['results']['course_name']}\">{$courses['results']['prog_abbrev']}{$courses['results']['course_code']}</abbr>"; ?></td>
				<td align="right"><?php print $courses['results']['total_lectures']; ?></td>
				<td align="right"><?php print $sold['value']; ?></td>
				<td align="right">$<?php print number_format($courses['results']['price'],2); ?></td>
				<td align="right">$<?php print number_format($total_gi[]=$courses['results']['price']*$sold['value'],2); ?></td>
				<td align="right">$<?php print number_format($courses['results']['writer_salary'],2); ?></td>
				<td align="right">$<?php print number_format($total_wp[]=$courses['results']['writer_salary']*$courses['results']['total_lectures'],2); ?></td>
<?php
		unset($printing);
		$printing['sql'] = "SELECT course_id, distribution, pages FROM sets WHERE course_id = {$courses['results']['id']} AND distribution = '0' AND pages > 0";
		$printing['query'] = mysql_query($printing['sql'], $info['sql_db_connect']) or die(mysql_error());
		$printing['results'] = mysql_fetch_assoc($printing['query']);
		if ( mysql_num_rows($printing['query']) > 0 ) {
			do {
				$printing['cost'] = $printing['cost'] + ($printing['results']['pages'] * $dept['results']['ds_printing_cost']);
			} while ( $printing['results'] = mysql_fetch_assoc($printing['query']) );
		} else {
			$printing['cost'] = 0;
		}
?>
				<td align="right">$<?php print number_format($total_p[] = $printing['cost'] * $sold['value'],2); ?></td>
				<td align="right"><strong><?php print money_format('%(#1n', $total_n[]=$courses['results']['price']*$sold['value']-$courses['results']['writer_salary']*$courses['results']['total_lectures']-$printing['cost'] * $sold['value']); ?></strong></td>
			</tr>
<?php
	} while ( $courses['results'] = mysql_fetch_assoc($courses['query']) );
?>
		</tbody>
<?php		
			$tbody = ob_get_clean();
			ob_start();			
?>
		<tfoot>
			<tr>
				<td colspan="4" align="right"><strong><abbr title="Totals">&sum;</abbr></strong></td>
				<td align="right">$<?php print number_format(array_sum($total_gi),2); ?></td>
				<td align="right">&nbsp;</td>
				<td align="right">$<?php print number_format(array_sum($total_wp),2); ?></td>
				<td align="right">$<?php print number_format(array_sum($total_p),2); ?></td>
				<td align="right"><strong><?php print money_format('%(#1n', array_sum($total_n)); ?></strong></td>
			</tr>
		</tfoot>
<?php
			$tfoot = ob_get_clean();
			print $tfoot;
			print $tbody;
?>
	</table>
</div>
<?php
	$layout->output_footer();
} elseif ( mysql_num_rows($courses['query']) == 0 ) {
	$layout->redirector('No Courses Available', 'Currently, there are no courses available. To add a course, please consult your student association\'s Athena administrator.');
} else {
	$layout->redirector('Error', 'There was an error:<br />'.mysql_error($info['sql_db_connect']));
}
?>
