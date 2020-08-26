<?php


include ('includes/session.php');
include('includes/SQL_CommonFunctions.inc');

if (isset($_GET['IssueNo'])){
	$_POST['IssueNo'] = $_GET['IssueNo'];
}

if(isset($_GET['IssueNo'])) {
	$IssueNo = filter_number_format($_GET['IssueNo']);
} elseif(isset($_POST['IssueNo'])) {
	$IssueNo = filter_number_format($_POST['IssueNo']);
} else {
	$IssueNo = '';
}

if (isset($IssueNo) and $IssueNo!='') {
	$SQL= "SELECT dispatchid,
				despatchdate,
				authorised_by,
				fullfill_by,
				initiator,
				locationcost.costname
			FROM stockrequest INNER JOIN locationcost
			ON locationcost.costid=stockrequest.lcost
			WHERE stockrequest.dispatchid='" . $IssueNo . "'
			AND stockrequest.closed=1";

	$ErrMsg = _('An error occurred getting the header information about the receipt batch number') . ' ' . $_POST['BatchNo'];
	$DbgMsg = _('The SQL used to get the receipt header information that failed was');
	$Result=DB_query($SQL,$ErrMsg,$DbgMsg);

	if (DB_num_rows($Result) == 0){
		$Title = _('Create PDF Print-out For A Batch Of Receipts');
		include ('includes/header.php');
		prnMsg(_('The issue note') . ' ' . $_POST['IssueNo'] . ' ' . _('was not found in the database') . '. ' . _('Please try again selecting a different batch number'), 'warn');
		include('includes/footer.php');
		exit;
	}
	/* OK get the row of receipt batch header info from the BankTrans table */
	$myrow = DB_fetch_array($Result);
	$RequestID = $myrow['dispatchid'];
	$RequestDate = $myrow['despatchdate'];
	$Authorised = $myrow['authorised_by'];
	$Fullfill = $myrow['fullfill_by'];
	$Initiator=  $myrow['initiator'];
	$Cost = $myrow['costname'];
	

	$SQL = "SELECT stockid,
			stockmaster.description,
			quantity,
			qtydelivered,
			uom
		FROM stockrequestitems INNER JOIN stockmaster
		ON stockmaster.stockid=stockrequestitems.stockid
		WHERE stockrequestitems.dispatchid='" . $_POST['IssueNo'] . "'
		AND stockrequestitems.completed=1";

	$RequestRecs=DB_query($SQL,'','',false,false);
	if (DB_error_no()!=0){
		$Title = _('Create PDF Print-out For A Batch Of Receipts');
		include ('includes/header.php');
	   	prnMsg(_('An error occurred getting the customer receipts for batch number') . ' ' . $_POST['IssueNo'],'error');
		if ($debug==1){
	        	prnMsg(_('The SQL used to get the customer receipt information that failed was') . '<br />' . $SQL,'error');
	  	}
		include('includes/footer.php');
	  	exit;
	}


	$PaperSize='A4';
	include('includes/PDFStarter.php');

	/*PDFStarter.php has all the variables for page size and width set up depending on the users default preferences for paper size */

	$pdf->addInfo('Title',_('Internal Request'));
	$pdf->addInfo('Subject',_('Internal Request') . ' ' . $_POST['IssueNo']);
	$line_height=12;
	$PageNumber = 0;
	$TotalBanked = 0;
    
	include ('includes/PDFIssueSummaryPageHeader.inc');

	while ($myrow=DB_fetch_array($RequestRecs)){
        $FontSize = 10;
		
        $LeftOvers = $pdf->addTextWrap($Left_Margin+5,$YPos,71,$FontSize,$myrow['stockid']);// Print item code.
		$LeftOvers = $pdf->addTextWrap($Left_Margin+80,$YPos,186,$FontSize,$myrow['description']);// Print item short description.
		$LeftOvers = $pdf->addTextWrap($Left_Margin+270,$YPos,76,$FontSize,$myrow['quantity']);
		$LeftOvers = $pdf->addTextWrap($Left_Margin+350,$YPos,36,$FontSize,$myrow['qtydelivered']);
		$LeftOvers = $pdf->addTextWrap($Left_Margin+390,$YPos,26,$FontSize,$myrow2['uom'],'center');


		$YPos -= ($line_height-6);
		$TotalBanked -= $myrow['ovamount'];
		$Name = $myrow['name'];
        $FontSize = 10;
        $LeftOvers = $pdf->addTextWrap($Left_Margin+40,$YPos + 63,300,10, $Name , 'left');
		if ($YPos - (2 *$line_height) < $Bottom_Margin){
			/*Then set up a new page */
			include ('includes/PDFIssueSummaryPageHeader.inc');
		} /*end of new page header  */
	} /* end of while there are customer receipts in the batch to print */


	$YPos =$Bottom_Margin + 2;
	$LeftOvers = $pdf->addTextWrap($Left_Margin,$Bottom_Margin+20,300,$FontSize,_('TOTAL') . ' ' . $Currency . ' ' . _('BANKED'), 'left');
	$LeftOvers = $pdf->addTextWrap($Left_Margin+100,$Bottom_Margin+20,100,9,locale_number_format($TotalBanked,2), 'right');

	$FontSize = 6;

	$LeftOvers = $pdf->addTextWrap($Left_Margin+60, $Bottom_Margin+5, 144, $FontSize, _('Issued by :'). ' ' .$_SESSION['UsersRealName']);
	$pdf->line($Left_Margin+150, $Bottom_Margin+5,$Page_Width-$Right_Margin, $Bottom_Margin+5);
	$pdf->OutputD($_SESSION['DatabaseName'] . '_BankingSummary_' . date('Y-m-d').'.pdf');
	$pdf->__destruct();
}

?>