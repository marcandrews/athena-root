<?php
session_start();
header('Cache-control: private');
require_once($_SERVER['DOCUMENT_ROOT'].'inc/settings.php');


// Get required variables and format them as integers.
$_GET['cid'] = (int) $_GET['cid'];
$_GET['nid'] = (int) $_GET['nid'];
$_GET['delete'] = (int) $_GET['delete'];


// Authenticate user.
$user_authentication->validate_student();
$user_authentication->validate_coordinator($_GET['cid']);


// Get the initial values.
$html['max_size'] = min(return_bytes(ini_get('post_max_size')), return_bytes(ini_get('upload_max_filesize')));
$allowed_uploads = array	(	'pdf'	=> 'application/pdf',
								'doc'	=> 'application/msword',
								'docx'	=> 'application/vnd.openxmlformats',
								'zip'	=> 'application/zip'
							);
$editing_sets['sql'] = 'SELECT	CONCAT(c.prog_abbrev,c.course_code) AS code, c.course_name, c.year, c.semester,
								n.id AS nid, n.course_id, n.num, DATE_FORMAT(n.date,"%Y/%m/%d") AS date, n.descr, n.pages, n.distribution, n.filename, n.modified, n.created, n.received
						FROM	courses AS c, purchases AS p, sets AS n
						WHERE	c.id = '.$_GET['cid'].' AND
								c.id = p.course_id AND
								p.student_id = '.$_SESSION['student']['student_id'].' AND
								p.course_id = n.course_id AND
								n.id = '.$_GET['nid'].'
						LIMIT 1';
$adding_sets['sql'] = '	SELECT	CONCAT(c.prog_abbrev,c.course_code) AS code, c.course_name, c.year, c.semester, c.release_day,
								n.num, n.date
						FROM	(courses AS c, purchases AS p)
						LEFT	JOIN sets AS n ON n.course_id = c.id
						WHERE	c.id = '.$_GET['cid'].' AND
								c.id = p.course_id AND
								p.student_id = '.$_SESSION['student']['student_id'].' AND
								p.coordinator = true
						ORDER BY n.num DESC, n.date DESC
						LIMIT 1';
if ($_GET['nid'] > 0 and $editing_sets['query'] = mysql_query($editing_sets['sql']) and mysql_num_rows($editing_sets['query']) == 1) {
	$editing_sets['results'] = mysql_fetch_assoc($editing_sets['query']);

	// Delete this set.
	if ($_GET['delete'] == true and ($editing_sets['results']['received'] == 0 or $_GET['force_delete'] == true)) {
		$deleting_sets['sql'] = 'DELETE FROM sets WHERE id = '.$_GET['nid'].' LIMIT 1';
		$filename = "{$info['download_path']}/{$editing_sets['results']['nid']}_{$editing_sets['results']['filename']}";
		if	(
				(
					($editing_sets['results']['distribution'] == 1 and (unlink($filename) or !file_exists($filename)))
					or ($editing_sets['results']['distribution'] == 0)
				)
				and mysql_query($deleting_sets['sql'])
			) {
			$layout->redirector('Set Successfully Deleted ...', 'Athena has been updated. Now redirecting you to ...', '/students/coordinate/?cid='.$_GET['cid']);
		} else {
			$layout->redirector('Error', 'When error occurred when attempting to retrieve the standard values for this course. Refresh the page and try again.');
		}
		exit;
	} elseif ($_GET['delete'] == true and ($editing_sets['results']['received']!=0 and $_GET['force_delete']==false)) {
		$layout->redirector('Set Cannot be Deleted...', 'Since this set has been received, it cannot be deleted.');
	}
	
	$html['code']			= $editing_sets['results']['code'];
	$html['title']			= 'Coordinate '.$editing_sets['results']['code'].' | Edit Set #'.$editing_sets['results']['num'];
	$html['heading']		= 'Edit '.$editing_sets['results']['code'].' Set #'.$editing_sets['results']['num'];
	$html['description']	= 'After editing '.$editing_sets['results']['code'].' Set #'.$editing_sets['results']['num'].', click on <strong>Edit this set</strong> to save the changes to Athena.';
	$html['submit_button']	= 'Edit this set';
	$html['edit_http_var']	= '&amp;nid='.$editing_sets['results']['nid'];

	$semester				= $editing_sets['results']['semester'];
	$year					= $editing_sets['results']['year'];

	if (!$_POST) {
		$_POST = $editing_sets['results'];
		$html['replace']['no'] = ' checked="checked"';
		$html['replace']['display'] = 'display:none;';
	}
	if ( $_POST['dl'] == 0 ) {
		$html['print']['checked'] = ' checked="checked"';
		$html['print']['display'] = '';				
		$html['online']['display'] = ' style="display:none;"';				
	} else {
		$html['print']['display'] = ' style="display:none;"';				
		$html['online']['checked'] = ' checked="checked"';
		$html['online']['display'] = '';				
	}
	$adding_sets['query'] = false;
} elseif ($adding_sets['query'] = mysql_query($adding_sets['sql']) and mysql_num_rows($adding_sets['query']) == 1) {
	$adding_sets['results']	= mysql_fetch_assoc($adding_sets['query']);
	$html['code']			= $adding_sets['results']['code'];
	$html['title']			= 'Coordinate '.$adding_sets['results']['code'].' | Add a set';
	$html['heading']		= 'Add a '.$adding_sets['results']['code'].' Set';
	$html['description']	= 'To add a '.$adding_sets['results']['code'].' set, complete the following, and then click on <strong>Add this set</strong>.';
	$html['submit_button']	= 'Add this set';

	$semester				= $adding_sets['results']['semester'];
	$year					= $adding_sets['results']['year'];

	switch ($semester) {
		case 2:
			$next_semester = 9;
			break;
		case 1:
			$next_semester = 5;
			break;
		default:
			$next_semester = 1;
		break;
	}

	if (!$_POST) {
		$_POST['num'] = $adding_sets['results']['num']+1;
		for ( $i = 0; $i <= 6; $i++ ) {
			if (date('w', mktime(0, 0, 0, date('m'), date('d') + $i, date('Y'))) == $adding_sets['results']['release_day'] or date('m', mktime(0, 0, 0, date('m'), date('d') + $i + 1, date('Y'))) == $next_semester) {
				$_POST['date'] = date('Y/m/d', mktime(0, 0, 0, date('m'), date('d') + $i, date('Y')));
				break;
			}
		}
		$html['print']['checked'] = ' checked="checked"';
		$html['print']['display'] = '';				
		$html['online']['display'] = ' style="display:none;"';
	}
	$editing_sets['query'] = false;
} else {
	$layout->redirector('Error', 'When error occurred when attempting to retrieve the standard values for this course. Refresh the page and try again.<br /><br /><em>'.mysql_error().'</em>');
}
$semester	= ($editing_sets['query']) ? $editing_sets['results']['semester'] : $adding_sets['results']['semester'];
$year		= ($editing_sets['query']) ? $editing_sets['results']['year'] : $adding_sets['results']['year'];
$cal_format	= 'Y, m - 1, d';
switch ($semester) {
	case 2:
		$html['calendar'] = 'minDate: new Date('.date($cal_format,mktime(0,0,0,5,1,$year+1)).'), maxDate: new Date('.date($cal_format,mktime(0,0,0,8,31,$year+1)).')';
		break;
	case 1:
		$html['calendar'] = 'minDate: new Date('.date($cal_format,mktime(0,0,0,1,1,$year+1)).'), maxDate: new Date('.date($cal_format,mktime(0,0,0,4,30,$year+1)).')';
		break;
	default:
		$html['calendar'] = 'minDate: new Date('.date($cal_format,mktime(0,0,0,9,1,$year)).'), maxDate: new Date('.date($cal_format,mktime(0,0,0,12,31,$year)).')';
	break;
}



// Validate submitted data.
if ( $_POST['submit'] ) {
	if ( !preg_match('/^[1-9][0-9]?$/',$_POST['num']) ) {
		$error['num'] = error_inline('The set number is required.');
	}
	if ( !$_POST['date'] ) {
		$error['date'] = error_inline('The date must be in this format: YYYY/MM/DD.');
	}
	if ( strlen(trim(stripslashes($_POST['descr']))) > 500 ) {
		$error['descr'] = error_inline('The description must be less than 500 characters.');
	}
	$_POST['descr'] = trim(stripslashes(htmlentities($_POST['descr'])));
	if ( $_POST['dl'] == 0 ) { // Set will be distributed in print.
		$html['print']['checked'] = ' checked="checked"';
		$html['print']['display'] = '';
		$html['online']['display'] = ' style="display:none;"';
		if ( !preg_match('/^[1-9][0-9]?$/',$_POST['pages']) ) {
			$error['pages'] = error_inline('The number of pages is required.');
		}
	} else { // Set will be distributed online.
		$html['print']['display'] = ' style="display:none;"';
		$html['online']['checked'] = ' checked="checked"';
		$html['online']['display'] = '';
		$upload_pathinfo = pathinfo($_FILES['upload_source']['name']);
		if ($_POST['upload'] == 1 and ($_FILES['upload_source']['name'] == '' or !in_array($_FILES['upload_source']['type'], $allowed_uploads) or !array_key_exists(strtolower($upload_pathinfo['extension']), $allowed_uploads))) {
			$error['upload'] = error_inline('A file meeting the conditions below must be uploaded if this set is to be distributed online.');
			$html['replace']['yes'] = ' checked="checked"';
			$html['replace']['display'] = '';
		} else {
			$html['replace']['no'] = ' checked="checked"';
			$html['replace']['display'] = 'display:none;';
		}
	}
	if (!isset($error)) $step = 2; // If there are no errors, continue to submit the data.
}



switch ( $step ) {
	case 2:
		
		// Build SQL: set standard values.
		$update_sets['sql'] .= 'SET course_id = '.$_GET['cid'].', num = '.$_POST['num'].', date = "'.$_POST['date'].'", descr = "'.$_POST['descr'].'", ';

		// Build SQL: set destribution.
		if ($_POST['dl'] == 0) {
			$update_sets['sql'] .= 'pages = '.$_POST['pages'].', distribution = 0, ';
		} else {
			$update_sets['sql'] .= 'pages = 0, distribution = true, ';
			if ($_POST['upload'] == 1) {
				$update_sets['sql'] .= 'filename = "'.basename($_FILES['upload_source']['name']).'", ';
			}
		}

		// Build SQL: set values based on whether this is an edit or an insert.
		if ($editing_sets['query']) {
			$update_sets['sql'] = 'UPDATE sets '.$update_sets['sql'].'modified = NOW() WHERE id = '.$editing_sets['results']['nid'];
			$succcess_msg = 'Set Successfully Edited ...';
		} else {
			$update_sets['sql'] = 'INSERT INTO sets '.$update_sets['sql'].'received = 0, modified = 0, created = NOW()';
			$succcess_msg = 'Set Successfully Added ...';
		}

		// Run SQL.
		if (mysql_query($update_sets['sql'])) {
			$nid = $editing_sets['query']?$editing_sets['results']['nid']:mysql_insert_id($info['sql_db_connect']);
			// If necessary, delete old file.
			if ($_POST['replace'] == 1 and !unlink("{$info['download_path']}/{$editing_sets['results']['nid']}_{$editing_sets['results']['filename']}")) {
				$layout->redirector('Error', 'An error occurred when attempting remove the original file.');
			}		
			// If necessary, upload new file.
			if ($_POST['dl'] == 1 and $_POST['upload'] == 1 and !move_uploaded_file($_FILES['upload_source']['tmp_name'],"{$info['download_path']}/{$nid}_" . basename($_FILES['upload_source']['name']))) {
				$layout->redirector('Error', 'An error occurred when attempting to upload the file.');
			}
			$layout->redirector($succcess_msg, 'Athena has been updated. Now redirecting you to ...', '/students/coordinate/?cid='.$_GET['cid']);
		} else {
			$layout->redirector('Error', 'An error occurred when attempting to update the database:<br /><br /><em>'.mysql_error().'</em>');
		}
		break;
	default:
		$layout->output_header($html['title']);
?>
<script type="text/javascript" src="/js/jqeury/jquery-calendar.pack.js"></script>
<script type="text/javascript">
//<![CDATA[

$(document).ready(function() {
	$('#date_format').hide();
	$('#date').calendar( {
		dateFormat: 'YMD/',
		closeAtTop: false,
		<?php print $html['calendar'] ?>,
		changeMonth: false, changeYear: false,
		clearText: "",
		changeFirstDay: false
	} );
});
//]]>
</script>
<ul id="breadcrumb">
	<li><?php print $html['heading'] ?></li>
	<li> &laquo; <a href="/students/coordinate/?cid=<?php print $_GET['cid'] ?>">Coordinate <?php print $html['code']; ?></a></li>
	<li> &laquo; <a href="/students/">Student Home</a></li>
</ul>
<h1><?php print $html['heading'] ?></h1>
<?php	general_error($error); ?>
<form enctype="multipart/form-data" action="<?php print $_SERVER['PHP_SELF'].'?cid='.$_GET['cid'].$html['edit_http_var'] ?>#content" method="post">
	<p><?php print $html['description'] ?></p>
	<fieldset>
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php print $html['max_size'] ?>" />
		<fieldset class="psuedo_p top-down">
			An asterisk (<span title="Required" class="required_field" >*</span>) indicates a required field.
			<ul>
				<li>
					<label for="num">Set Number<span title="Required" class="required_field" >*</span></label>
					<input type="text" name="num" id="num" value="<?php print $_POST['num'] ?>" maxlength="2" class="text<?php if ( $error['num'] ) print ' error'; ?>" style="width:14px;text-align:center;" /><?php print $error['num'] ?>
				</li>
				<li>
					<label for="date">Set Date<span title="Required" class="required_field" >*</span></label>
					<input type="text" name="date" id="date" value="<?php print $_POST['date'] ?>" maxlength="10" class="text<?php if ( $error['date'] ) print ' error'; ?>" style="width:68px;text-align:center;" />&nbsp;<span id="date_format">(i.e. YYYY/MM/DD)</span><?php print $error['date'] ?></li>
				<li>
					<label for="descr">Description</label>
					<textarea name="descr" id="descr" cols="20" rows="4" onkeyup="char_remaining(this,500,'char_remaining');" style="width:300px;height:5em;overflow:auto;"<?php if ( $error['descr'] ) print ' class="taerror"'; ?>><?php print $_POST['descr'] ?></textarea><?php print $error['descr'] ?><br />
					(<span id="char_remaining">500</span> characters remaining)
				</li>
<?php
		if ($editing_sets['query'] and $editing_sets['results']['distribution'] == 0 and $editing_sets['results']['received'] != 0) {
?>
				<li>
					<label>Distribution</label>
					<input type="hidden" name="dl" value="0" /><strong>Print</strong>
				</li>
				<li>
					<label for="pages">Pages<span title="Required" class="required_field" >*</span></label>
					<input name="pages" id="pages" type="text" value="<?php print $_POST['pages'] ?>" maxlength="2" class="text<?php if ( $error['pages'] ) print ' error'; ?>" style="width:14px;text-align:center;" /> (single-sided)<?php print $error['pages'] ?>
				</li>
<?php
		} elseif ($editing_sets['query'] and $editing_sets['results']['distribution'] == 1) {
?>
				<li>
					<label>Distribution</label>
					<input type="hidden" name="dl" value="1" /><strong>Online</strong>
				</li>
				<li>
					<label>File</label>
					<strong><?php print $editing_sets['results']['filename']; ?></strong> (<?php print number_format(filesize("{$info['download_path']}/{$editing_sets['results']['nid']}_{$editing_sets['results']['filename']}")/1024,2,'.',''); ?> KB)
				</li>
				<li>
					<label>File Replace</label>
					<fieldset class="list">
						<label><input type="radio" name="upload" value="1"<?php print $html['replace']['yes'] ?> onclick="$('div#replace').show('normal');" /> Yes</label>
						<label><input type="radio" name="upload" value="0"<?php print $html['replace']['no'] ?> onclick="$('div#replace').hide('normal');" /> No</label>
					</fieldset>
					<div id="replace" style=" <?php print $html['replace']['display'] ?>"><input name="upload_source" type="file" class="text<?php if ( $error['upload'] ) print ' error'; ?>" style="height:22px;" /><?php print $error['upload'] ?><br />
						(<?php print number_format($html['max_size']/1024/1024,2,'.','') ?> MB maximum; allowed file types: PDF, DOC, DOCX, ZIP)
				</li>
<?php
		} else {
?>
				<li>
					<label>Distribution<span title="Required" class="required_field" >*</span></label>
					<fieldset class="list">
						<label><input type="radio" name="dl" id="dl_print" value="0"<?php print $html['print']['checked'] ?> onclick="$('li#online').hide('fast', function(){ $('li#print').show('normal'); });" /> Print</label>
						<label><input type="radio" name="dl" id="dl_online" value="1"<?php print $html['online']['checked'] ?> onclick="$('li#print').hide('fast', function(){ $('li#online').show('normal'); });" /> Online</label>
					</fieldset>
				</li>
				<li id="print"<?php print $html['print']['display'] ?>>
					<label for="pages">Pages<span title="Required" class="required_field" >*</span></label>
					<input type="text" name="pages" id="pages" value="<?php print $_POST['pages'] ?>" maxlength="2" class="text<?php if ( $error['pages'] ) print ' error'; ?>" style="width:14px;text-align:center;" /> (single-sided)<?php print $error['pages']; ?>

				</li>
				<li id="online"<?php print $html['online']['display'] ?>>
					<label for="upload_source">Upload<span title="Required" class="required_field" >*</span></label>
					<input type="hidden" name="upload" value="1" /><input type="file" name="upload_source" id="upload_source" class="text<?php if ( $error['upload'] ) print ' error'; ?>" style="height:22px;" /><?php print $error['upload'] ?><br />
						(<?php print number_format($html['max_size']/1024/1024,2,'.','') ?> MB maximum; allowed file types: PDF, DOC, DOCX, ZIP)
				</li>
<?php
		}
		if ($editing_sets['query']) {
			if ($editing_sets['results']['distribution']==0 and $editing_sets['results']['received']!=0) {
?>
				<li>
					<label>Received</label>
					<strong><?php print date('Y/m/d \a\t H:i',strtotime($editing_sets['results']['received'])) ?></strong>
				</li>
<?php
			}
			if ($editing_sets['results']['modified']!=0) {
?>
				<li>
					<label>Modified</label>
					<strong><?php print date('Y/m/d \a\t H:i',strtotime($editing_sets['results']['modified'])) ?></strong>
				</li>
<?php
			}
?>
				<li>
					<label>Created</label>
					<strong><?php print date('Y/m/d \a\t H:i',strtotime($editing_sets['results']['created'])) ?></strong>
				</li>
<?php
		}
?>
			</ul>
		</fieldset>
		<fieldset id="controls" class="psuedo_p" title="Form controls">
			<input type="button" class="back" value="&lsaquo; Cancel" onclick="location.href='/students/coordinate/?cid=<?php print $_GET['cid'] ?>'" />
			<input type="submit" class="forward" name="submit" value="<?php print $html['submit_button'] ?> &rsaquo;" />
		</fieldset>
	</fieldset>
</form>
<script type="text/javascript">
//<![CDATA[
char_remaining(document.getElementById('descr'),500,'char_remaining');//]]>
</script>
<?php
		$layout->output_footer();
}
?>