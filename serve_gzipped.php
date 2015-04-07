<?php
if (isset($_GET['file']) and pathinfo($_GET['file'])['extension'] == 'js' and is_file($_SERVER['DOCUMENT_ROOT'].$_GET['file'])) {
	ob_start('ob_gzhandler');
	print file_get_contents($_SERVER['DOCUMENT_ROOT'].$_GET['file']);
	ob_end_flush();
} else {
	header('HTTP/1.0 404 Not Found');
}
?> 