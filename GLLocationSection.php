<?php
/* $Revision: 1.23 $ */
/* $Id$*/
include('includes/session.php');
$Title = _('Location Section');
$ViewTopic = 'GeneralLedger';
$BookMark = 'LocationSection';
include('includes/header.php');

include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $title.'</p>';

//function CheckForRecursiveGroup ($ParentGroupName, $GroupName, $db) {
//
///* returns true ie 1 if the group contains the parent group as a child group
//ie the parent group results in a recursive group structure otherwise false ie 0 */
//
//	$ErrMsg = _('An error occurred in retrieving the account groups of the parent account group during the check for recursion');
//	$DbgMsg = _('The SQL that was used to retrieve the account groups of the parent account group and that failed in the process was');
//
//	do {
//		$sql = "SELECT parentgroupname
//				FROM accountgroups
//				WHERE groupname='" . $GroupName ."'";
//
//		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);
//		$myrow = DB_fetch_row($result);
//		if ($ParentGroupName == $myrow[0]){
//			return true;
//		}
//		$GroupName = $myrow[0];
//	} while ($myrow[0]!='');
//	return false;
//} //end of function CheckForRecursiveGroupName

// If $Errors is set, then unset it.
if (isset($Errors)) {
	unset($Errors);
}

$Errors = array();

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test

	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	$i=1;

	$sql="SELECT count(sectionid)
			FROM locationsection
			WHERE sectionid='".$_POST['SectionID']."'";

	$DbgMsg = _('The SQL that was used to retrieve the information was');
	$ErrMsg = _('Could not check whether the group exists because');

	$result=DB_query($sql, $db,$ErrMsg,$DbgMsg);
	$myrow=DB_fetch_row($result);

	if ($myrow[0]!=0 and $_POST['SelectedLocationSection']=='') {
		$InputError = 1;
		prnMsg( _('The locatin cost ID already exists in the database'),'error');
		$Errors[$i] = 'SectionID';
		$i++;
	}
        if (mb_strlen($_POST['SectionID'])==0){
		$InputError = 1;
		prnMsg( _('The locatin cost ID must be at least one character long'),'error');
		$Errors[$i] = 'SectionID';
		$i++;
	}
        if (!ctype_digit($_POST['SectionID'])) {
		$InputError = 1;
		prnMsg( _('The locatin cost ID must be an integer'),'error');
		$Errors[$i] = 'SectionID';
		$i++;
	}
	if (ContainsIllegalCharacters($_POST['SectionName'])) {
		$InputError = 1;
		prnMsg( _('The locatin cost name cannot contain the character') . " '&' " . _('or the character') ."' '",'error');
		$Errors[$i] = 'SectionName';
		$i++;
	}
	
//	if ($_POST['ParentGroupName'] !=''){
//		if (CheckForRecursiveGroup($_POST['GroupName'],$_POST['ParentGroupName'],$db)) {
//			$InputError =1;
//			prnMsg(_('The parent account group selected appears to result in a recursive account structure - select an alternative parent account group or make this group a top level account group'),'error');
//			$Errors[$i] = 'ParentGroupName';
//			$i++;
//		} else {
//			$sql = "SELECT pandl,
//						sequenceintb,
//						sectioninaccounts
//					FROM accountgroups
//					WHERE groupname='" . $_POST['ParentGroupName'] . "'";
//
//			$DbgMsg = _('The SQL that was used to retrieve the information was');
//			$ErrMsg = _('Could not check whether the group is recursive because');
//
//			$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);
//
//			$ParentGroupRow = DB_fetch_array($result);
//			$_POST['SequenceInTB'] = $ParentGroupRow['sequenceintb'];
//			$_POST['PandL'] = $ParentGroupRow['pandl'];
//			$_POST['SectionInAccounts']= $ParentGroupRow['sectioninaccounts'];
//			prnMsg(_('Since this account group is a child group, the sequence in the trial balance, the section in the accounts and whether or not the account group appears in the balance sheet or profit and loss account are all properties inherited from the parent account group. Any changes made to these fields will have no effect.'),'warn');
//		}
//	}
	
//	if (!ctype_digit($_POST['SequenceInTB'])) {
//		$InputError = 1;
//		prnMsg( _('The sequence in the trial balance must be an integer'),'error');
//		$Errors[$i] = 'SequenceInTB';
//		$i++;
//	}
//	if (!ctype_digit($_POST['SequenceInTB']) or $_POST['SequenceInTB'] > 10000) {
//		$InputError = 1;
//		prnMsg( _('The sequence in the TB must be numeric and less than') . ' 10,000','error');
//		$Errors[$i] = 'SequenceInTB';
//		$i++;
//	}


	if ($_POST['SelectedLocationSection']!='' AND $InputError !=1) {

		/*SelectedAccountGroup could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/

		$sql = "UPDATE locationsection SET sectionid='" . $_POST['SectionID'] . "',
										sectionname='" . $_POST['SectionName'] . "',
										locgroupname='" . $_POST['GroupName'] . "'
									WHERE sectionid = '" . $_POST['SelectedLocationSection'] . "'";
		$ErrMsg = _('An error occurred in updating the account group');
		$DbgMsg = _('The SQL that was used to update the account group was');

		$msg = _('Record Updated');
	} elseif ($InputError !=1) {

	/*Selected group is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new account group form */

		$sql = "INSERT INTO locationsection ( sectionid,
											sectionname,
											locgroupname
										) VALUES (
											'" . $_POST['SectionID'] . "',
											'" . $_POST['SectionName'] . "',
											'" . $_POST['GroupName'] . "')";
		$ErrMsg = _('An error occurred in inserting the account group');
		$DbgMsg = _('The SQL that was used to insert the account group was');
		$msg = _('Record inserted');
	}

	if ($InputError!=1){
		//run the SQL from either of the above possibilites
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);
		prnMsg($msg,'success');
		unset ($_POST['SelectedLocationSection']);
		unset ($_POST['SectionID']);
//		unset ($_POST['SequenceInTB']);
	}
} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'LocationSection'

	
//		$sql = "SELECT COUNT(sectionname) AS section FROM locationsection WHERE sectionname = '" . $_GET['SelectedLocationSection'] . "'";
//		$ErrMsg = _('An error occurred in retrieving the parent group information');
//		$DbgMsg = _('The SQL that was used to retrieve the information was');
//		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);
//		$myrow = DB_fetch_array($result);
//		if ($myrow['section']>0) {
//			prnMsg( _('Cannot delete this account group because it is a parent account group of other account group(s)'),'warn');
//			echo '<br />' . _('There are') . ' ' . $myrow['groupnames'] . ' ' . _('account groups that have this group as its/there parent account group');
//		} else {
			$sql="DELETE FROM locationsection WHERE sectionid='" . $_GET['SelectedLocationSection'] . "'";
			$ErrMsg = _('An error occurred in deleting the account group');
			$DbgMsg = _('The SQL that was used to delete the account group was');
			$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);
			prnMsg( $_GET['SelectedLocationSection'] . ' ' . _('section has been deleted') . '!','success');
//		}

	} //end if account group used in GL accounts



if (!isset($_GET['SelectedLocationSection']) and !isset($_POST['SelectedLocationSection'])) {

/* An account group could be posted when one has been edited and is being updated or GOT when selected for modification
 SelectedAccountGroup will exist because it was sent with the page in a GET .
 If its the first time the page has been displayed with no parameters
then none of the above are true and the list of account groups will be displayed with
links to delete or edit each. These will call the same page again and allow update/input
or deletion of the records*/

	$sql = "SELECT sectionid,
					sectionname,
					locgroupname
			FROM locationsection
			LEFT JOIN locationgroup ON groupid = locgroupname
			ORDER BY sectionid";

	$DbgMsg = _('The sql that was used to retrieve the account group information was ');
	$ErrMsg = _('Could not get account groups because');
    $result = DB_query($sql, $db,$ErrMsg,$DbgMsg);
    
    echo '<p class="page_title_text"><img alt="" class="noprint" src="', $RootPath, '/css/', $Theme,
    '/images/maintenance.png" title="', // Icon image.
    _('Location Section'), '" /> ', // Icon title.
    _('Location Section'), '</p>';// Page title.

	echo '<br /><table class="selection">
			<tr>
				<th>' . _('Section ID') . '</th>
				<th>' . _('Section Name') . '</th>
				<th>' . _('Group Name') . '</th>
			</tr>';

	$k=0; //row colour counter
	while ($myrow = DB_fetch_array($result)) {

		if ($k==1){
			echo '<tr class="EvenTableRows">';
			$k=0;
		} else {
			echo '<tr class="OddTableRows">';
			$k++;
		}

//		switch ($myrow['pandl']) {
//		case -1:
//			$PandLText=_('Yes');
//			break;
//		case 1:
//			$PandLText=_('Yes');
//			break;
//		case 0:
//			$PandLText=_('No');
//			break;
//		} //end of switch statement

		echo '<td>' . $myrow['sectionid'] . '</td>
			<td>' . htmlentities($myrow['sectionname'], ENT_QUOTES,'UTF-8') . '</td>
			<td>' . $myrow['locgroupname'] . '</td>';
		echo '<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLocationSection=' . htmlentities($myrow['sectionid'], ENT_QUOTES,'UTF-8') . '">' . _('Edit') . '</a></td>';
		echo '<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLocationSection=' . htmlentities($myrow['sectionid'], ENT_QUOTES,'UTF-8') . '&amp;delete=1" onclick="return confirm(\'' . _('Are you sure you wish to delete this account group?') . '\');">' . _('Delete') .'</a></td></tr>';

	} //END WHILE LIST LOOP
	echo '</table>';
} //end of ifs and buts!


if (!isset($_GET['delete'])) {

	echo '<form method="post" id="LocationSection" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($_GET['SelectedLocationSection'])) {
		//editing an existing account group

		$sql = "SELECT sectionid,
						sectionname,
						locgroupname
				FROM locationsection
				WHERE sectionid='" . $_GET['SelectedLocationSection'] ."'";

		$ErrMsg = _('An error occurred in retrieving the account group information');
		$DbgMsg = _('The SQL that was used to retrieve the account group and that failed in the process was');
		$result = DB_query($sql, $db,$ErrMsg,$DbgMsg);
		if (DB_num_rows($result) == 0) {
			prnMsg( _('The account group name does not exist in the database'),'error');
			include('includes/footer.inc');
			exit;
		}
		$myrow = DB_fetch_array($result);

		$_POST['SectionID'] = $myrow['sectionid'];
		$_POST['SectionName']  = $myrow['sectionname'];
		$_POST['GroupName'] = $myrow['locgroupname'];

		echo '<table class="selection">';
		echo '<tr>
				<th colspan="2" class="header">' . _('Edit Location Section Details') . '</th>
			</tr>';
		echo '<input type="hidden" name="SelectedLocationSection" value="' . $_GET['SelectedLocationSection'] . '" />';
		echo '<input type="hidden" name="SectionID" value="' . $_POST['SectionID'] . '" />';
                 echo '<input type="hidden" name="GroupName" value="' . $_POST['GroupName'] . '" />';

		echo '<tr>
				<td>' . _('Location ID') . ':' . '</td>
				<td>' . $_POST['SectionID'] . '</td>
			</tr>';

	} else { //end of if $_POST['SelectedAccountGroup'] only do the else when a new record is being entered

		if (!isset($_POST['SelectedLocationSection'])){
			$_POST['SelectedLocationSection']='';
		}
		if (!isset($_POST['SectionID'])){
			$_POST['SectionID']='';
		}
		if (!isset($_POST['SectionName'])){
			$_POST['SectionName']='';
		}
		if (!isset($_POST['GroupName'])){
			$_POST['GroupName']='';
		}
		

		echo '<br /><table class="selection">';
		echo '<input  type="hidden" name="SelectedLocationSection" value="' . $_POST['SelectedLocationSection'] . '" />';
		echo '<tr>
				<th colspan="2" class="header">' . _('New Location Section Details') . '</th>
			</tr>';
		echo '<tr>
				<td>' . _('Location Section ID') . ':' . '</td>
				<td><input tabindex="1" ' . (in_array('SectionID',$Errors) ?  'class="inputerror"' : '' ) .' type="text" name="SectionID" size="50" maxlength="50" value="' . $_POST['SectionID'] . '" /></td>
			</tr>';
                echo '<tr>
				<td>' . _('Location Section Name') . ':' . '</td>
				<td><input tabindex="1" ' . (in_array('SectionName',$Errors) ?  'class="inputerror"' : '' ) .' type="text" name="SectionName" size="50" maxlength="50" value="' . $_POST['SectionName'] . '" /></td>
			</tr>';
	}
	

	echo '<tr>
			<td>' . _('Location Group') . ':' . '</td>
			<td><select tabindex="3" ' . (in_array('GroupName',$Errors) ?  'class="selecterror"' : '' ) . ' name="GroupName">';

	$sql = "SELECT groupid, groupname FROM locationgroup ORDER BY groupid";
	$grpresult = DB_query($sql, $db,$ErrMsg,$DbgMsg);
	while( $grprow = DB_fetch_array($grpresult) ) {
		if ($_POST['GroupName']==$grprow['groupid']) {
			echo '<option selected="selected" value="'.$grprow['groupid'].'">'.$grprow['groupname'].' ('.$grprow['groupid'].')</option>';
		} else {
			echo '<option value="'.$grprow['groupid'].'">'.$grprow['groupname'].' ('.$grprow['groupid'].')</option>';
		}
	}
	echo '</select>';
	echo '</td></tr>';



	echo '<tr>
			<td colspan="2"><div class="centre"><button tabindex="6" type="submit" name="submit">' . _('Enter Information') . '</button></div></td>
		</tr>';

	echo '</table><br />';

	if (isset($_POST['SelectedLocationSection']) or isset($_GET['SelectedLocationSection'])) {
		echo '<div style="text-align: right"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . _('Review Account Groups') . '</a></div>';
	}

	echo '<script  type="text/javascript">defaultControl(document.forms[0].SectionID);</script>';

	echo '</form>';

} //end if record deleted no point displaying form to add record
include('includes/footer.php');
?>