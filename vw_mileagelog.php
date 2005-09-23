<?php

	global $MILEAGELOG_CONFIG;

	$m = $AppUI->checkFileName(dPgetParam( $_GET, 'm', getReadableModule() ));
	$denyEdit = getDenyEdit( $m );
	if ($denyEdit) {
		$AppUI->redirect( "m=public&a=access_denied" );
	}

	$can_edit_other_mileagelog = $MILEAGELOG_CONFIG['minimum_edit_level']>=$AppUI->user_type;
	$show_other_mileagelog = $MILEAGELOG_CONFIG['minimum_see_level']>=$AppUI->user_type;
	$integrate_with_helpdesk = $MILEAGELOG_CONFIG['integrate_with_helpdesk'];
	
	// get date format
	$df = $AppUI->getPref('SHDATEFORMAT');
	$tf = $AppUI->getPref('TIMEFORMAT');

	if (isset( $_GET['user_id'] )) {
		$sql = "SELECT user_company FROM users WHERE user_id = ".$_GET['user_id'] ;
		$company_id = db_loadResult( $sql );
		if(getDenyRead( "companies", $company_id )){
			$AppUI->redirect( "m=public&a=access_denied" );
		}
		$AppUI->setState( 'MileageLogSelectedUser', $_GET['user_id'] );
	}
	$user_id = $AppUI->getState( 'MileageLogSelectedUser' ) ? $AppUI->getState( 'MileageLogSelectedUser' ) : $AppUI->user_id;

	if (isset( $_GET['interval'] )) {
		$AppUI->setState( 'MileageLogInterval', $_GET['interval'] );
	}
	$interval = $AppUI->getState( 'MileageLogInterval' ) ? $AppUI->getState( 'MileageLogInterval' ) : 'w';
	$intervals = array('w' => 'Weekly', 'm' => 'Monthly', 'r' => 'Date Range');

	if (isset( $_GET['start_date'] )) {
		$AppUI->setState( 'MileageLogStartDate', $_GET['start_date'] );
	}
	$start_day = new CDate( $AppUI->getState( 'MileageLogStartDate' ) ? $AppUI->getState( 'MileageLogStartDate' ) : NULL);
	
	//set the time to noon to combat a php date() function bug that was adding an hour.
	$date = $start_day->format("%Y-%m-%d")." 12:00:00";
	$start_day -> setDate($date, DATE_FORMAT_ISO);

	if (isset( $_GET['end_date'] )) {
		$AppUI->setState( 'MileageLogEndDate', $_GET['end_date'] );
	}
	$end_day = new CDate( $AppUI->getState( 'MileageLogEndDate' ) ? $AppUI->getState( 'MileageLogEndDate' ) : $start_day);
	
	$today_weekday = $start_day -> getDayOfWeek();

	switch ($interval) {
	case 'w':
		//roll back to the first day of that week, regardless of what day was specified
		$rollover_day = '0';
		$new_start_offset = $rollover_day - $today_weekday;
		$start_day -> addDays($new_start_offset);
		break;
	case 'm':
		//roll back to the first day of that month, regardless of what day was specified
		$start_day -> addDays(-($start_day->getDay()-1) );
		break;
	default:
		$start_day = new CDate( $_GET['start_date']);
	}
	//last day of that week/month, add 6 days
	switch ($interval) {
	case 'w':
		$end_day -> copy($start_day);
		$end_day -> addDays(6);
		break;
	case 'm':
		$end_day -> copy($start_day);
		$end_day -> addMonths(1);
		$end_day -> addDays(-1);
		break;
	default:
		$end_day = new CDate( $_GET['end_date']);
	}

	//date of the first day of the previous week/month.
	$prev_date = new CDate ();
	$prev_date -> copy($start_day);	
	switch ($interval) {
	case 'w':
		$prev_date -> addDays(-7);
		break;
	case 'm':
		$prev_date -> addMonths(-1);
		break;
	default:
	}

	//date of the first day of the next week/month
	$next_date = new CDate ();
	$next_date -> copy($end_day);
	$next_date -> addDays(1);
	
	$is_my_mileagelog = ($user_id == $AppUI->user_id);
	
	?>
<script language="javascript">

var calendarField = '';

function popCalendar( field ){
	calendarField = field;
	uts = eval( 'document.user_select.' + field + '_date.value' );
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&uts=' + uts, 'calwin', 'top=250,left=250,width=250, height=220, scollbars=false' );
}

function setCalendar( uts, fdate ) {
	fld_uts = eval( 'document.user_select.' + calendarField + '_date');
	fld_fdate = eval( 'document.user_select.' + calendarField + '_display_date');
	fld_uts.value = uts;
	fld_fdate.value = fdate;
	document.user_select.submit();
}
</script>

	<form name="user_select" method="get">
	<input type="hidden" name="m" value="mileagelog">
	<input type="hidden" name="tab" value="0">
	<input type="hidden" name="start_date" value="<?php echo $start_day->getDate();?>">
	<input type="hidden" name="end_date" value="<?php echo $end_day->getDate();?>">
	<table align="center">
		<tr>
			<td>
<?php
	echo $AppUI->_('View By') . arraySelect($intervals, 'interval', "onchange='document.user_select.submit();'", $interval);
?>
			</td>
			<td>
			<a href="javascript:document.user_select.start_date.value='<?=$prev_date->getDate()?>';document.user_select.submit();" <?=($interval=='r'?' style="visibility:hidden"':'')?>><img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'previous' );?>" border="0"></a>
			
			<input type="text" name="start_display_date" value="<?php echo $start_day->format($df);?>" class="text" disabled="disabled">
			<a href="#" onClick="popCalendar('start')" <?=($interval!='r'?' style="visibility:hidden"':'')?>><img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0"></a>
			</td>
			<td><b><?=$AppUI->_('through'). '&nbsp'?>
			</b></td>
			<td>
			<input type="text" name="end_display_date" value="<?php echo $end_day->format($df);?>" class="text" disabled="disabled">
			<a href="#" onClick="popCalendar('end')" <?=($interval!='r'?' style="visibility:hidden"':'')?>><img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0"></a>
			<a href="javascript:document.user_select.start_date.value='<?=$next_date->getDate()?>';document.user_select.submit();" <?=($interval=='r'?' style="visibility:hidden"':'')?>><img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'next' );?>" border="0"></a>			
			</td>
	<?php
		if($show_other_mileagelog){
	?>
			<td align="right">
	<?php
		// list active users
		$sql = "SELECT DISTINCT(user_id), CONCAT_WS(', ', contact_last_name, contact_first_name) as full_name FROM users ";
		$sql.="LEFT JOIN contacts ON user_contact = contact_id ";
//		LEFT JOIN permissions ON user_id = permission_user 
//		WHERE permission_value IS NOT NULL 
		$sql.="WHERE (user_id=".$AppUI->user_id." or (".getPermsWhereClause("companies", "user_company").")) ORDER BY full_name";
		$result = db_loadHashList($sql);
		echo arraySelect($result, "user_id", "onchange='document.user_select.submit();'", $user_id);
	?>
			</td>
			<td align="left" nowrap="nowrap"><a href="?m=mileagelog&tab=0&user_id=<?php echo $AppUI->user_id; ?>">[My Mileage Log]</a></td>
	<?php
		}
	?>
		</tr>
	<?php
	?>
	<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='1%'><?php echo $AppUI->_('Log Date'); ?></th>
			<th><?php echo $AppUI->_('Purpose'); ?></th>
			<th width='1%'><?php echo $AppUI->_('Odometer Start'); ?></th>
			<th width='1%'><?php echo $AppUI->_('Odometer End'); ?></th>
			<th width="1%"><?php echo $AppUI->_('Mileage'); ?></th>
		</tr>
	<?php
	//set the time the beginning of the first day and end of the last day.
	$date = $start_day->format("%Y-%m-%d")." 00:00:00";
	$start_day -> setDate($date, DATE_FORMAT_ISO);
	$date = $end_day->format("%Y-%m-%d")." 23:59:59";
	$end_day -> setDate($date, DATE_FORMAT_ISO);

	$sql = "SELECT mileage_log.* 
		FROM 
			mileage_log 
		WHERE "
		." mileage_log_user_id=".$user_id." AND"
		." mileage_log_date >= \"".$start_day->format( FMT_DATETIME_MYSQL )."\" AND "
		." mileage_log_date <= \"".$end_day->format( FMT_DATETIME_MYSQL )."\" "
		." ORDER BY mileage_log_date";
//print "<pre>$sql</pre>";
	
	$result = db_loadList($sql);
//print '<pre>'.sizeof($result).'<pre>';
	$date = $start_day->format("%Y-%m-%d")." 12:00:00";
	$start_day -> setDate($date, DATE_FORMAT_ISO);

	$rowspan_count=0;
	$total_miles_daily=0;
	$total_miles_weekly=0;
	$day=0;
	$last_day = new CDate();
	$last_day -> copy($start_day);
	$no_results = true;
	$first=1;
	
	$days = $end_day->dateDiff($start_day) + 1;
	$days = ($days > 1825 ? 1825 : $days);
	for($day=0;$day<$days;$day++){
		writeLogLine($last_day,$df,$AppUI->_('add mileage log'),$is_my_mileagelog || $can_edit_other_mileagelog, $user_id);
		foreach ($result as $log) {

			$log_date = new CDate( $log["mileage_log_date"] );
			
			if($last_day->dateDiff($log_date) == 0) {
				if ($log['mileage_log_miles'])
					$total_miles_daily += $log["mileage_log_miles"];
				else
					$total_miles_daily += $log["mileage_log_od_end"] - $log["mileage_log_od_start"];
					
				$mileage_log_purposes = db_loadList('select * from mileage_log_purpose where mileage_log_id=' . $log['mileage_log_id']);
?>
				<tr>
					<td nowrap="nowrap" valign="top">
						<input type='button' class='button' onclick='javascript:window.open("./index.php?m=mileagelog&tab=1&mid=<?=$log['mileage_log_id']?>", "_self");' value="<?=$AppUI->_('Edit')?>" />
					</td>
					<td nowrap="nowrap" valign="middle">
						<?=$log_date->format($df . ' ' . $tf)?>
					</td>
					<td>
<?php
					foreach ($mileage_log_purposes as $mileage_log_purpose) {
						switch ($mileage_log_purpose['mileage_log_purpose_relation_type']) {
						case 2:	// helpdesk item
							if ($MILEAGELOG_CONFIG['show_purpose_helpdesk']) {
								$desc = db_loadResult("select CONCAT_WS(' :: ', item_title, item_summary) from helpdesk_items where item_id=" . $mileage_log_purpose['mileage_log_purpose_relation_id']);
							}
						break;
						case 1: // task
							if ($MILEAGELOG_CONFIG['show_purpose_task']) {
								$desc = db_loadResult("select CONCAT_WS(' :: ', task_name, task_description) from tasks where task_id=" . $mileage_log_purpose['mileage_log_purpose_relation_id']);
							}
						break;
						default: // note
							if ($MILEAGELOG_CONFIG['show_purpose_task']) {
								$desc = $mileage_log_purpose['mileage_log_purpose_note'];
							}
						}
						if (strlen($desc))
							echo dPFormSafe($desc).'<br>';
					}
?>					
					</td>
					<td valign="middle" align="right"><?=$log['mileage_log_miles']?'':$log['mileage_log_od_start']?></td>
					<td valign="middle" align="right"><?=$log['mileage_log_miles']?'':$log['mileage_log_od_end']?></td>
					<td valign="middle" align="right"><?=$log['mileage_log_miles']?$log['mileage_log_miles']:$log['mileage_log_od_end']-$log['mileage_log_od_start']?></td>
				</tr>
<?php
			}
			
		}

		writeDayTotal($AppUI->_('Total mileage'),$last_day->isWorkingDay(),$total_miles_daily);
		$total_miles_weekly += $total_miles_daily;
		$total_miles_daily = 0;
		$last_day->addDays(1);
		$date = $last_day->format("%Y-%m-%d")." 12:00:00";
		$last_day -> setDate($date, DATE_FORMAT_ISO);
	}

	echo "<tr><th nowrap=\"nowrap\" valign=\"top\" colspan=\"6\" ><div align=\"left\"><b>".$AppUI->_('For the date of')." ".$start_day -> getDayName(false). " " .$start_day->format( $df )." ".$AppUI->_('through')." ".$end_day -> getDayName(false). " " .$end_day->format( $df )."</b></div></th></tr>";;
	echo "<tr><td colspan=\"5\" align=\"right\"><b>".$AppUI->_('Total mileage')."</b></td><td align=\"right\">";
	echo "<b><span  style=\"padding-left: 5px;padding-right:5px;border:2px solid #999999;\"> ".$total_miles_weekly."</span></b>";
	echo "</td></tr>";
	echo "<tr><td colspan=\"5\" align=\"right\"><b> x".sprintf('%0.2f',$MILEAGELOG_CONFIG['cost_per_unit']).' '.$AppUI->_('per mile')."</b></td><td align=\"right\">";
	echo "<b><span  style=\"padding-left: 5px;padding-right:5px;border:2px solid #999999;\"> ".sprintf('%0.2f',$total_miles_weekly*$MILEAGELOG_CONFIG['cost_per_unit'])."</span></b>";
	echo "</td></tr>";
	
	?>
	</table>
	</form>
<?php
function writeLogLine($day,$format,$purpose_string,$show_add, $user_id){
	$day_name = $day->getDayName(false);
	echo "<tr><td nowrap=\"nowrap\" align=\"center\" colspan=\"".($show_add?"5":"6")."\"  style=\"background-color:#D7EAFF;\">";
	echo "<div align=\"left\">";
	echo "<b>".$day_name."</b> ".$day->format( $format );
	echo "</div>";
	echo "</td>";
	if($show_add){
		echo "<td nowrap=\"nowrap\" align=\"center\"  style=\"background-color:#D7EAFF;\">";
		echo "<a href=\"?m=mileagelog&tab=1&user_id=$user_id&date=".urlencode($day->getDate())."\">[".$purpose_string."]</a>";
		echo "</td>";
	}
	echo "</tr>";
}

function writeDayTotal($total_string,$workday,$total_miles){
	echo "<tr><td colspan=\"5\" align=\"right\"><b>".$total_string."</b></td>";
	echo "<td align=\"right\" >";
	echo "<b><span style=\"padding-left: 5px;padding-right:5px;border:2px solid #999999;\"> ".$total_miles."</span></b>";
	echo "</td></tr>";
}

?>
