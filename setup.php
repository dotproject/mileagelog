<?php 
/*
dotProject Module

Name:      MileageLog
Directory: mileagelog
Version:   0.1
Class:     user
UI Name:   Mileage Log
UI Icon:	MileageLog.png

This file does no action in itself.
If it is accessed directory it will give a summary of the module parameters.
*/

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Mileage Log';
$config['mod_version'] = '0.1.1';
$config['mod_directory'] = 'mileagelog';
$config['mod_setup_class'] = 'CSetupMileageLog';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Mileage Log';
$config['mod_ui_icon'] = 'MileageLog.png';
$config['mod_description'] = 'Mileage keeps track of mileage for reimbursement.';
$config['mod_config'] = true;

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

require_once( $dPconfig['root_dir'].'/modules/system/syskeys/syskeys.class.php' );

/*
// MODULE SETUP CLASS
	This class must contain the following methods:
	install - creates the required db tables
	remove - drop the appropriate db tables
	upgrade - upgrades tables from previous versions
*/
class CSetupMileageLog {
	function install() {
		$success = 1;
		$bulk_sql[] = "
			CREATE TABLE mileage_log (
			  `mileage_log_id` int(11) unsigned NOT NULL auto_increment,
			  `mileage_log_user_id` int(11) NOT NULL default '0',
			  `mileage_log_date` datetime default NULL,
			  `mileage_log_od_start` int(11) NOT NULL default '0',
			  `mileage_log_od_end` int(11) NOT NULL default '0',
			  `mileage_log_miles` int(11) NOT NULL default '0',
			  PRIMARY KEY (mileage_log_id)
			) TYPE=MyISAM";

		$bulk_sql[] = "
			CREATE TABLE mileage_log_purpose (
			  `mileage_log_purpose_id` int(11) unsigned NOT NULL auto_increment,
			  `mileage_log_id` int(11) NOT NULL default '0',
			  `mileage_log_purpose_relation_id` int(11) NOT NULL default '0',
			  `mileage_log_purpose_relation_type` int(11) NOT NULL default '0',
			  `mileage_log_purpose_note` varchar(255) NOT NULL default '',
			  PRIMARY KEY (mileage_log_purpose_id)
			) TYPE=MyISAM";

    foreach ($bulk_sql as $s) {
      db_exec($s);

      if (db_error()) {
	$success = 0;
      }
    }

		$sk = new CSysKey( 'MileageLogList', 'Enter values for list', '0', "\n", '|' );
		$sk->store();

		$sv = new CSysVal( $sk->syskey_id, 'MileageLogRelation', "0|Note\n1|Task\n2|HelpDesk" );
		$sv->store();

        return $success;
	}

	function remove() {
		$success = 1;

		$bulk_sql[] = "DROP TABLE mileage_log";
		$bulk_sql[] = "DROP TABLE mileage_log_purpose";

		foreach ($bulk_sql as $s) {
			db_exec($s);
			if (db_error())
				$success = 0;
		}

		$sql = "
			SELECT syskey_id
			FROM syskeys
			WHERE syskey_name = 'MileageLogList'";
		$id = db_loadResult( $sql );

    	unset($bulk_sql);

		$bulk_sql[] = "DELETE FROM syskeys WHERE syskey_id = $id";
		$bulk_sql[] = "DELETE FROM sysvals WHERE sysval_key_id = $id";

		foreach ($bulk_sql as $s) {
			db_exec($s);
			if (db_error())
				$success = 0;
		}

		return $success;
	}

	function upgrade($old_version) {
		$success = 1;
		$bulk_sql = array();
		
	    switch ($old_version) {
		case '0.1':
			$bulk_sql[] = "ALTER TABLE mileage_log ADD mileage_log_miles int(11) NOT NULL default 0 AFTER mileage_log_od_end";
		break;
	    default:
			$success = 0;
	    }

		foreach ($bulk_sql as $s) {
			db_exec($s);
			if (db_error())
				$success = 0;
		}
  
		// NOTE: Need to return true, not null, if all is good
		return $success;
	}

	function configure() {
		global $AppUI;
		$AppUI->redirect("m=mileagelog&a=configure");
		return true;
	}
}

?>
