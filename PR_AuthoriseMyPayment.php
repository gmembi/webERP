<?php


include('includes/session.php');

$Title = _('Authorise Payment Request');

include('includes/header.php');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' . $Title .
	 '" alt="" />' . ' ' . $Title . '</p>';

$EmailSQL="SELECT email,realname FROM www_users WHERE userid='".$_SESSION['UserID']."'";
$EmailResult=DB_query($EmailSQL);
$EmailRow=DB_fetch_array($EmailResult);

if (isset($_POST['UpdateAll'])) {
	foreach ($_POST as $key => $value) {
		if (mb_substr($key,0,6)=='Status') {
			$PaymentNo=mb_substr($key,6);
			$Status=$_POST['Status'.$PaymentNo];
			$Comment=date($_SESSION['DefaultDateFormat']).' - '._('Authorised by').' <a href="mailto:' . $EmailRow['email'].'">' . $_SESSION['UserID'] . '</a><br />' . html_entity_decode($_POST['comment'],ENT_QUOTES,'UTF-8');
			$sql="UPDATE payrequest
					SET status='".$Status."',
						allowprint=1
					WHERE PaymentNo='". $PaymentNo."'";
			$result=DB_query($sql);
		}
		    $status1=$_POST['Status'.$PaymentNo];
            $date = date($_SESSION['DefaultDateFormat']);
                  if($status1=='Authorised'){
                            $sql1="UPDATE payrequest
				            SET authorisedby='" .$EmailRow['realname']. "',
                                dateauth='" . Date('Y-m-d') . "'
				            WHERE PaymentNo='".$PaymentNo."'";
			          $result=DB_query($sql1);
                        }
	}
}

/* Retrieve the purchase order header information
 */
$sql="SELECT payrequest.*,
			suppliers.suppname,
			www_users.realname,
			www_users.email
		FROM payrequest LEFT JOIN suppliers
			ON suppliers.supplierid=payrequest.supplierno
		LEFT JOIN www_users
			ON www_users.userid=payrequest.initiator
	WHERE status='Pending'";
$result=DB_query($sql);

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<div>
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
	<table class="selection">';

/* Create the table for the purchase order header */
echo '<thead>
		<tr>
		<th class="ascending">' . _('Payment Request Number') . '</th>
		<th class="ascending">' . _('Payee') . '</th>
		<th class="ascending">' . _('Supplier') . '</th>
		<th class="ascending">' . _('Date Requested') . '</th>
		<th class="ascending">' . _('Initiator') . '</th>
		<th class="ascending">' . _('Status') . '</th>
		</tr>
	</thead>
	<tbody>';

while ($myrow=DB_fetch_array($result)) {

	$AuthSQL="SELECT authlevel FROM payrequestauth
				WHERE userid='".$_SESSION['UserID']."'
				AND currabrev='".$myrow['currcode']."'";

	$AuthResult=DB_query($AuthSQL);
	$myauthrow=DB_fetch_array($AuthResult);
	$AuthLevel=$myauthrow['authlevel'];

	$OrderValueSQL="SELECT sum(amount) as ordervalue
		           	FROM payrequestdetails
			        WHERE PaymentNo='".$myrow['PaymentNo'] . "'";

	$OrderValueResult=DB_query($OrderValueSQL);
	$MyOrderValueRow=DB_fetch_array($OrderValueResult);
	$OrderValue=$MyOrderValueRow['ordervalue'];

	if ($AuthLevel>=$OrderValue) {
		echo '<tr>
				<td>' . $myrow['PaymentNo'] . '</td>
				<td>'.$myrow['PayeeName'].'</td>
                <td>'.$myrow['suppname'].'</td>
				<td>' . ConvertSQLDate($myrow['reqdate']) . '</td>
				<td><a href="mailto:'.$myrow['email'].'">' . $myrow['realname'] . '</td>
				<td><select name="Status'.$myrow['PaymentNo'].'">
					<option selected="selected" value="Pending">' . _('Pending') . '</option>
					<option value="Authorised">' . _('Authorised') . '</option>
					<option value="Rejected">' . _('Rejected') . '</option>
					<option value="Cancelled">' . _('Cancelled') . '</option>
					</select></td>
			</tr>';
		echo '<input type="hidden" name="comment" value="' . htmlspecialchars($myrow['stat_comment'], ENT_QUOTES,'UTF-8') . '" />';
		$LineSQL="SELECT payrequestdetails.*,
					     bankaccounts.bankaccountname,
                         locationgroup.groupname,
                         locationsection.sectionname,
                         locationcost.costname
				FROM payrequestdetails
				LEFT JOIN bankaccounts  
				ON bankaccounts.accountcode=payrequestdetails.bankact
                                LEFT JOIN locationgroup
				ON locationgroup.groupid=payrequestdetails.locgroup
                                LEFT JOIN locationsection
				ON locationsection.sectionid=payrequestdetails.locsection
                                LEFT JOIN locationcost
				ON locationcost.costid=payrequestdetails.loccost
				WHERE PaymentNo='".$myrow['PaymentNo'] . "'";
		$LineResult=DB_query($LineSQL);

		echo '<tr>
				<td></td>
				<td colspan="5" align="left">
					<table class="selection" align="left">
					<thead>
					<tr>
					<th>'._('Loc Group').'</th>
					<th>'._('Loc Section').'</th>
					<th>'._('Loc Cost').'</th>
					<th>'._('Narration ').'</th>
					<th>'._('Bank ').'</th>
					<th>'._('Line Total').'</th>
						</tr>
					</thead>
					<tbody>';

		while ($LineRow=DB_fetch_array($LineResult)) {
			if ($LineRow['decimalplaces']!=NULL){
				$DecimalPlaces = $LineRow['decimalplaces'];
			}else {
				$DecimalPlaces = 2;
			}
			echo '<tr>
					<td>' . $LineRow['groupname'] . '</td>
					<td>' . $LineRow['sectionname'] . '</td>
					<td>' . $LineRow['costname'] . '</td>
					<td>' . $LineRow['narrative'] . '</td>
					<td>' . $LineRow['bankaccountname'] . '</td>
					<td>' . $LineRow['amount'] . '</td>
				</tr>';
		} // end while order line detail
		echo '</tbody></table>
			</td>
			</tr>';
	}
} //end while header loop
echo '</tbody>
	</table>
		<br />
		<div class="centre">
			<input type="submit" name="UpdateAll" value="' . _('Update'). '" />
		</div>
        </div>
		</form>';
include('includes/footer.php');
?>
