<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_address_list($ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['type']) ||
		($args['type'] != 'partial' && $args['type'] != 'full' && $args['type'] != 'incremental') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1129', 'msg'=>'No type specified'));
	}
	if( $args['type'] == 'incremental' 
		&& (!isset($args['since_uts']) || $args['since_uts'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1130', 'msg'=>'No timestamp specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');

	//
	// Prepare the query to fetch the list
	//
	$strsql = "SELECT ciniki_customer_addresses.uuid, UNIX_TIMESTAMP(ciniki_customer_addresses.last_updated) AS last_updated "	
		. "FROM ciniki_customer_addresses "
		. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( $args['type'] == 'incremental' ) {
		$strsql .= "AND UNIX_TIMESTAMP(ciniki_customer_addresses.last_updated) >= '" . ciniki_core_dbQuote($ciniki, $args['since_uts']) . "' ";
	}
	$strsql .= "ORDER BY ciniki_customer_addresses.last_updated "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.customers', 'addresses', 'uuid');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1131', 'msg'=>'Unable to get list', 'err'=>$rc['err']));
	}

	if( !isset($rc['addresses']) ) {
		return array('stat'=>'ok', 'list'=>array());
	}
	$list = $rc['addresses'];

	//
	// Get any deleted addresses
	//
	$deleted = array();
	$strsql = "SELECT h1.id AS history_id, "
		. "h1.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "h1.session, "
		. "h1.action, "
		. "h1.table_field, "
		. "h1.table_key, "
		. "h1.new_value, "
		. "UNIX_TIMESTAMP(h1.log_date) AS log_date, h2.new_value AS uuid "
		. "FROM ciniki_customer_history AS h1 "
		. "LEFT JOIN ciniki_customer_history AS h2 ON (h1.table_key = h2.table_key "
			. "AND h2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND h2.table_field = 'uuid') "
		. "LEFT JOIN ciniki_users ON (h1.user_id = ciniki_users.id) "
		. "WHERE h1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND h1.table_name = 'ciniki_customer_addresses' "
		. "AND h1.table_key IN (SELECT DISTINCT table_key FROM ciniki_customer_history "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND action = 3 "
	    	. "AND table_name = 'ciniki_customer_addresses' "
			. "AND table_field = '*' ";
	if( $args['type'] == 'incremental' ) {
		$strsql .= "AND UNIX_TIMESTAMP(log_date) >= '" . ciniki_core_dbQuote($ciniki, $args['since_uts']) . "' ";
	}
	$strsql .= ") "
		. "ORDER BY h1.table_key, h1.log_date DESC "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1132', 'msg'=>'Unable to find deleted addresses'));
	}
	$prev_key = 0;
	foreach($rc['rows'] as $rid => $row) {
		// Check for delete as the most recent history item
		if( $prev_key != $row['table_key'] && $row['action'] == 3 ) {
			$deleted[$row['uuid']] = array(
				'id'=>$row['history_id'],
				'uuid'=>$row['history_uuid'],
				'user'=>$row['user_uuid'],
				'session'=>$row['session'],
				'action'=>$row['action'],
				'table_field'=>$row['table_field'],
				'new_value'=>$row['new_value'],
				'log_date'=>$row['log_date']);
		}
		$prev_key = $row['table_key'];
	}

	// The delete list is indexed by the deleted_uuid of the object.

//	if( $business_id == 5 ) {
//		error_log(print_r(array_keys($deleted), true));
//	}

	return array('stat'=>'ok', 'list'=>$list, 'deleted'=>$deleted);
}
?>
