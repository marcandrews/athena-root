<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');

$user_authentication->validate_student();

$mass_emailer = new mass_emailer();
$mass_emailer->create();
?>