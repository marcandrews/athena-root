<?php
session_start();
header('Cache-control: private');

require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_association();

$previous_courses['sql'] = '	SELECT		id, prog_abbrev, course_code, course_name, semester, year, distribution, price, sold, (price*sold - writer_salary*total_lectures - printing_cost) AS net_profit
								FROM		courses
								WHERE		association_id = '.$_SESSION['association']['association_id'].' AND
											year < "'.(current_semester() == 0 ? date('Y') : date('Y')-1).'"
								ORDER BY	year ASC, semester ASC, prog_abbrev ASC, course_code ASC';

if ( $previous_courses['query'] = mysql_query($previous_courses['sql']) and mysql_num_rows($previous_courses['query']) > 0 ) {
	$layout->output_header('Accounts History', 'association');
?>
<ul id="breadcrumb">
	<li>Accounts History</li>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
</ul>
<h1>Accounts History</h1>
<p>View the accounts from previous years.</p>
<div class="psuedo_p">
	<table style="border-collapse:collapse;">
		<thead>
			<tr>
				<th scope="col">Course</th>
				<th scope="col" style="width:100%;">Name</th>
				<th scope="col">Year</th>
				<th scope="col" style="text-align:right">Sold</th>		
				<th scope="col" style="text-align:right">Price</th>
				<th scope="col" style="text-align:right">Net</th>
			</tr>
		</thead>
<?php
	ob_start();
?>
		<tbody>
<?php
	while ( $previous_courses['results'] = mysql_fetch_assoc($previous_courses['query']) ) {
?>
			<tr>
				<td><?php print $previous_courses['results']['prog_abbrev'] ?><?php print $previous_courses['results']['course_code'] ?></td>
				<td><?php print $previous_courses['results']['course_name'] ?></td>
				<td class="nowrap"><?php print $previous_courses['results']['semester'] == 0 ? $previous_courses['results']['year'] : $previous_courses['results']['year']+1; ?> <?php print $info['semesters'][$previous_courses['results']['semester']] ?></td>
				<td align="right"><?php print $previous_courses['results']['sold'] ?></td>
				<td align="right"><?php print money_format('%(#1n',$previous_courses['results']['price']) ?></td>
				<td align="right"><strong><?php print money_format('%(#1n',$previous_courses['results']['net_profit']); $total_profit += $previous_courses['results']['net_profit']; ?></strong></td>
			</tr>
<?php
	}
?>
		</tbody>
<?php
	$tbody = ob_get_clean();
	ob_start();			
	if ( mysql_num_rows($previous_courses['query']) > 1 ) {
?>
		<tfoot>
			<tr>
				<td colspan="5" align="right"><strong>Total:</strong></td>
				<td align="right"><strong><?php print money_format('%(#1n',$total_profit) ?></strong></td>
			</tr>
		</tfoot>
<?php 
	}
	$tfoot = ob_get_clean();
	print $tfoot;
	print $tbody;
?>
	</table>
</div>
<?php
	$layout->output_footer();
} else {
	$layout->redirector('No Courses Available', 'There are no courses from previous years. Please try again next year.');
}
?>