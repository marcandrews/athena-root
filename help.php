<?php
session_start();
header('Cache-control: private');

require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$help['sql'] = 'SELECT * FROM help WHERE meant_for <= '.(($_SESSION['association']['association_id']) ? '2' : '1').' ORDER BY id, question';
if ($help['query'] = mysql_query($help['sql']) and mysql_num_rows($help['query']) > 0) {
	$layout->output_header('Help');
?>
<ul id="breadcrumb">
	<li>Help</li>
<?php if (is_array($_SESSION['student'])) { ?>
	<li> &laquo; <a href="/students/">Student Home</a></li>
<?php } elseif (is_array($_SESSION['association'])) { ?>
	<li> &laquo; <a href="/associations/">Student Association Home</a></li>
<?php } else { ?>
	<li> &laquo; <a href="/">Home</a></li>
<?php } ?>
</ul>
<h1>Help</h1>
<div class="psuedo_p">
	<p>Here are the answers to the most frequently asked questions regarding Athena. If you cannot find an answer to your question(s), <a href="/contact.php">contact the Administrators of Athena or your student association</a>.</p>
	<ul>
<?php
	while ($help['results'] = mysql_fetch_assoc($help['query'])) {
?>
		<li><a href="#question_<?php print $help['results']['id'] ?>"><?php print $help['results']['question'] ?></a></li>
<?php
	}
	mysql_data_seek($help['query'], 0);
?>
	</ul>
</div>
<?php
	while ($help['results'] = mysql_fetch_assoc($help['query'])) {
?>
<h2 id="question_<?php print $help['results']['id'] ?>"><?php print $help['results']['question'] ?></h2>
<div class="psuedo_p">
<?php print $help['results']['answer'] ?>
<a href="#content">Top of page &rsaquo;</a>
</div>
<?php
	}
	$layout->output_footer();
} else {
	$layout->redirector('Athena | Help', 'There is currently no help to display. If you require assistance, please contact the <a href="/contact.php?to=1">Administrators of Athena</a>.');
}
?>