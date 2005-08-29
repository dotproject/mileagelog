<?php /*  */

$MILEAGELOG_CONFIG = array();
require_once( "./modules/mileagelog/config.php" );
require_once( "./modules/mileagelog/mileagelog.class.php" );

$obj = new CMileageLog();
$AppUI->setMsg( 'Mileage Log' );

$msg = '';

if ($obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

$del = dPgetParam( $_POST, 'del', 0 );
print_r($del);die;
// prepare (and translate) the module name ready for the suffix
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "deleted", UI_MSG_ALERT, true );
		$AppUI->redirect( "m=mileagelog&user_id=" . @$_POST['user_id'] );
	}
} else {
	$isNotNew = @$_POST['mileage_log_id'];

	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( $isNotNew ? 'updated ': 'added', UI_MSG_OK, true );
		
		// update purposes
		if ($MILEAGELOG_CONFIG['show_purpose_task']) {
			if (intval($_POST['task_mileage_log_purpose_relation_id'])) {
				$values = array();
				$values['mileage_log_purpose_id'] = @$_POST['task_mileage_log_purpose_id'];
				$values['mileage_log_id'] = $obj->mileage_log_id;
				$values['mileage_log_purpose_relation_id'] = @$_POST['task_mileage_log_purpose_relation_id'];
				$values['mileage_log_purpose_relation_type'] = @$_POST['task_mileage_log_purpose_relation_type'];
				$purpose_obj = new CMileageLogPurpose();
				if (!$purpose_obj->bind( $values )) {
					$purpose_obj->store();
				}
			}
		}
		if ($MILEAGELOG_CONFIG['show_purpose_helpdesk']) {
			if (intval($_POST['helpdesk_mileage_log_purpose_relation_id'])) {
				$values = array();
				$values['mileage_log_purpose_id'] = @$_POST['helpdesk_mileage_log_purpose_id'];
				$values['mileage_log_id'] = $obj->mileage_log_id;
				$values['mileage_log_purpose_relation_id'] = @$_POST['helpdesk_mileage_log_purpose_relation_id'];
				$values['mileage_log_purpose_relation_type'] = @$_POST['helpdesk_mileage_log_purpose_relation_type'];
				$purpose_obj = new CMileageLogPurpose();
				if (!$purpose_obj->bind( $values )) {
					$purpose_obj->store();
				}
			}
		}
		if ($MILEAGELOG_CONFIG['show_purpose_note']) {
			$values = array();
			$values['mileage_log_purpose_id'] = @$_POST['note_mileage_log_purpose_id'];
			$values['mileage_log_id'] = $obj->mileage_log_id;
			$values['mileage_log_purpose_note'] = @$_POST['note_mileage_log_purpose_note'];
			$values['mileage_log_purpose_relation_type'] = @$_POST['note_mileage_log_purpose_relation_type'];
			$purpose_obj = new CMileageLogPurpose();
			if (!$purpose_obj->bind( $values )) {
				$purpose_obj->store();
			}
		}
	}
	
}
$AppUI->redirect();
?>
