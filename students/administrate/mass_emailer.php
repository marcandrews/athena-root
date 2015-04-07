<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$_GET['aid'] = (int)$_GET['aid'];
$user_authentication->validate_student();
$user_authentication->validate_administrator($_GET['aid']);

$association['sql'] = 'SELECT id, name, abbreviation FROM associations WHERE id = '.$_GET['aid'].' AND administrator = '.$_SESSION['student']['student_id'];
if ($association['query'] = mysql_query($association['sql']) and mysql_num_rows($association['query']) == 1) {
	$association['results'] = mysql_fetch_assoc($association['query']);
	$layout->output_header('Administrate '.$association['results']['abbreviation'].' | Mass Emailer', 'student');
?>
<ul id="breadcrumb">
	<li>Bulk Emails</li>
	<li> &laquo; <a href="/students/administrate/?id=<?php print $association['results']['id'] ?>">Administrate <?php print $association['results']['abbreviation'] ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Mass Emailer</h1>
<?php
	$mass_emailer = new mass_emailer();
	$mass_emailer->display_queue();
	$layout->output_footer();
}
?>
