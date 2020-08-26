<?php
/*	Please note that addTextWrap prints a font-size-height further down than
	addText and other functions.*/

// $PageNumber is initialised in 0 by includes/PDFStarter.php.
$PageNumber ++;// Increments $PageNumber before printing.
if ($PageNumber>1) {// Inserts a page break if it is not the first page.
	$pdf->newPage();
}

// set font
$pdf->SetFont('times', 'I', 16);


// Prints company logo:
//$XPos = $Page_Width/2 - 140;
$pdf->addJpegFromFile($_SESSION['LogoFile'],$XPos+40,710,0,80);

// Prints 'Quotation' title:
//$pdf->addTextWrap(0, $Page_Height-$Top_Margin-18, $Page_Width, 18, _('Quotation'), 'center');

// Prints company info:
$XPos = $Page_Width/2;
$YPos = 760;
$FontSize = 12;

$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize-15*1, 200, $FontSize, $_SESSION['CompanyRecord']['coyname'], 'right');
//$YPos -= $FontSize;
$FontSize = 10;
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize-15*2, 200, $FontSize, $_SESSION['CompanyRecord']['regoffice1'], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize-15*3, 200, $FontSize, $_SESSION['CompanyRecord']['regoffice2'], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize-15*4, 200, $FontSize, _('Phone') . ': ' . $_SESSION['CompanyRecord']['telephone'], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize-15*5, 200, $FontSize,  _('Email') . ': ' .$_SESSION['CompanyRecord']['email'], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize-15*6, 200, $FontSize,  _('TIN') . ': ' .$_SESSION['CompanyRecord']['gstno'], 'right');


// Prints 'Delivery To' info:
$XPos = 46;
$LineXPos = $Page_Width/2;
$LineYPos = 660;
$pdf->SetFont('times', 'B', 16);
$FontSize=14;

$pdf->line($LineXPos-100, $LineYPos, $Left_Margin, $LineYPos);
$pdf->addText($LineXPos-65, $LineYPos+10,$FontSize, _('PROFOMA INVOICE'));
$pdf->line($LineXPos+265, $LineYPos, $Right_Margin+380, $LineYPos);

$pdf->SetFont('times', '', 16);
$FontSize=10;

// Prints 'Bi' info:
$YPos -= 110;
$pdf->addText($XPos-10, $YPos,$FontSize, _('Bill To').':');
$pdf->addText($Right_Maring-90, $YPos,$FontSize, _('Profoma No').':');
$pdf->SetFont('times', 'B', 16);
$FontSize=12;
$pdf->addText($XPos-10, $YPos-15,$FontSize, $myrow['name']);
$pdf->addText($Right_Maring-90, $YPos-15,$FontSize, $_GET['QuotationNo']);

$pdf->SetFont('times', 'I', 16);
// Draws a box with round corners around 'Delivery To' info:
$XPos = 50;
$YPos += 15;

// Draws a box with round corners around around 'Quotation For' info:
$YPos -= 70;

$FontSize=10;

// Prints the currency name:
$pdf->addTextWrap($XPos-120, $YPos-5, 200, $FontSize,  _('Est Date') . ': ' .ConvertSQLDate($myrow['quotedate']), 'right');

require_once('includes/CurrenciesArray.php');// To get the currency name from the currency code.
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $YPos-5, 200, $FontSize, _('All amounts stated in') . ' ' . $myrow['currcode'] . ' - ' . $CurrencyName[$myrow['currcode']], 'right');

$pdf->SetFont('times', 'B', 16);
// Prints table header:
$YPos -= 45;
$XPos = 40;
$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,100,$FontSize, _('Item Code'));
$LeftOvers = $pdf->addTextWrap(120,$YPos,235,$FontSize, _('Item Description'));
$LeftOvers = $pdf->addTextWrap(370,$YPos,85,$FontSize, _('Quantity'),'left');
$LeftOvers = $pdf->addTextWrap(400,$YPos,85,$FontSize,_('Price'),'right');
$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-90, $YPos, 90, $FontSize, _('Total'),'right');
$pdf->SetFont('times', '', 16);
// Draws a box with round corners around line items:
$pdf->RoundRectangle(
	$Left_Margin,// RoundRectangle $XPos.
	$YPos+$FontSize+5,// RoundRectangle $YPos.
	$Page_Width-$Left_Margin-$Right_Margin,// RoundRectangle $Width.
	$YPos+$FontSize-$Bottom_Margin-50,// RoundRectangle $Height.
	2,// RoundRectangle $RadiusX.
	0);// RoundRectangle $RadiusY.

// Line under table headings:
$LineYPos = $YPos - $FontSize -1;
$pdf->line($Page_Width-$Right_Margin, $LineYPos, $Left_Margin, $LineYPos);

$YPos -= $FontSize;// This is to use addTextWrap's $YPos instead of normal $YPos.

?>
