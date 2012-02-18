<?php
//
// Description
// -----------
// This function will accept a uploaded Excel file via POST
// and will parse the Excel file into the table ciniki_customers_automerge_data.
//
// Arguments
// ---------
// api_key:
// auth_token:		
// business_id:			The business ID to create the excel file for.
// uploadfile:			The information about the file uploaded via a file form field.
//
// Returns
// -------
// <upload id="19384992" />
//
function ciniki_customers_automergeUploadXLS($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'name'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No name specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access to business_id
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
	$ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.automergeUploadXLS', 0);
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Setup memory limits to be able to process large files
	//
	ini_set("upload_max_filesize", "10M");
	ini_set('memory_limit', '4096M');


	if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'579', 'msg'=>'Upload failed, file too large.'));
	}

	if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['name'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'580', 'msg'=>'Upload failed, no file specified.', '_FILES'=>$_FILES));
	}

	if( $args['name'] == '' ) {
		$args['name'] = $_FILES['uploadfile']['name'];
	}
	

	//
	// Open Excel parsing library
	//
//	require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
	$inputFileType = 'Excel5';
	$inputFileName = $_FILES['uploadfile']['tmp_name'];

	//
	// Turn off autocommit
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Create a new upload entry in the database
	//
	$strsql = "INSERT INTO ciniki_customer_automerges (business_id, name, source_name, date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['name']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $_FILES['uploadfile']['name']) . "' "
		. ", UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'customers');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return $rc;
	}

	//
	// Grab the newly created ID 
	//
	$automerge_id = $rc['insert_id'];

	//
	// Copy the uploaded file
	//
	$filename = $ciniki['config']['core']['modules_dir'] . '/customers/uploads/automerge_' . $automerge_id . '.xls';
	rename($_FILES['uploadfile']['tmp_name'], $filename);

	//
	// Update the information in the database
	//
	$strsql = "UPDATE ciniki_customer_automerges SET status = 1, cache_name = '" . ciniki_core_dbQuote($ciniki, "automerge_" . $automerge_id) . "' "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $automerge_id) . "' ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'customers');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return $rc;
	}

	//
	// Commit the changes
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$automerge_id);
}
?>
