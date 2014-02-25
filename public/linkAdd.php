<?php
//
// Description
// -----------
// This function will add a new customer link to a customer.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the customer belongs to.
// customer_id:		The ID of the customer to add the link to.
// name:			(optional) The name for the link.
// url:				The url for the link.
// description:		(optional) The description for the link.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_linkAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
		'name'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Name'),
        'url'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'URL'), 
        'webflags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Webflags'), 
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.linkAdd', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Add the link
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.link', $args, 0x07);
}
?>
