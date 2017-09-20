<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_customers_hooks_businessReportBlocks(&$ciniki, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.220', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    $blocks = array();

    //
    // Return the list of blocks for the business
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x8000) ) {
        $blocks['ciniki.customers.birthdays'] = array(
            'name'=>'Upcoming Birthdays',
            'options'=>array(
                'days'=>array('label'=>'Number of Days', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
                ),
            );
    }


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>