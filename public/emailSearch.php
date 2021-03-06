<?php
//
// Description
// -----------
// This method will search for an exact match on the specified email address 
// belonging to the specified customer.
//
// Arguments
// ---------
// api_key;
// auth_token:
// tnid:     The ID of the tenant to search.
// customer_id:     The ID of the customer to search.
// email:           The email address to search for.
// 
// Returns
// -------
// <rsp stat="ok">
//    <email id="7" customer_id="2" email="veggiefrog@gmail.com" />
// </rsp>
//
function ciniki_customers_emailSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
//        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'email'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Email'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.emailSearch', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    $strsql = "SELECT id, customer_id, email "
        . "FROM ciniki_customer_emails "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//      . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND email = '" . ciniki_core_dbQuote($ciniki, $args['email']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && $rc['num_rows'] > 1 ) {
        return array('stat'=>'ambiguous', 'err'=>array('code'=>'ciniki.customers.78', 'msg'=>'Multiple emails found'));
    }
    if( !isset($rc['email']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.customers.79', 'msg'=>'Email not found'));
    }

    return array('stat'=>'ok', 'email'=>$rc['email']);
}
?>
