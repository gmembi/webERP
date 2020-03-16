<?php


$PricesSecurity = 12;

include('includes/session.php');

$Title = _('Search Outstanding Payment Request');

include('includes/header.php');
//include('includes/DefinePOClass.php');

if (isset($_GET['SelectedStockItem'])) {
	$SelectedStockItem = trim($_GET['SelectedStockItem']);
}
elseif (isset($_POST['SelectedStockItem'])) {
	$SelectedStockItem = trim($_POST['SelectedStockItem']);
}

if (isset($_GET['PaymentNumber'])) {
	$PaymentNumber = $_GET['PaymentNumber'];
}
elseif (isset($_POST['PaymentNumber'])) {
	$PaymentNumber = $_POST['PaymentNumber'];
}

if (isset($_GET['SelectedSupplier'])) {
	$SelectedSupplier = trim($_GET['SelectedSupplier']);
}
elseif (isset($_POST['SelectedSupplier'])) {
	$SelectedSupplier = trim($_POST['SelectedSupplier']);
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
	<div>
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
}

if (isset($PaymentNumber) AND $PaymentNumber != '') {
	if (!is_numeric($PaymentNumber)) {
		echo '<br /><b>' . _('The Payment Number entered') . ' <u>' . _('MUST') . '</u> ' . _('be numeric') . '.</b><br />';
		unset($PaymentNumber);
	} else {
		echo _('Payment Number') . ' - ' . $PaymentNumber;
	}
} else {
	if (isset($SelectedSupplier)) {
		echo '<br />
				<div class="page_help_text">' . _('For supplier') . ': ' . $SelectedSupplier . ' ' . _('and') . ' ';
		echo '<input type="hidden" name="SelectedSupplier" value="' . $SelectedSupplier . '" />
				</div>';
	}
	if (isset($SelectedStockItem)) {
		echo '<input type="hidden" name="SelectedStockItem" value="' . $SelectedStockItem . '" />';
	}
}




/* Not appropriate really to restrict search by date since user may miss older ouststanding orders
$OrdersAfterDate = Date("d/m/Y",Mktime(0,0,0,Date("m")-2,Date("d"),Date("Y")));
*/

if (!isset($PaymentNumber) or $PaymentNumber == '') {
	if (isset($SelectedSupplier)) {
		echo '<a href="' . $RootPath . '/GLPaymentRequest.php">' . _('Add Payment Request') . '</a>';
	} else {
		echo '<a href="' . $RootPath . '/GLPaymentRequest.php">' . _('Add Payment Request') . '</a>';
	}
	echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';
	echo '<table class="selection">
			<tr>
				<td>' . _('Payment Request') . ': <input type="text" name="PaymentNumber" autofocus="autofocus" maxlength="8" size="9" /> ';

	if (!isset($_POST['DateFrom'])) {
		$DateSQL = "SELECT min(reqdate) as fromdate,
							max(reqdate) as todate
						FROM payrequest";
		$DateResult = DB_query($DateSQL);
		$DateRow = DB_fetch_array($DateResult);
		if ($DateRow['fromdate'] != null) {
		$DateFrom = $DateRow['fromdate'];
		$DateTo = $DateRow['todate'];
	} else {
			$DateFrom = date('Y-m-d');
			$DateTo = date('Y-m-d');
		}
	} else {
		$DateFrom = FormatDateForSQL($_POST['DateFrom']);
		$DateTo = FormatDateForSQL($_POST['DateTo']);
	}

	echo ' ' . _('Payment Status:') . ' <select name="Status">';
	if (!isset($_POST['Status']) OR $_POST['Status'] == 'Pending_Authorised') {
		echo '<option selected="selected" value="Pending_Authorised">' . _('Pending and Authorised') . '</option>';
	} else {
		echo '<option value="Pending_Authorised">' . _('Pending and Authorised') . '</option>';
	}
	if(isset($_POST['Status'])){
		if ($_POST['Status'] == 'Pending') {
			echo '<option selected="selected" value="Pending">' . _('Pending') . '</option>';
		} else {
			echo '<option value="Pending">' . _('Pending') . '</option>';
		}
		if ($_POST['Status'] == 'Authorised') {
			echo '<option selected="selected" value="Authorised">' . _('Authorised') . '</option>';
		} else {
			echo '<option value="Authorised">' . _('Authorised') . '</option>';
		}
		if ($_POST['Status'] == 'Cancelled') {
			echo '<option selected="selected" value="Cancelled">' . _('Cancelled') . '</option>';
		} else {
			echo '<option value="Cancelled">' . _('Cancelled') . '</option>';
		}
		if ($_POST['Status'] == 'Rejected') {
			echo '<option selected="selected" value="Rejected">' . _('Rejected') . '</option>';
		} else {
			echo '<option value="Rejected">' . _('Rejected') . '</option>';
		}
	}
	
	echo '</select>
		' . _('Payment Request Between') . ':&nbsp;
			<input type="text" name="DateFrom" value="' . ConvertSQLDate($DateFrom) . '"  class="date" size="10" />
		' . _('and') . ':&nbsp;
			<input type="text" name="DateTo" value="' . ConvertSQLDate($DateTo) . '"  class="date" size="10" />
		<input type="submit" name="SearchOrders" value="' . _('Search Purchase Orders') . '" />
		</td>
		</tr>
		</table>';
} //!isset($PaymentNumber) or $PaymentNumber == ''

	//figure out the SQL required from the inputs available

	if (!isset($_POST['Status']) OR $_POST['Status'] == 'Pending_Authorised') {
		$StatusCriteria = " AND (payrequest.status='Pending' OR payrequest.status='Authorised' OR payrequest.status='Printed') ";
	} elseif ($_POST['Status'] == 'Authorised') {
		$StatusCriteria = " AND (payrequest.status='Authorised' OR payrequest.status='Printed')";
	} elseif ($_POST['Status'] == 'Pending') {
		$StatusCriteria = " AND payrequest.status='Pending' ";
	} elseif ($_POST['Status'] == 'Rejected') {
		$StatusCriteria = " AND payrequest.status='Rejected' ";
	} elseif ($_POST['Status'] == 'Cancelled') {
		$StatusCriteria = " AND payrequest.status='Cancelled' ";
	}
	if (isset($PaymentNumber) AND $PaymentNumber != '') {
		$SQL = "SELECT  payrequest.PaymentNo,
                        payrequest.PayeeName,
                        suppliers.suppname,
                        payrequest.initiator,
                        payrequest.reqdate,
                        payrequest.status,
                        payrequest.currcode,
						SUM(payrequestdetails.amount) AS ordervalue
				FROM payrequest INNER JOIN payrequestdetails
				ON payrequest.PaymentNo = payrequestdetails.PaymentNo
				INNER JOIN suppliers
				ON payrequest.supplierno = suppliers.supplierid
				WHERE payrequestdetails.completed=0
                AND payrequest.PaymentNo='" . $PaymentNumber . "'
				GROUP BY payrequest.PaymentNo ASC";
	} else {
		//$PaymentNumber is not set
		if (isset($SelectedSupplier)) {

            $SQL = "SELECT  payrequest.PaymentNo,
            payrequest.PayeeName,
            suppliers.suppname,
            payrequest.initiator,
            payrequest.reqdate,
            payrequest.status,
            payrequest.currcode,
            SUM(payrequestdetails.amount) AS ordervalue
    FROM payrequest INNER JOIN payrequestdetails
    ON payrequest.PaymentNo = payrequestdetails.PaymentNo
    INNER JOIN suppliers
    ON payrequest.supplierno = suppliers.supplierid
    WHERE payrequestdetails.completed=0
    AND reqdate>='" . $DateFrom . "'
	AND reqdate<='" . $DateTo . "'
	AND payrequest.supplierno='" . $SelectedSupplier . "'
	". $StatusCriteria . "
    GROUP BY payrequest.PaymentNo ASC,
        suppliers.suppname,
        payrequest.reqdate";
			
		} //isset($SelectedSupplier)
		else { //no supplier selected
	
            $SQL = "SELECT  payrequest.PaymentNo,
            payrequest.PayeeName,
            suppliers.suppname,
            payrequest.initiator,
            payrequest.reqdate,
            payrequest.status,
            payrequest.allowprint,
            payrequest.currcode,
            SUM(payrequestdetails.amount) AS ordervalue
    FROM payrequest INNER JOIN payrequestdetails
    ON payrequest.PaymentNo = payrequestdetails.PaymentNo
    LEFT JOIN suppliers
    ON payrequest.supplierno = suppliers.supplierid
    WHERE payrequestdetails.completed=0
    AND reqdate>='" . $DateFrom . "'
	AND reqdate<='" . $DateTo . "'
	". $StatusCriteria . "
    GROUP BY payrequest.PaymentNo ASC,
        suppliers.suppname,
        payrequest.reqdate";
			
		} //end selected supplier
	} //end not order number selected

	$ErrMsg = _('No orders were returned by the SQL because');
	$PurchOrdersResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($PurchOrdersResult) > 0) {
	/*show a table of the orders returned by the SQL */

		echo '<table cellpadding="2" width="97%" class="selection">
			<thead>';

            echo '<tr><th>' . _('Payment #') .
			'</th><th>' . _('Request Date') .
			'</th><th>' . _('Initiated by') .
			'</th><th>' . _('Payee Name') .
            '</th><th>' . _('Supplier Name') .
			'</th><th>' . _('Currency') .
			'</th>';
	if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($PricesSecurity)) {
		echo '<th>' . _('PaymentTotal') .'</th>';
	}
	echo '<th>' . _('Status') .
			'</th><th>' . _('Modify') .
			'</th><th>' . _('Print') .
			'</th><th>' . _('Post') .
	'</th></tr>
		</thead>
		<tbody>';

	while ($myrow = DB_fetch_array($PurchOrdersResult)) {
		// $Bal = '';
		// if (isset($_POST['PODetails'])) {
		// 	//lets retrieve the PO balance here to make it a standard sql query.
		// 	$BalSql = "SELECT itemcode, quantityord - quantityrecd as balance FROM purchorderdetails WHERE orderno = '" . $myrow['orderno'] . "'";
		// 	$ErrMsg = _('Failed to retrieve purchorder details');
		// 	$BalResult  = DB_query($BalSql,$ErrMsg);
		// 	if (DB_num_rows($BalResult)>0) {
		// 		while ($BalRow = DB_fetch_array($BalResult)) {
		// 			$Bal .= '<br/>' . $BalRow['itemcode'] . ' -- ' . $BalRow['balance'];
		// 		}
		// 	}
		// }
		// if (isset($_POST['PODetails'])) {
		// 	$BalRow = '<td width="250" style="word-break:break-all">' . $Bal . '</td>';
		// } else {
		// 	$BalRow = '';
		// }

		$ModifyPayment = $RootPath . '/ModifyPayments.php?PRNumber=' . $myrow['PaymentNo'];
		if ($myrow['status'] == 'Printed') {
			$ReceiveOrder = '<a href="' . $RootPath . '/PaymentReceived.php?PRNumber=' . $myrow['PaymentNo'].'">' . _('Post') . '</a>';
		} else {
			$ReceiveOrder = '';
		}
		if ($myrow['status'] == 'Authorised' AND $myrow['allowprint'] == 1) {
			$PrintPurchOrder = '<a target="_blank" href="' . $RootPath . '/PR_PDFPaymentRequest.php?PaymentNo=' . $myrow['PaymentNo'] . '">' . _('Print') . '</a>';
		} elseif ($myrow['status'] == 'Authorisied' AND $myrow['allowprint'] == 0) {
			$PrintPurchOrder = _('Printed');
		} elseif ($myrow['status'] == 'Printed') {
			$PrintPurchOrder = '<a target="_blank" href="' . $RootPath . '/PR_PDFPaymentRequest.php?PaymentNo=' . $myrow['PaymentNo'] . '&amp;realorderno=' . $myrow['realorderno'] . '&amp;ViewingOnly=2">
				' . _('Print Copy') . '</a>';
		} else {
			$PrintPurchOrder = _('N/A');
		}


		$FormatedOrderDate = ConvertSQLDate($myrow['reqdate']);
		$FormatedOrderValue = locale_number_format($myrow['ordervalue'], $myrow['currdecimalplaces']);
		$sql = "SELECT realname FROM www_users WHERE userid='" . $myrow['initiator'] . "'";
		$UserResult = DB_query($sql);
		$MyUserRow = DB_fetch_array($UserResult);
		$InitiatorName = $MyUserRow['realname'];

        echo '<tr class="striped_row">
            <td>' . $myrow['PaymentNo'] . '</td>
			<td>' . $FormatedOrderDate . '</td>
			<td>' . $InitiatorName . '</td>
            <td>' . $myrow['PayeeName'] . '</td>
            <td>' . $myrow['suppname'] . '</td>
			<td>' . $myrow['currcode'] . '</td>';
		if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($PricesSecurity)) {
			echo '<td class="number">' . $FormatedOrderValue . '</td>';
		}
        echo '<td>' . _($myrow['status']) . '</td>
             <td><a href="' . $ModifyPayment . '">' . _('Modify') . '</a></td>
				<td>' . $PrintPurchOrder . '</td>
				<td>' . $ReceiveOrder . '</td>
			</tr>';
	} //end of while loop around purchase orders retrieved

		echo '</tbody></table>';
	}

echo '</div>
      </form>';
include('includes/footer.php');
?>
