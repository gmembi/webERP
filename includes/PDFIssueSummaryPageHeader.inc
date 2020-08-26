<?php

/*	Please note that addTextWrap() prints a font-size-height further down than
	addText() and other functions. Use addText() instead of addTextWrap() to
	print left aligned elements.*/

if(!$FirstPage) { /* only initiate a new page if its not the first */
	$pdf->newPage();
}

$YPos = $Page_Height-$Top_Margin;

// Company Logo:
/*$pdf->addJpegFromFile($_SESSION['LogoFile'], $Left_Margin, $YPos-60, 0, 65);*/
$pdf->Image(
	$_SESSION['LogoFile'],// Name of the file containing the image.
	$Left_Margin,// Abscissa from left border to the upper-left corner (LTR).
	$Page_Height -($YPos-60) -(35),// Ordinate from top border to the upper-left corner (LTR).
	0,// Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
	65,// Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
	''// Image format. If not specified, the type is inferred from the file extension.
);// Public function Image() in ~/includes/tcpdf/tcpdf.php.

$FontSize =15;
if($InvOrCredit=='Invoice') {

	$pdf->addText($Page_Width/2 - 140, $YPos, $FontSize, _('COMBINED REQUSITION AND ISSUE NOTE') . ' ');
} else {
	$pdf->addText($Page_Width/2 - 60, $YPos, $FontSize, _('TAX CREDIT NOTE') . ' ');
}

// Prints page number:
$FontSize = 10;
$YPos -= $FontSize; //Downs one line height mesure (addText position is from left-bottom).
$pdf->addTextWrap($Page_Width-$Left_Margin-72, $YPos, 72, $FontSize, _('Page') . ' ' . $PageNumber, 'right');



/*Now print out company info at the top left */

$XPos = $Left_Margin +200;
$YPos = $Page_Height - $Top_Margin - 30;

$FontSize = 10;
$LineHeight = 13;
$LineCount = 0;

$pdf->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, $_SESSION['CompanyRecord']['coyname']);

$FontSize = 12;
$LineHeight = 14;

if($_SESSION['CompanyRecord']['regoffice1'] <> '') {
  $LineCount += 1;
  $pdf->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice1']);
}
if($_SESSION['CompanyRecord']['regoffice2'] <> '') {
  $LineCount += 1;
  $pdf->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice2']);
}
if(($_SESSION['CompanyRecord']['regoffice3'] <> '') OR ($_SESSION['CompanyRecord']['regoffice4'] <> '') OR ($_SESSION['CompanyRecord']['regoffice5'] <> '')) {
  $LineCount += 1;
  $pdf->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice3'] . '  ' . $_SESSION['CompanyRecord']['regoffice4'] . '  ' . $_SESSION['CompanyRecord']['regoffice5']);  // country in 6 not printed
}

$XPos = $Left_Margin +400;
$YPos = $Page_Height - $Top_Margin - 70;

$FontSize = 14;
$LineHeight = 13;
$LineCount = 0;

$pdf->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, _('Request No') . ': ' . $RequestNo, 'right');



$XPos = $Left_Margin;
$YPos = $Page_Height - $Top_Margin - 100 ;

$pdf->addText($Left_Margin, $YPos-8, $FontSize, _('Department') . ': ' . $Cost );
$pdf->addText($Left_Margin + 350, $YPos-8, $FontSize, _('Request Date') . ': ' . $RequestDate );
$BoxHeight = $Page_Height-200;

// Draws a rounded rectangle around line items:
$pdf->RoundRectangle(
	$Left_Margin,// RoundRectangle $XPos.
	$Bottom_Margin+$BoxHeight+10,// RoundRectangle $YPos.
	$Page_Width-$Right_Margin-$Left_Margin,// RoundRectangle $Width.
	$BoxHeight+10,// RoundRectangle $Height.
	10,// RoundRectangle $RadiusX.
	10);// RoundRectangle $RadiusY.

$YPos -= 35;
/*Set up headings */
$FontSize=10;
$LineHeight = 14;



$TopOfColHeadings = $YPos;

$pdf->addText($Left_Margin, $YPos, $FontSize, _('Item Code'));
$pdf->addText($Left_Margin+80, $YPos, $FontSize, _('Description'));
$pdf->addText($Left_Margin+350, $YPos, $FontSize, _('Qty Requested '));
$pdf->addText($Left_Margin+420, $YPos, $FontSize, _('Qty Issued '));
$pdf->addText($Left_Margin+490, $YPos, $FontSize, _('UOM'));


$YPos -= 12;

/*draw a line */
$pdf->line($XPos, $YPos-1,$Page_Width-$Right_Margin, $YPos-1);

$YPos -= ($line_height);

?>
