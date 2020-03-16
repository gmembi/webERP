<?php

/* $Id$*/

include('includes/session.php');
$Title = _('Inventory Issue');
include('includes/header.php');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text"><img alt="" src="', $RootPath, '/css/', $Theme,
	'/images/transactions.png" title="', // Icon image.
	$Title, '" /> ', // Icon title.
	$Title, '</p>';// Page title.

if(isset($_GET['WO'])) {
	$SelectedWO = $_GET['WO'];
} elseif(isset($_POST['WO'])) {
	$SelectedWO = $_POST['WO'];
} else {
	unset($SelectedWO);
        
}      

if (isset($_GET['loccode'])){
	$LocCode = $_GET['loccode'];
} else {
	$LocCode=$_SESSION['UserStockLocation'];
}

foreach ($_POST as $key=>$value) {
	if(substr($key, 0, 9)=='OutputQty' OR substr($key, 0, 7)=='RecdQty') {
		$_POST[$key] = filter_number_format($value);
	}
}

// check for new or modify condition
if(isset($SelectedWO) AND$SelectedWO!='') {
	// modify
	$_POST['WO'] = (int)$SelectedWO;
	$EditingExisting = true;
} else {
	// new
	$_POST['WO'] = GetNextTransNo(14,$db);
//	$sql = "INSERT INTO workorders (wo,
//							 loccode,
//							 requiredby,
//							 startdate)
//			 VALUES ('" . $_POST['WO'] . "',
//					'" . $LocCode . "',
//					'" . $ReqDate . "',
//					'" . $StartDate. "')";
//	$InsWOResult = DB_query($sql,$db);
}


if (isset($_GET['NewItem'])){
	$NewItem = $_GET['NewItem'];
}

if (!isset($_POST['StockLocation'])){
	if (isset($LocCode)){
		$_POST['StockLocation']=$LocCode;
	} elseif (isset($_SESSION['UserStockLocation'])){
		$_POST['StockLocation']=$_SESSION['UserStockLocation'];
	}
}


if (isset($_POST['Search'])){

	If ($_POST['Keywords'] AND $_POST['StockCode']) {
		prnMsg(_('Stock description keywords have been used in preference to the Stock code extract entered'),'warn');
	}
	If (mb_strlen($_POST['Keywords'])>0) {
			//insert wildcard characters in spaces
		$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		if ($_POST['StockCat']=='All'){
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
					FROM stockmaster,
					stockcategory
					WHERE stockmaster.categoryid=stockcategory.categoryid
					AND stockmaster.description " . LIKE . " '$SearchString'
					AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
					FROM stockmaster, stockcategory
					WHERE  stockmaster.categoryid=stockcategory.categoryid
					AND stockmaster.discontinued=0
					AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} elseif (mb_strlen($_POST['StockCode'])>0){

		$_POST['StockCode'] = mb_strtoupper($_POST['StockCode']);
		$SearchString = '%' . $_POST['StockCode'] . '%';

		if ($_POST['StockCat']=='All'){
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
					FROM stockmaster, stockcategory
					WHERE stockmaster.categoryid=stockcategory.categoryid
					AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
					AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
					FROM stockmaster, stockcategory
					WHERE stockmaster.categoryid=stockcategory.categoryid
					AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
					AND stockmaster.discontinued=0
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}
	} else {
		if ($_POST['StockCat']=='All'){
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
					FROM stockmaster, stockcategory
					WHERE  stockmaster.categoryid=stockcategory.categoryid
					AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
					FROM stockmaster, stockcategory
					WHERE stockmaster.categoryid=stockcategory.categoryid
					AND stockmaster.discontinued=0
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		  }
	}

	$SQL = $SQL . ' LIMIT ' . $_SESSION['DisplayRecordsMax'];

	$ErrMsg = _('There is a problem selecting the part records to display because');
	$DbgMsg = _('The SQL used to get the part selection was');
	$SearchResult = DB_query($SQL,$db,$ErrMsg, $DbgMsg);

	if (DB_num_rows($SearchResult)==0 ){
		prnMsg (_('There are no products available meeting the criteria specified'),'info');

		if ($debug==1){
			prnMsg(_('The SQL statement used was') . ':<br />' . $SQL,'info');
		}
	}
	if (DB_num_rows($SearchResult)==1){
		$myrow=DB_fetch_array($SearchResult);
		$NewItem = $myrow['stockid'];
		DB_data_seek($SearchResult,0);
	}

} //end of if search

if (isset($NewItem) AND isset($_POST['WO'])){

	$InputError=false;
//        $PeriodNo = GetPeriod ($_POST['issueddate'], $db);
        
	$CheckItemResult = DB_query("SELECT mbflag,
											eoq,
											controlled
										FROM stockmaster
										WHERE stockid='" . $NewItem . "'",
								$db);
	if (DB_num_rows($CheckItemResult)==1){
		$CheckItemRow = DB_fetch_array($CheckItemResult);
		if ($CheckItemRow['controlled']==1 AND $_SESSION['DefineControlledOnWOEntry']==1){ //need to add serial nos or batches to determine quantity
			$EOQ = 0;
		} else {
			if (!isset($ReqQty)) {
				$ReqQty=$CheckItemRow['eoq'];
			}
			$EOQ = $ReqQty;
		}
//		if ($CheckItemRow['mbflag']!='M'){
//			prnMsg(_('The item selected cannot be added to a work order because it is not a manufactured item'),'warn');
//			$InputError=true;
//		}
	} else {
		prnMsg(_('The item selected cannot be found in the database'),'error');
		$InputError = true;
	}
	$CheckItemResult = DB_query("SELECT stockid
									FROM stockmoves
									WHERE stockid='" . $NewItem . "'
									AND transno='" .$_POST['WO'] . "'",
									$db);
	if (DB_num_rows($CheckItemResult)==1){
		prnMsg(_('This item is already on the stock issue and cannot be added again'),'warn');
		$InputError=true;
	}


	if ($InputError==false){
//		$CostResult = DB_query("SELECT SUM((materialcost+labourcost+overheadcost)*bom.quantity) AS cost
//									FROM stockmaster INNER JOIN bom
//									ON stockmaster.stockid=bom.component
//									WHERE bom.parent='" . $NewItem . "'
//									AND bom.loccode='" . $_POST['StockLocation'] . "'",
//							 $db);
//		$CostRow = DB_fetch_array($CostResult);
//		if (is_null($CostRow['cost']) OR $CostRow['cost']==0){
//				$Cost =0;
//				prnMsg(_('The cost of this item as accumulated from the sum of the component costs is nil. This could be because there is no bill of material set up ... you may wish to double check this'),'warn');
//		} else {
//				$Cost = $CostRow['cost'];
//		}
//		if (!isset($EOQ)){
//			$EOQ=1;
//		}

		$Result = DB_Txn_Begin($db);
                
               
		// insert parent item info
		$sql = "INSERT INTO stockmoves (
				stockid,
				type,
				transno)
			VALUES (
				'" . $NewItem . "',
				14,
				'" . $_POST['WO'] . "')";
                
		$ErrMsg = _('The stock issue item could not be added');
		$result = DB_query($sql,$db,$ErrMsg);

		//Recursively insert real component requirements - see includes/SQL_CommonFunctions.in for function WoRealRequirements
//		WoRealRequirements($db, $_POST['WO'], $_POST['StockLocation'], $NewItem);

		$result = DB_Txn_Commit($db);

		unset($NewItem);
	} //end if there were no input errors
} //adding a new item to the work order


if (isset($_POST['submit']) or isset($_POST['Search'])) { //The update button has been clicked

	echo '<div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') .'">' . _('Enter a new stock issue') . '</a>';
//	echo '<br /><a href="' . $rootpath . '/SelectWorkOrder.php">' . _('Select an existing work order') . '</a>';
//	echo '<br /><a href="'. $rootpath . '/WorkOrderCosting.php?WO=' .  $_REQUEST['WO'] . '">' . _('Go to Costing'). '</a></div>';

	$Input_Error = false; //hope for the best
	 for ($i=1;$i<=$_POST['NumberOfOutputs'];$i++){
	   	if (!is_numeric($_POST['OutputQty'.$i])){
		   	prnMsg(_('The quantity entered must be numeric'),'error');
			$Input_Error = true;
		} 
//                elseif ($_POST['OutputQty'.$i]<=0){
//			prnMsg(_('The quantity entered must be a positive number greater than zero'),'error');
//			$Input_Error = true;
//		}
                
        if ($_POST['LGroup']=='') {
		prnMsg( _('You must enter a location group for this transaction'),'error');
		$Input_Error = true;		
	
        } elseif ($_POST['LSection']=='') {
		prnMsg( _('You must enter a location section for this transaction'),'error');
		$Input_Error = true;		
	
        } elseif ($_POST['LCost']=='') {
		prnMsg( _('You must enter a location cost for this transaction'),'error');
		$Input_Error = true;		
	
        } elseif ($_POST['source']=='') {
		prnMsg( _('You must enter a source of funds for this transaction'),'error');
		$Input_Error = true;		
	    }
			
            if ($_SESSION['ProhibitNegativeStock']==1){
											//don't need to check labour or dummy items
		$SQL = "SELECT stockmaster.description,
					   		locstock.quantity,
					   		stockmaster.mbflag
		 			FROM locstock
		 			INNER JOIN stockmaster
					ON stockmaster.stockid=locstock.stockid
				WHERE locstock.stockid ='" . $_POST['OutputItem'.$i] . "'
				AND locstock.loccode ='" . $_POST['StockLocation'] . "'";
		$CheckNegResult = DB_query($SQL);
		$CheckNegRow = DB_fetch_array($CheckNegResult);
		if ($CheckNegRow['quantity']<$_POST['OutputQty'.$i]){
			$Input_Error = true;
			prnMsg( _('Invoicing the selected order would result in negative stock. The system parameters are set to prohibit negative stocks from occurring. This invoice cannot be created until the stock on hand is corrected.'),'error',$_POST['OutputItem'.$i] . ' ' . $CheckNegRow['description'] . ' - ' . _('Negative Stock Prohibited'));
		} 
                    

	}
	 }
	 if (!Is_Date($_POST['IssueDate'])){
		prnMsg(_('The issue date entered is in an invalid format'),'error');
		$Input_Error = true;
	 }
         
       
	if ($Input_Error == false) {

		$SQL_IssueDate = FormatDateForSQL($_POST['IssueDate']);
		$QtyRecd=0;

        $PeriodNo = GetPeriod ($_POST['IssueDate']);

		for ($i=1;$i<=$_POST['NumberOfOutputs'];$i++){
           
                        
//			if ($_POST['OutputQty'.$i]>0){
				/* can only change location cost if QtyRecd=0 */
				$CostResult = DB_query("SELECT (materialcost+labourcost+overheadcost) AS cost
											FROM stockmaster 
											WHERE stockid='" . $_POST['OutputItem'.$i] . "'",
									 $db);
				$CostRow = DB_fetch_array($CostResult);
				if (is_null($CostRow['cost'])){
					$Cost =0;
					prnMsg(_('The cost of this item as accumulated from the sum of the component costs is nil. This could be because there is no bill of material set up ... you may wish to double check this'),'warn');
				} else {
					$Cost = $CostRow['cost'];
				}
                                
                        $SQL="SELECT locstock.quantity
			             FROM locstock
			                  WHERE locstock.stockid='" . $_POST['OutputItem'.$i] . "'
			                  AND loccode= '" . $_POST['StockLocation'] . "'";
		                          $Result = DB_query($SQL, $db);
		                               if (DB_num_rows($Result)==1){
			                           $LocQtyRow = DB_fetch_row($Result);
			                           $QtyOnHandPrior = $LocQtyRow[0];
		                                   } else {
			                           // There must actually be some error this should never happen
			                           $QtyOnHandPrior = 0;
		                                   }
                                                   
				$sql[] = "UPDATE stockmoves SET trandate =  '". $SQL_IssueDate . "', qty =  '". -$_POST['OutputQty' . $i] . "', prd =  '". $PeriodNo . "', loccode = '". $_POST['StockLocation'] ."', newqoh =  '".(($QtyOnHandPrior - $_POST['OutputQty' . $i]))."', groupid =  '". $_POST['LGroup'] . "', sectionid =  '". $_POST['LSection'] . "', costid =  '". $_POST['LCost'] . "', sourceref =  '".$_POST['source']."', reference =  '".$_POST['Narrative']."'				 
								  WHERE transno='" . $_POST['WO'] . "'
								  AND stockid='" . $_POST['OutputItem'.$i] . "'";
                                
                                
                                $sql[] = "UPDATE locstock SET quantity = quantity - " . $_POST['OutputQty' . $i] . "
				WHERE stockid='" . $_POST['OutputItem'.$i] . "'
				AND loccode='" . $_POST['StockLocation'] . "'";
                                
                                
        if ($_SESSION['CompanyRecord']['gllink_stock']==1){

			$StockGLCodes = GetStockGLCode($_POST['OutputItem'.$i],$db);
            $PeriodNo = GetPeriod ($_POST['IssueDate']);
			$SQL = "INSERT INTO gltrans (type,
							typeno,
							trandate,
							periodno,
							account,
							amount,
							narrative,
                            locgroup,
							locsection,
							loccost,
                            source)
					VALUES (14,
						" .$_POST['WO'] . ",
						'" . FormatDateForSQL($_POST['IssueDate']) . "',
						" . $PeriodNo . ",
						" .  $StockGLCodes['adjglact'] . ",
						" . $Cost * ($_POST['OutputQty' . $i]) . ",
						'" . $_POST['OutputItem'.$i] . " x " . $_POST['OutputQty' . $i] . " @ " . $Cost . " " . $_POST['Narrative'] . "', 
                        '".$_POST['LGroup']."',
                        '".$_POST['LSection']."',
                        '".$_POST['LCost']."',
                        '".$_POST['source']."')";
                                
                                
	

			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The general ledger transaction entries could not be added because');
			$DbgMsg = _('The following SQL to insert the GL entries was used');
			$Result = DB_query($SQL,$db, $ErrMsg, $DbgMsg, true);

			$SQL = "INSERT INTO gltrans (type,
							typeno,
							trandate,
							periodno,
							account,
							amount,
							narrative)
					VALUES (14,
						" .$_POST['WO'] . ",
						'" . FormatDateForSQL($_POST['IssueDate']) . "',
						" . $PeriodNo . ",
						" .  $StockGLCodes['stockact'] . ",
						" . $Cost * -$_POST['OutputQty' . $i] . ",
						'" . $_POST['OutputItem'.$i] . " x " . $_POST['OutputQty' . $i] . " @ " . $Cost . " " . $_POST['Narrative'] . "')";

			$Errmsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The general ledger transaction entries could not be added because');
			$DbgMsg = _('The following SQL to insert the GL entries was used');
			$Result = DB_query($SQL,$db, $ErrMsg, $DbgMsg,true);
		}
		
//  			} 
		}

		//run the SQL from either of the above possibilites
		$ErrMsg = _('The stock issue could not be added/updated');
		foreach ($sql as $sql_stmt){
		//	echo '<br />' . $sql_stmt;
			$result = DB_query($sql_stmt,$db,$ErrMsg);

		}
		if (!isset($_POST['Search'])) {
			prnMsg(_('The stock issue has been updated'),'success');
		}

		for ($i=1;$i<=$_POST['NumberOfOutputs'];$i++){
		  		 unset($_POST['OutputItem'.$i]);
				 unset($_POST['OutputQty'.$i]);
		}
	}
} 
elseif (isset($_POST['delete'])) {
//the link to delete a selected record was clicked instead of the submit button
$CancelDelete=false; //always assume the best

	// can't delete it there are open work issues
	$HasTransResult = DB_query("SELECT transno
									FROM stockmoves
								WHERE (stockmoves.type= 26 OR stockmoves.type=28)
								AND reference " . LIKE  . " '%" . $_POST['WO'] . "%'");
	if(DB_num_rows($HasTransResult)>0) {
		prnMsg(_('This work order cannot be deleted because it has issues or receipts related to it'),'error');
		$CancelDelete=true;
	}

	if($CancelDelete==false) { //ie all tests proved ok to delete
		DB_Txn_Begin();
		$ErrMsg = _('The work order could not be deleted');
		$DbgMsg = _('The SQL used to delete the work order was');
		//delete the worequirements
		$SQL = "DELETE FROM worequirements WHERE wo='" . $_POST['WO'] . "'";
		$result = DB_query($SQL,$ErrMsg,$DbgMsg,true);
		//delete the items on the work order
		$SQL = "DELETE FROM woitems WHERE wo='" . $_POST['WO'] . "'";
		$result = DB_query($SQL,$ErrMsg,$DbgMsg,true);
		//delete the controlled items defined in wip
		$SQL="DELETE FROM woserialnos WHERE wo='" . $_POST['WO'] . "'";
		$ErrMsg=_('The work order serial numbers could not be deleted');
		$result = DB_query($SQL,$ErrMsg,$DbgMsg,true);
		// delete the actual work order
		$SQL="DELETE FROM workorders WHERE wo='" . $_POST['WO'] . "'";
		$ErrMsg=_('The work order could not be deleted');
		$result = DB_query($SQL,$ErrMsg,$DbgMsg,true);

		DB_Txn_Commit();
		prnMsg(_('The work order has been cancelled'),'success');


		echo '<p><a href="' . $RootPath . '/SelectWorkOrder.php">' . _('Select an existing outstanding work order') . '</a></p>';
		unset($_POST['WO']);
		for ($i=1;$i<=$_POST['NumberOfOutputs'];$i++) {
			unset($_POST['OutputItem'.$i]);
			unset($_POST['OutputQty'.$i]);
			unset($_POST['QtyRecd'.$i]);
			unset($_POST['NetLotSNRef'.$i]);
			unset($_POST['HasWOSerialNos'.$i]);
			unset($_POST['WOComments'.$i]);
		}
		include('includes/footer.php');
		exit;
	}
	
}

if(isset($_GET['Delete'])) {

    remove_from_issue($db, $_POST['WO'], $_GET['StockID']);
    //header('Location: '. $_SERVER['PHP_SELF'] . '?WO=' . $_GET['WO']);
}

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" name="form1">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<br /><table class="selection">';

$sql="SELECT stockmoves.stockid,
			 stockmaster.description,
			 stockmaster.decimalplaces
		FROM stockmoves
		INNER JOIN stockmaster
			ON stockmoves.stockid=stockmaster.stockid
		WHERE stockmoves.transno='" . $_POST['WO'] . "'";

        $WOResult = DB_query($sql,$db);

	$NumberOfOutputs=DB_num_rows($WOResult);
	$i=1;
	while ($WOItem=DB_fetch_array($WOResult)){
				$_POST['OutputItem' . $i]=$WOItem['stockid'];
				$_POST['OutputItemDesc'.$i]=$WOItem['description'];
				$_POST['DecimalPlaces' . $i] = $WOItem['decimalplaces'];
		  		$i++;
	}


echo '<input type="hidden" name="WO" value="' .$_POST['WO'] . '" />';
echo '<tr><td class="label">' . _('Stock Issue Reference') . ':</td><td>' . $_POST['WO'] . '</td></tr>';
echo '<tr><td class="label">' . _('Issued From Stock At Location') .':</td>
	<td><select name="StockLocation">';
$LocResult = DB_query("SELECT loccode,locationname FROM locations",$db);
while ($LocRow = DB_fetch_array($LocResult)){
	if ($_POST['StockLocation']==$LocRow['loccode']){
		echo '<option selected="True" value="' . $LocRow['loccode'] .'">' . $LocRow['locationname'] . '</option>';
	} else {
		echo '<option value="' . $LocRow['loccode'] .'">' . $LocRow['locationname'] . '</option>';
	}
}
echo '</select></td></tr>';
 echo '<tr><td class="label">' . _('Select Location Group') . ':</td>
		<td><select name="LGroup" onChange="return ReloadForm(UpdateLoc)">';

	$SQL = "SELECT groupid,
                             groupname
			FROM locationgroup
			ORDER BY groupid";

	$result=DB_query($SQL,$db);
	if (DB_num_rows($result)==0){
		echo '</select></td></tr>';
		//prnMsg(_('No Location groups have been set up yet') . ' - ' . _('payments cannot be analysed against GL accounts until the Location are set up'),'error');
	} else {
		echo '<option value=""></option>';
		while ($myrow=DB_fetch_array($result)){
			if (isset($_POST['LGroup']) AND ($_POST['LGroup']==$myrow['groupid'])){
				echo '<option selected="True" value="' . $myrow['groupid'] . '">' . $myrow['groupid'] . ' - ' . $myrow['groupname'] . '</option>';
			} else {
				echo '<option value="' . $myrow['groupid'] . '">' . $myrow['groupid'] . ' - ' . $myrow['groupname'] . '</option>';
			}
		}
		echo '</select><button type="submit" name="UpdateLoc">' . _('Select') . '</button></td></tr>';
	}

	if (isset($_POST['LGroup']) AND $_POST['LGroup']!='') {
		$SQL = "SELECT sectionid,
					sectionname
			FROM locationsection
			WHERE locgroupname='".$_POST['LGroup']."'
			ORDER BY sectionid";
	} 
        else {
		$SQL = "SELECT sectionid,
					sectionname
			FROM locationsection
			ORDER BY sectionid";
	}


	echo '<tr><td class="label">' . _('Select Location Section') . ':</td>
		<td><select name="LSection" onChange="return ReloadForm(UpdateSec)">';

	$result=DB_query($SQL,$db);
	if (DB_num_rows($result)==0){
		echo '</select></td></tr>';
		prnMsg(_('No General ledger accounts have been set up yet') . ' - ' . _('payments cannot be analysed against GL accounts until the GL accounts are set up'),'error');
	} else {
		echo '<option value=""></option>';
		while ($myrow=DB_fetch_array($result)){
			if (isset($_POST['LSection']) AND $_POST['LSection']==$myrow['sectionid']){
				echo '<option selected="True" value="' . $myrow['sectionid'] . '">' . $myrow['sectionid'] . ' - ' . $myrow['sectionname'] . '</option>';
			} else {
				echo '<option value="' . $myrow['sectionid'] . '">' . $myrow['sectionid'] . ' - ' . $myrow['sectionname'] . '</option>';
			}
		}
		echo '</select><button type="submit" name="UpdateSec">' . _('Select') . '</button></td></tr>';
	}
        
        if (isset($_POST['LSection']) AND $_POST['LSection']!='') {
		$SQL = "SELECT costid,
					costname
			FROM locationcost
			WHERE locsectionname='".$_POST['LSection']."'
			ORDER BY costid";
	} 
        else {
		$SQL = "SELECT costid,
					costname
			FROM locationcost
			ORDER BY costid";
	}

	echo '<tr><td class="label">' . _('Select Location Cost') . ':</td>
		<td><select name="LCost">';

	$result=DB_query($SQL,$db);
	if (DB_num_rows($result)==0){
		echo '</select></td></tr>';
		prnMsg(_('No General ledger accounts have been set up yet') . ' - ' . _('payments cannot be analysed against GL accounts until the GL accounts are set up'),'error');
	} else {
		echo '<option value=""></option>';
		while ($myrow=DB_fetch_array($result)){
			if (isset($_POST['LCost']) AND $_POST['LCost']==$myrow['costid']){
				echo '<option selected="True" value="' . $myrow['costid'] . '">' . $myrow['costid'] . ' - ' . $myrow['costname'] . '</option>';
			} else {
				echo '<option value="' . $myrow['costid'] . '">' . $myrow['costid'] . ' - ' . $myrow['costname'] . '</option>';
			}
		}
		//echo '</select><button type="submit" name="UpdateCost">' . _('Select') . '</button></td></tr>';
	}
        //End select location

echo '<tr><td class="label">' . _('Select Source') . ':</td>
	<td><select name="source">';

$SQL = 'SELECT sourceref,
				sourcedescription
		FROM source
		ORDER BY sourceref';

$result=DB_query($SQL,$db);
if (DB_num_rows($result)==0){
   echo '</select></td></tr>';
   prnMsg(_('No Source of funds have been set up yet') . ' - ' . _('payments cannot be analysed against a tag until the tag is set up'),'error');
} else {
	echo '<option selected value=></option>';
	while ($myrow=DB_fetch_array($result)){
	    if ($_POST['source']==$myrow["sourceref"]){
		echo '<OPTION selected value=' . $myrow['sourceref'] . '>' .$myrow['sourcedescription'];
	    } else {
		echo '<OPTION value=' . $myrow['sourceref'] . '>' .$myrow['sourcedescription'];
	    }
	}
	echo '</select></td></tr>';
}
	// End select source
if (!isset($_POST['IssueDate'])){
	$_POST['IssueDate'] = Date($_SESSION['DefaultDateFormat']);
}

echo '<tr>
		<td class="label">' . _('Issue Date') . ':</td>
		<td><input type="text" name="IssueDate" size="12" maxlength="12" value="' . $_POST['IssueDate'] .'" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" /></td>
	</tr>';

echo '<TR><TD>'. _('Comments On Why').':</TD>
	<TD><input type=text name="Narrative" size=32 maxlength=30 value="' . $_POST['Narrative']. '"></TD></TR>';

echo '</table>
		<br /><table class="selection">';
echo '<tr><th>' . _('Output Item') . '</th>
		  <th>' . _('Qty To Issued') . '</th>
		  <th>' . _('Delete') . '</th>
		  </tr>';
$j=0;

if (isset($NumberOfOutputs)){
	for ($i=1;$i<=$NumberOfOutputs;$i++){
		if ($j==1) {
			echo '<tr class="OddTableRows">';
			$j=0;
		} else {
			echo '<tr class="EvenTableRows">';
			$j++;
		}
		echo '<td><input type="hidden" name="OutputItem' . $i . '" value="' . $_POST['OutputItem' .$i] . '" />' .
			$_POST['OutputItem' . $i] . ' - ' . $_POST['OutputItemDesc' .$i] . '</td>';
		
		echo'<td><input type="text" name="OutputQty' . $i . '" value="' . locale_number_format($_POST['OutputQty' . $i], $_POST['DecimalPlaces' . $i]) . '" size="10" maxlength="10" /></td>';
		
		echo '<td>
			
                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Delete=Yes&StockID=' . $_POST['OutputItem' . $i] . '&WO='.$_POST['WO'].'" onclick="return confirm(\'' . _('Are You Sure?') . '\');">' . _('Delete') . '</a></td>';
		echo '<td>';
//		wikiLink('WorkOrder', $_POST['WO'] . $_POST['OutputItem' .$i]);
		echo '</td>';
		echo '</tr>';
		
	}
	echo '<input type="hidden" name="NumberOfOutputs" value="' . ($i -1).'" />';
}
echo '</table>';

echo '<br /><div class="centre"><button type="submit" name="submit">' . _('Update') . '</button></div>';

//echo '<br /><div class="centre"><button type="submit" name="delete" onclick="return confirm(\'' . _('Are You Sure?') . '\');">' . _('Cancel This Work Order') . '</button>';

echo '</div><br />';

$SQL="SELECT categoryid,
			categorydescription
		FROM stockcategory
		WHERE stocktype='F' OR stocktype='D'
		ORDER BY categorydescription";
	$result1 = DB_query($SQL,$db);

echo '<table class="selection"><tr><td>' . _('Select a stock category') . ':<select name="StockCat">';

if (!isset($_POST['StockCat'])){
	echo '<option selected="True" value="All">' . _('All') . '</option>';
	$_POST['StockCat'] ='All';
} else {
	echo '<option value="All">' . _('All') . '</option>';
}

while ($myrow1 = DB_fetch_array($result1)) {

	if ($_POST['StockCat']==$myrow1['categoryid']){
		echo '<option selected="True" value=' . $myrow1['categoryid'] . '>' . $myrow1['categorydescription'] . '</option>';
	} else {
		echo '<option value='. $myrow1['categoryid'] . '>' . $myrow1['categorydescription'] . '</option>';
	}
}

if (!isset($_POST['Keywords'])) {
    $_POST['Keywords']='';
}

if (!isset($_POST['StockCode'])) {
    $_POST['StockCode']='';
}

echo '</select>
    <td>' . _('Enter text extracts in the') . ' <b>' . _('description') . '</b>:</td>
    <td><input type="text" name="Keywords" size="20" maxlength="25" value="' . $_POST['Keywords'] . '" /></td></tr>
    <tr><td>&nbsp;</td>
	<td><font size="3"><b>' . _('OR') . ' </b></font>' . _('Enter extract of the') . ' <b>' . _('Stock Code') . '</b>:</td>
	<td><input type="text" name="StockCode" size="15" maxlength="18" value="' . $_POST['StockCode'] . '" /></td>
	</tr>
	</table>
	<br /><div class="centre"><button type="submit" name="Search">' . _('Search Now') . '</button></div>';

if (isset($SearchResult)) {

	if (DB_num_rows($SearchResult)>1){

		echo '<br /><table cellpadding="2" class="selection">';
        $TableHeader = '<tr>
                            <th>' . _('Code') . '</th>
				   			<th>' . _('Description') . '</th>
                            <th>' . _('Units') . '</th>
                            <th>' . _('Image') . '</th>
                            <th>' . _('Quantity') . '</th></tr>';
		echo $TableHeader;
		$j = 1;
		$k=0; //row colour counter
		$ItemCodes = array();
		for ($i=1;$i<=$NumberOfOutputs;$i++){
			$ItemCodes[] =$_POST['OutputItem'.$i];
		}

		while ($myrow=DB_fetch_array($SearchResult)) {

			if(!in_array($myrow['stockid'],$ItemCodes)) {

				$SupportedImgExt = array('png','jpg','jpeg');
				$imagefile = reset((glob($_SESSION['part_pics_dir'] . '/' . $myrow['stockid'] . '.{' . implode(",", $SupportedImgExt) . '}', GLOB_BRACE)));
				if(extension_loaded('gd') && function_exists('gd_info') && file_exists ($imagefile) ) {
					$ImageSource = '<img src="GetStockImage.php?automake=1&amp;textcolor=FFFFFF&amp;bgcolor=CCCCCC'.
						'&amp;StockID='.urlencode($myrow['stockid']).
						'&amp;text='.
						'&amp;width=64'.
						'&amp;height=64'.
						'" alt="" />';
				} else if(file_exists ($imagefile)) {
					$ImageSource = '<img src="' . $imagefile . '" height="64" width="64" />';
				} else {
					$ImageSource = _('No Image');
				}

				if($myrow['controlled']==1 AND $_SESSION['DefineControlledOnWOEntry']==1) { //need to add serial nos or batches to determine quantity

				printf('<tr class="striped_row">
						<td><font size="1">%s</font></td>
						<td><font size="1">%s</font></td>
						<td><font size="1">%s</font></td>
						<td>%s</td>
						<td><font size="1"><a href="%s">'
						. _('Add to Stock Issue') . '</a></font></td>
						<td><input type="checkbox" value="%s" name="Check_%s" /></td>
						</tr>',
						$myrow['stockid'],
						$myrow['description'],
						$myrow['units'],
						$ImageSource,
						htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?WO=' . $_POST['WO'] . '&NewItem=' . urlencode($myrow['stockid']).'&Line='.$i,
						$myrow['stockid'],
						$j);
				} else {
						if(!isset($myrow['quantity'])) {
							$myrow['quantity'] = 0;
						}
						printf('<tr class="striped_row">
						<td><font size="1">%s</font></td>
						<td><font size="1">%s</font></td>
						<td><font size="1">%s</font></td>
						<td>%s</td>
						<td><font size="1"><a href="%s">'
						. _('Add to Stock Issue') . '</a></font></td>
						</tr>',
						$myrow['stockid'],
						$myrow['description'],
						$myrow['units'],
						$ImageSource,
						htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?WO=' . $_POST['WO'] . '&NewItem=' . urlencode($myrow['stockid']).'&Line='.$i,
						$j,
						$myrow['stockid'],
						$j);
				}


				$j++;
			} //end if not already on work order
		}//end of while loop
	} //end if more than 1 row to show
	echo '</table>';

}#end if SearchResults to show




echo '</form>';
include('includes/footer.php');
?>