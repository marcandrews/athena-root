<?php
function task () {
	if (mail('athena@susonline.net','Test email','Test subject')) {
		return true;
	} else {
		return 'Could not send mail.';
	}
}
?>