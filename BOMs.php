<?php
/* $Revision: 1.37 $ */
/* $Id$*/

include('includes/session.inc');

$title = _('Multi-Level Bill Of Materials Maintenance');

include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');


// *** POPAD&T -  ... Phil modified to english variables
function display_children($parent, $level, &$BOMTree) {

	global $db;
	global $i;

	// retrive all children of parent
	$c_result = DB_query("SELECT parent,
					component
				FROM bom WHERE parent='" . $parent. "'"
				 ,$db);
	if (DB_num_rows($c_result) > 0) {
		//echo ("<UL>\n");


		while ($row = DB_fetch_array($c_result)) {
			//echo '<br />Parent: ' . $parent . ' Level: ' . $level . ' row[component]: ' . $row['component'] .'<br />';
			if ($parent != $row['component']) {
				// indent and display the title of this child
				$BOMTree[$i]['Level'] = $level; 		// Level
				if ($level > 15) {
					prnMsg(_('A maximum of 15 levels of bill of materials only can be displayed'),'error');
					exit;
				}
				$BOMTree[$i]['Parent'] = $parent;		// Assemble
				$BOMTree[$i]['Component'] = $row['component'];	// Component
				// call this function again to display this
				// child's children
				$i++;
				display_children($row['component'], $level + 1, $BOMTree);
			}
		}
	}
}


function CheckForRecursiveBOM ($UltimateParent, $ComponentToCheck, $db) {

/* returns true ie 1 if the BOM contains the parent part as a component
ie the BOM is recursive otherwise false ie 0 */

	$sql = "SELECT component FROM bom WHERE parent='".$ComponentToCheck."'";
	$ErrMsg = _('An error occurred in retrieving the components of the BOM during the check for recursion');
	$DbgMsg = _('The SQL that was used to retrieve the components of the BOM and that failed in the process was');
	$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);

	if (DB_num_rows($result)!=0) {
		while ($myrow=DB_fetch_row($result)){
			if ($myrow[0]==$UltimateParent){
				return 1;
			}
			if (CheckForRecursiveBOM($UltimateParent, $myrow[0],$db)){
				return 1;
			}
		} //(while loop)
	} //end if $result is true

	return 0;

} //end of function CheckForRecursiveBOM

function DisplayBOMItems($UltimateParent, $Parent, $Component,$Level, $db) {

		global $ParentMBflag;
		// Modified by POPAD&T
		$sql = "SELECT bom.component,
				stockmaster.description,
				locations.locationname,
				workcentres.description,
				bom.quantity,
				bom.effectiveafter,
				bom.effectiveto,
				stockmaster.mbflag,
				bom.autoissue,
				stockmaster.controlled,
				locstock.quantity AS qoh,
				stockmaster.decimalplaces
			FROM bom,
				stockmaster,
				locations,
				workcentres,
				locstock
			WHERE bom.component='".$Component."'
			AND bom.parent = '".$Parent."'
			AND bom.component=stockmaster.stockid
			AND bom.loccode = locations.loccode
			AND locstock.loccode=bom.loccode
			AND bom.component = locstock.stockid
			AND bom.workcentreadded=workcentres.code
			AND stockmaster.stockid=bom.component";

		$ErrMsg = _('Could not retrieve the BOM components because');
		$DbgMsg = _('The SQL used to retrieve the components was');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);

		//echo $TableHeader;
		$RowCounter =0;

		while ($myrow=DB_fetch_row($result)) {

			$Level1 = str_repeat('-&nbsp;',$Level-1).$Level;
			if( $myrow[7]=='B' OR $myrow[7]=='K' OR $myrow[7]=='D') {
				$DrillText = '%s%s';
				$DrillLink = '<div class="centre">'._('No lower levels').'</div>';
				$DrillID='';
			} else {
				$DrillText = '<a href="%s&Select=%s">' . _('Drill Down');
				$DrillLink = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?';
				$DrillID=$myrow[0];
			}
			if ($ParentMBflag!='M' AND $ParentMBflag!='G'){
				$AutoIssue = _('N/A');
			} elseif ($myrow[9]==0 AND $myrow[8]==1){//autoissue and not controlled
				$AutoIssue = _('Yes');
			} elseif ($myrow[9]==0) {
				$AutoIssue = _('No');
			} else {
				$AutoIssue = _('N/A');
			}

			if ($myrow[7]=='D' OR $myrow[7]=='K' OR $myrow[7]=='A' OR $myrow[7]=='G'){
				$QuantityOnHand = _('N/A');
			} else {
				$QuantityOnHand = number_format($myrow[10],$myrow[11]);
			}
			printf('<td>%s</td>
				<td>%s</td>
			    <td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td class="number">%s</td>
				<td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td class="number">%s</td>
				<td><a href="%sSelect=%s&SelectedComponent=%s">' . _('Edit') . '</a></td>
				<td>'.$DrillText.'</a></td>
				 <td><a href="%sSelect=%s&SelectedComponent=%s&delete=1&ReSelect=%s">' . _('Delete') . '</a></td>
				 </tr>',
				$Level1,
				$myrow[0],
				$myrow[1],
				$myrow[2],
				$myrow[3],
				$myrow[4],
				ConvertSQLDate($myrow[5]),
				ConvertSQLDate($myrow[6]),
				$AutoIssue,
				$QuantityOnHand,
				htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?',
				$Parent,
				$myrow[0],
				$DrillLink,
				$DrillID,
				htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?',
				$Parent,
				$myrow[0],
				$UltimateParent);

		} //END WHILE LIST LOOP
} //end of function DisplayBOMItems

//---------------------------------------------------------------------------------

/* SelectedParent could come from a post or a get */
if (isset($_GET['SelectedParent'])){
	$SelectedParent = $_GET['SelectedParent'];
}else if (isset($_POST['SelectedParent'])){
	$SelectedParent = $_POST['SelectedParent'];
}



/* SelectedComponent could also come from a post or a get */
if (isset($_GET['SelectedComponent'])){
	$SelectedComponent = $_GET['SelectedComponent'];
} elseif (isset($_POST['SelectedComponent'])){
	$SelectedComponent = $_POST['SelectedComponent'];
}

if (isset($_GET['Select'])){
	$Select = $_GET['Select'];
} elseif (isset($_POST['Select'])){
	$Select = $_POST['Select'];
}


$msg='';

if (isset($Errors)) {
	unset($Errors);
}

$Errors = array();
$InputError = 0;

if (isset($Select)) { //Parent Stock Item selected so display BOM or edit Component
	$SelectedParent = $Select;
	unset($Select);// = NULL;
	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $title.'</p><br />';

	if (isset($SelectedParent) AND isset($_POST['Submit'])) {

		//editing a component need to do some validation of inputs

		$i = 1;

		if (!Is_Date($_POST['EffectiveAfter'])) {
			$InputError = 1;
			prnMsg(_('The effective after date field must be a date in the format dd/mm/yy or dd/mm/yyyy or ddmmyy or ddmmyyyy or dd-mm-yy or dd-mm-yyyy'),'error');
			$Errors[$i] = 'EffectiveAfter';
			$i++;
		}
		if (!Is_Date($_POST['EffectiveTo'])) {
			$InputError = 1;
			prnMsg(_('The effective to date field must be a date in the format dd/mm/yy or dd/mm/yyyy or ddmmyy or ddmmyyyy or dd-mm-yy or dd-mm-yyyy'),'error');
			$Errors[$i] = 'EffectiveTo';
			$i++;
		}
		if (!is_numeric($_POST['Quantity'])) {
			$InputError = 1;
			prnMsg(_('The quantity entered must be numeric'),'error');
			$Errors[$i] = 'Quantity';
			$i++;
		}
		if ($_POST['Quantity']==0) {
			$InputError = 1;
			prnMsg(_('The quantity entered cannot be zero'),'error');
			$Errors[$i] = 'Quantity';
			$i++;
		}
		if(!Date1GreaterThanDate2($_POST['EffectiveTo'], $_POST['EffectiveAfter'])){
			$InputError = 1;
			prnMsg(_('The effective to date must be a date after the effective after date') . '<br />' . _('The effective to date is') . ' ' . DateDiff($_POST['EffectiveTo'], $_POST['EffectiveAfter'], 'd') . ' ' . _('days before the effective after date') . '! ' . _('No updates have been performed') . '.<br />' . _('Effective after was') . ': ' . $_POST['EffectiveAfter'] . ' ' . _('and effective to was') . ': ' . $_POST['EffectiveTo'],'error');
			$Errors[$i] = 'EffectiveAfter';
			$i++;
			$Errors[$i] = 'EffectiveTo';
			$i++;
		}
		if($_POST['AutoIssue']==1 and isset($_POST['Component'])){
			$sql = "SELECT controlled FROM stockmaster WHERE stockid='" . $_POST['Component'] . "'";
			$CheckControlledResult = DB_query($sql,$db);
			$CheckControlledRow = DB_fetch_row($CheckControlledResult);
			if ($CheckControlledRow[0]==1){
				prnMsg(_('Only non-serialised or non-lot controlled items can be set to auto issue. These items require the lot/serial numbers of items issued to the works orders to be specified so autoissue is not an option. Auto issue has been automatically set to off for this component'),'warn');
				$_POST['AutoIssue']=0;
			}
		}

		if (!in_array('EffectiveAfter', $Errors)) {
			$EffectiveAfterSQL = FormatDateForSQL($_POST['EffectiveAfter']);
		}
		if (!in_array('EffectiveTo', $Errors)) {
			$EffectiveToSQL = FormatDateForSQL($_POST['EffectiveTo']);
		}

		if (isset($SelectedParent) AND isset($SelectedComponent) AND $InputError != 1) {


			$sql = "UPDATE bom SET workcentreadded='" . $_POST['WorkCentreAdded'] . "',
						loccode='" . $_POST['LocCode'] . "',
						effectiveafter='" . $EffectiveAfterSQL . "',
						effectiveto='" . $EffectiveToSQL . "',
						quantity= '" . $_POST['Quantity'] . "',
						autoissue='" . $_POST['AutoIssue'] . "'
					WHERE bom.parent='" . $SelectedParent . "'
					AND bom.component='" . $SelectedComponent . "'";

			$ErrMsg =  _('Could not update this BOM component because');
			$DbgMsg =  _('The SQL used to update the component was');

			$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);
			$msg = _('Details for') . ' - ' . $SelectedComponent . ' ' . _('have been updated') . '.';
			UpdateCost($db, $SelectedComponent);

		} elseif ($InputError !=1 AND ! isset($SelectedComponent) AND isset($SelectedParent)) {

		/*Selected component is null cos no item selected on first time round so must be				adding a record must be Submitting new entries in the new component form */

		//need to check not recursive BOM component of itself!

			if (!CheckForRecursiveBOM ($SelectedParent, $_POST['Component'], $db)) {

				/*Now check to see that the component is not already on the BOM */
				$sql = "SELECT component
						FROM bom
					WHERE parent='".$SelectedParent."'
					AND component='" . $_POST['Component'] . "'
					AND workcentreadded='" . $_POST['WorkCentreAdded'] . "'
					AND loccode='" . $_POST['LocCode'] . "'" ;

				$ErrMsg =  _('An error occurred in checking the component is not already on the BOM');
				$DbgMsg =  _('The SQL that was used to check the component was not already on the BOM and that failed in the process was');

				$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);

				if (DB_num_rows($result)==0) {

					$sql = "INSERT INTO bom (parent,
								component,
								workcentreadded,
								loccode,
								quantity,
								effectiveafter,
								effectiveto,
								autoissue)
							VALUES ('".$SelectedParent."',
								'" . $_POST['Component'] . "',
								'" . $_POST['WorkCentreAdded'] . "',
								'" . $_POST['LocCode'] . "',
								" . $_POST['Quantity'] . ",
								'" . $EffectiveAfterSQL . "',
								'" . $EffectiveToSQL . "',
								" . $_POST['AutoIssue'] . ")";

					$ErrMsg = _('Could not insert the BOM component because');
					$DbgMsg = _('The SQL used to insert the component was');

					$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);

					UpdateCost($db, $_POST['Component']);
					$msg = _('A new component part') . ' ' . $_POST['Component'] . ' ' . _('has been added to the bill of material for part') . ' - ' . $SelectedParent . '.';


				} else {

				/*The component must already be on the BOM */

					prnMsg( _('The component') . ' ' . $_POST['Component'] . ' ' . _('is already recorded as a component of') . ' ' . $SelectedParent . '.' . '<br />' . _('Whilst the quantity of the component required can be modified it is inappropriate for a component to appear more than once in a bill of material'),'error');
					$Errors[$i]='ComponentCode';
				}


			} //end of if its not a recursive BOM

		} //end of if no input errors

		if ($msg != '') {prnMsg($msg,'success');}

	} elseif (isset($_GET['delete']) AND isset($SelectedComponent) AND isset($SelectedParent)) {

	//the link to delete a selected record was clicked instead of the Submit button

		$sql="DELETE FROM bom WHERE parent='".$SelectedParent."' AND component='".$SelectedComponent."'";

		$ErrMsg = _('Could not delete this BOM components because');
		$DbgMsg = _('The SQL used to delete the BOM was');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg);

		$ComponentSQL = "SELECT component from bom where parent='" . $SelectedParent ."'";
		$ComponentResult = DB_query($ComponentSQL,$db);
		$ComponentArray = DB_fetch_row($ComponentResult);
		UpdateCost($db, $ComponentArray[0]);

		prnMsg(_('The component part') . ' - ' . $SelectedComponent . ' - ' . _('has been deleted from this BOM'),'success');
		// Now reselect

	} elseif (isset($SelectedParent)
		AND !isset($SelectedComponent)
		AND ! isset($_POST['submit'])) {

	/* It could still be the second time the page has been run and a record has been selected	for modification - SelectedParent will exist because it was sent with the new call. if		its the first time the page has been displayed with no parameters then none of the above		are true and the list of components will be displayed with links to delete or edit each.		These will call the same page again and allow update/input or deletion of the records*/
		//DisplayBOMItems($SelectedParent, $db);

	} //BOM editing/insertion ifs


	if(isset($_GET['ReSelect'])) {
		$SelectedParent = $_GET['ReSelect'];
	}

	//DisplayBOMItems($SelectedParent, $db);
	$sql = "SELECT stockmaster.description,
			stockmaster.mbflag
		FROM stockmaster
		WHERE stockmaster.stockid='" . $SelectedParent . "'";

	$ErrMsg = _('Could not retrieve the description of the parent part because');
	$DbgMsg = _('The SQL used to retrieve description of the parent part was');
	$result=DB_query($sql,$db,$ErrMsg,$DbgMsg);

	$myrow=DB_fetch_row($result);

	$ParentMBflag = $myrow[1];

	switch ($ParentMBflag){
		case 'A':
			$MBdesc = _('Assembly');
			break;
		case 'B':
			$MBdesc = _('Purchased');
			break;
		case 'M':
			$MBdesc = _('Manufactured');
			break;
		case 'K':
			$MBdesc = _('Kit Set');
			break;
		case 'G':
			$MBdesc = _('Phantom');
			break;
	}

	echo '<div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . _('Select a Different BOM') . '</a></div><br />';
	echo '<table class="selection">';
	// Display Manufatured Parent Items
	$sql = "SELECT bom.parent,
			stockmaster.description,
			stockmaster.mbflag
		FROM bom, stockmaster
		WHERE bom.component='".$SelectedParent."'
		AND stockmaster.stockid=bom.parent
		AND stockmaster.mbflag='M'";

	$ErrMsg = _('Could not retrieve the description of the parent part because');
	$DbgMsg = _('The SQL used to retrieve description of the parent part was');
	$result=DB_query($sql,$db,$ErrMsg,$DbgMsg);
	$ix = 0;
	$reqnl = 0;
	if( DB_num_rows($result) > 0 ) {
	 echo '<tr><td><div class="centre">'._('Manufactured parent items').' : ';
	 while ($myrow = DB_fetch_array($result)){
	 	   echo (($ix)?', ':'').'<a href="'.htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Select='.$myrow['parent'].'">'.
			$myrow['description'].'&nbsp;('.$myrow['parent'].')</a>';
			$ix++;
	 } //end while loop
	 echo '</div></td></tr>';
	 $reqnl = $ix;
	}
	// Display Assembly Parent Items
	$sql = "SELECT bom.parent, stockmaster.description, stockmaster.mbflag
		FROM bom, stockmaster
		WHERE bom.component='".$SelectedParent."'
		AND stockmaster.stockid=bom.parent
		AND stockmaster.mbflag='A'";

	$ErrMsg = _('Could not retrieve the description of the parent part because');
	$DbgMsg = _('The SQL used to retrieve description of the parent part was');
	$result=DB_query($sql,$db,$ErrMsg,$DbgMsg);
	if( DB_num_rows($result) > 0 ) {
		echo (($reqnl)?'<br />':'').'<tr><td><div class="centre">'._('Assembly parent items').' : ';
	 	$ix = 0;
	 	while ($myrow = DB_fetch_array($result)){
	 	   echo (($ix)?', ':'').'<a href="'.htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Select='.$myrow['parent'].'">'.
			$myrow['description'].'&nbsp;('.$myrow['parent'].')</a>';
			$ix++;
	 	} //end while loop
	 	echo '</div></td></tr>';
	}
	// Display Kit Sets
	$sql = "SELECT bom.parent, stockmaster.description, stockmaster.mbflag
		FROM bom, stockmaster
		WHERE bom.component='".$SelectedParent."'
		AND stockmaster.stockid=bom.parent
		AND stockmaster.mbflag='K'";

	$ErrMsg = _('Could not retrieve the description of the parent part because');
	$DbgMsg = _('The SQL used to retrieve description of the parent part was');
	$result=DB_query($sql,$db,$ErrMsg,$DbgMsg);
	if( DB_num_rows($result) > 0 ) {
		echo (($reqnl)?'<br />':'').'<tr><td><div class="centre">'._('Kit sets').' : ';
	 	$ix = 0;
	 	while ($myrow = DB_fetch_array($result)){
	 	   echo (($ix)?', ':'').'<a href="'.htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Select='.$myrow['parent'].'">'.
			$myrow['description'].'&nbsp;('.$myrow['parent'].')</a>';
			$ix++;
	 	} //end while loop
	 	echo '</div></td></tr>';
	}
	// Display Phantom/Ghosts
	$sql = "SELECT bom.parent, stockmaster.description, stockmaster.mbflag
		FROM bom, stockmaster
		WHERE bom.component='".$SelectedParent."'
		AND stockmaster.stockid=bom.parent
		AND stockmaster.mbflag='G'";

	$ErrMsg = _('Could not retrieve the description of the parent part because');
	$DbgMsg = _('The SQL used to retrieve description of the parent part was');
	$result=DB_query($sql,$db,$ErrMsg,$DbgMsg);
	if( DB_num_rows($result) > 0 ) {
		echo (($reqnl)?'<br />':'').'<tr><td><div class="centre">'._('Phantom').' : ';
	 	$ix = 0;
	 	while ($myrow = DB_fetch_array($result)){
	 	   echo (($ix)?', ':'').'<a href="'.htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Select='.$myrow['parent'].'">'.
			$myrow['description'].'&nbsp;('.$myrow['parent'].')</a>';
			$ix++;
	 	} //end while loop
	 	echo '</div></td></tr>';
	}
	echo '</table><br /><table class="selection">';
	echo '<tr><th colspan="13" class="header">'.$SelectedParent .' - ' . $myrow[0] . ' ('. $MBdesc. ')</th></tr>';

    // *** POPAD&T
	$BOMTree = array();
	//BOMTree is a 2 dimensional array with three elements for each item in the array - Level, Parent, Component
	//display children populates the BOM_Tree from the selected parent
	$i =0;
	display_children($SelectedParent, 1, $BOMTree);

	$TableHeader =  '<tr>
			<th>' . _('Level') . '</th>
			<th>' . _('Code') . '</th>
			<th>' . _('Description') . '</th>
			<th>' . _('Location') . '</th>
			<th>' . _('Work Centre') . '</th>
			<th>' . _('Quantity') . '</th>
			<th>' . _('Effective After') . '</th>
			<th>' . _('Effective To') . '</th>
			<th>' . _('Auto Issue') . '</th>
			<th>' . _('Qty On Hand') . '</th>
			</tr>';
	echo $TableHeader;
	if(count($BOMTree) == 0) {
		echo '<tr class="OddTableRows"><td colspan="8">'._('No materials found.').'</td></tr>';
	} else {
		$UltimateParent = $SelectedParent;
		$k = 0;
		$RowCounter = 1;

		foreach($BOMTree as $BOMItem){
			$Level = $BOMItem['Level'];
			$Parent = $BOMItem['Parent'];
			$Component = $BOMItem['Component'];
			if ($k==1){
				echo '<tr class="EvenTableRows">';
				$k=0;
			}else {
				echo '<tr class="OddTableRows">';
				$k++;
			}
			DisplayBOMItems($UltimateParent, $Parent, $Component, $Level, $db);
		}
	}
	// *** end POPAD&T
	echo '</table><br />';

	if (! isset($_GET['delete'])) {

		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Select=' . $SelectedParent .'">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($_GET['SelectedComponent']) and $InputError !=1) {
		//editing a selected component from the link to the line item

			$sql = "SELECT loccode,
					effectiveafter,
					effectiveto,
					workcentreadded,
					quantity,
					autoissue
				FROM bom
				WHERE parent='".$SelectedParent."'
				AND component='".$SelectedComponent."'";

			$result = DB_query($sql, $db);
			$myrow = DB_fetch_array($result);

			$_POST['LocCode'] = $myrow['loccode'];
			$_POST['EffectiveAfter'] = ConvertSQLDate($myrow['effectiveafter']);
			$_POST['EffectiveTo'] = ConvertSQLDate($myrow['effectiveto']);
			$_POST['WorkCentreAdded']  = $myrow['workcentreadded'];
			$_POST['Quantity'] = $myrow['quantity'];
			$_POST['AutoIssue'] = $myrow['autoissue'];

			prnMsg(_('Edit the details of the selected component in the fields below') . '. <br />' . _('Click on the Enter Information button to update the component details'),'info');
			echo '<br /><input type="hidden" name="SelectedParent" value="'.$SelectedParent.'" />';
			echo '<input type="hidden" name="SelectedComponent" value="'.$SelectedComponent.'" />';
			echo '<table class="selection">';
			echo '<tr><th colspan="13"><div class="centre"><font color="blue" size="3"><b>'. _('Edit Component Details') .'</font></b></th></tr>';
			echo '<tr><td>' . _('Component') . ':</td><td><b>' . $SelectedComponent . '</b></td></tr>';

		} else { //end of if $SelectedComponent

			echo '<input type="hidden" name="SelectedParent" value="'.$SelectedParent.'" />';
			/* echo "Enter the details of a new component in the fields below. <br />Click on 'Enter Information' to add the new component, once all fields are completed.";
			*/
			echo '<table class="selection">';
			echo '<tr><th colspan="13" class="header">'. _('New Component Details') .'</th></tr>';
			echo '<tr><td>' . _('Component code') . ':</td><td>';
			echo '<select ' . (in_array('ComponentCode',$Errors) ?  'class="selecterror"' : '' ) .' tabindex="1" name="Component">';


			if ($ParentMBflag=='A'){ /*Its an assembly */
				$sql = "SELECT stockmaster.stockid,
						stockmaster.description
					FROM stockmaster INNER JOIN stockcategory
						ON stockmaster.categoryid = stockcategory.categoryid
					WHERE ((stockcategory.stocktype='L' AND stockmaster.mbflag ='D')
					OR stockmaster.mbflag !='D')
					AND stockmaster.mbflag !='K'
					AND stockmaster.mbflag !='A'
					AND stockmaster.controlled = 0
					AND stockmaster.stockid != '".$SelectedParent."'
					ORDER BY stockmaster.stockid";

			} else { /*Its either a normal manufac item, phantom, kitset - controlled items ok */
				$sql = "SELECT stockmaster.stockid,
						stockmaster.description
					FROM stockmaster INNER JOIN stockcategory
						ON stockmaster.categoryid = stockcategory.categoryid
					WHERE ((stockcategory.stocktype='L' AND stockmaster.mbflag ='D')
					OR stockmaster.mbflag !='D')
					AND stockmaster.mbflag !='K'
					AND stockmaster.mbflag !='A'
					AND stockmaster.stockid != '".$SelectedParent."'
					ORDER BY stockmaster.stockid";
			}

			$ErrMsg = _('Could not retrieve the list of potential components because');
			$DbgMsg = _('The SQL used to retrieve the list of potential components part was');
			$result = DB_query($sql,$db,$ErrMsg, $DbgMsg);

			while ($myrow = DB_fetch_array($result)) {
				echo '<option value="'.$myrow['stockid'].'">' . str_pad($myrow['stockid'],21, '_', STR_PAD_RIGHT) . $myrow['description'] . '</option>';
			} //end while loop

			echo '</select></td></tr>';
		}

		echo '<tr><td>' . _('Location') . ': </td><td><select tabindex="2" name="LocCode">';

		DB_free_result($result);
		$sql = "SELECT locationname, loccode FROM locations";
		$result = DB_query($sql,$db);

		while ($myrow = DB_fetch_array($result)) {
			if (isset($_POST['LocCode']) and $myrow['loccode']==$_POST['LocCode']) {
				echo '<option selected="True" value="'.$myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			} else {
				echo '<option value="'.$myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			}

		} //end while loop

		DB_free_result($result);

		echo '</select></td></tr><tr><td>' . _('Work Centre Added') . ': </td><td>';

		$sql = "SELECT code, description FROM workcentres";
		$result = DB_query($sql,$db);

		if (DB_num_rows($result)==0){
			prnMsg( _('There are no work centres set up yet') . '. ' . _('Please use the link below to set up work centres') . '.','warn');
			echo '<a href="'.$rootpath.'/WorkCentres.php">' . _('Work Centre Maintenance') . '</a></td></tr></table><br />';
			include('includes/footer.inc');
			exit;
		}

		echo '<select tabindex="3" name="WorkCentreAdded">';

		while ($myrow = DB_fetch_array($result)) {
			if (isset($_POST['WorkCentreAdded']) and $myrow['code']==$_POST['WorkCentreAdded']) {
				echo '<option selected="True" value="'.$myrow['code'] . '">' . $myrow['description'] . '</option>';
			} else {
				echo '<option value="'.$myrow['code'] . '">' . $myrow['description'] . '</option>';
			}
		} //end while loop

		DB_free_result($result);

		echo '</select></td></tr><tr><td>' . _('Quantity') . ': </td><td>';

		if (isset($_POST['Quantity'])){
			echo '<input ' . (in_array('Quantity',$Errors) ?  'class="inputerror"' : '' ) .' tabindex="4" type="text" class="number" name="Quantity" class="number" size="10" maxlength="8" value="'.$_POST['Quantity'] . '" />';
		} else {
			echo '<input ' . (in_array('Quantity',$Errors) ?  'class="inputerror"' : '' ) .'  tabindex="4" type="text" class="number" name="Quantity" class="number" size="10" maxlength="8" value="1" />';
		}

		echo '</td></tr>';

		if (!isset($_POST['EffectiveTo']) OR $_POST['EffectiveTo']=='') {
			$_POST['EffectiveTo'] = Date($_SESSION['DefaultDateFormat'],Mktime(0,0,0,Date('m'),Date('d'),(Date('y')+20)));
		}
		if (!isset($_POST['EffectiveAfter']) OR $_POST['EffectiveAfter']=='') {
			$_POST['EffectiveAfter'] = Date($_SESSION['DefaultDateFormat'],Mktime(0,0,0,Date('m'),Date('d')-1,Date('y')));
		}

		echo '<tr><td>' . _('Effective After') . ' (' . $_SESSION['DefaultDateFormat'] . '):</td>
		  <td><input ' . (in_array('EffectiveAfter',$Errors) ?  'class="inputerror"' : '' ) . ' tabindex="5" type="text" name="EffectiveAfter" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" size="11" maxlength="10" value="' . $_POST['EffectiveAfter'] .'" />
		  </td></tr><tr><td>' . _('Effective To') . ' (' . $_SESSION['DefaultDateFormat'] . '):</td><td>
		  <input  ' . (in_array('EffectiveTo',$Errors) ?  'class="inputerror"' : '' ) . ' tabindex="6" type="text" name="EffectiveTo" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" size="11" maxlength="10" value="' . $_POST['EffectiveTo'] .'" /></td></tr>';

		if ($ParentMBflag=='M' OR $ParentMBflag=='G'){
			echo '<tr><td>' . _('Auto Issue this Component to Work Orders') . ':</td>
				<td>
				<select tabindex="7" name="AutoIssue">';

			if (!isset($_POST['AutoIssue'])){
				$_POST['AutoIssue'] = $_SESSION['AutoIssue'];
			}
			if ($_POST['AutoIssue']==0) {
				echo '<option selected="True" value=0>' . _('No') . '</option>';
				echo '<option value=1>' . _('Yes') . '</option>';
			} else {
				echo '<option selected="True" value=1>' . _('Yes') . '</option>';
				echo '<option value=0>' . _('No') . '</option>';
			}


			echo '</select></td></tr>';
		} else {
			echo '<input type="hidden" name="AutoIssue" value="0" />';
		}

		echo '</table><br /><div class="centre"><button tabindex="8" type="submit" name="Submit">' . _('Enter Information') . '</button></div><br /></form>';

	} //end if record deleted no point displaying form to add record

	// end of BOM maintenance code - look at the parent selection form if not relevant
// ----------------------------------------------------------------------------------

} elseif (isset($_POST['Search'])){
	// Work around to auto select
	if ($_POST['Keywords']=='' AND $_POST['StockCode']=='') {
		$_POST['StockCode']='%';
	}
	if ($_POST['Keywords'] AND $_POST['StockCode']) {
		prnMsg( _('Stock description keywords have been used in preference to the Stock code extract entered'), 'info' );
	}
	if ($_POST['Keywords']=='' AND $_POST['StockCode']=='') {
		prnMsg( _('At least one stock description keyword or an extract of a stock code must be entered for the search'), 'info' );
	} else {
		if (strlen($_POST['Keywords'])>0) {
			//insert wildcard characters in spaces
			$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

			$sql = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag,
					SUM(locstock.quantity) as totalonhand
				FROM stockmaster,
					locstock
				WHERE stockmaster.stockid = locstock.stockid
				AND stockmaster.description " . LIKE . " '".$SearchString."'
				AND (stockmaster.mbflag='M' OR stockmaster.mbflag='K' OR stockmaster.mbflag='A' OR stockmaster.mbflag='G')
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag
				ORDER BY stockmaster.stockid";

		} elseif (strlen($_POST['StockCode'])>0){
			$sql = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag,
					sum(locstock.quantity) as totalonhand
				FROM stockmaster,
					locstock
				WHERE stockmaster.stockid = locstock.stockid
				AND stockmaster.stockid " . LIKE  . "'%" . $_POST['StockCode'] . "%'
				AND (stockmaster.mbflag='M'
					OR stockmaster.mbflag='K'
					OR stockmaster.mbflag='G'
					OR stockmaster.mbflag='A')
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag
				ORDER BY stockmaster.stockid";

		}

		$ErrMsg = _('The SQL to find the parts selected failed with the message');
		$result = DB_query($sql,$db,$ErrMsg);

	} //one of keywords or StockCode was more than a zero length string
} //end of if search

if (!isset($SelectedParent)) {

	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $title . '</p>';
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">' .
	'<div class="page_help_text">'. _('Select a manufactured part') . ' (' . _('or Assembly or Kit part') . ') ' .
		 _('to maintain the bill of material for using the options below') . '.' . '<br /><font size="1">' .
	 _('Parts must be defined in the stock item entry') . '/' . _('modification screen as manufactured') .
     ', ' . _('kits or assemblies to be available for construction of a bill of material') .'</div>'.
     '</font><br /><table class="selection" cellpadding="3"><tr><td><font size="1">' . _('Enter text extracts in the') .
	 ' <b>' . _('description') . '</b>:</font></td><td><input tabindex="1" type="text" name="Keywords" size="20" maxlength="25" /></td>
	 <td><font size="3"><b>' . _('OR') . '</b></font></td><td><font size="1">' . _('Enter extract of the') .
     ' <b>' . _('Stock Code') . '</b>:</font></td><td><input tabindex="2" type="text" name="StockCode" size="15" maxlength="18" /></td>
	 </tr></table><br /><div class="centre"><button tabindex="3" type="submit" name="Search">' . _('Search Now') . '</button></div><br />';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($_POST['Search']) and isset($result) AND !isset($SelectedParent)) {

	echo '<table cellpadding="2" class="selection">';
	$TableHeader = '<tr><th>' . _('Code') . '</th>
				<th>' . _('Description') . '</th>
				<th>' . _('On Hand') . '</th>
				<th>' . _('Units') . '</th>
			</tr>';

	echo $TableHeader;

	$j = 1;
	$k=0; //row colour counter
	while ($myrow=DB_fetch_array($result)) {
		if ($k==1){
			echo '<tr class="EvenTableRows">';;
			$k=0;
		} else {
			echo '<tr class="OddTableRows">';;
			$k++;
		}
		if ($myrow['mbflag']=='A' OR $myrow['mbflag']=='K' OR $myrow['mbflag']=='G'){
			$StockOnHand = _('N/A');
		} else {
			$StockOnHand = number_format($myrow['totalonhand'],2);
		}
		$tab = $j+3;
		printf('<td><button tabindex="'.$tab.'" type="submit" name="Select" value="%s" />%s</button></td>
		        <td>%s</td>
				<td class="number">%s</td>
				<td>%s</td></tr>',
				$myrow['stockid'],
				$myrow['stockid'],
				$myrow['description'],
				$StockOnHand,
				$myrow['units']
			);

		$j++;
//end of page full new headings if
	}
//end of while loop

	echo '</table><br />';

}
//end if results to show

if (!isset($SelectedParent) or $SelectedParent=='') {
	echo "<script>defaultControl(document.forms[0].StockCode);</script>";
} else {
	echo "<script>defaultControl(document.form.JournalProcessDate);</script>";
}

echo '</form>';

} //end StockID already selected

include('includes/footer.inc');
?>
