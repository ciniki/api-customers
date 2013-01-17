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
function ciniki_customers_address_delete(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['uuid']) || $args['uuid'] == '' 
		|| !isset($args['history']) || $args['history'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1105', 'msg'=>'No address specified'));
	}
	$uuid = $args['uuid'];
	$history = $args['history'];

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the local customer address to update
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'address_get');
		$rc = ciniki_customers_address_get($ciniki, $sync, $business_id, array('uuid'=>$args['uuid'], 'translate'=>'no'));
		if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1120', 'msg'=>'Unable to get customer address', 'err'=>$rc['err']));
		}
		if( !isset($rc['address']) ) {
			// Already deleted
			return array('stat'=>'ok');
		}
		$local_address = $rc['address'];
	}

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	$db_updated = 0;

	//
	// Remove from the local server
	//
	$strsql = "DELETE FROM ciniki_customer_addresses "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_address['id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1121', 'msg'=>"Unable to delete the local customer address", 'err'=>$rc['err']));
	}
	if( $rc['num_affected_rows'] > 0 ) {
		$db_updated = 1;
	}

	//
	// Update history
	//
	if( isset($local_address['history']) ) {
		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
			'ciniki_customer_history', $local_address['id'], 'ciniki_customer_addresses', array($history['uuid']=>$history), $local_address['history'], array(
				'customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
				'related_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
			));
	} else {
		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
			'ciniki_customer_history', $local_address['id'], 'ciniki_customer_addresses', array($history['uuid']=>$history), array(), array(
				'customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
				'related_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
			));
	}
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1122', 'msg'=>'Unable to update customer address history', 'err'=>$rc['err']));
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Add to syncQueue to sync with other servers.  This allows for cascading syncs.  Don't need to
	// include the delete_id because the history is already specified.
	//
	if( $db_updated > 0 ) {
		$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.address.push', 
			'args'=>array('delete_uuid'=>$args['uuid'], 'history'=>$args['history'], 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok');
}
?>
