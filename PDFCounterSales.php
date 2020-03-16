<?php
/*	PrintCustTransPortrait.php */
/*  Print Invoices or Credit Notes (Portrait Mode) */

include('includes/session.php');
$Title = _('Print Invoices or Credit Notes (Portrait Mode)');
$ViewTopic = 'ARReports';
$BookMark = 'PrintInvoicesCredits';

if(isset($_GET['BatchNo'])) {
	$BatchNo = filter_number_format($_GET['BatchNo']);
} elseif(isset($_POST['BatchNo'])) {
	$BatchNo = filter_number_format($_POST['BatchNo']);
} else {
	$BatchNo = '';
}

if(isset($_GET['InvOrCredit'])) {
	$InvOrCredit = $_GET['InvOrCredit'];
} elseif(isset($_POST['InvOrCredit'])) {
	$InvOrCredit = $_POST['InvOrCredit'];
}

if(isset($_GET['PrintPDF'])) {
	$PrintPDF = $_GET['PrintPDF'];
} elseif(isset($_POST['PrintPDF'])) {
	$PrintPDF = $_POST['PrintPDF'];
}

if(!isset($_POST['ToTransNo'])
	OR trim($_POST['ToTransNo'])==''
	OR filter_number_format($_POST['ToTransNo']) < $BatchNo) {

	$_POST['ToTransNo'] = $BatchNo;
}

if(isset($_GET['InvNo'])) {
	$InvNo = filter_number_format($_GET['InvNo']);
} elseif(isset($_POST['InvNo'])) {
	$InvNo = filter_number_format($_POST['InvNo']);
} else {
	$InvNo = '';
}

$FirstTrans = $BatchNo; /* Need to start a new page only on subsequent transactions */

if(isset($PrintPDF)
	and $PrintPDF!=''
	and isset($BatchNo)
	and isset($InvOrCredit)
	and $BatchNo!='') {
	
	$PaperSize='Z5';
	include ('includes/PDFStarter.php');

	if($InvOrCredit=='Invoice') {
		$pdf->addInfo('Title',_('Counter Sales Receipt') . ' ' . $BatchNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
		$pdf->addInfo('Subject',_('Invoices from') . ' ' . $BatchNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
	} else {
		$pdf->addInfo('Title',_('Sales Credit Note') );
		$pdf->addInfo('Subject',_('Credit Notes from') . ' ' . $BatchNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
	}

	$FirstPage = true;
	$line_height=12;

	//Keep a record of the user's language
	$UserLanguage = $_SESSION['Language'];

	while($BatchNo <= filter_number_format($_POST['ToTransNo'])) {

	/*retrieve the invoice details from the database to print
	notice that salesorder record must be present to print the invoice purging of sales orders will
	nobble the invoice reprints */

	// check if the user has set a default bank account for invoices, if not leave it blank
		$sql = "SELECT bankaccounts.invoice,
					bankaccounts.bankaccountnumber,
					bankaccounts.bankaccountcode,
					banktrans.currcode
				FROM bankaccounts
				WHERE bankaccounts.invoice = '1'";
		$result=DB_query($sql,'','',false,false);
		if(DB_error_no()!=1) {
			if(DB_num_rows($result)==1) {
				$myrow = DB_fetch_array($result);
				$DefaultBankAccountNumber = _('Account') .': ' .$myrow['bankaccountnumber'];
				$DefaultBankAccountCode = _('Bank Code:') .' ' .$myrow['bankaccountcode'];
				$Currency = $myrow['currcode'];
			} else {
				$DefaultBankAccountNumber = '';
				$DefaultBankAccountCode = '';
			}
		} else {
			$DefaultBankAccountNumber = '';
			$DefaultBankAccountCode = '';
		}
// gather the invoice data

		if($InvOrCredit=='Invoice') {
			$sql = "SELECT debtortrans.trandate,
							debtortrans.ovamount,
							debtortrans.ovdiscount,
							debtortrans.ovfreight,
							debtortrans.ovgst,
							debtortrans.rate,
							debtorsmaster.name,
							debtorsmaster.currcode,
							salesorders.deliverto,
							debtortrans.debtorno,
							debtortrans.branchcode,
							currencies.decimalplaces
						FROM debtortrans INNER JOIN debtorsmaster
						ON debtortrans.debtorno=debtorsmaster.debtorno
						INNER JOIN salesorders
						ON debtortrans.order_ = salesorders.orderno
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						WHERE debtortrans.type=10
						AND debtortrans.transno='" . $InvNo . "'";

			if(isset($_POST['PrintEDI']) and $_POST['PrintEDI']=='No') {
				$sql = $sql . ' AND debtorsmaster.ediinvoices=0';
			}
		} 

		$result=DB_query($sql,'','',false,false);

		if(DB_error_no()!=0) {

			$Title = _('Transaction Print Error Report');
			include ('includes/header.php');

			prnMsg( _('There was a problem retrieving the invoice or credit note details for note number') . ' ' . $InvoiceToPrint . ' ' . _('from the database') . '. ' . _('To print an invoice, the sales order record, the customer transaction record and the branch record for the customer must not have been purged') . '. ' . _('To print a credit note only requires the customer, transaction, salesman and branch records be available'),'error');
			if($debug==1) {
				prnMsg (_('The SQL used to get this information that failed was') . '<br />' . $sql,'error');
			}
			include ('includes/footer.php');
			exit;
		}
		if(DB_num_rows($result)==1) {
			$myrow = DB_fetch_array($result);

			$ExchRate = $myrow['rate'];
			$Name = $myrow['deliverto'];
			//$Currency = $myrow['currcode'];
			//Change the language to the customer's language
			$_SESSION['Language'] = $myrow['language_id'];
			include('includes/LanguageSetup.php');

			if($InvOrCredit == 'Invoice') {
				$sql = "SELECT stockmoves.stockid,
						stockmaster.description,
						-stockmoves.qty as quantity,
						stockmoves.discountpercent,
						((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . "* -stockmoves.qty) AS fxnet,
						(stockmoves.price * " . $ExchRate . ") AS fxprice,
						stockmoves.narrative,
						stockmaster.controlled,
						stockmaster.serialised,
						stockmaster.units,
						stockmoves.stkmoveno,
						stockmaster.decimalplaces
					FROM stockmoves INNER JOIN stockmaster
					ON stockmoves.stockid = stockmaster.stockid
					WHERE stockmoves.type=10
					AND stockmoves.transno='" . $InvNo . "'
					AND stockmoves.show_on_inv_crds=1";
			} 

		$result=DB_query($sql);
		if(DB_error_no()!=0) {
			$Title = _('Transaction Print Error Report');
			include ('includes/header.php');
			echo '<br />' . _('There was a problem retrieving the invoice or credit note stock movement details for invoice number') . ' ' . $BatchNo . ' ' . _('from the database');
			if($debug==1) {
				echo '<br />' . _('The SQL used to get this information that failed was') . '<br />' . $sql;
			}
			include('includes/footer.php');
			exit;
		}


		if(DB_num_rows($result)>0) {

			$FontSize = 10;
			$PageNumber = 1;

			include('includes/PDFCounterSalesPageHeader.inc');
			$FirstPage = False;

			while($myrow2=DB_fetch_array($result)) {
				if($myrow2['discountpercent'] == 0) {
					$DisplayDiscount = '';
				} else {
					$DisplayDiscount = locale_number_format($myrow2['discountpercent'] * 100, 2) . '%';
					$DiscountPrice = $myrow2['fxprice'] * (1 - $myrow2['discountpercent']);
				}
				$DisplayNet = locale_number_format($myrow2['fxnet'],$myrow['decimalplaces']);
				$DisplayPrice = locale_number_format($myrow2['fxprice'],$myrow['decimalplaces']);
				$DisplayQty = locale_number_format($myrow2['quantity'],$myrow2['decimalplaces']);

			    $lines=explode("\r\n",htmlspecialchars_decode($myrow2['description']));
				for ($i=0;$i<sizeOf($lines);$i++) {
					while(mb_strlen($lines[$i])>1) {
						if($YPos-$line_height <= $Bottom_Margin) {
							/* head up a new invoice/credit note page */
							/*draw the vertical column lines right to the bottom */
							PrintLinesToBottom ();
							include ('includes/PDFCounterSalesPageHeader.inc');
						} //end if need a new page headed up
						/*increment a line down for the next line item */
						if(mb_strlen($lines[$i])>1) {
							$lines[$i] = $pdf->addTextWrap($Left_Margin,$YPos,120,9,stripslashes($lines[$i]), 'left');
						}
						$YPos -= ($line_height);
					}
				}

				$LeftOvers = $pdf->addTextWrap($Left_Margin+100,$YPos+10,100,9, $DisplayNet, 'right');

				
				

				if ($YPos - (2 *$line_height) < $Bottom_Margin){
					/*Then set up a new page */
					include ('includes/PDFCounterSalesPageHeader.inc');
				} /*end of new page header  */

			} /*end while there are line items to print out*/

		} /*end if there are stock movements to show on the invoice or credit note*/

		$YPos -= $line_height;

		/* check to see enough space left to print the 4 lines for the totals/footer */
		if(($YPos-$Bottom_Margin)<(2*$line_height)) {
			PrintLinesToBottom ();
			include ('includes/PDFCounterSalesPageHeader.inc');
		}
		

		if($InvOrCredit=='Invoice') {
			
			$DisplayTotal = locale_number_format($myrow['ovfreight']+$myrow['ovgst']+$myrow['ovamount'],$myrow['decimalplaces']);
		} 

		$YPos = $Bottom_Margin+(3*$line_height);

	/*Print out the invoice text entered */
		

		$YPos+=10;
		if($InvOrCredit=='Invoice') {
			

			$LeftOvers = $pdf->addTextWrap($Left_Margin,$Bottom_Margin+20, 144, 10, _('TOTAL') . ' ' . $Currency . ' ' . _('INVOICE'));

			/* Add Images for Visa / Mastercard / Paypal */
			if(file_exists('companies/' . $_SESSION['DatabaseName'] . '/payment.jpg')) {
				$pdf->addJpegFromFile('companies/' . $_SESSION['DatabaseName'] . '/payment.jpg',$Page_Width/2 -60,$YPos-15,0,20);
			}

			// Print Bank acount details if available and default for invoices is selected
			$pdf->addText($Left_Margin, $YPos+22-$line_height*3, $FontSize, $DefaultBankAccountCode . ' ' . $DefaultBankAccountNumber);
			$FontSize=10;
		} 

		$LeftOvers = $pdf->addTextWrap($Left_Margin+100,$Bottom_Margin+20,100,9, $DisplayTotal, 'right');
		} /* end of check to see that there was an invoice record to print */
		$FontSize = 6;
        $LeftOvers = $pdf->addTextWrap($Left_Margin+40,$YPos+420,300,12, $Name , 'left');
		$FontSize = 6;
		$LeftOvers = $pdf->addTextWrap($Left_Margin+60, $Bottom_Margin+5, 144, $FontSize, _('Issued by :'). ' ' .$_SESSION['UsersRealName']);
	    $pdf->line($Left_Margin+150, $Bottom_Margin+5,$Page_Width-$Right_Margin, $Bottom_Margin+5);
		$BatchNo++;
	} /* end loop to print invoices */

	/* Put the transaction number back as would have been incremented by one after last pass */
	$BatchNo--;

	if(isset($_GET['Email'])) { //email the invoice to address supplied
		include ('includes/htmlMimeMail.php');
		$FileName = $_SESSION['reports_dir'] . '/' . $_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . $_GET['BatchNo'] . '.pdf';
		$pdf->Output($FileName,'F');
		$mail = new htmlMimeMail();

		$Attachment = $mail->getFile($FileName);
		$mail->setText(_('Please find attached') . ' ' . $InvOrCredit . ' ' . $_GET['BatchNo'] );
		$mail->SetSubject($InvOrCredit . ' ' . $_GET['BatchNo']);
		$mail->addAttachment($Attachment, $FileName, 'application/pdf');
		if($_SESSION['SmtpSetting'] == 0) {
			$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>');
			$result = $mail->send(array($_GET['Email']));
		} else {
			$result = SendmailBySmtp($mail,array($_GET['Email']));
		}

		unlink($FileName); //delete the temporary file

		$Title = _('Emailing') . ' ' .$InvOrCredit . ' ' . _('Number') . ' ' . $BatchNo;
		include('includes/header.php');
		echo '<p>' . $InvOrCredit . ' ' . _('number') . ' ' . $BatchNo . ' ' . _('has been emailed to') . ' ' . $_GET['Email'];
		include('includes/footer.php');
		exit;

	} 	else { //its not an email just print the invoice to PDF
		$pdf->OutputD($_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . $FromTransNo . '.pdf');

	}
	$pdf->__destruct();
	//Change the language back to the user's language
	$_SESSION['Language'] = $UserLanguage;
	include('includes/LanguageSetup.php');
	}

function PrintLinesToBottom () {

	global $Bottom_Margin;
	global $Left_Margin;
	global $line_height;
	global $PageNumber;
	global $pdf;
	global $TopOfColHeadings;

	// Prints column vertical lines:
	$pdf->line($Left_Margin+ 78, $TopOfColHeadings,$Left_Margin+ 78,$Bottom_Margin);
	$pdf->line($Left_Margin+268, $TopOfColHeadings,$Left_Margin+268,$Bottom_Margin);
	$pdf->line($Left_Margin+348, $TopOfColHeadings,$Left_Margin+348,$Bottom_Margin);
	$pdf->line($Left_Margin+388, $TopOfColHeadings,$Left_Margin+388,$Bottom_Margin);
	$pdf->line($Left_Margin+418, $TopOfColHeadings,$Left_Margin+418,$Bottom_Margin);
	$pdf->line($Left_Margin+448, $TopOfColHeadings,$Left_Margin+448,$Bottom_Margin);

	$PageNumber++;
}


	
?>
