<?php
//
// Description
// -----------
// This method will add a new phone address to a customer.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the customer is attached to.
// customer_id:     The ID of the customer to add the phone address to.
// flags:           The options for the phone address.
//
//                  0x01 - 
//                  0x01 - 
//                  0x01 - 
//                  0x08 - public, visible on member site
//
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_phoneAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'phone_label'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Label'),
        'phone_number'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'name'=>'Number'),
        'flags'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Options'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.phoneAdd', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Add the address
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.phone', $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the short_description
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
    $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $args['tnid'], $args['customer_id'], 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.customer', 'object_id'=>$args['customer_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.members', 'object_id'=>$args['customer_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.dealers', 'object_id'=>$args['customer_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.distributors', 'object_id'=>$args['customer_id']));

    return array('stat'=>'ok');
}
?>
