<?php

/* $Id$*/
if(!isset($IsIncluded)) {// Runs normally if this script is NOT included in another.
	include('includes/session.php');
}
$Title = _('Location Profit and Loss');
$Title2 = _('Statement of Comprehensive Income');// Name as IAS.
$ViewTopic= 'GeneralLedger';
$BookMark = 'ProfitAndLoss';

include_once('includes/SQL_CommonFunctions.inc');
include_once('includes/AccountSectionsDef.php'); // This loads the $Sections variable
if(!isset($IsIncluded)) {// Runs normally if this script is NOT included in another.
	include('includes/header.php');
}
echo '<p class="page_title_text"><img alt="" src="', $RootPath, '/css/', $Theme, '/images/printer.png" title="', // Icon image.
		$Title2, '" /> ', // Icon title.
		$Title, '</p>';// Page title.
	
		echo '<br />
				<table class="selection">
				<tr>
				<td><img src="'.$rootpath.'/css/'.$theme.'/images/reports.png" title="' . _('Invoice') . '" alt="" /></td>
					<td><a target="_blank" href= GLLocationGroupProfit_Loss.php >'. _('Profit and Loss by Location Group') .'</a></td>
				</tr>';
		echo '<tr>
				<td><img src="'.$rootpath.'/css/'.$theme.'/images/reports.png" title="' . _('Invoice') . '" alt="" /></td>
					<td><a target="_blank" href= GLLocationSectionProfit_Loss.php >'. _('Profit and Loss by Location Section') .'</a></td>
			</tr>';
		echo '<tr>
				<td><img src="'.$rootpath.'/css/'.$theme.'/images/reports.png" title="' . _('Invoice') . '" alt="" /></td>
					<td><a target="_blank" href= GLLocationCostProfit_Loss.php >'. _('Profit and Loss by Location Cost') .'</a></td>
			</tr>';
		
		echo'</table>';

	
include('includes/footer.php');

?>