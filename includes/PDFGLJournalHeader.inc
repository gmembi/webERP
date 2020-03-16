<?php
/*PDF page header for price list report */
if ($PageNumber>1){
	$pdf->newPage();
}

$pdf->addJpegFromFile($_SESSION['LogoFile'] ,$FormDesign->logo->x,$Page_Height-$FormDesign->logo->y,$FormDesign->logo->width,$FormDesign->logo->height);

$LeftOvers = $pdf->addText($FormDesign->CompanyName->x,$Page_Height-$FormDesign->CompanyName->y,$FormDesign->CompanyName->FontSize,$_SESSION['CompanyRecord']['coyname']);
$LeftOvers = $pdf->addText($FormDesign->GRNNumber->x,$Page_Height-$FormDesign->GRNNumber->y,$FormDesign->GRNNumber->FontSize, _('Journal number ').' ' . $JournalNo );
$LeftOvers = $pdf->addText($FormDesign->OrderNumber->x,$Page_Height-$FormDesign->OrderNumber->y,$FormDesign->OrderNumber->FontSize, _('Journal Date ').' ' . ConvertSQLDate($JournalDate)  );
$LeftOvers = $pdf->addText($FormDesign->PrintDate->x,$Page_Height-$FormDesign->PrintDate->y,$FormDesign->PrintDate->FontSize, _('Printed').': ' . Date($_SESSION['DefaultDateFormat']) . '   '. _('Page'). ' ' . $PageNumber);

/*Draw a rectangle to put the headings in     */
$pdf->Rectangle($FormDesign->HeaderRectangle->x, $Page_Height - $FormDesign->HeaderRectangle->y, $FormDesign->HeaderRectangle->width,$FormDesign->HeaderRectangle->height);

/*set up the headings */
$LeftOvers = $pdf->addText($FormDesign->Headings->Column1->x,$Page_Height - $FormDesign->Headings->Column1->y, $FormDesign->Headings->Column1->FontSize,  _('Account Code'));
$LeftOvers = $pdf->addText($FormDesign->Headings->Column2->x,$Page_Height - $FormDesign->Headings->Column2->y, $FormDesign->Headings->Column2->FontSize,  _('Account Description'));
$LeftOvers = $pdf->addText($FormDesign->Headings->Column3->x,$Page_Height - $FormDesign->Headings->Column3->y, $FormDesign->Headings->Column3->FontSize,  _('Narrative'));
$LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column4->x,$Page_Height - $FormDesign->Headings->Column4->y, $FormDesign->Headings->Column4->Length, $FormDesign->Headings->Column4->FontSize,  _('Amount'), 'right');
$LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column5->x,$Page_Height - $FormDesign->Headings->Column5->y, $FormDesign->Headings->Column5->Length, $FormDesign->Headings->Column5->FontSize,  _('Cost Details'), 'right');
$LeftOvers = $pdf->addTextWrap($FormDesign->Headings->Column6->x,$Page_Height - $FormDesign->Headings->Column6->y, $FormDesign->Headings->Column6->Length, $FormDesign->Headings->Column6->FontSize,  _('Source of Fund'), 'right');

/*Draw a rectangle to put the data in     */
$pdf->Rectangle($FormDesign->DataRectangle->x, $Page_Height - $FormDesign->DataRectangle->y, $FormDesign->DataRectangle->width,$FormDesign->DataRectangle->height);

$pdf->Line($FormDesign->LineAboveFooter->startx, $Page_Height - $FormDesign->LineAboveFooter->starty, $FormDesign->LineAboveFooter->endx,$Page_Height - $FormDesign->LineAboveFooter->endy);
$sql="SELECT realname FROM www_users WHERE userid='".$_SESSION['UserID']."'";
$payerresult=DB_query($sql);
$myrow1=DB_fetch_array($payerresult);
$LeftOvers = $pdf->addText($FormDesign->PrintedBy->x,$Page_Height-$FormDesign->PrintedBy->y,$FormDesign->PrintedBy->FontSize, _('Printed by').': ' . $myrow1['realname']);
$LeftOvers = $pdf->addText($FormDesign->SignedFor->x,$Page_Height-$FormDesign->SignedFor->y,$FormDesign->SignedFor->FontSize, _('Signed for .....................................................'));
$pdf->Line($FormDesign->Column1->startx, $Page_Height - $FormDesign->Column1->starty, $FormDesign->Column1->endx,$Page_Height - $FormDesign->Column1->endy);
$pdf->Line($FormDesign->Column3->startx, $Page_Height - $FormDesign->Column3->starty, $FormDesign->Column3->endx,$Page_Height - $FormDesign->Column3->endy);
$pdf->Line($FormDesign->Column4->startx, $Page_Height - $FormDesign->Column4->starty, $FormDesign->Column4->endx,$Page_Height - $FormDesign->Column4->endy);
$pdf->Line($FormDesign->Column5->startx, $Page_Height - $FormDesign->Column5->starty, $FormDesign->Column5->endx,$Page_Height - $FormDesign->Column5->endy);
$pdf->Line($FormDesign->Column6->startx, $Page_Height - $FormDesign->Column6->starty, $FormDesign->Column6->endx,$Page_Height - $FormDesign->Column6->endy);
$PageNumber++;
?>