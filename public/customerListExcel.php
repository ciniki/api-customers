<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the customers belong to.
//
// Returns
// -------
// A word document
//
function ciniki_customers_customerListExcel(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'columns'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Columns'), 
		'memberlist'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Members Only'),
		'subscription_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subscription'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.customerListExcel', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Load maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
	$rc = ciniki_customers_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Check if we are to include ids
	//
	$ids = 'no';
	foreach($args['columns'] as $column) {
		if($column == 'ids' ) { $ids = 'yes'; }
	}

	//
	// If seasons is enabled and requested, get the requested season names
	//
	$season_ids = array();
	$seasons = array();
	if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x02000000) > 0 ) {
		foreach($args['columns'] as $column) {
			if( preg_match("/^season-([0-9]+)$/", $column, $matches) ) {
				$season_ids[] = $matches[1];
			}
		}
		if( count($season_ids) > 0 ) {
			$strsql = "SELECT id, name "
				. "FROM ciniki_customer_seasons "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND id IN (" . ciniki_core_dbQuoteIDs($ciniki, $season_ids) . ") "
				. "";
			$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
				array('container'=>'seasons', 'fname'=>'id', 
					'fields'=>array('id', 'name')),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['seasons']) ) {
				$seasons = $rc['seasons'];
			}
			$strsql = "SELECT season_id, customer_id, status "
				. "FROM ciniki_customer_season_members "
				. "WHERE ciniki_customer_season_members.season_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $season_ids) . ") "
				. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "ORDER BY season_id, customer_id "
				. "";
			$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
				array('container'=>'seasons', 'fname'=>'season_id', 'fields'=>array('season_id')),
				array('container'=>'customers', 'fname'=>'customer_id', 
					'fields'=>array('id'=>'customer_id', 'status')),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['seasons']) ) {
				foreach($seasons as $sid => $season) {
					if( isset($rc['seasons'][$sid]['customers']) ) {
						$seasons[$sid]['customers'] = $rc['seasons'][$sid]['customers'];
					}
				}
			}
		}
	}

	//
	// If subscriptions are enabled
	//
	$subscription_ids = array();
	$subscriptions = array();
	if( isset($ciniki['business']['modules']['ciniki.subscriptions']) ) {
		foreach($args['columns'] as $column) {
			if( preg_match("/^subscription-([0-9]+)$/", $column, $matches) ) {
				$subscription_ids[] = $matches[1];
			}
		}
		if( count($subscription_ids) > 0 ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'hooks', 'subscriptionCustomers');
			$rc = ciniki_subscriptions_hooks_subscriptionCustomers($ciniki, $args['business_id'], 
				array('subscription_ids'=>$subscription_ids));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$subscriptions = $rc['subscriptions'];
		}
	}

	//
	// Load tax locations
	//
	$tax_locations = array();
	$strsql = "SELECT id, name, code "
		. "FROM ciniki_tax_locations "
		. "WHERE ciniki_tax_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
		array('container'=>'locations', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'code')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['locations']) ) {
		$tax_locations = $rc['locations'];
	}

	//
	// Load sales reps
	//
	$salesreps = array();
	$strsql = "SELECT user_id AS id, eid "
		. "FROM ciniki_business_users "
		. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_business_users.package = 'ciniki' "
		. "AND ciniki_business_users.permission_group = 'salesreps' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
		array('container'=>'reps', 'fname'=>'id', 
			'fields'=>array('id', 'eid')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['reps']) ) {
		$salesreps = $rc['reps'];
	}

	//
	// Load pricepoints
	//
	$pricepoints = array();
	$strsql = "SELECT id, name, code "
		. "FROM ciniki_customer_pricepoints "
		. "WHERE ciniki_customer_pricepoints.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
		array('container'=>'pricepoints', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'code')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['pricepoints']) ) {
		$pricepoints = $rc['pricepoints'];
	}

	//
	// Load the categories
	//
	$member_categories = array();
	$strsql = "SELECT customer_id, "
		. "ciniki_customer_tags.tag_name AS member_categories "
		. "FROM ciniki_customer_tags "
		. "WHERE ciniki_customer_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customer_tags.tag_type = 40 "
		. "ORDER BY ciniki_customer_tags.customer_id "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_id', 
			'fields'=>array('member_categories'),
			'dlists'=>array('member_categories'=>', ')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		$member_categories = $rc['customers'];
	}

	//
	// Load the phones
	//
	$phones = array();
	$num_phone_columns = 1;
	$strsql = "SELECT customer_id, "
		. "ciniki_customer_phones.id, "
		. "ciniki_customer_phones.phone_label, "
		. "ciniki_customer_phones.phone_number, "
		. "CONCAT_WS(': ', ciniki_customer_phones.phone_label, "
			. "ciniki_customer_phones.phone_number) AS phones "
		. "FROM ciniki_customer_phones "
		. "WHERE ciniki_customer_phones.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_customer_phones.customer_id, ciniki_customer_phones.phone_label "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_id', 
			'fields'=>array('phones'),
			'dlists'=>array('phones'=>', ')),
		array('container'=>'split_phones', 'fname'=>'id', 'fields'=>array('id', 'phone_label', 'phone_number')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		$phones = $rc['customers'];
		foreach($phones as $phone) {
			if( isset($phone['split_phones']) && count($phone['split_phones']) > $num_phone_columns ) {
				$num_phone_columns = count($phone['split_phones']);
			}
		}
	} 

	//
	// Load the addresses
	//
	$addresses = array();
	$num_address_columns = 1;
	$strsql = "SELECT customer_id, "
		. "ciniki_customer_addresses.id, "
		. "ciniki_customer_addresses.flags, "
		. "ciniki_customer_addresses.flags AS type, "
		. "ciniki_customer_addresses.address1, "
		. "ciniki_customer_addresses.address2, "
		. "ciniki_customer_addresses.city, "
		. "ciniki_customer_addresses.province, "
		. "ciniki_customer_addresses.postal, "
		. "CONCAT_WS(', ', ciniki_customer_addresses.address1, "
			. "ciniki_customer_addresses.address2, "
			. "ciniki_customer_addresses.city, "
			. "ciniki_customer_addresses.province, "
			. "ciniki_customer_addresses.postal) AS addresses "
		. "FROM ciniki_customer_addresses "
		. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_customer_addresses.customer_id "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_id', 
			'fields'=>array('addresses'),
			'dlists'=>array('addresses'=>'/')),
		array('container'=>'split_addresses', 'fname'=>'id', 
			'fields'=>array('id', 'flags', 'type', 'address1', 'address2', 'city', 'province', 'postal'),
			'flags'=>array('type'=>$maps['address']['flags_shortcodes'])
			),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		$addresses = $rc['customers'];
		foreach($addresses as $address) {
			if( isset($address['split_addresses']) && count($address['split_addresses']) > $num_address_columns ) {
				$num_address_columns = count($address['split_addresses']);
			}
		}
	}

	//
	// Load the emails
	//
	$emails = array();
	$num_email_columns = 1;
	$strsql = "SELECT customer_id, "
		. "ciniki_customer_emails.id, "
		. "ciniki_customer_emails.email, "
		. "ciniki_customer_emails.email AS emails "
		. "FROM ciniki_customer_emails "
		. "WHERE ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_customer_emails.customer_id "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_id', 
			'fields'=>array('emails'),
			'dlists'=>array('emails'=>', ')),
		array('container'=>'split_emails', 'fname'=>'id', 'fields'=>array('id', 'email')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		$emails = $rc['customers'];
		foreach($emails as $email) {
			if( isset($email['split_emails']) && count($email['split_emails']) > $num_email_columns ) {
				$num_email_columns = count($email['split_emails']);
			}
		}
	}

	//
	// Load the links
	//
	$links = array();
	$num_link_columns = 1;
	$strsql = "SELECT customer_id, "
		. "ciniki_customer_links.id, "
		. "ciniki_customer_links.name, "
		. "ciniki_customer_links.url, "
		. "ciniki_customer_links.url AS links "
		. "FROM ciniki_customer_links "
		. "WHERE ciniki_customer_links.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_customer_links.customer_id "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_id', 
			'fields'=>array('links'),
			'dlists'=>array('links'=>', ')),
		array('container'=>'split_links', 'fname'=>'id', 'fields'=>array('id', 'name', 'url')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		$links = $rc['customers'];
		foreach($links as $link) {
			if( isset($link['split_links']) && count($link['split_links']) > $num_link_columns ) {
				$num_link_columns = count($link['split_links']);
			}
		}
	}

	require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
	$objPHPExcel = new PHPExcel();

	if( isset($args['membersonly']) && $args['membersonly'] == 'yes' ) {
		$strsql = "SELECT ciniki_customers.id, eid, prefix, first, middle, last, suffix, "
			. "company, department, title, display_name, "
			. "ciniki_customers.type, "
			. "ciniki_customers.status, "
			. "ciniki_customers.member_status, "
			. "ciniki_customers.member_lastpaid, "
			. "ciniki_customers.membership_length, "
			. "ciniki_customers.membership_type, "
			. "IF(ciniki_customers.primary_image_id>0,'yes','no') AS primary_image, "
			. "ciniki_customers.primary_image_caption, "
			. "ciniki_customers.short_description, "
			. "ciniki_customers.full_bio, "
			. "IF((ciniki_customers.webflags&0x07)>0,'Visible','Hidden') AS visible, "
			. "ciniki_customers.dealer_status, "
			. "ciniki_customers.distributor_status, "
			. "ciniki_customers.connection, "
			. "ciniki_customers.pricepoint_id, "
			. "ciniki_customers.salesrep_id, "
			. "ciniki_customers.tax_number, "
			. "ciniki_customers.tax_location_id, "
			. "ciniki_customers.reward_level, "
			. "ciniki_customers.sales_total, "
			. "ciniki_customers.sales_total_prev, "
			. "ciniki_customers.start_date, "
			. "'' AS member_categories, "
			. "'' AS phones, "
			. "'' AS addresses, "
			. "'' AS links, "
			. "'' AS emails "
			. "FROM ciniki_customers "
			. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customers.member_status = 10 "
			. "ORDER BY ciniki_customers.sort_name "
			. "";
	} elseif( isset($args['subscription_id']) && $args['subscription_id'] != '' && $args['subscription_id'] > 0 ) {
		$strsql = "SELECT ciniki_customers.id, eid, prefix, first, middle, last, suffix, "
			. "company, department, title, display_name, "
			. "ciniki_customers.type, "
			. "ciniki_customers.status, "
			. "ciniki_customers.member_status, "
			. "ciniki_customers.member_lastpaid, "
			. "ciniki_customers.membership_length, "
			. "ciniki_customers.membership_type, "
			. "IF(ciniki_customers.primary_image_id>0,'yes','no') AS primary_image, "
			. "ciniki_customers.primary_image_caption, "
			. "ciniki_customers.short_description, "
			. "ciniki_customers.full_bio, "
			. "IF((ciniki_customers.webflags&0x07)>0,'Visible','Hidden') AS visible, "
			. "ciniki_customers.dealer_status, "
			. "ciniki_customers.distributor_status, "
			. "ciniki_customers.connection, "
			. "ciniki_customers.pricepoint_id, "
			. "ciniki_customers.salesrep_id, "
			. "ciniki_customers.tax_number, "
			. "ciniki_customers.tax_location_id, "
			. "ciniki_customers.reward_level, "
			. "ciniki_customers.sales_total, "
			. "ciniki_customers.sales_total_prev, "
			. "ciniki_customers.start_date, "
			. "'' AS member_categories, "
			. "'' AS phones, "
			. "'' AS addresses, "
			. "'' AS links, "
			. "'' AS emails "
			. "FROM ciniki_subscription_customers, ciniki_customers "
			. "WHERE ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
			. "AND ciniki_subscription_customers.status = 10 "
			. "AND ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_subscription_customers.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_customers.sort_name "
			. "";
	} else {
		$strsql = "SELECT ciniki_customers.id, eid, prefix, first, middle, last, suffix, "
			. "company, department, title, display_name, "
			. "ciniki_customers.type, "
			. "ciniki_customers.status, "
			. "ciniki_customers.member_status, "
			. "ciniki_customers.member_lastpaid, "
			. "ciniki_customers.membership_length, "
			. "ciniki_customers.membership_type, "
			. "IF(ciniki_customers.primary_image_id>0,'yes','no') AS primary_image, "
			. "ciniki_customers.primary_image_caption, "
			. "ciniki_customers.short_description, "
			. "ciniki_customers.full_bio, "
			. "IF((ciniki_customers.webflags&0x07)>0,'Visible','Hidden') AS visible, "
			. "ciniki_customers.dealer_status, "
			. "ciniki_customers.distributor_status, "
			. "ciniki_customers.connection, "
			. "ciniki_customers.pricepoint_id, "
			. "ciniki_customers.salesrep_id, "
			. "ciniki_customers.tax_number, "
			. "ciniki_customers.tax_location_id, "
			. "ciniki_customers.reward_level, "
			. "ciniki_customers.sales_total, "
			. "ciniki_customers.sales_total_prev, "
			. "ciniki_customers.start_date, "
			. "'' AS member_categories, "
			. "'' AS phones, "
			. "'' AS addresses, "
			. "'' AS links, "
			. "'' AS emails "
			. "FROM ciniki_customers "
			. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_customers.sort_name "
			. "";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'eid', 'status', 'prefix', 'first', 'middle', 'last', 'suffix',
				'company', 'display_name', 'type', 'visible', 
				'member_status', 'member_lastpaid', 'membership_length', 'membership_type', 'member_categories',
				'dealer_status', 'distributor_status',
				'connection', 'pricepoint_id', 'salesrep_id', 'tax_number', 'tax_location_id', 
				'reward_level', 'sales_total', 'sales_total_prev', 'start_date',
				'phones', 'emails', 'addresses', 'links',
				'primary_image', 'primary_image_caption', 'short_description', 'full_bio'),
			'maps'=>array(
				'type'=>array('1'=>'Individual', '2'=>'Business'),
				'status'=>$maps['customer']['status'], //array('10'=>'Active', '60'=>'Former'),
				'member_status'=>$maps['customer']['member_status'], //array('10'=>'Active', '60'=>'Former'),
				'membership_length'=>$maps['customer']['membership_length'], // array('10'=>'Monthly', '20'=>'Yearly', '60'=>'Lifetime'),
				'membership_type'=>$maps['customer']['membership_type'], // array('10'=>'Regular', '20'=>'Complimentary', '30'=>'Reciprocal'),
				'dealer_status'=>$maps['customer']['dealer_status'], //array('10'=>'Active', '60'=>'Former'),
				'distributor_status'=>$maps['customer']['distributor_status'], //array('10'=>'Active', '60'=>'Former'),
				),
			'dlists'=>array('emails'=>', ')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customers']) ) {
		$customers = array();
	}

	$objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

	//
	// Add headers
	//
	$row = 1;
	$col = 0;
	foreach($args['columns'] as $column) {
		$value = '';
		switch($column) {
			case 'ids': $value = 'ID'; break;
			case 'eid': $value = 'EID'; break;
			case 'status': $value = 'Status'; break;
			case 'prefix': $value = 'Prefix'; break;
			case 'first': $value = 'First'; break;
			case 'middle': $value = 'Middle'; break;
			case 'last': $value = 'Last'; break;
			case 'suffix': $value = 'Suffix'; break;
			case 'company': $value = 'Company'; break;
			case 'department': $value = 'Department'; break;
			case 'title': $value = 'Title'; break;
			case 'display_name': $value = 'Name'; break;
			case 'type': $value = 'Type'; break;
			case 'visible': $value = 'Visible'; break;
			case 'member_status': $value = 'Member'; break;
			case 'member_lastpaid': $value = 'Last Paid'; break;
			case 'membership_length': $value = 'Length'; break;
			case 'membership_type': $value = 'Type'; break;
			case 'member_categories': $value = 'Categories'; break;
			case 'dealer_status': $value = 'Dealer Status'; break;
			case 'salesrep': $value = 'Sales Rep'; $salesrep = 'yes'; break;
			case 'pricepoint_name': $value = 'Pricepoint'; $pricepoint = 'yes'; break;
			case 'pricepoint_code': $value = 'Pricepoint Code'; $pricepoint = 'yes'; break;
			case 'tax_number': $value = 'Tax Number'; break;
			case 'tax_location_name': $value = 'Tax'; $tax_code = 'yes'; break;
			case 'tax_location_code': $value = 'Tax Code'; $tax_code = 'yes'; break;
			case 'reward_level': $value = 'Reward Level'; break;
			case 'sales_total': $value = 'Sales Total'; break;
			case 'start_date': $value = 'Start Date'; break;
			case 'distributor_status': $value = 'Distributor Status'; break;
			case 'phones': $value = 'Phones'; break;
			case 'emails': $value = 'Emails'; break;
			case 'addresses': $value = 'Addresses'; break;
			case 'links': $value = 'Websites'; break;
			case 'notes': $value = 'Notes'; break;
			case 'primary_image': $value = 'Image'; break;
			case 'primary_image_caption': $value = 'Image Caption'; break;
			case 'short_description': $value = 'Short Bio'; break;
			case 'full_bio': $value = 'Full Bio'; break;
		}
		if( $column == 'split_phones' && $num_phone_columns > 0 ) {
			for($i=0;$i<$num_phone_columns;$i++) {
				if( $ids == 'yes' ) { 
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'PID ' . ($i+1), false); 
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone ' . ($i+1), false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone ' . ($i+1) . ' Number', false);
				} else {
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone Number', false);
				}
			}
		}
		elseif( $column == 'split_addresses' && $num_address_columns > 0 ) {
			for($i=0;$i<$num_address_columns;$i++) {
				if( $ids == 'yes' ) {
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'AID ' . ($i+1), false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address ' . ($i+1) . ' Type', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address ' . ($i+1) . ' 1', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address ' . ($i+1) . ' 2', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'City' . ($i+1) . ' ', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Province' . ($i+1) . ' ', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Postal' . ($i+1) . ' ', false);
				} else {
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address Type', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address 1', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address 2', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'City', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Province', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Postal', false);
				}
			}
		}
		elseif( $column == 'split_emails' && $num_email_columns > 0 ) {
			for($i=0;$i<$num_email_columns;$i++) {
				if( $ids == 'yes' ) {
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'EID ' . ($i+1), false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email ' + ($i+1), false);
				} else {
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
				}
			}
		} 
		elseif( $column == 'split_links' && $num_link_columns > 0 ) {
			for($i=0;$i<$num_link_columns;$i++) {
				if( $ids == 'yes' ) {
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'LID ' . ($i+1), false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Link ' + ($i+1) . ' Name', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Link ' + ($i+1) . ' URL', false);
				} else {
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Link Name', false);
					$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Link URL', false);
				}
			}
		} 
		else {
			if( preg_match("/^season-([0-9]+)$/", $column, $matches) ) {
				if( isset($seasons[$matches[1]]) ) {
					$value = $seasons[$matches[1]]['name'];
				}
			}
			if( preg_match("/^subscription-([0-9]+)$/", $column, $matches) ) {
				if( isset($subscriptions[$matches[1]]) ) {
					$value = $subscriptions[$matches[1]]['name'];
				}
			}
			$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $value, false);
			$col++;
		}
	}
//	$objPHPExcelWorksheet->getStyle('A1:' . PHPExcel_Cell::stringFromColumnIndex($col) chr(65+$col-1) . '1')->getFont()->setBold(true);
	$objPHPExcelWorksheet->getStyle('A1:' . PHPExcel_Cell::stringFromColumnIndex($col) . '1')->getFont()->setBold(true);
	$objPHPExcelWorksheet->freezePane('A2');

	$row++;

	foreach($rc['customers'] as $customer) {
		$customer = $customer['customer'];

		$col = 0;
		foreach($args['columns'] as $column) {
			if( $column == 'ids' ) {
				$value = $customer['id'];
			}
			elseif( preg_match("/^season-([0-9]+)$/", $column, $matches) ) {
				$value = '';
				if( isset($seasons[$matches[1]]['customers'][$customer['id']]['status'])
					&& $seasons[$matches[1]]['customers'][$customer['id']]['status'] > 0 
					&& isset($maps['season_member']['status'][$seasons[$matches[1]]['customers'][$customer['id']]['status']]) 
					) {
					$value = $maps['season_member']['status'][$seasons[$matches[1]]['customers'][$customer['id']]['status']];
				} else {
					$col++;
					continue;
				}
			} 
			elseif( preg_match("/^subscription-([0-9]+)$/", $column, $matches) ) {
				$value = '';
				if( isset($subscriptions[$matches[1]]['customers'][$customer['id']]['status_text'])
					&& $subscriptions[$matches[1]]['customers'][$customer['id']]['status_text'] != ''
					) {
					$value = $subscriptions[$matches[1]]['customers'][$customer['id']]['status_text'];
				} else {
					$col++;
					continue;
				}
			} 
			elseif( $column == 'member_categories' && isset($member_categories[$customer['id']]['member_categories']) ) {
				$value = $member_categories[$customer['id']]['member_categories'];
			} 
			elseif( $column == 'phones' && isset($phones[$customer['id']]['phones']) ) {
				$value = $phones[$customer['id']]['phones'];
			} 
			elseif( $column == 'addresses' && isset($addresses[$customer['id']]['addresses']) ) {
				$value = preg_replace('/, ,/', ',', $addresses[$customer['id']]['addresses']);
			} 
			elseif( $column == 'emails' && isset($emails[$customer['id']]['emails']) ) {
				$value = preg_replace('/, ,/', ',', $emails[$customer['id']]['emails']);
			} 
			elseif( $column == 'links' && isset($links[$customer['id']]['links']) ) {
				$value = $links[$customer['id']]['links'];
			} 
			elseif( $column == 'salesrep' && isset($salesreps[$customer['salesrep_id']]) ) {
				$value = $salesreps[$customer['salesrep_id']]['eid'];
			} 
			elseif( $column == 'pricepoint_name' && isset($pricepoints[$customer['pricepoint_id']]) ) {
				$value = $pricepoints[$customer['pricepoint_id']]['name'];
			} 
			elseif( $column == 'pricepoint_code' && isset($pricepoints[$customer['pricepoint_id']]) ) {
				$value = $pricepoints[$customer['pricepoint_id']]['code'];
			} 
			elseif( $column == 'tax_location_code' && isset($tax_locations[$customer['tax_location_id']]) ) {
				$value = $tax_locations[$customer['tax_location_id']]['code'];
			} 
			elseif( $column == 'split_phones' ) {
				$i = 0;
				if( isset($phones[$customer['id']]['split_phones']) ) {
					foreach($phones[$customer['id']]['split_phones'] as $phone) {
						if( $ids == 'yes' ) { $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $phone['id'], false); }
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $phone['phone_label'], false);
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $phone['phone_number'], false);
						$i++;
					}
				}
				while($i<$num_phone_columns) { $col+=(2+($ids=='yes'?1:0)); $i++; }
				continue;
			} 
			elseif( $column == 'split_addresses' ) {
				$i = 0;
				if( isset($addresses[$customer['id']]['split_addresses']) ) {
					foreach($addresses[$customer['id']]['split_addresses'] as $address) {
						if( $ids == 'yes' ) { $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['id'], false); }
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, preg_replace('/, /', '', $address['type']), false);
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['address1'], false);
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['address2'], false);
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['city'], false);
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['province'], false);
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['postal'], false);
						$i++;
					}
				}
				while($i<$num_address_columns) { ($col+=6+($ids=='yes'?1:0)); $i++; }
				continue;
			} 
			elseif( $column == 'split_emails' ) {
				$i = 0;
				if( isset($emails[$customer['id']]['split_emails']) ) {
					foreach($emails[$customer['id']]['split_emails'] as $email) {
						if( $ids == 'yes' ) { $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $email['id'], false); }
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $email['email'], false);
						$i++;
					}
				}
				while($i<$num_email_columns) { ($col+=1+($ids=='yes'?1:0)); $i++; }
				continue;
			} elseif( $column == 'split_links' ) {
				$i = 0;
				if( isset($links[$customer['id']]['split_links']) ) {
					foreach($links[$customer['id']]['split_links'] as $link) {
						if( $ids == 'yes' ) { $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $link['id'], false); }
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $link['name'], false);
						$objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $link['url'], false);
						$i++;
					}
				}
				while($i<$num_link_columns) { ($col+=2+($ids=='yes'?1:0)); $i++; }
				continue;
			} elseif( !isset($customer[$column]) ) {
				$col++;
				continue;
			} else {
				$value = $customer[$column];
			}
			$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $value, false);
			$col++;
		}
		$row++;
	}

	$col = 0;
	PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
//	foreach($args['columns'] as $column) {
//		$objPHPExcelWorksheet->getColumnDimension(chr(65+$col))->setAutoSize(true);
//		$col++;
//	}

	//
	// Redirect output to a client’s web browser (Excel)
	//
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="export.xls"');
	header('Cache-Control: max-age=0');

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');

	return array('stat'=>'exit');
}
?>
