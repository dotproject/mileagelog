<?php

// check permissions
$denyRead = getDenyRead( $m );
$denyEdit = getDenyEdit( $m );

if ($denyRead) {
	$AppUI->redirect( "m=help&a=access_denied" );
}
$AppUI->savePlace();

$MILEAGELOG_CONFIG = array();
require_once( "./modules/mileagelog/config.php" );

// setup the title block
$titleBlock = new CTitleBlock( $AppUI->_('Mileage Log'), 'MileageLog.png', $m, "$m.$a" );

$titleBlock->show();

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'MileageLogVwTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'MileageLogVwTab' ) ? $AppUI->getState( 'MileageLogVwTab' ) : 0;

$tabBox = new CTabBox( "?m=mileagelog", "./modules/mileagelog/", $tab );
$tabBox->add( 'vw_mileagelog', $AppUI->_('Mileage Log') );
$tabBox->add( 'addedit', $AppUI->_('New/Update Log') );
$tabBox->show();
?>
