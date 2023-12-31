<?php
/* $Revision: 1.13 $ */
/* $Id$*/

include('includes/DefineCartClass.php');
include('includes/DefineSerialItems.php');

include('includes/session.inc');
$title = _('Specify Dispatched Controlled Items');

/* Session started in header.inc for password checking and authorisation level check */
include('includes/header.inc');

echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/magnifier.png" title="" alt="" />' . ' ' . $title . '</p>';

if (isset($_GET['LineNo'])){
        $LineNo = (int)$_GET['LineNo'];
} elseif (isset($_POST['LineNo'])){
        $LineNo = (int)$_POST['LineNo'];
} else {
	echo '<div class="centre"><a href="' . $rootpath . '/ConfirmDispatch_Invoice.php">'.
		_('Select a line item to invoice').'</a><br />';
	echo '<br />';
	prnMsg( _('This page can only be opened if a line item on a sales order to be invoiced has been selected') . '. ' . _('Please do that first'),'error');
	echo '</div>';
	include('includes/footer.inc');
	exit;
}

if (!isset($_SESSION['Items']) OR !isset($_SESSION['ProcessingOrder'])) {
	/* This page can only be called with a sales order number to invoice */
	echo '<div class="centre"><a href="' . $rootpath . '/SelectSalesOrder.php">'. _('Select a sales order to invoice').
		'</a><br />';
	prnMsg( _('This page can only be opened if a sales order and line item has been selected Please do that first'),'error');
	echo '</div>';
	include('includes/footer.inc');
	exit;
}


/*Save some typing by referring to the line item class object in short form */
$LineItem = &$_SESSION['Items']->LineItems[$LineNo];


//Make sure this item is really controlled
if ( $LineItem->Controlled != 1 ){
	echo '<div class="centre"><a href="' . $rootpath . '/ConfirmDispatch_Invoice.php">'. _('Back to the Sales Order'). '</a></div>';
	echo '<br />';
	prnMsg( _('The line item must be defined as controlled to require input of the batch numbers or serial numbers being sold'),'error');
	include('includes/footer.inc');
	exit;
}

/********************************************
  Get the page going....
********************************************/
echo '<div class="centre">';

echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/magnifier.png" title="" alt="" />'. _('Dispatch of up to').' '. number_format($LineItem->Quantity-$LineItem->QtyInv, $LineItem->DecimalPlaces). ' '. _('Controlled items').' ' . $LineItem->StockID  . ' - ' . $LineItem->ItemDescription . ' '. _('on order').' ' . $_SESSION['Items']->OrderNo . ' '. _('to'). ' ' . $_SESSION['Items']->CustomerName . '</p>';

/** vars needed by InputSerialItem : **/
$StockID = $LineItem->StockID;
$RecvQty = $LineItem->Quantity-$LineItem->QtyInv;
$ItemMustExist = true;  /*Can only invoice valid batches/serial numbered items that exist */
$LocationOut = $_SESSION['Items']->Location;
$InOutModifier=1;
$ShowExisting=false;

include ('includes/OutputSerialItems.php');

/*TotalQuantity set inside this include file from the sum of the bundles
of the item selected for dispatch */
$_SESSION['Items']->LineItems[$LineNo]->QtyDispatched = $TotalQuantity;

echo '<br /><div style="text-align: right"><a href="'. $rootpath. '/ConfirmDispatch_Invoice.php">'. _('Back to Confirmation of Dispatch') . '/' . _('Invoice'). '</a></div>';

include('includes/footer.inc');
exit;
?>
