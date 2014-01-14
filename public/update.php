<?php
//
// Description
// -----------
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_update(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'cid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Type'), 
        'prefix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Prefix'), 
        'first'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Name'), 
        'middle'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Middle Name'), 
        'last'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Last Name'), 
        'suffix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Suffix'), 
        'company'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company'), 
        'department'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Department'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Title'), 
        'phone_home'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Home Phone'), 
        'phone_work'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Phone'), 
        'phone_cell'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cell Phone'), 
        'phone_fax'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Fax Number'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'birthdate'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Birthday'), 
		'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Subscriptions'),
		'unsubscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Unsubscriptions'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.update', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the current settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
	$rc = ciniki_customers_getSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	if( isset($args['prefix']) || isset($args['first']) 
		|| isset($args['middle']) || isset($args['last']) || isset($args['suffix']) 
		|| isset($args['company']) || isset($args['type']) ) {
		//
		// Get the existing customer name
		//
		$strsql = "SELECT type, prefix, first, middle, last, suffix, display_name, company "
			. "FROM ciniki_customers "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['customer']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1467', 'msg'=>'Customer does not exist'));
		}
		$customer = $rc['customer'];

		//
		// Build the persons name
		//
		$space = '';
		$person_name = '';
		if( isset($args['prefix']) && $args['prefix'] != '' ) {
			$person_name .= $args['prefix'];
		} elseif( !isset($args['prefix']) && $customer['prefix'] != '' ) {
			$person_name .= $customer['prefix'];
		}
		if( $space == '' && $person_name != '' ) { $space = ' '; }
		if( isset($args['first']) && $args['first'] != '' ) {
			$person_name .= $space . $args['first'];
		} elseif( !isset($args['first']) && $customer['first'] != '' ) {
			$person_name .= $space . $customer['first'];
		}
		if( $space == '' && $person_name != '' ) { $space = ' '; }
		if( isset($args['middle']) && $args['middle'] != '' ) {
			$person_name .= $space . $args['middle'];
		} elseif( !isset($args['middle']) && $customer['middle'] != '' ) {
			$person_name .= $space . $customer['middle'];
		}
		if( $space == '' && $person_name != '' ) { $space = ' '; }
		if( isset($args['last']) && $args['last'] != '' ) {
			$person_name .= $space . $args['last'];
		} elseif( !isset($args['last']) && $customer['last'] != '' ) {
			$person_name .= $space . $customer['last'];
		}
		if( $space == '' && $person_name != '' ) { $space = ' '; }
		if( isset($args['suffix']) && $args['suffix'] != '' ) {
			$person_name .= $space . $args['suffix'];
		} elseif( !isset($args['suffix']) && $customer['suffix'] != '' ) {
			$person_name .= $space . $customer['suffix'];
		}
		//
		// Build the display_name
		//
		$type = (isset($args['type']))?$args['type']:$customer['type'];
		$company = (isset($args['company']))?$args['company']:$customer['company'];
		if( $type == 2 && $company != '' ) {
			if( !isset($settings['display-name-business-format']) 
				|| $settings['display-name-business-format'] == 'company' ) {
				$args['display_name'] = $company;
			} elseif( $settings['display-name-business-format'] == 'company - person' ) {
				$args['display_name'] = $company . ' - ' . $person_name;
			} elseif( $settings['display-name-business-format'] == 'person - company' ) {
				$args['display_name'] = $person_name . ' - ' . $company;
			} elseif( $settings['display-name-business-format'] == 'company [person]' ) {
				$args['display_name'] = $company . ' [' . $person_name . ']';
			} elseif( $settings['display-name-business-format'] == 'person [company]' ) {
				$args['display_name'] = $person_name . ' [' . $company . ']';
			}
		} else {
			$args['display_name'] = $person_name;
		}
	}
    
	//
	// Update the customer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.customers.customer', $args['customer_id'], $args, 0x06);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check for subscriptions
	//
	if( isset($args['subscriptions']) || isset($args['unsubscriptions']) ) {
		// incase one of the args isn't set, setup with blank arrays
		if( !isset($args['subscriptions']) ) { $args['subscriptions'] = array(); }
		if( !isset($args['unsubscriptions']) ) { $args['unsubscriptions'] = array(); }
		ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'updateCustomerSubscriptions');
		$rc = ciniki_subscriptions_updateCustomerSubscriptions($ciniki, $args['business_id'], 
			$args['customer_id'], $args['subscriptions'], $args['unsubscriptions']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
