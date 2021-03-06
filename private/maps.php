<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_maps($ciniki) {

    $maps = array();
    $maps['customer'] = array(
        'type'=>array(
            '1'=>'Person',
            '2'=>'Business',
            '10'=>'Individual',
            '20'=>'Family',
            '21'=>'Parent',
            '22'=>'Child',
            '30'=>'Business',
            '31'=>'Admin',
            '32'=>'Employee',
            ),
        'status'=>array(
            '10'=>'Active',
            '40'=>'On Hold',
            '50'=>'Suspended',
            '60'=>'Deleted',
            ),
        'member_status'=>array(
            '0'=>'',
            '10'=>'Active',
            '60'=>'Inactive',
            ),
        'dealer_status'=>array(
            '0'=>'',
            '5'=>'Prospect',
            '10'=>'Active',
            '40'=>'Previous',
            '60'=>'Closed',
            ),
        'distributor_status'=>array(
            '0'=>'',
            '5'=>'Prospect',
            '10'=>'Active',
            '40'=>'Previous',
            '60'=>'Closed',
            ),
        'membership_length'=>array(
            '0'=>'',
            '10'=>'Monthly',
            '20'=>'Yearly',
            '60'=>'Lifetime',
            ),
        'membership_type'=>array(
            '0'=>'Unknown',
            '10'=>'Regular',
            '20'=>'Student',
            '30'=>'Individual',
            '40'=>'Family',
            '110'=>'Complimentary',
            '150'=>'Reciprocal',
            '200'=>'Purchased',
            ),
        );
    $maps['season_member'] = array(
        'status'=>array(
            '0'=>'Unknown',
            '10'=>'Active',
            '60'=>'Inactive',
            ),
        );

    $maps['address'] = array(
        'flags'=>array(
            '0'=>'',
            0x01=>'Shipping',
            0x02=>'Billing',
            0x04=>'Mailing',
            0x08=>'Public',
            ),
        'flags_shortcodes'=>array(
            '0'=>'',
            0x01=>'S',
            0x02=>'B',
            0x04=>'M',
            0x08=>'P',
            ),
        );
    $maps['log'] = array(
        'status'=>array(
            '10' => 'Success',
            '30' => 'Warning',
            '50' => 'Error',
            ),
        );
    $maps['product'] = array(
        'type'=>array(
            '10' => 'Subscription',
            '20' => 'Lifetime',
            '40' => 'Subscription Add-on',
            '60' => 'One Time Add-on',
            ),
        'status'=>array(
            '10' => 'Active',
            '90' => 'Archived',
            ),
        );
    
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
