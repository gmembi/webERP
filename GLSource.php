<?php

/* $Id$*/

include('includes/session.php');
$Title = _('Source of Funds');
$ViewTopic = 'GeneralLedger';
$BookMark = 'SourceFunds';
include('includes/header.php');

if (isset($_GET['SelectedSource'])) {
	$sql="SELECT sourceref, sourcedescription FROM source where sourceref='".$_GET['SelectedSource']."'";
	$result= DB_query($sql,$db);
	$myrow = DB_fetch_array($result,$db);
	$ref=$myrow[0];
	$description=$myrow[1];
} else {
	$description='';
	$_GET['SelectedSource']='';
}

if (isset($_POST['submit'])) {
	$sql = "INSERT INTO source VALUES(NULL, '".$_POST['description']."')";
	$result= DB_query($sql,$db);
}

if (isset($_POST['update'])) {
	$sql = "UPDATE source SET sourcedescription='".$_POST['description'].
		"' WHERE sourceref='".$_POST['reference']."'";
	$result= DB_query($sql,$db);
}
echo '<p class="page_title_text"><img alt="" class="noprint" src="', $RootPath, '/css/', $Theme,
'/images/maintenance.png" title="', // Icon image.
_('Source of Funds'), '" /> ', // Icon title.
_('Source of Funds'), '</p>';// Page title.

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" name="form">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<br /><table class="selection"><tr>';


echo '<td>'. _('Description') . '</td>
		<td><input type="text" size="30" maxlength="30" name="description" value="'.$description.'" /></td><td>
		<input type="hidden" name="reference" value="'.$_GET['SelectedSource'].'" />';

if (isset($_GET['Action']) and $_GET['Action']=='edit') {
	echo '<button type="submit" name="update">' . _('Update') . '</button>';
} else {
	echo '<button type="submit" name="submit">' . _('Insert') . '</button>';
}

echo '</td></tr></table><p></p>';

echo '</form>';

echo '<table class="selection">';
echo '<tr><th>'. _('Source ID') .'</th>';
echo '<th>'. _('Description'). '</th>';

$sql="SELECT sourceref, sourcedescription FROM source order by sourceref";
$result= DB_query($sql,$db);

while ($myrow = DB_fetch_array($result,$db)){
	echo '<tr><td>'.$myrow[0].'</td><td>'.$myrow[1].'</td><td>
		<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedSource=' . $myrow[0] . '&Action=edit">' . _('Edit') . '</a></td></tr>';
}

echo '</table><p></p>';

echo '<script>defaultControl(document.form.description);</script>';

include('includes/footer.php');

?>