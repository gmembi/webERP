<?php
/* $Revision: 1.7 $ */
/* $Id$*/
include('includes/session.php');
$Title = _('Location Groups');
$ViewTopic = 'GeneralLedger';
$BookMark = 'LocationGroup';
include('includes/header.php');

echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $title.'</p>';

// SOME TEST TO ENSURE THAT AT LEAST INCOME AND COST OF SALES ARE THERE
//	$sql= "SELECT groupid,groupname FROM locationgroup WHERE groupid=1";
//	$result = DB_query($sql,$db);
//
//	if( DB_num_rows($result) == 0 ) {
//		$sql = "INSERT INTO accountsection (sectionid,
//											sectionname
//										) VALUES (
//											1,
//											'Income')";
//		$result = DB_query($sql,$db);
//	}
//
//	$sql= "SELECT sectionid FROM accountsection WHERE sectionid=2";
//	$result = DB_query($sql,$db);
//
//	if( DB_num_rows($result) == 0 ) {
//		$sql = "INSERT INTO accountsection (sectionid,
//											sectionname
//										) VALUES (
//											2,
//											'Cost Of Sales')";
//		$result = DB_query($sql,$db);
//	}
//// DONE WITH MINIMUM TESTS
//
//
//if (isset($Errors)) {
//	unset($Errors);
//}
//
//$Errors = array();

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test

	$InputError = 0;
	$i=1;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (isset($_POST['GroupID'])) {
		$sql="SELECT groupid
					FROM locationgroup
					WHERE groupid='".$_POST['GroupID']."'";
		$result=DB_query($sql, $db);

		if ((DB_num_rows($result)!=0 and !isset($_POST['SelectedGroupID']))) {
			$InputError = 1;
			prnMsg( _('The location group already exists in the database'),'error');
			$Errors[$i] = 'GroupID';
			$i++;
		}
	}
	if (ContainsIllegalCharacters($_POST['GroupName'])) {
		$InputError = 1;
		prnMsg( _('The location group name cannot contain any illegal characters') ,'error');
		$Errors[$i] = 'GroupName';
		$i++;
	}
	if (mb_strlen($_POST['GroupName'])==0) {
		$InputError = 1;
		prnMsg( _('The location group name cannot contain any illegal characters') ,'error');
		$Errors[$i] = 'GroupName';
		$i++;
	}
	if (isset($_POST['GroupID']) and (!is_numeric($_POST['GroupID']))) {
		$InputError = 1;
		prnMsg( _('The location group id must be an integer'),'error');
		$Errors[$i] = 'GroupID';
		$i++;
	}
	if (isset($_POST['GroupID']) and mb_strpos($_POST['GroupID'],".")>0) {
		$InputError = 1;
		prnMsg( _('The location group id must be an integer'),'error');
		$Errors[$i] = 'GroupID';
		$i++;
	}

	if (isset($_POST['SelectedGroupID']) and $_POST['SelectedGroupID']!='' AND $InputError !=1) {

		/*SelectedSectionID could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/

		$sql = "UPDATE locationgroup SET groupname='" . $_POST['GroupName'] . "'
				WHERE groupid = " . $_POST['SelectedGroupID'];

		$msg = _('Record Updated');
	} elseif ($InputError !=1) {

	/*SelectedSectionID is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new account section form */

		$sql = "INSERT INTO locationgroup (groupid,
											groupname
										) VALUES (
											'" . $_POST['GroupID'] . "',
											'" . $_POST['GroupName'] ."')";
		$msg = _('Record inserted');
	}

	if ($InputError!=1){
		//run the SQL from either of the above possibilites
		$result = DB_query($sql,$db);
		prnMsg($msg,'success');
		unset ($_POST['SelectedGroupID']);
		unset ($_POST['GroupID']);
		unset ($_POST['GroupName']);
	}

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'accountgroups'
	$sql= "SELECT COUNT(groupname) AS sections FROM locationsection WHERE groupname='" . $_GET['SelectedGroupID'] . "'";
	$result = DB_query($sql,$db);
	$myrow = DB_fetch_array($result);
	if ($myrow['sections']>0) {
		prnMsg( _('Cannot delete this account section because general ledger location section have been created using this group'),'warn');
		echo '<br />' . _('There are') . ' ' . $myrow['sections'] . ' ' . _('general ledger accounts groups that refer to this account section') . '</font>';

	} else {
		//Fetch section name
		$sql = "SELECT groupname FROM locationgroup WHERE groupid='".$_GET['SelectedGroupID'] . "'";
		$result = DB_query($sql,$db);
		$myrow = DB_fetch_array($result);
		$GroupName = $myrow['groupname'];

		$sql="DELETE FROM locationgroup WHERE groupid='" . $_GET['SelectedGroupID'] . "'";
		$result = DB_query($sql,$db);
		prnMsg( $GroupName . ' ' . _('location group has been deleted') . '!','success');

	} //end if account group used in GL accounts
	unset ($_GET['SelectedGroupID']);
	unset($_GET['delete']);
	unset ($_POST['SelectedGroupID']);
	unset ($_POST['GroupID']);
	unset ($_POST['GroupName']);
}

if (!isset($_GET['SelectedGroupID']) and !isset($_POST['SelectedGroupID'])) {

/* An account section could be posted when one has been edited and is being updated
  or GOT when selected for modification
  SelectedSectionID will exist because it was sent with the page in a GET .
  If its the first time the page has been displayed with no parameters
  then none of the above are true and the list of account groups will be displayed with
  links to delete or edit each. These will call the same page again and allow update/input
  or deletion of the records*/

	$sql = "SELECT groupid,
			groupname
		FROM locationgroup
		ORDER BY groupid";

	$ErrMsg = _('Could not get location group because');
    $result = DB_query($sql,$db,$ErrMsg);
    
    echo '<p class="page_title_text"><img alt="" class="noprint" src="', $RootPath, '/css/', $Theme,
		'/images/maintenance.png" title="', // Icon image.
		_('Location Group'), '" /> ', // Icon title.
		_('Location Group'), '</p>';// Page title.



	echo '<br /><table name="SectionList" class="selection">
			<tr>
				<th>' . _('Group Number') . '</th>
				<th>' . _(' Group Description') . '</th>
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

		echo '<td>' . $myrow['groupid'] . '</td><td>' . $myrow['groupname'] . '</td>';
		echo '<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedGroupID=' . $myrow['groupid'] . '">' . _('Edit') . '</a></td>';
		if ( $myrow['groupid'] == '1' or $myrow['groupid'] == '2' ) {
			echo '<td><b>'._('Restricted').'</b></td>';
		} else {
			echo '<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedGroupID=' . $myrow['groupid'] . '&amp;delete=1">' . _('Delete') .'</a></td>';
		}
		echo '</tr>';
	} //END WHILE LIST LOOP
	echo '</table><br />';
} //end of ifs and buts!

if (! isset($_GET['delete'])) {

	echo '<form method="post" name="LocationGroup" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($_GET['SelectedGroupID'])) {
		//editing an existing section

		$sql = "SELECT groupid,
				groupname
			FROM locationgroup
			WHERE groupid='" . $_GET['SelectedGroupID'] ."'";

		$result = DB_query($sql, $db);
		if ( DB_num_rows($result) == 0 ) {
			prnMsg( _('Could not retrieve the requested section please try again.'),'warn');
			unset($_GET['SelectedGroupID']);
		} else {
			$myrow = DB_fetch_array($result);

			$_POST['GroupID'] = $myrow['groupid'];
			$_POST['GroupName']  = $myrow['groupname'];

			echo '<input type="hidden" name="SelectedGroupID" value="' . $_POST['GroupID'] . '" />';
			echo '<br /><table class="selection">
					<tr>
						<th class="header" colspan="2">' . _('Edit Location Group Details') . '</th>
					</tr>
					<tr>
						<td>' . _('Group Number') . ':' . '</td>
						<td>' . $_POST['GroupID'] . '</td>
					</tr>';
		}

	}  else {

		if (!isset($_POST['SelectedGroupID'])){
			$_POST['SelectedGroupID']='';
		}
		if (!isset($_POST['GroupID'])){
			$_POST['GroupID']='';
		}
		if (!isset($_POST['GroupName'])) {
			$_POST['GroupName']='';
		}
		echo '<table class="selection">
					<tr>
						<th class="header" colspan="2">' . _('New Location Group Details') . '</th>
					</tr>
			<tr>
				<td>' . _('Group Number') . ':' . '</td>
				<td><input tabindex="1" ' . (in_array('GroupID',$Errors) ?  'class="inputerror"' : '' ) .' type="text" name="GroupID" class="number" size="4" maxlength="4" value="' . $_POST['GroupID'] . '" /></td>
			</tr>';
	}
	echo '<tr><td>' . _('Group Description') . ':' . '</td>
		<td><input tabindex="2" ' . (in_array('GroupName',$Errors) ?  'class="inputerror"' : '' ) .' type="text" name="GroupName" size="30" maxlength="30" value="' . $_POST['GroupName'] . '" /></td>
		</tr>';

	echo '<tr><td colspan="2"><div class="centre"><button tabindex="3" type="submit" name="submit">' . _('Enter Information') . '</button></div></td></tr>';
	echo '</table><br />';

	if (isset($_POST['SelectedGroupID']) or isset($_GET['SelectedGroupID'])) {
		echo '<div style="text-align: right"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . _('Review Location Group') . '</a></div>';
	}

	if (!isset($_GET['SelectedGroupID']) or $_GET['SelectedGroupID']=='') {
		echo '<script>defaultControl(document.LocationGroup.GroupID);</script>';
	} else {
		echo '<script>defaultControl(document.LocationGroup.GroupName);</script>';
	}

	echo '</form>';

} //end if record deleted no point displaying form to add record

include('includes/footer.php');
?>