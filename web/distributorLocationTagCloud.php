<?php
//
// Description
// -----------
// This function will return a list of posts organized by date
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
// type:			The type of the tag.
//
//
// Returns
// -------
//
function ciniki_customers_web_distributorLocationTagCloud($ciniki, $settings, $business_id, $args) {

	//
	// Load the business settings
	//
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
//	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
//	if( $rc['stat'] != 'ok' ) {
//		return $rc;
//	}
//	$intl_timezone = $rc['settings']['intl-default-timezone'];
//	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//	$intl_currency = $rc['settings']['intl-default-currency'];

	$rsp = array('stat'=>'ok');
	
	//
	// Build the tag cloud based on countries served
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	if( isset($args['country']) && $args['country'] != '' 
		&& isset($args['province']) && $args['province'] != '' && $args['province'] != '*' 
		) {
		$strsql = "SELECT ciniki_customer_addresses.city, "
			. "COUNT(ciniki_customers.id) AS num_tags "
			. "FROM ciniki_customer_addresses "
			. "LEFT JOIN ciniki_customers ON ("
				. "ciniki_customer_addresses.customer_id = ciniki_customers.id " 
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_addresses.country = '" . ciniki_core_dbQuote($ciniki, $args['country']) . "' "
			. "AND ciniki_customer_addresses.province = '" . ciniki_core_dbQuote($ciniki, $args['province']) . "' "
			. "AND ciniki_customer_addresses.city <> '' "
			. "AND (ciniki_customer_addresses.flags&0x08) = 0x08 "	// Only public addresses
			. "AND ciniki_customers.distributor_status = 10 " // Must be active distributor
			. "AND (ciniki_customers.webflags&0x04) = 0x04 " // Must be visible online
			. "GROUP BY ciniki_customer_addresses.country, ciniki_customer_addresses.province, "
				. "ciniki_customer_addresses.city "
			. "ORDER BY ciniki_customer_addresses.country, ciniki_customer_addresses.province, "
				. "ciniki_customer_addresses.city "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'tags', 'fname'=>'city',
				'fields'=>array('name'=>'city', 'num_tags')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tags']) ) {
			$rsp['cities'] = $rc['tags'];
		} else {
			$rsp['cities'] = array();
		}
	} elseif( isset($args['country']) && $args['country'] != '' && $args['country'] != '-' ) {
		$strsql = "SELECT ciniki_customer_addresses.province, "
			. "COUNT(ciniki_customers.id) AS num_tags "
			. "FROM ciniki_customer_addresses "
			. "LEFT JOIN ciniki_customers ON ("
				. "ciniki_customer_addresses.customer_id = ciniki_customers.id " 
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_addresses.country = '" . ciniki_core_dbQuote($ciniki, $args['country']) . "' "
			. "AND ciniki_customer_addresses.province <> '' "
			. "AND (ciniki_customer_addresses.flags&0x08) = 0x08 "	// Only public addresses
			. "AND ciniki_customers.distributor_status = 10 " // Must be active distributor
			. "AND (ciniki_customers.webflags&0x04) = 0x04 " // Must be visible online
			. "GROUP BY ciniki_customer_addresses.country, ciniki_customer_addresses.province "
			. "ORDER BY ciniki_customer_addresses.country, ciniki_customer_addresses.province "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'tags', 'fname'=>'province',
				'fields'=>array('name'=>'province', 'num_tags')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tags']) ) {
			//
			// Check if only one province returned, then recursively call function for that city
			//
			if( count($rc['tags']) == 1 ) {
				$province = array_pop($rc['tags']);
				$args['province'] = $province['name'];
				return ciniki_customers_web_distributorLocationTagCloud($ciniki, $settings, $business_id, $args);
			} else {
				$rsp['provinces'] = $rc['tags'];
			}
		} else {
			$rsp['provinces'] = array();
		}
	} else {
		$strsql = "SELECT ciniki_customer_addresses.country, "
			. "COUNT(ciniki_customers.id) AS num_tags "
			. "FROM ciniki_customer_addresses "
			. "LEFT JOIN ciniki_customers ON ("
				. "ciniki_customer_addresses.customer_id = ciniki_customers.id " 
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_addresses.country <> '' "
			. "AND (ciniki_customer_addresses.flags&0x08) = 0x08 "	// Only public addresses
			. "AND ciniki_customers.distributor_status = 10 " // Must be active distributor
			. "AND (ciniki_customers.webflags&0x04) = 0x04 " // Must be visible online
			. "GROUP BY ciniki_customer_addresses.country "
			. "ORDER BY ciniki_customer_addresses.country "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'tags', 'fname'=>'country',
				'fields'=>array('name'=>'country', 'num_tags')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tags']) ) {
			//
			// Check if only one country returned, then recursively call function for that country
			//
			if( count($rc['tags']) == 1 ) {
				$country = array_pop($rc['tags']);
				$args['country'] = $country['name'];
				return ciniki_customers_web_distributorLocationTagCloud($ciniki, $settings, $business_id, $args);
			} else {
				$rsp['countries'] = $rc['tags'];
			}
		} else {
			$rsp['countries'] = array();
		}
	}

	return $rsp;
}
?>