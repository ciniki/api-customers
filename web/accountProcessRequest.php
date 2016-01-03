<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_accountProcessRequest($ciniki, $settings, $business_id, $args) {

    $page = array(
        'title'=>'Account',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
    );

    $base_url = $args['base_url'];

    //
    // Check for change password
    //
    if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'changepassword' ) {
//        $page['breadcrumbs'][] = array('name'=>'Change Password', 'url'=>$ciniki['request']['domain_base_url'] . '/account/changepassword');
        $page['title'] = 'Change Password';
    
        $display_form = 'yes';
        if( isset($_POST['action']) && $_POST['action'] == 'update' 
            && isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 
            ) {
			//
			// Check if customer wants to change their password
			//
			if( isset($_POST['oldpassword']) && $_POST['oldpassword'] != '' 
				&& isset($_POST['newpassword']) && $_POST['newpassword'] != '' 
				&& (!isset($settings['page-account-password-change']) 
					|| $settings['page-account-password-change'] == 'yes')
				) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'changePassword');
				$rc = ciniki_customers_web_changePassword($ciniki, $ciniki['request']['business_id'], 
					$_POST['oldpassword'], $_POST['newpassword']);
				if( $rc['stat'] != 'ok' ) {
                    $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to set your new password, please try again.');
				} else {
                    $page['blocks'][] = array('type'=>'formmessage', 'level'=>'success', 'message'=>'Your password has been updated.');
                    $display_form = 'no';
				}
			}
        }
       
        if( $display_form == 'yes' ) {
            $page['blocks'][] = array('type'=>'message', 'content'=>'If you would like to change your password, enter your old password followed by a new one.');
            $content = "<form action='' method='POST'>";
            $content .= "<div class='change-password-form'>";
            $content .= "<input type='hidden' name='action' value='update'/>";
            $content .= "<div class='input'>"
                . "<label for='oldpassword'>Old Password:</label>"
                . "<input class='text password' id='oldpassword' type='password' name='oldpassword' />"
                . "</div>";
            $content .= "<div class='input'>"
                . "<label for='newpassword'>New Password:</label>"
                . "<input class='text password' id='newpassword' type='password' name='newpassword' />"
                . "</div>";
            $content .= "<div class='submit'><input type='submit' class='button submit' value='Change Password'></div>\n";
            $content .= "</div>";
            $content .= "</form>";
            $page['blocks'][] = array('type'=>'content', 'html'=>$content);
        }
    } 
   
    //
    // Check for contact details update
    //
    elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'contactdetails' 
        && ((isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes')
            || (isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes')
            || (isset($settings['page-account-email-update']) && $settings['page-account-email-update'] == 'yes')
            )
        ) {
        $page['breadcrumbs'][] = array('name'=>'Contact Details', 'url'=>$ciniki['request']['domain_base_url'] . '/account/contactdetails');

        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'accountProcessRequestContactDetails');
        $rc = ciniki_customers_web_accountProcessRequestContactDetails($ciniki, $settings, $business_id, array());
        if( $rc['stat'] != 'ok' ) {
            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>$rc['err']['msg'] . " Please try again or contact us for help.");
            return $rc;
        } else {
            if( $rc['errors'] == 'yes' ) {
                $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>$error_msg . " Please try again or contact us for help.");
            } elseif( $rc['updated'] == 'yes' ) {
                $page['blocks'][] = array('type'=>'formmessage', 'level'=>'success', 'message'=>'Your contact information is updated.');
            }
            $form = "<form action='' method='POST'>"
                . "<input type='hidden' name='action' value='update'>";
            $form .= $rc['form'];
            $form .= "<div class='submit'><input type='submit' class='submit' value='Save'></div>";
            $form .= "</div>"
                . "</form>";
        }

        $page['title'] = 'Contact Details';
        $page['blocks'][] = array('type'=>'content', 'html'=>$form);
    } 

    //
    // Check for other accounts
    //
    elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'accounts' ) {
        $page['breadcrumbs'][] = array('name'=>'Accounts', 'url'=>$ciniki['request']['domain_base_url'] . '/account/accounts');
        $page['title'] = 'Other Accounts';

        $aside = "<p>Name: " . $ciniki['session']['customer']['display_name'] . "</p>";
        if( isset($customer['addresses']) ) {
            foreach($customer['addresses'] as $addr) {
                $addr = $addr['address'];
                if( ($addr['flags']&0x02) ) {
                    $aside .= "<p><b>Billing Address</b><br/>"
                        . preg_replace('/\n/', '<br/>', $addr['joined'])
                        . "</p>";
                }
                if( ($addr['flags']&0x01) ) {
                    $aside .= "<p><b>Shipping Address</b><br/>"
                        . preg_replace('/\n/', '<br/>', $addr['joined'])
                        . "</p>";
                }
            }
        }
        $page['blocks'][] = array('type'=>'asidecontent', 'title'=>'Account', 'html'=>$aside);

        $content = "<div class='largebutton-list'>";
        foreach($ciniki['session']['customers'] as $cust) {
            $content .= "<div class='button-list-wrap'><div class='button-list-button'>";
            $content .= "<a href='" . $ciniki['request']['base_url'] . '/account/switch/' . $cust['id'] . "'>" . $cust['display_name'] . "</a>";
            $content .= "</div></div><br/>";
        }
        $content .= "</div>";
        $page['blocks'][] = array('type'=>'content', 'html'=>$content);
    }

	return array('stat'=>'ok', 'page'=>$page);
}
?>
