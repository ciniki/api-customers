<?php
//
// Description
// -----------
// Search customers by name
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to search for the customers.
// start_needle:        The search string to use.
// limit:               (optional) The maximum number of results to return.  If not
//                      specified, the maximum results will be 25.
// 
// Returns
// -------
//
function ciniki_customers_connectionSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search String'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.connectionSearch', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the list of existing connections
    //
    $strsql = "SELECT DISTINCT connection "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND connection LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "AND connection <> '' "
        . "ORDER BY connection "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.customers', 'connections', 'connection', array('stat'=>'ok', 'connections'=>array()));
}
?>
