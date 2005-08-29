<?php

//Based largely on the page with the same funtion in the existing TimeCard module

require_once( "./modules/mileagelog/mileagelog.class.php" );  

global $MILEAGELOG_CONFIG;

// check permissions
$m = $AppUI->checkFileName(dPgetParam( $_GET, 'm', getReadableModule() ));
$denyEdit = getDenyEdit( $m );
if ($denyEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

require_once $AppUI->getSystemClass('date');
$df = $AppUI->getPref('SHDATEFORMAT');

$mid = isset($_GET['mid']) ? $_GET['mid'] : 0;
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $AppUI->user_id;

$mileage_log = new CMileageLog();
if ($mid)
	$mileage_log->load($mid);

if ($MILEAGELOG_CONFIG['show_purpose_task']) {
	$task_mileage_log_purpose = new CMileageLogPurpose();
	if ($mid) {
		db_loadObject( "select * from mileage_log_purpose where mileage_log_id = $mid and mileage_log_purpose_relation_type='1'", $task_mileage_log_purpose );
		if ($task_mileage_log_purpose->mileage_log_purpose_relation_id)
			$task_desc = db_loadResult("select CONCAT_WS(' :: ', task_name, task_description) from tasks where task_id=" . $task_mileage_log_purpose->mileage_log_purpose_relation_id);
	}
}
if ($MILEAGELOG_CONFIG['show_purpose_helpdesk']) {
	$helpdesk_mileage_log_purpose = new CMileageLogPurpose();
	if ($mid) {
		db_loadObject( "select * from mileage_log_purpose where mileage_log_id = $mid and mileage_log_purpose_relation_type='2'", $helpdesk_mileage_log_purpose );
		if ($helpdesk_mileage_log_purpose->mileage_log_purpose_relation_id)
		$helpdesk_desc = db_loadResult("select CONCAT_WS(' :: ', item_title, item_summary) from helpdesk_items where item_id=" . $helpdesk_mileage_log_purpose->mileage_log_purpose_relation_id);
	}
}
if ($MILEAGELOG_CONFIG['show_purpose_note']) {
	$note_mileage_log_purpose = new CMileageLogPurpose();
	if ($mid)
		db_loadObject( "select * from mileage_log_purpose where mileage_log_id = $mid and mileage_log_purpose_relation_type='0'", $note_mileage_log_purpose );
}
$is_new_record = !$mid;
if ($is_new_record)
	$mileage_log->mileage_log_od_start = db_loadResult("select max(mileage_log_od_end) from mileage_log where mileage_log_user_id = $user_id") + 1;
	
//Prevent users from editing other ppls mileagelogs.
$can_edit_other_mileagelogs = $MILEAGELOG_CONFIG['minimum_edit_level']>=$AppUI->user_type;
if (!$can_edit_other_mileagelogs)
{
	if(isset($_GET['mid']) && ((isset($mileage_log['mileage_log_user_id']) && $mileage_log['mileage_log_user_id'] != $AppUI->user_id) || (!isset($mileage_log['mileage_log_user_id']))))
	{
		$AppUI->redirect( "m=public&a=access_denied" );
	}
}

if (isset( $mileage_log['mileage_log_date'] )) {
	$date = new CDate( $mileage_log['mileage_log_date'] ); 
} else if (isset( $_GET['date'] )) {
	$date = new CDate($_GET['date']); 
} else {
	$date = new CDate();
}

$sql = "SELECT DISTINCT(user_id), CONCAT_WS(', ', contact_last_name, contact_first_name) as full_name FROM users ";
$sql.="LEFT JOIN contacts ON user_contact = contact_id ";
//		LEFT JOIN permissions ON user_id = permission_user 
//		WHERE permission_value IS NOT NULL 
$sql.="WHERE (user_id=".$AppUI->user_id." or (".getPermsWhereClause("companies", "user_company").")) ORDER BY full_name";
$users = db_loadHashList($sql);

$delete_msg = "hello hi";
?>

<script language="javascript">

var calendarField = '';

function popCalendar( field ){
	calendarField = field;
	uts = eval( 'document.addedit.mileage_log_' + field + '.value' );
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&uts=' + uts, 'calwin', 'top=250,left=250,width=250, height=220, scollbars=false' );
}

function setCalendar( uts, fdate ) {
	fld_uts = eval( 'document.addedit.mileage_log_' + calendarField );
	fld_fdate = eval( 'document.addedit.' + calendarField );
	fld_uts.value = uts;
	fld_fdate.value = fdate;
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function submitIt() {
	
	var f = document.addedit;
 	var od_start = parseInt( f.mileage_log_od_start.value );
	var od_end = parseInt( f.mileage_log_od_end.value );
	var miles = parseInt( f.mileage_log_miles.value );
	
	var purpose_entered = false;
	var task_ctrl = eval( 'document.addedit.task_mileage_log_purpose_relation_id' );
	var helpdesk_ctrl = eval( 'document.addedit.helpdesk_mileage_log_purpose_relation_id' );
	var note_ctrl = eval( 'document.addedit.note_mileage_log_purpose_note' );
	
	if (task_ctrl != null || helpdesk_ctrl != null || note_ctrl != null) {
		if (task_ctrl != null) {
			if (parseInt(task_ctrl.value) > 0)
				purpose_entered = true;
		}

		if (helpdesk_ctrl != null)
			if (parseInt(helpdesk_ctrl.value) > 0)
				purpose_entered = true;

		if (note_ctrl != null) {
			if (note_ctrl.value.length > 0) {
				purpose_entered = true;
			}
		}
				
	} else
		purpose_entered = true;

	if (!purpose_entered) {
		alert(<?= "'" . $AppUI->_('Please enter purpose.') . "'"?>);
		return false;
	}
	
	if (miles <= 0 || isNaN(miles)) {
		if (od_start < 0 || isNaN(od_start)) {
			var msg = <?="'".$AppUI->_("Starting odometer must be at least zero.")."'"?>;
			alert( msg );
			return false;
		} else 
		if (od_end <= od_start || isNaN(od_end)) {
			var msg;
			if (isNaN(od_end))
				msg = <?='"'.$AppUI->_("Ending odometer end is required.").'"'?>;
			else
				msg = <?='"'.$AppUI->_("Ending odometer must be higher than starting odometer.").'"'?>;
				
			alert(msg);
			return false;
		}
	} else {
		if ( od_start > 0 || od_end > 0 ) {
			var msg = <?="'" . $AppUI->_('Enter either miles or odometer readings, NOT BOTH.') . "'"?>;
			alert(msg);
			return false;
		}
	}
	return true;
}

function delIt() {
//	if (confirm( <?="'".$AppUI->_("Are you sure that you would like to delete this mileage log?")."'"?> )) {
		var form = document.addedit;
		form.del.value=1;
		form.submit();
//	}
//	alert(confirm);
}
function setTask( key, val ) {
  var f = document.addedit;

  if (val != '') {
    f.task_mileage_log_purpose_id.value = key;
	f.task_mileage_log_purpose_relation_id.value = key;
    f.task_mileage_log_purpose_note.value = val;
  }
}
</script>

<form name="addedit" action="" method="post">
<input type="hidden" name="m" value="mileagelog">
<input type="hidden" name="tab" value="0">
<input type="hidden" name="dosql" value="do_mileagelog_aed">
<input type="hidden" name="del" value="0">
<input type="hidden" name="mileage_log_id" value="<?php echo (($mid > 0) ? $mid : "0"); ?>">

<table cellspacing="0" cellpadding="4" border="0" width="98%" class="std">
<?php if ($mid)  { ?> 
<tr>
	<td colspan="2" width="50%" align="right">
		<A href="javascript:delIt()"><img align="absmiddle" src="./images/icons/stock_delete-16.png" width="16" height="16" alt="<?=$AppUI->_('Delete this mileage log?')?>" border="0"><?php echo $AppUI->_('delete mileage log');?></a>
	</td>
</tr>
<? } ?>
<tr>
	<th colspan="2"><?php echo $mid?$AppUI->_('Editing'):$AppUI->_('Creating New'); ?>&nbsp;<?=$AppUI->_('Log')?></th>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?=$AppUI->_('User')?></td>
	<td>
		<?=arraySelect($users, 'mileage_log_user_id', '', ($mid?$mileage_log->mileage_log_user_id:$user_id))?>
	</td>

</tr><tr>
	<td align="right" nowrap="nowrap"><?=$AppUI->_('Date')?>:</td>
	<td>
		<input type="hidden" name="mileage_log_date" value="<?php echo $date->getDate();?>">
		<input type="text" name="date" value="<?php echo $date->format($df);?>" class="text" disabled="disabled">
		<a href="#" onClick="popCalendar('date')">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0">
		</a>

	</td>
</tr>
<tr><th align="right"><?=$AppUI->_('Miles')?></th><td>(<?=$AppUI->_('enter either odometer or miles')?>)*</td></tr>
<tr>
	<td align="right" nowrap="nowrap"><?=$AppUI->_('Odometer start')?>*</td>
	<td>
		<input type="text" name="mileage_log_od_start" value="<?=$mileage_log->mileage_log_od_start?>" class="text" size="15" maxlength="10">
		&nbsp;<?=$AppUI->_('Odometer end')?>*
		<input type="text" name="mileage_log_od_end" value="<?=$mileage_log->mileage_log_od_end?>" class="text" size="15" maxlength="10">
	</td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?=$AppUI->_('Miles')?>*</td>
	<td>
		<input type="text" name="mileage_log_miles" value="<?=$mileage_log->mileage_log_miles?>" class="text" size="15" maxlength="10">
	</td>
</tr>
<tr><th align="right"><?=$AppUI->_('Purpose')?></th><td>(<?=$AppUI->_('enter one or more')?>)*</td></tr>
<?php if ($MILEAGELOG_CONFIG['show_purpose_task']) { ?>
<tr>
	<input type="hidden" name="task_mileage_log_purpose_id" value="<?=$task_mileage_log_purpose->mileage_log_purpose_id?>" />
	<input type="hidden" name="task_mileage_log_purpose_relation_type" value="1" />
	<td align="right" valign="top" nowrap="nowrap"><?php echo $AppUI->_('Task ID');?></td>
	<td align="left">
		<input type="text" class="text" size="13" maxlength="10" name="task_mileage_log_purpose_relation_id" value="<?=$task_mileage_log_purpose->mileage_log_purpose_relation_id?>"/>&nbsp;<input type="button" class="button" value="<?=$AppUI->_('search...')?>" onclick="javascript:window.open('./index.php?m=public&a=selector&callback=setTask&table=tasks&dialog=1', 'selector', 'left=50,top=50,height=250,width=400,resizable');"/><br>
		<textarea name="task_mileage_log_purpose_note" cols="60" rows="3" wrap="virtual" class="textarea" readonly><?=dPFormSafe($task_desc)?></textarea>
	</td>
</tr>
<? }
if ($MILEAGELOG_CONFIG['show_purpose_helpdesk']) { 
?>
<tr>
	<input type="hidden" name="helpdesk_mileage_log_purpose_id" value="<?=$helpdesk_mileage_log_purpose->mileage_log_purpose_id?>" />
	<input type="hidden" name="helpdesk_mileage_log_purpose_relation_type" value="2" />
	<td align="right" valign="top" nowrap="nowrap"><?php echo $AppUI->_('Help Desk Item ID');?></td>
	<td align="left">
		<input type="text" class="text" size="13" maxlength="10" name="helpdesk_mileage_log_purpose_relation_id" value="<?=$helpdesk_mileage_log_purpose->mileage_log_purpose_relation_id?>"/>&nbsp;<input type="button" class="button" value="<?=$AppUI->_('search...')?>" onclick="javascript:window.open('./index.php?m=contacts', '_blank');"/><br>
		<textarea name="helpdesk_mileage_log_purpose_note" cols="60" rows="3" wrap="virtual" class="textarea" readonly></textarea>		
	</td>
</tr>
<? }
if ($MILEAGELOG_CONFIG['show_purpose_note']) {
?>
<tr>
	<input type="hidden" name="note_mileage_log_purpose_id" value="<?=$note_mileage_log_purpose->mileage_log_purpose_id?>" />
	<input type="hidden" name="note_mileage_log_purpose_relation_type" value="0" />
	<td align="right" valign="top" nowrap="nowrap"><?php echo $AppUI->_('Note');?></td>
	<td align="left">
		<textarea name="note_mileage_log_purpose_note" cols="60" rows="4" wrap="virtual" class="textarea"><?=dPformSafe($note_mileage_log_purpose->mileage_log_purpose_note)?></textarea>
	</td>
</tr>
<? } ?>
<tr>
	<td>
		<input class="button" type="Button" name="Cancel" value="cancel" onClick="javascript:if(confirm('<?=$AppUI->_('Are you sure you want to cancel?')?>')){location.href = './index.php?m=mileagelog&tab=0';}">
	</td>
	<td align="right">
		<input class="button" type="submit" name="submit" value="save" onClick="return submitIt();">
	</td>
</tr>
</table>
</form>
<?=$AppUI->_('* indicates required field')?>