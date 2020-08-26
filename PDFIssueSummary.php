<?php
/*	PrintCustTransPortrait.php */
/*  Print Invoices or Credit Notes (Portrait Mode) */

include('includes/session.php');
$Title = _('Print issue Note Summary (Portrait Mode)');
$ViewTopic = 'ARReports';
$BookMark = 'PrintInvoicesCredits';

if(isset($_GET['IssueNo'])) {
	$IssueNo = filter_number_format($_GET['IssueNo']);
} elseif(isset($_POST['IssueNo'])) {
	$IssueNo = filter_number_format($_POST['IssueNo']);
} else {
	$IssueNo = '';
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
	OR filter_number_format($_POST['ToTransNo']) < $IssueNo) {

	$_POST['ToTransNo'] = $IssueNo;
}

$FirstTrans = $IssueNo; /* Need to start a new page only on subsequent transactions */

if(isset($PrintPDF)
	and $PrintPDF!=''
	and isset($IssueNo)
	and isset($InvOrCredit)
	and $IssueNo!='') {

	include ('includes/PDFStarter.php');

	if($InvOrCredit=='Invoice') {
		$pdf->addInfo('Title',_('Issue Note') . ' ' . $IssueNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
		$pdf->addInfo('Subject',_('Invoices from') . ' ' . $IssueNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
	} else {
		$pdf->addInfo('Title',_('Sales Credit Note') );
		$pdf->addInfo('Subject',_('Credit Notes from') . ' ' . $IssueNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
	}

	$FirstPage = true;
	$line_height=16;

	//Keep a record of the user's language
	$UserLanguage = $_SESSION['Language'];

	while($IssueNo <= filter_number_format($_POST['ToTransNo'])) {

	/*retrieve the invoice details from the database to print
	notice that salesorder record must be present to print the invoice purging of sales orders will
	nobble the invoice reprints */

	
// gather the issue note data

		if($InvOrCredit=='Invoice') {
			$sql = "SELECT stockrequest.dispatchid,
							stockrequest.despatchdate,
							stockrequest.lcost,
							stockrequest.authorised_by,
							stockrequest.fullfill_by,
							stockrequest.initiator,
							locationcost.costname
						FROM stockrequest INNER JOIN locationcost
						ON stockrequest.lcost=locationcost.costid
						WHERE stockrequest.dispatchid='" . $IssueNo . "'";

		} 

		$result=DB_query($sql,'','',false,false);

		if(DB_error_no()!=0) {

			$Title = _('Issue Print Error Report');
			include ('includes/header.php');

			prnMsg( _('There was a problem retrieving the issue note details for note number') . ' ' . $InvoiceToPrint . ' ' . _('from the database') . '. ' . _('To print an invoice, the sales order record, the customer transaction record and the branch record for the customer must not have been purged') . '. ' . _('To print a credit note only requires the customer, transaction, salesman and branch records be available'),'error');
			if($debug==1) {
				prnMsg (_('The SQL used to get this information that failed was') . '<br />' . $sql,'error');
			}
			include ('includes/footer.php');
			exit;
		}
		if(DB_num_rows($result)==1) {
			$myrow = DB_fetch_array($result);

			$RequestNo = $myrow['dispatchid'];
			$RequestDate = $myrow['despatchdate'];
			$Authoriser = $myrow['authorised_by'];
			$Initiator = $myrow['initiator'];
			$Dispenser = $myrow['fullfill_by'];
			$Cost = $myrow['costname'];

			//Change the language to the customer's language
			$_SESSION['Language'] = $myrow['language_id'];
			include('includes/LanguageSetup.php');

			if($InvOrCredit == 'Invoice') {
				$sql = "SELECT stockrequestitems.stockid,
						stockmaster.description,
						stockrequestitems.quantity,
						stockrequestitems.qtydelivered,
						stockrequestitems.uom,
						stockrequestitems.decimalplaces
					FROM stockrequestitems INNER JOIN stockmaster
					ON stockrequestitems.stockid = stockmaster.stockid
					WHERE stockrequestitems.dispatchid='" . $IssueNo . "'";
			} 
			

		$result=DB_query($sql);
		if(DB_error_no()!=0) {
			$Title = _('Transaction Print Error Report');
			include ('includes/header.php');
			echo '<br />' . _('There was a problem retrieving the invoice or credit note stock movement details for invoice number') . ' ' . $IssueNo . ' ' . _('from the database');
			if($debug==1) {
				echo '<br />' . _('The SQL used to get this information that failed was') . '<br />' . $sql;
			}
			include('includes/footer.php');
			exit;
		}


		if(DB_num_rows($result)>0) {

			$FontSize = 10;
			$PageNumber = 1;

			include('includes/PDFIssueSummaryPageHeader.inc');
			$FirstPage = False;

			while($myrow2=DB_fetch_array($result)) {
				
				

				$LeftOvers = $pdf->addTextWrap($Left_Margin+5,$YPos,71,$FontSize,$myrow2['stockid']);// Print item code.
				//Get translation if it exists

				$LeftOvers = $pdf->addTextWrap($Left_Margin+80,$YPos,220,$FontSize,$myrow2['description']);// Print item short description.

				$lines=1;
				while($LeftOvers!='') {
					$LeftOvers = $pdf->addTextWrap($Left_Margin+80,$YPos-(10*$lines),220,$FontSize,$LeftOvers);
					$lines++;
				}

				$LeftOvers = $pdf->addTextWrap($Left_Margin+250,$YPos,120,$FontSize,$myrow2['quantity'],'right');
				$LeftOvers = $pdf->addTextWrap($Left_Margin+400,$YPos,36,$FontSize,$myrow2['qtydelivered'],'right');
				$LeftOvers = $pdf->addTextWrap($Left_Margin+490,$YPos,26,$FontSize,$myrow2['uom'],'center');
				

				$YPos -= ($FontSize*$lines);


			} /*end while there are line items to print out*/

		} /*end if there are stock movements to show on the invoice or credit note*/

		$YPos -= $line_height;

		/* check to see enough space left to print the 4 lines for the totals/footer */
		if(($YPos-$Bottom_Margin)<(2*$line_height)) {
			PrintLinesToBottom ();
			include ('includes/PDFIssueSummaryPageHeader.inc');
		}
		/*Print a column vertical line with enough space for the footer*/
		/*draw the vertical column lines to 4 lines shy of the bottom
		to leave space for invoice footer info ie totals etc*/
		$pdf->line($Left_Margin+78, $TopOfColHeadings,$Left_Margin+78,$Bottom_Margin+(4*$line_height));

		

		/*Print a column vertical line */
		$pdf->line($Left_Margin+348, $TopOfColHeadings,$Left_Margin+348,$Bottom_Margin+(4*$line_height));

		
		/*Print a column vertical line */
		$pdf->line($Left_Margin+418, $TopOfColHeadings,$Left_Margin+418,$Bottom_Margin+(4*$line_height));

		$pdf->line($Left_Margin+490, $TopOfColHeadings,$Left_Margin+490,$Bottom_Margin+(4*$line_height));

		/*Rule off at bottom of the vertical lines */
		$pdf->line($Left_Margin, $Bottom_Margin+(4*$line_height),$Page_Width-$Right_Margin,$Bottom_Margin+(4*$line_height));

		/*Now print out the footer and totals */

		

		$YPos = $Bottom_Margin+(3*$line_height);
		
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 40, $YPos+5, 72, $FontSize, _('Requested By'));
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 30, $YPos-15, 72, $FontSize, $Initiator);
		
		
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 240, $YPos+5, 72, $FontSize, _('Authorised By'));
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 230, $YPos-15, 72, $FontSize, $Authoriser);
		
		
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 440, $YPos+5, 72, $FontSize, _('Dispense By'));
		$LeftOvers = $pdf->addTextWrap($Left_Margin + 430, $YPos-15, 72, $FontSize, $Dispenser);
	    
		} /* end of check to see that there was an invoice record to print */

		$IssueNo++;
	} /* end loop to print invoices */

	/* Put the transaction number back as would have been incremented by one after last pass */
	$IssueNo--;

	if(isset($_GET['Email'])) { //email the invoice to address supplied
		include ('includes/htmlMimeMail.php');
		$FileName = $_SESSION['reports_dir'] . '/' . $_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . $_GET['IssueNo'] . '.pdf';
		$pdf->Output($FileName,'F');
		$mail = new htmlMimeMail();

		$Attachment = $mail->getFile($FileName);
		$mail->setText(_('Please find attached') . ' ' . $InvOrCredit . ' ' . $_GET['IssueNo'] );
		$mail->SetSubject($InvOrCredit . ' ' . $_GET['IssueNo']);
		$mail->addAttachment($Attachment, $FileName, 'application/pdf');
		if($_SESSION['SmtpSetting'] == 0) {
			$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>');
			$result = $mail->send(array($_GET['Email']));
		} else {
			$result = SendmailBySmtp($mail,array($_GET['Email']));
		}

		unlink($FileName); //delete the temporary file

		$Title = _('Emailing') . ' ' .$InvOrCredit . ' ' . _('Number') . ' ' . $IssueNo;
		include('includes/header.php');
		echo '<p>' . $InvOrCredit . ' ' . _('number') . ' ' . $IssueNo . ' ' . _('has been emailed to') . ' ' . $_GET['Email'];
		include('includes/footer.php');
		exit;

	} else { //its not an email just print the invoice to PDF
		$pdf->OutputD($_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . $IssueNo . '.pdf');

	}
	$pdf->__destruct();
	//Change the language back to the user's language
	$_SESSION['Language'] = $UserLanguage;
	include('includes/LanguageSetup.php');

} 



?>
