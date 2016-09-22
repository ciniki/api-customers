<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
//require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/dropbox/lib/Dropbox/autoload.php');
//use \Dropbox as dbx;

function ciniki_customers_dropboxDownloadImages(&$ciniki, $business_id, $client, $customer, $details) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'insertFromDropbox');

    foreach($details as $img) {
        $webflags = 0x01;  // Visible on website
        if( $img['mime_type'] == 'image/jpeg' ) {
            $rc = ciniki_images_insertFromDropbox($ciniki, $business_id, $ciniki['session']['user']['id'], $client, $img['path'], 1, '', '', 'no');
            if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
                return $rc;
            }
            $found = 'no';
            if( isset($customer['images']) ) {
                foreach($customer['images'] as $customer_img) {
                    if( $customer_img['image_id'] == $rc['id'] ) {
                        $found = 'yes';
                        break;
                    }
                }
            }
            
            if( $found == 'no' ) {
                $image_id = $rc['id'];
                // Get UUID
                $rc = ciniki_core_dbUUID($ciniki, 'ciniki.customers');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3557', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
                }
                $uuid = $rc['uuid'];
                // Add object
                $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.customers.image', array(
                    'uuid'=>$uuid,
                    'customer_id'=>$customer['id'],
                    'name'=>'',
                    'permalink'=>$uuid,
                    'webflags'=>$webflags,
                    'image_id'=>$image_id,
                    'description'=>'',
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>