<?php
//
// Description
// -----------
// This function returns the index details for an object
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_customers_hooks_webIndexObject($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.20', 'msg'=>'No object specified'));
    }

    if( !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.21', 'msg'=>'No object ID specified'));
    }


    if( $args['object'] == 'ciniki.customers.members' ) {
        //
        // Setup the base_url for use in index
        //
        if( isset($args['base_url']) ) {
            $base_url = $args['base_url'];
        } else {
            $base_url = '/members';
        }

        $strsql = "SELECT id, display_name, member_status, webflags, permalink, "
            . "primary_image_id, short_description, full_bio "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.22', 'msg'=>'Object not found'));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.customers.23', 'msg'=>'Object not found'));
        }
        $item = $rc['item'];

        //
        // Check if item is visible on website
        //
        if( ($item['webflags']&0x01) == 0 ) {
            return array('stat'=>'ok');
        }
        if( $item['member_status'] != '10' ) {
            return array('stat'=>'ok');
        }
        $object = array(
            'label'=>'Members',
            'title'=>$item['display_name'],
            'subtitle'=>'',
            'meta'=>'',
            'primary_image_id'=>$item['primary_image_id'],
            'synopsis'=>$item['short_description'],
            'object'=>'ciniki.customers.members',
            'object_id'=>$item['id'],
            'primary_words'=>$item['display_name'] . ' member members',
            'secondary_words'=>$item['short_description'],
            'tertiary_words'=>$item['full_bio'],
            'weight'=>15000,
            'url'=>$base_url . '/' . $item['permalink']
            );

        //
        // Get the categories for the member if categories enabled
        //
        $category_permalink = '';
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x04) ) {
            $strsql = "SELECT DISTINCT tag_name, permalink "
                . "FROM ciniki_customer_tags "
                . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND tag_type = 40 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'tag');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.24', 'msg'=>'Member categories not found'));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    $object['primary_words'] .= ' ' . $row['tag_name'];
                }
                //
                // NO full bio, don't go to the member page, just the listings
                //
                if( $item['full_bio'] == '' && isset($rc['rows'][0]['permalink']) && $rc['rows'][0]['permalink'] != '' ) {
                    $category_permalink = '/category/' . $rc['rows'][0]['permalink'];
                }
            }
        }

        //
        // NO full bio, don't go to the member page, just the listings
        //
        if( $item['full_bio'] == '' ) {
            $object['url'] = $base_url . $category_permalink . '#' . $item['permalink'];
        }

        return array('stat'=>'ok', 'object'=>$object);
    }

    if( $args['object'] == 'ciniki.customers.dealers' ) {
        //
        // Setup the base_url for use in index
        //
        if( isset($args['base_url']) ) {
            $base_url = $args['base_url'];
        } else {
            $base_url = '/dealers';
        }

        $strsql = "SELECT id, display_name, dealer_status, webflags, permalink, "
            . "primary_image_id, short_description, full_bio "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.25', 'msg'=>'Object not found'));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.26', 'msg'=>'Object not found'));
        }
        $item = $rc['item'];

        //
        // Check if item is visible on website
        //
        if( ($item['webflags']&0x02) == 0 ) {
            return array('stat'=>'ok');
        }
        //
        // Get the public address for the dealer
        //
        $strsql = "SELECT address1, address2, city, province, postal, country "
            . "FROM ciniki_customer_addresses "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x08) = 0x08 "    // Public address
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.27', 'msg'=>'Dealer address not found'));
        }
        $address_keywords = '';
        if( isset($rc['rows'][0]) ) {
            $address = $rc['rows'][0];
            if( $item['full_bio'] == '' ) {
                $item['permalink'] = 'location/' . ($address['country'] == '' ? '-' : $address['country']) 
                    . '/' . ($address['province'] == '' ? '-' : $address['province']) 
                    . '/' . ($address['city'] == '' ? '-' : $address['city']);
            } else {
                $item['permalink'] = 'location/' . ($address['country'] == '' ? '-' : $address['country']) 
                    . '/' . ($address['province'] == '' ? '-' : $address['province']) 
                    . '/' . ($address['city'] == '' ? '-' : $address['city'])
                    . '/' . $item['permalink'];
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeAddressKeywords');
            $address_keywords = ciniki_core_makeAddressKeywords($ciniki, $address);
        } else {
            $address = array();
            return array('stat'=>'ok');         // Require an address to be in search engine
        }

        $object = array(
            'label'=>'Dealers',
            'title'=>$item['display_name'],
            'subtitle'=>'',
            'meta'=>'',
            'primary_image_id'=>$item['primary_image_id'],
            'synopsis'=>$item['short_description'],
            'object'=>'ciniki.customers.dealers',
            'object_id'=>$item['id'],
            'primary_words'=>$item['display_name'] . ' dealer dealers',
            'secondary_words'=>$item['short_description'] . ' ' . $address_keywords,
            'tertiary_words'=>$item['full_bio'],
            'weight'=>15000,
            'url'=>$base_url . '/' . $item['permalink']
            );
        return array('stat'=>'ok', 'object'=>$object);
    }

    if( $args['object'] == 'ciniki.customers.distributors' ) {
        //
        // Setup the base_url for use in index
        //
        if( isset($args['base_url']) ) {
            $base_url = $args['base_url'];
        } else {
            $base_url = '/distributors';
        }

        $strsql = "SELECT id, display_name, distributor_status, webflags, permalink, "
            . "primary_image_id, short_description, full_bio "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.28', 'msg'=>'Object not found'));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.29', 'msg'=>'Object not found'));
        }
        $item = $rc['item'];

        //
        // Get the public address for the distributor
        //
        $strsql = "SELECT id, address1, address2, city, province, postal, country "
            . "FROM ciniki_customer_addresses "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x08) = 0x08 "    // Public address
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.30', 'msg'=>'Distributor address not found'));
        }
        if( isset($rc['address']) ) {
            $address = $rc['address'];
            $item['permalink'] = 'location/' . ($address['country'] == '' ? '-' : $address['country']) 
                . '/' . ($address['province'] == '' ? '-' : $address['province']) 
                . '/' . ($address['city'] == '' ? '-' : $address['city']);
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makeAddressKeywords');
            $address_keywords = ciniki_core_makeAddressKeywords($ciniki, $address);
        } else {
            $address = array();
            return array('stat'=>'ok');         // Require an address to be in search engine
        }

        //
        // Check if item is visible on website
        //
        if( ($item['webflags']&0x04) == 0 ) {
            return array('stat'=>'ok');
        }
        $object = array(
            'label'=>'Distributors',
            'title'=>$item['display_name'],
            'subtitle'=>'',
            'meta'=>'',
            'primary_image_id'=>$item['primary_image_id'],
            'synopsis'=>$item['short_description'],
            'object'=>'ciniki.customers.distributors',
            'object_id'=>$item['id'],
            'primary_words'=>$item['display_name'] . ' distributor distributors',
            'secondary_words'=>$item['short_description'] . $address_keywords,
            'tertiary_words'=>$item['full_bio'],
            'weight'=>15000,
            'url'=>$base_url . '/' . $item['permalink']
            );
        return array('stat'=>'ok', 'object'=>$object);
    }

    return array('stat'=>'ok');
}
?>
