<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$_GET['id'] = (int)$_GET['id'];
$user_authentication->validate_student();
$user_authentication->validate_administrator($_GET['id']);

$association['sql'] = 'SELECT id, name, abbreviation FROM associations WHERE id = '.$_GET['id'].' AND administrator = '.$_SESSION['student']['student_id'];
$association['query'] = mysql_query($association['sql'], $info['sql_db_connect']) or die(mysql_error());
$association['results'] = mysql_fetch_assoc($association['query']);
if (mysql_num_rows($association['query']) == 1) {
	$layout->output_header('Administrate ' . $association['results']['abbreviation'], 'student');
?>
<ul id="breadcrumb">
	<li>Administrate <?php print $association['results']['abbreviation'] ?></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1>Administrate <span class="small-caps"><?php print ucwords(strtolower($association['results']['abbreviation'])) ?></span></h1>
<div class="psuedo_p">
	<p>You are the administrator of the <strong><?php print $association['results']['name'] ?></strong>. What would you like to do today?</p>
	<p>&rsaquo; <a href="/students/administrate/edit_association.php?id=<?php print $association['results']['id'] ?>">Edit this student association</a><br />
		&rsaquo; <a href="/students/administrate/edit_courses.php?id=<?php print $association['results']['id'] ?>">Edit courses</a><br />
		&rsaquo; <a href="/students/administrate/mass_emailer.php?aid=<?php print $association['results']['id'] ?>">Send a mass email</a><br />
		&rsaquo; <a href="/students/administrate/association_gateway.php?id=<?php print $association['results']['id'] ?>">Go to your student assoication's home page</a><br />
		&rsaquo; <a href="/students/administrate/edit_administrator.php?id=<?php print $association['results']['id'] ?>">Descend from administrator privileges</a></p>
</div>
<?php
	$layout->output_footer();
}
?>
