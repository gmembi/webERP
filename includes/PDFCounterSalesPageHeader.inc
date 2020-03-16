<?php

/*	Please note that addTextWrap() prints a font-size-height further down than
	addText() and other functions. Use addText() instead of addTextWrap() to
	print left aligned elements.*/

if(!$FirstPage) { /* only initiate a new page if its not the first */
	$pdf->newPage();
}

$YPos = $Page_Height - $Top_Margin - 40;

$XPos = $Page_Width/2 - 20;

// Company Logo:
$pdf->addJpegFromFile($_SESSION['LogoFile'],$Left_Margin,$YPos,0,40);

$FontSize=5;
$pdf->addText($Right_Margin - 90, $YPos + 40, $FontSize, _('TIN') . ' # :'  . $_SESSION['CompanyRecord']['gstno']);
$pdf->addText($Right_Margin - 90, $YPos + 33, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($Right_Margin - 90, $YPos + 26, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($Right_Margin - 90, $YPos + 19, $FontSize, _('Phone') . ':' . $_SESSION['CompanyRecord']['telephone']);
$pdf->addText($Right_Margin - 90, $YPos + 12, $FontSize, _('Email') . ':' . $_SESSION['CompanyRecord']['email']);

$FontSize=14;

$XPos = $Page_Width/2 - 30;
if($InvOrCredit=='Invoice') {

	$pdf->addText($XPos, $YPos - 10,$FontSize, _('RECEIPT'));
} else {
	$pdf->addText($XPos, $YPos - 10,$FontSize, _('RECEIPT'));
}

// Prints page number:
$FontSize=12;
$pdf->addText($Left_Margin, $YPos - 35,$FontSize, _('Receipt') . ' # ' . $BatchNo);
$pdf->addText($XPos+ 30, $YPos - 35,$FontSize, _('Date') .' : ' . ConvertSQLDate($myrow['trandate']));

$pdf->addText($Left_Margin, $YPos - 55,$FontSize, _('Name').' : ');

$XPos = $Page_Width-$Right_Margin-50;
$YPos -=65;
$FontSize=8;

$YPos -=$line_height;
$XPos = $Left_Margin;

/*draw a square grid for entering line items */
$pdf->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);
$pdf->line($Page_Width-$Right_Margin, $YPos,$Page_Width-$Right_Margin, $Bottom_Margin + 30);
$pdf->line($Page_Width-$Right_Margin - 70, $YPos,$Page_Width-$Right_Margin - 70, $Bottom_Margin + 30);
$pdf->line($Page_Width-$Right_Margin, $Bottom_Margin + 30,$XPos, $Bottom_Margin + 30);
$pdf->line($XPos, $Bottom_Margin + 30,$XPos, $YPos);

$YPos -= $line_height;
/*Set up headings */
$FontSize=12;


$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,120,$FontSize,_('Item Description'), 'centre');
$LeftOvers = $pdf->addTextWrap($Left_Margin+100,$YPos,150,$FontSize,_('Amount'), 'centre');
$YPos-=$line_height;

$YPos -= 12;

/*draw a line */
$pdf->line($XPos, $YPos + 15,$Page_Width-$Right_Margin, $YPos + 15);
$pdf->line($Left_Margin+35, $YPos + 42,$Page_Width-$Right_Margin, $YPos + 42);
$FontSize=6;

$YPos -= ($line_height-12);

?>
