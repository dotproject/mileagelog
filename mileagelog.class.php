<?php
require_once( $AppUI->getSystemClass( 'dp' ) );
//require_once( $AppUI->getSystemClass( 'libmail' ) );

// Function to build a where clause to be appended to any sql that will narrow
// down the returned data to only permitted entities

function getPermsWhereClause($mod, $mod_id_field){
	GLOBAL $AppUI, $perms;

  // Figure out the module and field
	switch($mod){
		case "companies":
			$id_field = "company_id";
			break;
		case "users":
			$id_field = "user_id";
			break;
		case "projects":
			$id_field = "project_id";
			break;
		case "tasks":
			$id_field = "task_id";
			break;
		case "helpdesk_items":
			$id_field = "item_id";
			break;
		default:
			return null;
	}
	if(
		(isset($perms[$mod]['-1']) && ($perms[$mod]['-1']=='1' || $perms[$mod]['-1']=='-1')) || 
     		(isset($perms["all"]['-1']) && ($perms["all"]['-1']=='1' || $perms["all"]['-1']=='-1'))
     	) {
		$sql = "SELECT $id_field FROM $mod";
		$list = db_loadColumn( $sql );
	} else {
		$list = array();
	}

	$list[] = "''";
	$list[] = "0";

	if(isset($perms[$mod])){
		foreach($perms[$mod] as $key => $value){
			//-1 is all perms, so not a specific one

			if($key=='-1')
				continue;

			switch($value){
				case '-1': //edit
					$list[] = $key;
					break;
				case '0'://deny
					unset($list[array_search($key, $list)]);
					break;
				case '1'://read
					$list[] = $key;
					break;
				default:
					break;
			}
		}
	}

	$list = array_unique($list);

	return " $mod_id_field in (".implode(",",$list).")";
}

class CMileageLog {

	var $mileage_log_id = NULL;
	var $mileage_log_user_id = NULL;
	var $mileage_log_date = NULL;
	var $mileage_log_od_start = NULL;
	var $mileage_log_od_end = NULL;
	var $mileage_log_miles = NULL;
	
	function CMileageLog () {
		// empty constructor
	}

	function load( $mileage_log_id ) {
		$sql = "SELECT * FROM mileage_log WHERE mileage_log_id = $mileage_log_id";
		return db_loadObject( $sql, $this );
	}

	function bind( $hash ) {
		if (!is_array( $hash )) {
			return get_class( $this )."::bind failed";
		} else {
			bindHashToObject( $hash, $this );
			return NULL;
		}
	}

	function check() {
		if ($this->mileage_log_id === NULL) {
			return 'mileage log id is NULL';
		}
		// TODO MORE
		return NULL; // object is ok
	}

	function store() {
		if( !$this ) {
			return get_class( $this )."::store-check failed";
		}
		if( $this->mileage_log_id ) {
			$ret = db_updateObject( 'mileage_log', $this, 'mileage_log_id');
		} else {
			$ret = db_insertObject( 'mileage_log', $this, 'mileage_log_id');
		}
		if( !$ret ) {
			return get_class( $this )."::store failed <br>" . db_error();
		} else {
			return NULL;
		}
	}
	function delete() {
		$sql = "DELETE FROM mileage_log WHERE mileage_log_id = $this->mileage_log_id";
		if (!db_exec( $sql )) {
			return db_error();
		} else {
			return NULL;
		}
	}
}

class CMileageLogPurpose {

	var $mileage_log_purpose_id = NULL;
	var $mileage_log_id = NULL;
	var $mileage_log_purpose_relation_id = NULL;
	var $mileage_log_purpose_relation_type = NULL;
	var $mileage_log_purpose_note = NULL;
	
	function CMileageLogPurpose () {
		// empty constructor
	}

	function load( $mileage_log_purpose_id ) {
		$sql = "SELECT * FROM mileage_log_purpose WHERE mileage_log_purpose_id = $mileage_log_purpose_id";
		return db_loadObject( $sql, $this );
	}

	function bind( $hash ) {
		if (!is_array( $hash )) {
			return get_class( $this )."::bind failed";
		} else {
			bindHashToObject( $hash, $this );
			return NULL;
		}
	}

	function check() {
		if ($this->mileage_log_purpose_id === NULL) {
			return 'mileage log purpose id is NULL';
		}
		// TODO MORE
		return NULL; // object is ok
	}

	function store() {
		if( !$this ) {
			return get_class( $this )."::store-check failed";
		}
		if( $this->mileage_log_purpose_id ) {
			$ret = db_updateObject( 'mileage_log_purpose', $this, 'mileage_log_purpose_id');
		} else {
			$ret = db_insertObject( 'mileage_log_purpose', $this, 'mileage_log_purpose_id');
		}
		if( !$ret ) {
			return get_class( $this )."::store failed <br>" . db_error();
		} else {
			return NULL;
		}
	}
	function delete() {
		$sql = "DELETE FROM mileage_log_purpose WHERE mileage_log_purpose_id = $this->mileage_log_purpose_id";
		if (!db_exec( $sql )) {
			return db_error();
		} else {
			return NULL;
		}
	}
}
?>
