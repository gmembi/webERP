<?php


include('includes/session.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/DefinePOClass.php');

if (!isset($_GET['PaymentNo']) AND !isset($_POST['PaymentNo'])) {
	$Title = _('Select a purchase order');
	include('includes/header.php');
	echo '<div class="centre"><br /><br /><br />';
	prnMsg(_('Select a Payment request Number to Print before calling this page'), 'error');
	echo '<br />
				<br />
				<br />
				<table class="table_index">
					<tr><td class="menu_group_item">
						<li><a href="' . $RootPath . '/PR_SelectOSPayrequest.php">' . _('Outstanding Payment Request') . '</a></li>
						</td>
					</tr></table>
				</div>
				<br />
				<br />
				<br />';
	include('includes/footer.php');
	exit();

	echo '<div class="centre"><br /><br /><br />' . _('This page must be called with a payment request number to print');
	echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a></div>';
	exit;
}
if (isset($_GET['PaymentNo'])) {
	$PaymentNo = $_GET['PaymentNo'];
}
elseif (isset($_POST['PaymentNo'])) {
	$PaymentNo = $_POST['PaymentNo'];
}
$Title = _('Print Payment Voucher Number') . ' ' . $PaymentNo;

if (isset($_POST['PrintOrEmail']) AND isset($_POST['EmailTo'])) {
	if ($_POST['PrintOrEmail'] == 'Email' AND !IsEmailAddress($_POST['EmailTo'])) {
		include('includes/header.php');
		prnMsg(_('The email address entered does not appear to be valid. No emails have been sent.'), 'warn');
		include('includes/footer.php');
		exit;
	}
}
$ViewingOnly = 0;

if (isset($_GET['ViewingOnly']) AND $_GET['ViewingOnly'] != '') {
	$ViewingOnly = $_GET['ViewingOnly'];
}
elseif (isset($_POST['ViewingOnly']) AND $_POST['ViewingOnly'] != '') {
	$ViewingOnly = $_POST['ViewingOnly'];
}

/* If we are previewing the order then we dont want to email it */
if ($PaymentNo == 'Preview') { //PaymentNo is set to 'Preview' when just looking at the format of the printed order
	$_POST['PrintOrEmail'] = 'Print';
	/*These are required to kid the system - I hate this */
	$_POST['ShowAmounts'] = 'Yes';
	$OrderStatus = _('Printed');
	$MakePDFThenDisplayIt = True;
} //$PaymentNo == 'Preview'

if (isset($_POST['DoIt']) AND ($_POST['PrintOrEmail'] == 'Print' OR $ViewingOnly == 1)) {
	$MakePDFThenDisplayIt = True;
	$MakePDFThenEmailIt = False;
} elseif (isset($_POST['DoIt']) AND $_POST['PrintOrEmail'] == 'Email' AND isset($_POST['EmailTo'])) {
	$MakePDFThenEmailIt = True;
	$MakePDFThenDisplayIt = False;
}

if (isset($PaymentNo) AND $PaymentNo != '' AND $PaymentNo > 0 AND $PaymentNo != 'Preview') {
	/*retrieve the order details from the database to print */
	$ErrMsg = _('There was a problem retrieving the purchase order header details for Order Number') . ' ' . $PaymentNo . ' ' . _('from the database');
	$sql = "SELECT	payrequest.PayeeName,
                    payrequest.supplierno,
                    suppliers.suppname,
                    payrequest.initiator,
                    www_users.realname,
                    payrequest.reqdate,
                    payrequest.currcode,
                    payrequest.authorisedby,
                    payrequest.dateauth,
                    payrequest.dateprinted,
                    payrequest.allowprint,
                    payrequest.status,
					payrequest.paymenttypes
				FROM payrequest LEFT JOIN suppliers
					ON payrequest.supplierno = suppliers.supplierid
				LEFT JOIN www_users
					ON payrequest.initiator=www_users.userid
				WHERE payrequest.PaymentNo='" . $PaymentNo . "'";
	$result = DB_query($sql, $ErrMsg);
	if (DB_num_rows($result) == 0) {
		/*There is no order header returned */
		$Title = _('Print Payment Request Error');
		include('includes/header.php');
		echo '<div class="centre"><br /><br /><br />';
		prnMsg(_('Unable to Locate Payment Request Number') . ' : ' . $PaymentNo . ' ', 'error');
		echo '<br />
			<br />
			<br />
			<table class="table_index">
				<tr><td class="menu_group_item">
				<li><a href="' . $RootPath . '/PR_SelectOSPayrequest.php">' . _('Outstanding Payment Request') . '</a></li>
				</td>
				</tr>
			</table>
			</div><br /><br /><br />';
		include('includes/footer.php');
		exit();
	} elseif (DB_num_rows($result) == 1) {
		/*There is only one order header returned  (as it should be!)*/

		$POHeader = DB_fetch_array($result);

		if ($POHeader['status'] != 'Authorised' AND $POHeader['status'] != 'Printed') {
			include('includes/header.php');
			prnMsg(_('Payment request can only be printed once they have been authorised') . '. ' . _('This order is currently at a status of') . ' ' . _($POHeader['status']), 'warn');
			include('includes/footer.php');
			exit;
		}

		if ($ViewingOnly == 0) {
			if ($POHeader['allowprint'] == 0) {
				$Title = _('Payment Request Already Printed');
				include('includes/header.php');
				echo '<p>';
				prnMsg(_('Payment Request Number') . ' ' . $PaymentNo . ' ' . _('has previously been printed') . '. ' . _('It was printed on') . ' ' . ConvertSQLDate($POHeader['dateprinted']) . '<br />' . _('To re-print the payment request it must be modified to allow a reprint') . '<br />' . _('This check is there to ensure that duplicate payment request '), 'warn');

				echo '<div class="centre">
 					<li><a href="' . $RootPath . '/PR_PDFPaymentRequest.php?PaymentNo=' . $PaymentNo . '&ViewingOnly=1">' . _('Print This Payment Request as a Copy') . '</a>
					<li><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a></div>';

				include('includes/footer.php');
				exit;
			} //AllowedToPrint
		} //not ViewingOnly
	} // 1 valid record
} //if there is a valid order number
else if ($PaymentNo == 'Preview') { // We are previewing the order

	/* Fill the order header details with dummy data */
    $POHeader['PayeeName']=str_pad('',10,'x');
    $POHeader['suppname']=str_pad('',10,'x');
    $POHeader['realname']=str_pad('',10,'x');
	$POHeader['initiator']=str_pad('',40,'x');
	$POHeader['authorisedby']=str_pad('',40,'x');
	$POHeader['paidby']=str_pad('',40,'x');
	$POHeader['reqdate']='1900-01-01';
    $POHeader['dateauth']='1900-01-01';
	$POHeader['dateprinted']='1900-01-01';
	$POHeader['allowprint']=1;
	$POHeader['requisitionno']=str_pad('',15,'x');
	$POHeader['paymenttypes']=str_pad('',15,'x');
	$POHeader['currency']='XXX';
} // end of If we are previewing the order

/* Load the relevant xml file */
if (isset($MakePDFThenDisplayIt) or isset($MakePDFThenEmailIt)) {
	if ($PaymentNo == 'Preview') {
		$FormDesign = simplexml_load_file(sys_get_temp_dir() . '/PaymentRequest.xml');
	} else {
		$FormDesign = simplexml_load_file($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/FormDesigns/PaymentRequest.xml');
	}
	// Set the paper size/orintation
	$PaperSize = $FormDesign->PaperSize;
	include('includes/PDFStarter.php');
	$pdf->addInfo('Title', _('Purchase Order'));
	$pdf->addInfo('Subject', _('Purchase Order Number') . ' ' . $PaymentNo);
	$line_height = $FormDesign->LineHeight;
	$PageNumber = 1;
	/* Then there's an order to print and its not been printed already (or its been flagged for reprinting)
	Now ... Has it got any line items */
	if ($PaymentNo != 'Preview') { // It is a real order
		$ErrMsg = _('There was a problem retrieving the line details for order number') . ' ' . $PaymentNo . ' ' . _('from the database');
		$sql = "SELECT payrequest.PayeeName,
        suppliers.suppname,
        initiator,
        reqdate,
        authorisedby,
        dateauth,
        payrequestdetails.narrative,
        payrequest.currcode,
        paidby,
        payrequestdetails.amount,
		payrequestdetails.paymenttypes
FROM payrequest LEFT JOIN suppliers
ON payrequest.supplierno = suppliers.supplierid
         LEFT JOIN payrequestdetails
ON payrequest.PaymentNo=payrequestdetails.PaymentNo
WHERE payrequest.PaymentNo='" . $PaymentNo ."'";
		$result = DB_query($sql);
	}
	if ($PaymentNo == 'Preview' or DB_num_rows($result) > 0) {
		/*Yes there are line items to start the ball rolling with a page header */
		include('includes/PR_PDFOrderPageHeader.inc');
		$YPos = $Page_Height - $FormDesign->Data->y;
		$OrderTotal = 0;
		while ((isset($PaymentNo) AND $PaymentNo == 'Preview') OR (isset($result) AND !is_bool($result) AND $POLine = DB_fetch_array($result))) {
			/* If we are previewing the order then fill the
			 * order line with dummy data */
			if ($PaymentNo == 'Preview') {
				$POLine['PayeeName']=str_pad('',10,'x');
                $POLine['suppname']=str_pad('',10,'x');
				$POLine['reqdate']='1900-01-01';
                $POLine['dateauth']='1900-01-01';
				$POLine['narrative']=str_pad('',50,'x');
				$POLine['amount']=9999.99;
				$POLine['paidby']=str_pad('',4,'x');
				$POLine['authorisedby']=str_pad('',50,'x');
				$POLine['initiator']=str_pad('',50,'x');
				$POLine['paymenttypes']=str_pad('',50,'x');
			}
			if ($POLine['decimalplaces'] != NULL) {
				$DecimalPlaces = $POLine['decimalplaces'];
			}
			else {
				$DecimalPlaces = 2;
			}
			// $DisplayQty = locale_number_format($POLine['quantityord'] / $POLine['conversionfactor'], $DecimalPlaces);
			// if ($_POST['ShowAmounts'] == 'Yes') {
			// 	$DisplayPrice = locale_number_format($POLine['unitprice'] * $POLine['conversionfactor'], $POHeader['currdecimalplaces']);
			// } else {
			// 	$DisplayPrice = '----';
			// }
            $DisplayReqDate = ConvertSQLDate($POLine['reqdate']);
            $DisplayAuthDate = ConvertSQLDate($POLine['dateauth']);
			// if ($_POST['ShowAmounts'] == 'Yes') {
			// 	$DisplayLineTotal = locale_number_format($POLine['unitprice'] * $POLine['quantityord'], $POHeader['currdecimalplaces']);
			// } else {
			// 	$DisplayLineTotal = '----';
			// }
			/* If the supplier item code is set then use this to display on the PO rather than the businesses item code */
			// if (mb_strlen($POLine['suppliers_partno'])>0){
			// 	$ItemCode = $POLine['suppliers_partno'];
			// } else {
			// 	$ItemCode = $POLine['itemcode'];
			// }
			$RequestTotal += $POLine['amount'];

			$lines=explode("\r\n",htmlspecialchars_decode($POLine['narrative']));
				for ($i=0;$i<sizeOf($lines);$i++) {
					while(mb_strlen($lines[$i])>1) {
						if($YPos-$line_height <= $Bottom_Margin + 40) {
							/* head up a new invoice/credit note page */
							/*draw the vertical column lines right to the bottom */
							//PrintLinesToBottom ();
							include ('includes/PR_PDFOrderPageHeader.inc');
						} //end if need a new page headed up
						/*increment a line down for the next line item */
						if(mb_strlen($lines[$i])>1) {
							$lines[$i] = $pdf->addTextWrap($FormDesign->Data->Column1->x, $YPos, $FormDesign->Data->Column1->Length, $FormDesign->Data->Column1->FontSize, stripslashes($lines[$i]), 'left');
						}
						$YPos -= ($line_height-2);
					}
				}
			
			while (mb_strlen($LeftOvers) > 1) {
				$YPos -= $line_height;
				if ($YPos - $line_height <= $Bottom_Margin+100) {
					/* We reached the end of the page so finsih off the page and start a newy */
					$PageNumber++;
					$YPos = $Page_Height - $FormDesign->Data->y;
					include('includes/PR_PDFOrderPageHeader.inc');
				} //end if we reached the end of page
				$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column1->x, $YPos, $FormDesign->Data->Column1->Length, $FormDesign->Data->Column1->FontSize, $LeftOvers, 'left');
			} //end if need a new page headed up
			$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column2->x, $YPos+10, $FormDesign->Data->Column2->Length, $FormDesign->Data->Column2->FontSize, locale_number_format($POLine['amount']), 'left');
			// $LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column4->x, $YPos, $FormDesign->Data->Column4->Length, $FormDesign->Data->Column4->FontSize, $POLine['suppliersunit'], 'left');
			// $LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column5->x, $YPos, $FormDesign->Data->Column5->Length, $FormDesign->Data->Column5->FontSize, $DisplayDelDate, 'left');
			// $LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column6->x, $YPos, $FormDesign->Data->Column6->Length, $FormDesign->Data->Column6->FontSize, $DisplayPrice, 'right');
			// $LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column7->x, $YPos, $FormDesign->Data->Column7->Length, $FormDesign->Data->Column7->FontSize, $DisplayLineTotal, 'right');
			if (mb_strlen($LeftOvers) > 1) {
				$LeftOvers = $pdf->addTextWrap($Left_Margin + 1 + 94, $YPos - $line_height, 270, $FontSize, $LeftOvers, 'left');
				$YPos -= $line_height;
			}
			if ($YPos - $line_height <= $Bottom_Margin+100) {
				/* We reached the end of the page so finsih off the page and start a newy */
				$PageNumber++;
				$YPos = $Page_Height - $FormDesign->Data->y;
				include('includes/PR_PDFOrderPageHeader.inc');
			} //end if need a new page headed up

			/*increment a line down for the next line item */
			$YPos -= $line_height;
			/* If we are previewing we want to stop showing order
			 * lines after the first one */
			if ($PaymentNo == 'Preview') {
				$PaymentNo = 'Preview_PurchaseOrder';
			} //$PaymentNo == 'Preview'
		} //end while there are line items to print out
		if ($YPos - $line_height <= $Bottom_Margin) { // need to ensure space for totals
			$PageNumber++;
			include('includes/PR_PDFOrderPageHeader.inc');
		} //end if need a new page headed up
		if ($_POST['ShowAmounts'] == 'Yes') {
			$DisplayOrderTotal = locale_number_format($RequestTotal, $POHeader['currdecimalplaces']);
		} else {
			$DisplayOrderTotal = '----';
		}
		$pdf->Line($FormDesign->LineBetweenTotal->startx, $Page_Height - $FormDesign->LineBetweenTotal->starty, $FormDesign->LineBetweenTotal->endx,$Page_Height - $FormDesign->LineBetweenTotal->endy);
		$pdf->Rectangle($FormDesign->TotalRectangle->x, $Page_Height - $FormDesign->TotalRectangle->y, $FormDesign->TotalRectangle->width,$FormDesign->TotalRectangle->height);
		$pdf->addText($FormDesign->OrderTotalCaption->x, $Page_Height - $FormDesign->OrderTotalCaption->y, $FormDesign->OrderTotalCaption->FontSize, _('Payment Voucher Total ') . ' ' . $POHeader['currcode']);
		$LeftOvers = $pdf->addTextWrap($FormDesign->RequestTotal->x, $Page_Height - $FormDesign->RequestTotal->y, $FormDesign->RequestTotal->Length, $FormDesign->RequestTotal->FontSize, $DisplayOrderTotal, 'right');

		$pdf->addText($FormDesign->RequestDate->x,$Page_Height - $FormDesign->RequestDate->y, $FormDesign->RequestDate->FontSize, _('Requested Date') . ':' );
        $pdf->addText($FormDesign->RequestDate->x+70,$Page_Height - $FormDesign->RequestDate->y, $FormDesign->RequestDate->FontSize, ConvertSQLDate($POHeader['reqdate']));
       /*Now the Initiator */
$pdf->addText($FormDesign->Initiator->x,$Page_Height - $FormDesign->Initiator->y, $FormDesign->Initiator->FontSize, _('Initiator').': ');
$pdf->addText($FormDesign->Initiator->x+40,$Page_Height - $FormDesign->Initiator->y, $FormDesign->Initiator->FontSize, $POHeader['realname']);

$pdf->addText($FormDesign->AuthorisedDate->x,$Page_Height - $FormDesign->AuthorisedDate->y, $FormDesign->AuthorisedDate->FontSize, _('Authorised Date') . ':' );
$pdf->addText($FormDesign->AuthorisedDate->x+70,$Page_Height - $FormDesign->AuthorisedDate->y, $FormDesign->AuthorisedDate->FontSize, ConvertSQLDate($POHeader['dateauth']));
/*Now the Initiator */
$pdf->addText($FormDesign->Authoriser->x,$Page_Height - $FormDesign->Authoriser->y, $FormDesign->Authoriser->FontSize, _('Authoriser').': ');
$pdf->addText($FormDesign->Authoriser->x+50,$Page_Height - $FormDesign->Authoriser->y, $FormDesign->Authoriser->FontSize, $POHeader['authorisedby']);

$pdf->addText($FormDesign->PaidDate->x,$Page_Height - $FormDesign->PaidDate->y, $FormDesign->PaidDate->FontSize, _('Paid Date') . ':' );
$pdf->addText($FormDesign->PaidDate->x+40,$Page_Height - $FormDesign->PaidDate->y, $FormDesign->PaidDate->FontSize, Date($_SESSION['DefaultDateFormat']));
/*Now the Initiator */
$pdf->addText($FormDesign->PaidBy->x,$Page_Height - $FormDesign->PaidBy->y, $FormDesign->PaidBy->FontSize, _('Paid For ').': ');
$sql="SELECT realname FROM www_users WHERE userid='".$_SESSION['UserID']."'";
$payerresult=DB_query($sql);
$myrow1=DB_fetch_array($payerresult);
$pdf->addText($FormDesign->PaidBy->x+40,$Page_Height - $FormDesign->PaidBy->y, $FormDesign->PaidBy->FontSize, ($myrow1['realname']));

$pdf->addText($FormDesign->ReceivedDate->x,$Page_Height - $FormDesign->ReceivedDate->y, $FormDesign->ReceivedDate->FontSize, _('Received Date').': ');
$pdf->addText($FormDesign->ReceivedDate->x+60,$Page_Height - $FormDesign->ReceivedDate->y, $FormDesign->ReceivedDate->FontSize, Date($_SESSION['DefaultDateFormat']));

$pdf->addText($FormDesign->Receiver->x,$Page_Height - $FormDesign->Receiver->y, $FormDesign->Receiver->FontSize, _('Payee Name').': ');
$pdf->addText($FormDesign->Receiver->x+50,$Page_Height - $FormDesign->Receiver->y, $FormDesign->Receiver->FontSize, $POHeader['PayeeName']);

//$pdf->addText($FormDesign->Supplier->x,$Page_Height - $FormDesign->Supplier->y, $FormDesign->Supplier->FontSize, _('Supplier Name').': ');
$pdf->addText($FormDesign->Supplier->x+50,$Page_Height - $FormDesign->Supplier->y, $FormDesign->Supplier->FontSize, $POHeader['suppname']);

$LeftOvers = $pdf->addText($FormDesign->SignedFor->x,$Page_Height-$FormDesign->SignedFor->y,$FormDesign->SignedFor->FontSize, _('Signed for ').'______________________');

$LeftOvers = $pdf->addText($FormDesign->SignedBy->x,$Page_Height-$FormDesign->SignedBy->y,$FormDesign->SignedBy->FontSize, _('Signed By ').'______________________');
	} /*end if there are order details to show on the order - or its a preview*/

	$Success = 1; //assume the best and email goes - has to be set to 1 to allow update status
	if ($MakePDFThenDisplayIt) {
		$pdf->OutputD($_SESSION['DatabaseName'] . '_PaymentVoucher_' . $PaymentNo . '_' . date('Y-m-d') . '.pdf');
		$pdf->__destruct();
	} else {
		/* must be MakingPDF to email it */

		$PdfFileName = $_SESSION['DatabaseName'] . '_PaymentVoucher_' . $PaymentNo . '_' . date('Y-m-d') . '.pdf';
		$pdf->Output($_SESSION['reports_dir'] . '/' . $PdfFileName, 'F');
		$pdf->__destruct();
		include('includes/htmlMimeMail.php');
		$mail = new htmlMimeMail();
		$attachment = $mail->getFile($_SESSION['reports_dir'] . '/' . $PdfFileName);
		$mail->setText(_('Please find herewith our purchase order number') . ' ' . $PaymentNo);
		$mail->setSubject(_('Purchase Order Number') . ' ' . $PaymentNo);
		$mail->addAttachment($attachment, $PdfFileName, 'application/pdf');
		//since sometime the mail server required to verify the users, so must set this information.
		if($_SESSION['SmtpSetting'] == 0){//use the mail service provice by the server.
			$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . '<' . $_SESSION['CompanyRecord']['email'] . '>');
			$Success = $mail->send(array($_POST['EmailTo']));
		}else if($_SESSION['SmtpSetting'] == 1) {
			$Success = SendmailBySmtp($mail,array($_POST['EmailTo']));

		}else{
			prnMsg(_('The SMTP settings are wrong, please ask administrator for help'),'error');
			exit;
			include('includes/footer.php');
		}

		if ($Success == 1) {
			$Title = _('Email a Purchase Order');
			include('includes/header.php');
			echo '<div class="centre"><br /><br /><br />';
			prnMsg(_('Purchase Order') . ' ' . $PaymentNo . ' ' . _('has been emailed to') . ' ' . $_POST['EmailTo'] . ' ' . _('as directed'), 'success');

		} else { //email failed
			$Title = _('Email a Purchase Order');
			include('includes/header.php');
			echo '<div class="centre"><br /><br /><br />';
			prnMsg(_('Emailing Purchase order') . ' ' . $PaymentNo . ' ' . _('to') . ' ' . $_POST['EmailTo'] . ' ' . _('failed'), 'error');
		}
	}
	if ($ViewingOnly == 0 AND $Success == 1) {
		$StatusComment = date($_SESSION['DefaultDateFormat']) . ' - ' . _('Printed by') . ' <a href="mailto:' . $_SESSION['UserEmail'] . '">' . $_SESSION['UsersRealName'] . '</a><br />' . html_entity_decode($POHeader['stat_comment'], ENT_QUOTES, 'UTF-8');

		$sql = "UPDATE payrequest	SET	allowprint =  0,
										dateprinted  = '" . Date('Y-m-d') . "',
										status = 'Printed',
                                        paidby = '" .$_SESSION['UserID']. "'
				WHERE payrequest.PaymentNo = '" . $PaymentNo . "'";
		$result = DB_query($sql);
	}
	include('includes/footer.php');
} //isset($MakePDFThenDisplayIt) OR isset($MakePDFThenEmailIt)

/* There was enough info to either print or email the purchase order */
else {
	/*the user has just gone into the page need to ask the question whether to print the order or email it to the supplier */
	include('includes/header.php');
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
	echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	if ($ViewingOnly == 1) {
		echo '<input type="hidden" name="ViewingOnly" value="1" />';
	} //$ViewingOnly == 1
	echo '<br /><br />';
	echo '<input type="hidden" name="PaymentNo" value="' . $PaymentNo . '" />';
	echo '<table>
         <tr>
             <td>' . _('Print or Email the Order') . '</td>
             <td><select name="PrintOrEmail">';

	if (!isset($_POST['PrintOrEmail'])) {
		$_POST['PrintOrEmail'] = 'Print';
	}
	if ($ViewingOnly != 0) {
		echo '<option selected="selected" value="Print">' . _('Print') . '</option>';
	}
	else {
		if ($_POST['PrintOrEmail'] == 'Print') {
			echo '<option selected="selected" value="Print">' . _('Print') . '</option>';
			echo '<option value="Email">' . _('Email') . '</option>';
		} else {
			echo '<option value="Print">' . _('Print') . '</option>';
			echo '<option selected="selected" value="Email">' . _('Email') . '</option>';
		}
	}
	echo '</select></td></tr>';
	echo '<tr><td>' . _('Show Amounts on the Order') . '</td><td>
		<select name="ShowAmounts">';
	if (!isset($_POST['ShowAmounts'])) {
		$_POST['ShowAmounts'] = 'Yes';
	}
	if ($_POST['ShowAmounts'] == 'Yes') {
		echo '<option selected="selected" value="Yes">' . _('Yes') . '</option>';
		echo '<option value="No">' . _('No') . '</option>';
	} else {
		echo '<option value="Yes">' . _('Yes') . '</option>';
		echo '<option selected="selected" value="No">' . _('No') . '</option>';
	}
	echo '</select></td></tr>';
	if ($_POST['PrintOrEmail'] == 'Email') {
		$ErrMsg = _('There was a problem retrieving the contact details for the supplier');
		$SQL = "SELECT suppliercontacts.contact,
						suppliercontacts.email
				FROM suppliercontacts INNER JOIN payrequest
				ON suppliercontacts.supplierid=payrequest.supplierno
				WHERE payrequest.PaymentNo='" . $PaymentNo . "'";
		$ContactsResult = DB_query($SQL, $ErrMsg);
		if (DB_num_rows($ContactsResult) > 0) {
			echo '<tr><td>' . _('Email to') . ':</td><td><select name="EmailTo">';
			while ($ContactDetails = DB_fetch_array($ContactsResult)) {
				if (mb_strlen($ContactDetails['email']) > 2 AND mb_strpos($ContactDetails['email'], '@') > 0) {
					if ($_POST['EmailTo'] == $ContactDetails['email']) {
						echo '<option selected="selected" value="' . $ContactDetails['email'] . '">' . $ContactDetails['Contact'] . ' - ' . $ContactDetails['email'] . '</option>';
					} else {
						echo '<option value="' . $ContactDetails['email'] . '">' . $ContactDetails['contact'] . ' - ' . $ContactDetails['email'] . '</option>';
					}
				}
			}
			echo '</select></td></tr></table>';
		} else {
			echo '</table><br />';
			prnMsg(_('There are no contacts defined for the supplier of this order') . '. ' . _('You must first set up supplier contacts before emailing an order'), 'error');
			echo '<br />';
		}
	} else {
		echo '</table>';
	}
	echo '<br />
         <div class="centre">
              <input type="submit" name="DoIt" value="' . _('OK') . '" />
         </div>
         </div>
         </form>';

	include('includes/footer.php');
}
?>
