<?php

/* $Id$*/

/* $Revision: 1.44 $ */

/* Session started in header.php for password checking and authorisation level check */
include('includes/DefinePRClass.php');
//include('includes/DefineSerialItems.php');
//include('includes/DefinePaymentClass.php');
include('includes/session.php');
include('includes/SQL_CommonFunctions.inc');
if (empty($identifier)) {
	$identifier='';
	unset($_SESSION['CurImportFile']);
}
$Title = _('Modify Payment Request');
$ViewTopic = 'General Ledger';
$BookMark = 'ModifyPaymentRequest';
include('includes/header.php');

echo '<a href="'. $RootPath . '/PR_SelectOSPayRequest.php">' . _('Back to Payment Request'). '</a><br />';

if (isset($_GET['PRNumber']) and $_GET['PRNumber']<=0 and !isset($_SESSION['PR'])) {
	/* This page can only be called with a purchase order number for invoicing*/
	echo '<div class="centre"><a href= "' . $RootPath . '/PR_SelectOSPayRequest.php">'.
		_('Select a payment request to receive').'</a></div>';
	echo '<br />'. _('This page can only be opened if a payment request has been selected. Please select a payment request first');
	include ('includes/footer.php');
	exit;
} 

if (isset($_POST['Commit'])){
/* SQL to process the postings for the GRN reversal.. */
    
	$Result = DB_Txn_Begin($db);

   

/*Now the SQL to do the update to the PurchOrderDetails */
        foreach ($_POST as $key) {

    $SQL1 = "UPDATE payrequest
               SET PayeeName= '" . $_POST['payee'] . "' 
               WHERE PaymentNo = '" . $_POST['pv'] . "'";
            $Result1 = DB_query($SQL1, true); 
            
	$SQL2 = "UPDATE payrequestdetails
						SET bankact= '" . $_POST['bankaccount'] . "' ,
											account='" . $_POST['account'] . "',
											locgroup= '" . $_POST['LGroup'] . "',
											locsection= '" . $_POST['LSection'] . "',
											loccost='" . $_POST['LCost'] . "',
											source= '" . $_POST['source'] . "',
											amount='" . $_POST['amount'] . "'
						WHERE paynoid = '" . $_POST['payno'] . "'";

	$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The purchase order detail record could not be updated with the quantity reversed because');
	$DbgMsg = _('The following SQL to update the purchase order detail record was used');
	$Result=DB_query($SQL2,$ErrMsg,$DbgMsg,true);
            
}
/*Now the purchorder header status in case it was completed  - now incomplete - just printed */
	
	$Result = DB_Txn_Commit($db);

	echo '<br />' . _('Payment number') . ' ' . $_GET['PRNumber'] . '  ' . _('has been updated') . '<br />';
	unset($_POST['PRNumber']);  // to ensure it cant be done again!!
//	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . _('Select another GRN to Reverse') . '</a>';
/*end of Process Goods Received Reversal entry */

} else {
echo '<p class="page_title_text"><img alt="" src="', $RootPath, '/css/', $Theme,
'/images/supplier.png" title="', // Icon image.
_('Modify'), '" /> ', // Icon title.
_('Modify Payment Request'),'</p>';// Page title.
    echo ' : '.$_GET['PRNumber'].' '. _('paid to'). ' ' . $_GET['PRNumber'] . '</p>';
	echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?identifier=', $identifier, '" id="form1" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	
	echo '<table class="selection"><tr>';
	
	if (isset($_GET['PRNumber'])){
            

		$sql = "SELECT paynoid,
						payrequestdetails.PaymentNo,
                        bankact,
						account,
						amount,
						locgroup,
						locsection,
						loccost,
						source,
                        payrequest.PayeeName
					FROM payrequestdetails
                    LEFT JOIN payrequest ON payrequestdetails.PaymentNo = payrequest.PaymentNo 
					WHERE payrequestdetails.PaymentNo= '" . $_GET['PRNumber'] . "'";

		$ErrMsg = _('An error occurred in the attempt to get the payment request') . ' ' . $_GET['PRNumber'] . '. ' . _('The message was') . ':';
  		$DbgMsg = _('The SQL that failed was') . ':';
		$result = DB_query($sql,$ErrMsg,$DbgMsg);

		if (count($result)==0){
			prnMsg(_('There are no outstanding goods received yet to be invoiced for') . ' ' . $_POST['SuppName'] . '.<br />' . _('To reverse a GRN that has been invoiced first it must be credited'),'warn');
		} else { //there are GRNs to show

			echo '<br /><table cellpadding="2" class="selection">';
			$TableHeader = '<tr>
                    <th>' . _('No') . ' #</th>
                    <th>' . _('PV Number') . ' #</th>
                    <th>' . _('Payee') . '</th>
					<th>' . _('Group') . '</th>
					<th>' . _('Section')  . '</th>
					<th>' . _('Cost') . '</th>   
                    <th>' . _('Source') . '</th>  
                    <th>' . _('Bank Account') . '</th>
                    <th>' . _('GL Account') . '</th>
				    <th>' . _('Amount')  . '</th>';
					

			echo $TableHeader;

			/* show the GRNs outstanding to be invoiced that could be reversed */
			$RowCounter =0;
			$k=0;
			foreach ($result as $Request) {
                            $LinkToRevGRN = '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?PayNo=' . $myrow['payno'] . '">' . _('Update') . '</a>';
				if ($k==1){
					echo '<tr class="EvenTableRows">';
					$k=0;
				} else {
					echo '<tr class="OddTableRows">';
					$k=1;
				}
                               
				
echo '<td><input type="text" name="payno" size="12" maxlength="25" readonly value="'. $Request['paynoid'] . '" /></td>';
echo '<td><input type="text" name="pv" size="15" maxlength="25" readonly value="'. $Request['PaymentNo'] . '" /></td>';
echo '<td><input type="text" name="payee" size="35" maxlength="100" value="'. $Request['PayeeName'] . '" /></td>';
				echo '<td><select name="LGroup" >';
$sql = "SELECT groupid, groupname FROM locationgroup ORDER BY groupname";
$result = DB_query($sql, $db);


while ($myrow = DB_fetch_array($result)) {
	if ($myrow['groupid'] ==  $Request['locgroup']  ){
		echo '<option selected="True" value="' . $myrow['groupid'] . '">' . $myrow['groupname'] . '</option>';
	} else {
		echo '<option value="' . $myrow['groupid'] . '">' . $myrow['groupname'] . '</option>';
	}
        $LGroup=$myrow['groupid'];
} //end while loop

echo '</select></td>';
if (isset($_POST['LGroup']) AND $_POST['LGroup']!='') {
		$SQL = "SELECT sectionid,
					sectionname
			FROM locationsection
			WHERE locgroupname='".$_POST['LGroup']."'
			ORDER BY sectionid";
	} 
        else {
		$SQL = "SELECT sectionid,
					sectionname
			FROM locationsection
			ORDER BY sectionid";
	}
                echo '<td><select name="LSection">';
$result = DB_query($SQL, $db);


while ($myrow = DB_fetch_array($result)) {
	if ($Request['locsection']  == $myrow['sectionid']){
		echo '<option selected="True" value="' . $myrow['sectionid'] . '">' . $myrow['sectionname'] . '</option>';
	} else {
		echo '<option value="' . $myrow['sectionid'] . '">' . $myrow['sectionname'] . '</option>';
	}
} //end while loop

echo '</select></td>';
if (isset($_POST['LSection']) AND $_POST['LSection']!='') {
		$SQL = "SELECT costid,
					costname
			FROM locationcost
			WHERE locsectionname='".$_POST['LSection']."'
			ORDER BY costid";
	} 
        else {
		$SQL = "SELECT costid,
					costname
			FROM locationcost
			ORDER BY costid";
	}
                echo '<td><select name="LCost">';
$result = DB_query($SQL, $db);


while ($myrow = DB_fetch_array($result)) {
	if ($Request['loccost']  == $myrow['costid']){
		echo '<option selected="True" value="' . $myrow['costid'] . '">' . $myrow['costname'] . '</option>';
	} else {
		echo '<option value="' . $myrow['costid'] . '">' . $myrow['costname'] . '</option>';
	}
} //end while loop

echo '</select></td>';
    
  echo '<td><select name="source">';
$sql = "SELECT sourceref, sourcedescription FROM source ORDER BY sourcedescription";
$result = DB_query($sql, $db);


while ($myrow = DB_fetch_array($result)) {
	if ($Request['source']  == $myrow['sourceref']){
		echo '<option selected="True" value="' . $myrow['sourceref'] . '">' . $myrow['sourcedescription'] . '</option>';
	} else {
		echo '<option value="' . $myrow['sourceref'] . '">' . $myrow['sourcedescription'] . '</option>';
	}
} //end while loop

echo '</select></td>';
echo '<td><select name="bankaccount">';
$sql = "SELECT accountcode, bankaccountname FROM bankaccounts ORDER BY bankaccountname";
$result = DB_query($sql, $db);


while ($myrow = DB_fetch_array($result)) {
	if ($Request['bankact']  == $myrow['accountcode']){
		echo '<option selected="True" value="' . $myrow['accountcode'] . '">' . $myrow['accountcode'] . '</option>';
	} else {
		echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountcode'] . '</option>';
	}
} //end while loop

echo '</select></td>';
echo '<td><select name="account" >';
$sql = "SELECT accountcode, accountname FROM chartmaster ORDER BY accountname";
$result = DB_query($sql, $db);


while ($myrow = DB_fetch_array($result)) {
	if ($Request['account']  == $myrow['accountcode']){
		echo '<option selected="True" value="' . $myrow['accountcode'] . '">' . $myrow['accountcode'] . '</option>';
	} else {
		echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountcode'] . '</option>';
	}
} //end while loop

echo '</select></td>';
                
echo '<td><input type="text" name="amount" size="12" maxlength="25" value="'. $Request['amount'] . '" /></td>';
//echo '<td> '.$LinkToRevGRN.'</td>';

				
			}

			echo '</table>';
		}
                
	}
        echo '<br /><div class="centre"><button type="submit" name="Commit">' . _('Modify Request') . '</button></div>';
}
include ('includes/footer.php');
?>