<?php
/* $Revision: 1.4 $ */
/* $Id$*/

include('includes/session.inc');
$title = _('Customer Notes');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

if (isset($_GET['Id'])){
	$Id = (int)$_GET['Id'];
} else if (isset($_POST['Id'])){
	$Id = (int)$_POST['Id'];
}
if (isset($_POST['DebtorNo'])){
	$DebtorNo = $_POST['DebtorNo'];
} elseif (isset($_GET['DebtorNo'])){
	$DebtorNo = $_GET['DebtorNo'];
}

echo '<a href="' . $rootpath . '/SelectCustomer.php?DebtorNo=' . $DebtorNo . '">' . _('Back to Select Customer') . '</a>';

if ( isset($_POST['submit']) ) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;
	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (!is_long((integer)$_POST['priority'])) {
		$InputError = 1;
		prnMsg( _('The contact priority must be an integer.'), 'error');
	} elseif (strlen($_POST['note']) >200) {
		$InputError = 1;
		prnMsg( _('The contact\'s notes must be two hundred characters or less long'), 'error');
	} elseif( trim($_POST['note']) == '' ) {
		$InputError = 1;
		prnMsg( _('The contact\'s notes may not be empty'), 'error');
	}

	if (isset($Id) and $InputError !=1) {

		$sql = "UPDATE custnotes SET note='" . $_POST['note'] . "',
									date='" . FormatDateForSQL($_POST['date']) . "',
									href='" . $_POST['href'] . "',
									priority='" . $_POST['priority'] . "'
				WHERE debtorno ='".$DebtorNo."'
				AND noteid='".$Id."'";
		$msg = _('Customer Notes') . ' ' . $DebtorNo  . ' ' . _('has been updated');
	} elseif ($InputError !=1) {

		$sql = "INSERT INTO custnotes (debtorno,
										href,
										note,
										date,
										priority)
				VALUES ('" . $DebtorNo. "',
						'" . $_POST['href'] . "',
						'" . $_POST['note'] . "',
						'" . FormatDateForSQL($_POST['date']) . "',
						'" . $_POST['priority'] . "')";
		$msg = _('The contact notes record has been added');
	}

	if ($InputError !=1) {
		$result = DB_query($sql,$db);
				//echo '<br />'.$sql;

		echo '<br />';
		prnMsg($msg, 'success');
		unset($Id);
		unset($_POST['note']);
		unset($_POST['noteid']);
		unset($_POST['date']);
		unset($_POST['href']);
		unset($_POST['priority']);
	}
} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'SalesOrders'

	$sql="DELETE FROM custnotes
			WHERE noteid='".$Id."'
			AND debtorno='".$DebtorNo."'";
	$result = DB_query($sql,$db);

	echo '<br />';
	prnMsg( _('The contact note record has been deleted'), 'success');
	unset($Id);
	unset($_GET['delete']);
}

if (!isset($Id)) {
	$SQLname="SELECT * FROM debtorsmaster
				WHERE debtorno='".$DebtorNo."'";
	$Result = DB_query($SQLname,$db);
	$row = DB_fetch_array($Result);
	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . _('Notes for Customer').': <b>' .$row['name'].'</b></p>';

	$sql = "SELECT noteid,
					debtorno,
					href,
					note,
					date,
					priority
				FROM custnotes
				WHERE debtorno='".$DebtorNo."'
				ORDER BY date DESC";
	$result = DB_query($sql,$db);

	echo '<table class="selection">
		<tr>
			<th>' . _('Date') . '</th>
			<th>' . _('Note') . '</th>
			<th>' . _('WWW') . '</th>
			<th>' . _('Priority') . '</th>
		</tr>';

	$k=0; //row colour counter

	while ($myrow = DB_fetch_array($result)) {
		if ($k==1){
			echo '<tr class="OddTableRows">';
			$k=0;
		} else {
			echo '<tr class="EvenTableRows">';
			$k=1;
		}
		printf('<td>%s</td>
				<td>%s</td>
				<td><a href="%s">%s</a></td>
				<td>%s</td>
				<td><a href="%sId=%s&DebtorNo=%s">'. _('Edit').' </td>
				<td><a href="%sId=%s&DebtorNo=%s&delete=1" onclick="return confirm(\'' . _('Are you sure you wish to delete this customer note?') . '\');">'. _('Delete'). '</td></tr>',
				ConvertSQLDate($myrow['date']),
				$myrow['note'],
				$myrow['href'],
				$myrow['href'],
				$myrow['priority'],
				htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?',
				$myrow['noteid'],
				$myrow['debtorno'],
				htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?',
				$myrow['noteid'],
				$myrow['debtorno']);

	}
	//END WHILE LIST LOOP
	echo '</table>';
}
if (isset($Id)) {
	echo '<div class="centre">
			<a href="'.htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?DebtorNo='.$DebtorNo.'">'._('Review all notes for this Customer').'</a>
		</div>';
}
echo '<br />';

if (!isset($_GET['delete'])) {

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?DebtorNo=' . $DebtorNo . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($Id)) {
		//editing an existing

		$sql = "SELECT noteid,
						debtorno,
						href,
						note,
						date,
						priority
					FROM custnotes
					WHERE noteid='".$Id."'
						AND debtorno='".$DebtorNo."'";

		$result = DB_query($sql, $db);

		$myrow = DB_fetch_array($result);

		$_POST['noteid'] = $myrow['noteid'];
		$_POST['note']	= $myrow['note'];
		$_POST['href']  = $myrow['href'];
		$_POST['date']  = $myrow['date'];
		$_POST['priority']  = $myrow['priority'];
		$_POST['debtorno']  = $myrow['debtorno'];
		echo '<input type="hidden" name="Id" value="'. $Id .'" />';
		echo '<input type="hidden" name="Con_ID" value="' . $_POST['noteid'] . '" />';
		echo '<input type="hidden" name="DebtorNo" value="' . $_POST['debtorno'] . '" />';
		echo '<table class="selection">
			<tr>
				<td>'. _('Note ID').':</td>
				<td>' . $_POST['noteid'] . '</td>
			</tr>';
	} else {
		echo '<table class="selection">';
	}

	echo '<tr>
			<td>' . _('Contact Note'). '</td>';
	if (isset($_POST['note'])) {
		echo '<td><textarea name="note">' .$_POST['note'] . '</textarea></td>
			</tr>';
	} else {
		echo '<td><textarea name="note"></textarea></td>
			</tr>';
	}
	echo '<tr>
			<td>'. _('WWW').'</td>';
	if (isset($_POST['href'])) {
		echo '<td><input type="text" name="href" value="'.$_POST['href'].'" size="35" maxlength="100" /></td>
			</tr>';
	} else {
		echo '<td><input type="text" name="href" size="35" maxlength="100" /></td>
			</tr>';
	}
	echo '<tr>
			<td>' . _('Date') .'</td>';
	if (isset($_POST['date'])) {
		echo '<td><input type="text" name="date" class="date" alt="' .$_SESSION['DefaultDateFormat']. '" id="datepicker" value="'.ConvertSQLDate($_POST['date']).'" size="10" maxlength="10" /></td>
			</tr>';
	} else {
		echo '<td><input type="text" name="date" class="date" alt="' .$_SESSION['DefaultDateFormat']. '" id="datepicker" size="10" maxlength="10" value="'.date($_SESSION['DefaultDateFormat']).'" /></td>
			</tr>';
	}
	echo '<tr>
			<td>'. _('Priority'). '</td>';
	if (isset($_POST['priority'])) {
		echo '<td><input type="text" name="priority" value="' .$_POST['priority']. '" size="1" maxlength="3" /></td>
			</tr>';
	} else {
		echo '<td><input type="text" name="priority" size="1" maxlength="3" /></td>
			</tr>';
	}
	echo '<tr>
			<td colspan="2">
			<div class="centre">
				<button type="submit" name="submit">'._('Enter Information').'</button>
			</div>
			</td>
		</tr>
		</table>
		</form>';

} //end if record deleted no point displaying form to add record

include('includes/footer.inc');
?>
