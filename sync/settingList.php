<?php
//
// Description
// -----------
// This method will return the history from the customer module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_sync_settingList($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['type']) ||
		($args['type'] != 'partial' && $args['type'] != 'full' && $args['type'] != 'incremental') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'137', 'msg'=>'No type specified'));
	}
	if( $args['type'] == 'incremental' 
		&& (!isset($args['since_uts']) || $args['since_uts'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'136', 'msg'=>'No timestamp specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');

	//
	// Prepare the query to fetch the list
	//
	$strsql = "SELECT detail_key, UNIX_TIMESTAMP(last_updated) AS last_updated "	
		. "FROM ciniki_customer_settings "
		. "WHERE ciniki_customer_settings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( $args['type'] == 'incremental' ) {
		$strsql .= "AND UNIX_TIMESTAMP(ciniki_customer_settings.last_updated) >= '" . ciniki_core_dbQuote($ciniki, $args['since_uts']) . "' ";
	}
	$strsql .= "ORDER BY last_updated "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.customers', 'settings', 'detail_key');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'132', 'msg'=>'Unable to get list', 'err'=>$rc['err']));
	}

	if( !isset($rc['settings']) ) {
		return array('stat'=>'ok', 'settings'=>array());
	}

	return array('stat'=>'ok', 'settings'=>$rc['settings']);
}
?>
