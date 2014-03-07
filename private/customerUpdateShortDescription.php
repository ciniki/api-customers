<?php
//
// Description
// -----------
// This function will update the customers short description for the website listing.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_customerUpdateShortDescription(&$ciniki, $business_id, $customer_id, $upd=0x04, $format='') {
    
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$date_format = ciniki_users_dateFormat($ciniki);

	if( $format == '' ) {
		error_log('lookup');
		$format = 'shortbio';
		$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'business_id',	$business_id,
			'ciniki.web', 'settings', 'page-members');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$settings = $rc['settings'];
		if( isset($settings['page-members-list-format']) && $settings['page-members-list-format'] != '' ) {
			$format = $settings['page-members-list-format'];
		}
	}

	//
	// Get the customer information for the short description
	//
	$strsql = "SELECT id, short_bio, short_description "
		. "FROM ciniki_customers "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customer']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1606', 'msg'=>'Unable to find customer'));
	}
	$customer = $rc['customer'];

	//
	// Get the public addresses
	//
	$strsql = "SELECT id, address1, address2, city, province, postal "
		. "FROM ciniki_customer_addresses "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (flags&0x08) > 0 "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'addresses', 'fname'=>'id',
			'fields'=>array('id', 'address1', 'address2', 'city', 'province', 'postal')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['addresses']) ) {
		$customer['addresses'] = $rc['addresses'];
	}

	//
	// Get the public email addresses
	//
	$strsql = "SELECT id, email "
		. "FROM ciniki_customer_emails "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (flags&0x08) > 0 "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'emails', 'fname'=>'id',
			'fields'=>array('id', 'email')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['emails']) ) {
		$customer['emails'] = $rc['emails'];
	}

	//
	// Get the phone numbers for the customer
	//
	$strsql = "SELECT id, phone_label, phone_number, flags "
		. "FROM ciniki_customer_phones "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (flags&0x08) > 0 "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'phones', 'fname'=>'id',
			'fields'=>array('id', 'phone_label', 'phone_number', 'flags')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['phones']) ) {
		$customer['phones'] = $rc['phones'];
	}

	//
	// Get the phone numbers for the customer
	//
	$strsql = "SELECT id, name, url, webflags "
		. "FROM ciniki_customer_links "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (webflags&0x01) > 0 "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'links', 'fname'=>'id',
			'fields'=>array('id', 'name', 'url', 'webflags')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['links']) ) {
		$customer['links'] = $rc['links'];
	}

	//
	// Build the new short description
	//
	$pieces = array(
		'shortbio'=>$customer['short_bio'],
		'addresses'=>'',
		'townsprovinces'=>'',
		'phones'=>'',
		'emails'=>'',
		'links'=>'',
		);

	//
	// Add addresses
	//
	if( isset($customer['addresses']) ) {
		foreach($customer['addresses'] as $address) {
			$addr = '';
			$townprovince = '';
			if( $address['address1'] != '' ) {
				$addr .= (($addr!='')?', ':'') . $address['address1'];
			}
			if( $address['address2'] != '' ) {
				$addr .= (($addr!='')?', ':'') . $address['address2'];
			}
			if( $address['city'] != '' ) {
				$addr .= (($addr!='')?', ':'') . $address['city'];
				$townprovince .= (($townprovince!='')?', ':'') . $address['city'];
			}
			if( $address['province'] != '' ) {
				$addr .= (($addr!='')?', ':'') . $address['province'];
				$townprovince .= (($townprovince!='')?', ':'') . $address['province'];
			}
			if( $address['postal'] != '' ) {
				$addr .= (($addr!='')?'  ':'') . $address['postal'];
			}
			if( $addr != '' ) {
				$pieces['addresses'] .= ($pieces['addresses']!=''?"\n":'') . "$addr";
			}
			if( $townprovince != '' ) {
				$pieces['townsprovinces'] .= ($pieces['townsprovinces']!=''?"\n":'') . "$townprovince";
			}
		}
	}

	//
	// Add phones
	//
	if( isset($customer['phones']) ) {
		foreach($customer['phones'] as $phone) {
//			$desc .= "\n" . $phone['phone_label'] . ': ' . $phone['phone_number'];
			$pieces['phones'] .= ($pieces['phones']!=''?"\n":'') . $phone['phone_label'] . ": " . $phone['phone_number'];
		}
	}

	//
	// Add emails
	//
	if( isset($customer['emails']) ) {
		foreach($customer['emails'] as $email) {
			// $desc .= "\n" . $email['email'];
			$pieces['emails'] .= ($pieces['emails']!=''?"\n":'') . $email['email'];
		}
	}

	//
	// Add links
	//
	if( isset($customer['links']) ) {
		foreach($customer['links'] as $link) {
			if( $link['name'] != '' ) {
				$pieces['links'] .=  ($pieces['links']!=''?"\n":'') . "<a href='" . $link['url'] . "' target='_blank'>" . $link['name'] . "</a>";
			} else {
				$rc = ciniki_web_processURL($ciniki, $link['url']);
				$pieces['links'] .= ($pieces['links']!=''?"\n":'') . "<a href='" . $rc['url'] . "' target='_blank'>" . $rc['display'] . "</a>";
			}
		}
	}

	//
	// Build the new description based on requirements.  This will go through the list of elements
	// from the web setting.
	//
	$desc = '';
	$fmt = explode('-', $format);
	error_log("format: $format");
	foreach($fmt as $piece) {
		if( isset($pieces[$piece]) && $pieces[$piece] != '' ) {
			$desc .= ($desc!=''?"\n":'') . $pieces[$piece];
		}
	}

	//
	// Update the short description
	//
	if( $desc != $customer['short_description'] ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
		$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.customer',
			$customer_id, array('short_description'=>$desc), $upd);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}
	
	return array('stat'=>'ok');
}
?>