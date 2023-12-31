<?php

/* $Id: $ */

include('includes/DefineContractClass.php');

include('includes/session.inc');
$title = _('Contract Bill of Materials');

$identifier=$_GET['identifier'];

/* If a contract header does n0t exist, then go to
 * Contracts.php to create one
 */

if (!isset($_SESSION['Contract'.$identifier])){
	header('Location:' . $rootpath . '/Contracts.php');
	exit;
}
include('includes/header.inc');

$Maximum_Number_Of_Parts_To_Show=50;

if (isset($_POST['UpdateLines']) OR isset($_POST['BackToHeader'])) {
	if($_SESSION['Contract'.$identifier]->Status!=2){ //dont do anything if the customer has committed to the contract
		foreach ($_SESSION['Contract'.$identifier]->ContractBOM as $ContractComponent) {
			if ($_POST['Qty'.$ContractComponent->ComponentID]==0){
				//this is the same as deleting the line - so delete it
				$_SESSION['Contract'.$identifier]->Remove_ContractComponent($ContractComponent->ComponentID);
			} else {
				$_SESSION['Contract'.$identifier]->ContractBOM[$ContractComponent->ComponentID]->Quantity=filter_number_input($_POST['Qty'.$ContractComponent->ComponentID]);
			}
		} // end loop around the items on the contract BOM
	} // end if the contract is not currently committed to by the customer
}// end if the user has hit the update lines or back to header buttons


if (isset($_POST['BackToHeader'])){
	echo '<meta http-equiv="Refresh" content="0; url=' . $rootpath . '/Contracts.php?identifier='.$identifier. '" />';
	echo '<br />';
	prnMsg(_('You should automatically be forwarded to the Contract page. If this does not happen perhaps the browser does not support META Refresh') .	'<a href="' . $rootpath . '/Contracts.php?identifier='.$identifier . '">' . _('click here') . '</a> ' . _('to continue'),'info');
	include('includes/footer.inc');
	exit;
}

if (isset($_POST['Search'])){  /*ie seach for stock items */

	if ($_POST['Keywords'] AND $_POST['StockCode']) {
		prnMsg(_('Stock description keywords have been used in preference to the Stock code extract entered'), 'info');
	}

	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		if ($_POST['StockCat']=='All'){
			$sql = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				WHERE stockmaster.mbflag!='D'
				AND stockmaster.mbflag!='A'
				AND stockmaster.mbflag!='K'
				and stockmaster.discontinued!=1
				AND stockmaster.description " . LIKE . " '$SearchString'
				ORDER BY stockmaster.stockid";
		} else {
			$sql = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				WHERE stockmaster.mbflag!='D'
				AND stockmaster.mbflag!='A'
				AND stockmaster.mbflag!='K'
				and stockmaster.discontinued!=1
				AND stockmaster.description " . LIKE . " '$SearchString'
				AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
				ORDER BY stockmaster.stockid";
		}

	} elseif ($_POST['StockCode']){

		$_POST['StockCode'] = '%' . $_POST['StockCode'] . '%';

		if ($_POST['StockCat']=='All'){
			$sql = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				WHERE stockmaster.mbflag!='D'
				AND stockmaster.mbflag!='A'
				AND stockmaster.mbflag!='K'
				and stockmaster.discontinued!=1
				AND stockmaster.stockid " . LIKE . " '" . $_POST['StockCode'] . "'
				ORDER BY stockmaster.stockid";
		} else {
			$sql = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				WHERE stockmaster.mbflag!='D'
				AND stockmaster.mbflag!='A'
				AND stockmaster.mbflag!='K'
				and stockmaster.discontinued!=1
				AND stockmaster.stockid " . LIKE . " '" . $_POST['StockCode'] . "'
				AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
				ORDER BY stockmaster.stockid";
		}

	} else {
		if ($_POST['StockCat']=='All'){
			$sql = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				WHERE stockmaster.mbflag!='D'
				AND stockmaster.mbflag!='A'
				AND stockmaster.mbflag!='K'
				and stockmaster.discontinued!=1
				ORDER BY stockmaster.stockid";
		} else {
			$sql = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				WHERE stockmaster.mbflag!='D'
				AND stockmaster.mbflag!='A'
				AND stockmaster.mbflag!='K'
				and stockmaster.discontinued!=1
				AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
				ORDER BY stockmaster.stockid";
		}
	}

	$ErrMsg = _('There is a problem selecting the part records to display because');
	$DbgMsg = _('The SQL statement that failed was');
	$SearchResult = DB_query($sql,$db,$ErrMsg,$DbgMsg);

	if (DB_num_rows($SearchResult)==0 and $debug==1){
		prnMsg( _('There are no products to display matching the criteria provided'),'warn');
	}
	if (DB_num_rows($SearchResult)==1){
		$myrow=DB_fetch_array($SearchResult);
		$_GET['NewItem'] = $myrow['stockid'];
		DB_data_seek($SearchResult,0);
	}

} //end of if search


if(isset($_GET['Delete'])){
	if($_SESSION['Contract'.$identifier]->Status!=2){
		$_SESSION['Contract'.$identifier]->Remove_ContractComponent($_GET['Delete']);
	} else {
		prnMsg( _('The contract BOM cannot be alterned because the customer has already placed the order'),'warn');
	}
}



if (isset($_POST['NewItem'])){ /* NewItem is set from the part selection list as the part code selected */
/* take the form entries and enter the data from the form into the PurchOrder class variable */
	foreach ($_POST as $key => $value) {
		if (substr($key, 0, 7)=='StockID') {
			$Index=substr($key, 7);
			$ItemCode=$value;
			$Quantity=filter_number_input($_POST['qty'.$Index]);

			$AlreadyOnThisBOM = 0;

			if (count($_SESSION['Contract'.$identifier]->ContractBOM)!=0){

				foreach ($_SESSION['Contract'.$identifier]->ContractBOM AS $Component) {

				/* do a loop round the items on the order to see that the item
				is not already on this order */
					if ($Component->StockID == $ItemCode) {
						$AlreadyOnThisBOM = 1;
						prnMsg( _('The item') . ' ' . $ItemCode . ' ' . _('is already in the bill of material for this contract. The system will not allow the same item on the contract more than once. However you can change the quantity required for the item.'),'error');
					}
				} /* end of the foreach loop to look for preexisting items of the same code */
			}

			if ($AlreadyOnThisBOM!=1 and $Quantity>0){

				$sql = "SELECT stockmaster.description,
								stockmaster.stockid,
								stockmaster.units,
								stockmaster.decimalplaces,
								stockmaster.materialcost+labourcost+overheadcost AS unitcost
							FROM stockmaster
							WHERE stockmaster.stockid = '". $ItemCode . "'";

				$ErrMsg = _('The item details could not be retrieved');
				$DbgMsg = _('The SQL used to retrieve the item details but failed was');
				$result1 = DB_query($sql,$db,$ErrMsg,$DbgMsg);

				if ($myrow = DB_fetch_array($result1)){

					$_SESSION['Contract'.$identifier]->Add_To_ContractBOM ($ItemCode,
																			$myrow['description'],
																			$_SESSION['Contract'.$identifier]->DefaultWorkCentre,
																			$Quantity, /* Qty */
																			$myrow['unitcost'],
																			$myrow['units']);
				} else {
					prnMsg (_('The item code') . ' ' . $ItemCode . ' ' . _('does not exist in the database and therefore cannot be added to the contract BOM'),'error');
					if ($debug==1){
						echo '<br />'.$sql;
					}
					include('includes/footer.inc');
					exit;
				}
			} /* end of if not already on the contract BOM */
		}
	}
} /* end of if its a new item */

/* This is where the order as selected should be displayed  reflecting any deletions or insertions*/

echo '<form name="ContractBOMForm" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier='.$identifier. '" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (count($_SESSION['Contract'.$identifier]->ContractBOM)>0){
	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/contract.png" title="' ._('Contract Bill of Material') . '" alt="" />'.
			$_SESSION['Contract'.$identifier]->CustomerName . '</p>';

	echo '<table cellpadding="2" class="selection">';

	if (isset($_SESSION['Contract'.$identifier]->ContractRef)) {
		echo  '<tr><th colspan="7" class="header">' . _('Contract Reference:') .' '. $_SESSION['Contract'.$identifier]->ContractRef.'</th></tr>';
	}

	echo '<tr>
		<th>' . _('Item Code') . '</th>
		<th>' . _('Description') . '</th>
		<th>' . _('Quantity') . '</th>
		<th>' . _('UOM') .'</th>
		<th>' . _('Unit Cost') .  '</th>
		<th>' . _('Sub-total') . '</th>
		</tr>';

	$_SESSION['Contract'.$identifier]->total = 0;
	$k = 0;  //row colour counter
	$TotalCost =0;
	foreach ($_SESSION['Contract'.$identifier]->ContractBOM as $ContractComponent) {

		$LineTotal = $ContractComponent->Quantity * $ContractComponent->ItemCost;

		$DisplayLineTotal = locale_money_format($LineTotal,$_SESSION['Contract'.$identifier]->CurrCode);

		if ($k==1){
			echo '<tr class="EvenTableRows">';
			$k=0;
		} else {
			echo '<tr class="OddTableRows">';
			$k=1;
		}

		echo '<td>' . $ContractComponent->StockID . '</td>
			  <td>' . $ContractComponent->ItemDescription . '</td>
			  <td><input type="text" class="number" name="Qty' . $ContractComponent->ComponentID . '" size="11" value="' . locale_number_format($ContractComponent->Quantity, $ContractComponent->DecimalPlaces)  . '" /></td>
			  <td>' . $ContractComponent->UOM . '</td>
			  <td class="number">' . locale_number_format($ContractComponent->ItemCost,4) . '</td>
			  <td class="number">' . $DisplayLineTotal . '</td>
			  <td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier='.$identifier. '&amp;Delete=' . $ContractComponent->ComponentID . '">' . _('Delete') . '</a></td></tr>';
		$TotalCost += $LineTotal;
	}

	$DisplayTotal = locale_money_format($TotalCost,$_SESSION['Contract'.$identifier]->CurrCode);
	echo '<tr><td colspan="6" class="number">' . _('Total Cost') . '</td><td class="number"><b>' . $DisplayTotal . '</b></td></tr></table>';
	echo '<br /><div class="centre"><button type="submit" name="UpdateLines">' . _('Update Lines') . '</button>';
	echo '<button type="submit" name="BackToHeader">' . _('Back To Contract Header') . '</button></div>';

} /*Only display the contract BOM lines if there are any !! */

if (!isset($_GET['Edit'])) {
	$sql="SELECT categoryid,
			categorydescription
		FROM stockcategory
		WHERE stocktype<>'L'
		AND stocktype<>'D'
		ORDER BY categorydescription";
	$ErrMsg = _('The supplier category details could not be retrieved because');
	$DbgMsg = _('The SQL used to retrieve the category details but failed was');
	$result1 = DB_query($sql,$db,$ErrMsg,$DbgMsg);
	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/magnifier.png" title="' . _('Print') . '" alt="" />' .
			' ' . _('Search For Stock Items') . '</p>';
	echo '<table class="selection"><tr>';

	echo ':</tr><tr><td><select name="StockCat">';

	echo '<option selected="true" value="All">' . _('All').'</option>';
	while ($myrow1 = DB_fetch_array($result1)) {
		if (isset($_POST['StockCat']) and $_POST['StockCat']==$myrow1['categoryid']){
			echo '<option selected="True" value="'. $myrow1['categoryid'] . '">' . $myrow1['categorydescription'].'</option>';
		} else {
			echo '<option value="'. $myrow1['categoryid'] . '">' . $myrow1['categorydescription'].'</option>';
		}
	}

	unset($_POST['Keywords']);
	unset($_POST['StockCode']);

	if (!isset($_POST['Keywords'])) {
		$_POST['Keywords']='';
	}

	if (!isset($_POST['StockCode'])) {
		$_POST['StockCode']='';
	}

	echo '</select></td>
		<td>' . _('Enter text extracts in the description') . ':</td>
		<td><input type="text" name="Keywords" size="20" maxlength="25" value="' . $_POST['Keywords'] . '" /></td></tr>
		<tr><td></td>
		<td><font size="3"> <b>' . _('OR') . ' </b></font>' . _('Enter extract of the Stock Code') .
			':</td>
		<td><input type="text" name="StockCode" size="15" maxlength="18" value="' . $_POST['StockCode'] . '" /></td>
		</tr>
		<tr><td></td>
		<td><font size="3"><b>' . _('OR') . ' </b></font><a target="_blank" href="'.$rootpath.'/Stocks.php?">' . _('Create a New Stock Item') . '</a></td></tr>
		</table><br />
		<div class="centre"><button type="submit" name="Search">' . _('Search Now') . '</button>
		</div><br />';


	$PartsDisplayed =0;
}

if (isset($SearchResult)) {

	echo '<table cellpadding="1" class="selection">';

	$TableHeader = '<tr>
					<th>' . _('Code')  . '</th>
					<th>' . _('Description') . '</th>
					<th>' . _('Units') . '</th>
					<th>' . _('Image') . '</th>
					<th>' . _('Quantity') . '</th>
					</tr>';
	echo $TableHeader;

	$Index = 1;
	$k=0; //row colour counter

	while ($myrow=DB_fetch_array($SearchResult)) {

		if ($k==1){
			echo '<tr class="EvenTableRows">';
			$k=0;
		} else {
			echo '<tr class="OddTableRows">';
			$k=1;
		}

		$filename = $myrow['stockid'] . '.jpg';
		if (file_exists( $_SESSION['part_pics_dir'] . '/' . $filename) ) {
			$ImageSource = '<img src="'.$rootpath . '/' . $_SESSION['part_pics_dir'] . '/' . $filename . '" width="50" height="50" />';
		} else {
			$ImageSource = '<i>'._('No Image').'</i>';
		}

		echo '<td>'.$myrow['stockid'].'</td>
				<td>'.$myrow['description'].'</td>
				<td>'.$myrow['units'] . '</td>
				<td>'.$ImageSource.'</td>
				<td><input class="number" type="text" size="6" value="0" name="qty'.$Index.'" /></td>
				<td><input type="hidden" value="'.$myrow['stockid'].'" name="StockID'.$Index.'" /></td>
				</tr>';

		$PartsDisplayed++;
		if ($PartsDisplayed == $Maximum_Number_Of_Parts_To_Show){
			break;
		}
		$Index++;
#end of page full new headings if
	}
#end of while loop
	echo '</table>';
	if ($PartsDisplayed == $Maximum_Number_Of_Parts_To_Show){

	/*$Maximum_Number_Of_Parts_To_Show defined in config.php */

		prnMsg( _('Only the first') . ' ' . $Maximum_Number_Of_Parts_To_Show . ' ' . _('can be displayed') . '. ' .
			_('Please restrict your search to only the parts required'),'info');
	}
	echo '<br /><div class="centre"><button type="submit" name="NewItem">' . _('Add to Contract Bill Of Material') .'</button></div>';
}#end if SearchResults to show

echo '</form>';
include('includes/footer.inc');
?>
