<?php

/* $Id$*/

include('includes/session.inc');

if (isset($_POST['PrintPDF'])
	and isset($_POST['FromCriteria'])
	and strlen($_POST['FromCriteria'])>=1
	and isset($_POST['ToCriteria'])
	and strlen($_POST['ToCriteria'])>=1){

	include('includes/PDFStarter.php');
	$pdf->addInfo('Title',_('Aged Supplier Listing'));
	$pdf->addInfo('Subject',_('Aged Suppliers'));
	$FontSize=12;
	$PageNumber=0;
	$line_height=12;

	  /*Now figure out the aged analysis for the Supplier range under review */

	if ($_POST['All_Or_Overdues']=='All'){
		$SQL = "SELECT suppliers.supplierid,
						suppliers.suppname,
						currencies.currency,
						currencies.currabrev,
						paymentterms.terms,
						SUM(supptrans.ovamount + supptrans.ovgst  - supptrans.alloc) as balance,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= paymentterms.daysbeforedue THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1', 'MONTH') . "), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= 0 THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						END) AS due,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						ELSE
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1', 'MONTH') ."), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= " . $_SESSION['PastDueDays1'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						END) AS overdue1,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue	AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						ELSE
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1', 'MONTH') . "), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= " . $_SESSION['PastDueDays2'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						END) AS overdue2
				FROM suppliers INNER JOIN paymentterms
				ON suppliers.paymentterms = paymentterms.termsindicator
				INNER JOIN currencies
				ON suppliers.currcode = currencies.currabrev
				INNER JOIN supptrans
				ON suppliers.supplierid = supptrans.supplierno
				WHERE suppliers.supplierid >= '" . $_POST['FromCriteria'] . "'
				AND suppliers.supplierid <= '" . $_POST['ToCriteria'] . "'
				AND  suppliers.currcode ='" . $_POST['Currency'] . "'
				GROUP BY suppliers.supplierid,
						suppliers.suppname,
						currencies.currency,
						paymentterms.terms,
						paymentterms.daysbeforedue,
						paymentterms.dayinfollowingmonth
				HAVING SUM(supptrans.ovamount + supptrans.ovgst - supptrans.alloc) <>0";

	} else {

		$SQL = "SELECT suppliers.supplierid,
						suppliers.suppname,
						currencies.currency,
						currencies.currabrev,
						paymentterms.terms,
						SUM(supptrans.ovamount + supptrans.ovgst - supptrans.alloc) AS balance,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
							CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= paymentterms.daysbeforedue  THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						ELSE
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1', 'MONTH') . "), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= 0 THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						END) AS due,
						Sum(CASE WHEN paymentterms.daysbeforedue > 0 THEN
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						ELSE
							CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1', 'MONTH') . "), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= " . $_SESSION['PastDueDays1'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						END) AS overdue1,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue	AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						ELSE
							CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1', 'MONTH') . "), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= " . $_SESSION['PastDueDays2'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
						END) AS overdue2
						FROM suppliers INNER JOIN paymentterms
						ON suppliers.paymentterms = paymentterms.termsindicator
						INNER JOIN currencies
						ON suppliers.currcode = currencies.currabrev
						INNER JOIN supptrans
						ON suppliers.supplierid = supptrans.supplierno
						WHERE suppliers.supplierid >= '" . $_POST['FromCriteria'] . "'
						AND suppliers.supplierid <= '" . $_POST['ToCriteria'] . "'
						AND suppliers.currcode ='" . $_POST['Currency'] . "'
						GROUP BY suppliers.supplierid,
							suppliers.suppname,
							currencies.currency,
							paymentterms.terms,
							paymentterms.daysbeforedue,
							paymentterms.dayinfollowingmonth
						HAVING SUM(IF (paymentterms.daysbeforedue > 0,
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END,
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1', 'MONTH') . "), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= " . $_SESSION['PastDueDays1'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END)) > 0";

	}

	$SupplierResult = DB_query($SQL,$db,'','',False,False); /*dont trap errors */

	if (DB_error_no($db) !=0) {
		$title = _('Aged Supplier Account Analysis') . ' - ' . _('Problem Report') ;
		include('includes/header.inc');
		prnMsg(_('The Supplier details could not be retrieved by the SQL because') .  ' ' . DB_error_msg($db),'error');
		echo '<br /><a href="' . $rootpath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($debug==1){
			echo '<br />' . $SQL;
		}
		include('includes/footer.inc');
		exit;
	}

	include ('includes/PDFAgedSuppliersPageHeader.inc');
	$TotBal = 0;
	$TotDue = 0;
	$TotCurr = 0;
	$TotOD1 = 0;
	$TotOD2 = 0;

	$ListCount = DB_num_rows($SupplierResult); // UldisN

	while ($AgedAnalysis = DB_fetch_array($SupplierResult,$db)){

		$DisplayDue = locale_money_format($AgedAnalysis['due']-$AgedAnalysis['overdue1'],$AgedAnalysis['currabrev']);
		$DisplayCurrent = locale_money_format($AgedAnalysis['balance']-$AgedAnalysis['due'],$AgedAnalysis['currabrev']);
		$DisplayBalance = locale_money_format($AgedAnalysis['balance'],$AgedAnalysis['currabrev']);
		$DisplayOverdue1 = locale_money_format($AgedAnalysis['overdue1']-$AgedAnalysis['overdue2'],$AgedAnalysis['currabrev']);
		$DisplayOverdue2 = locale_money_format($AgedAnalysis['overdue2'],$AgedAnalysis['currabrev']);

		$TotBal += $AgedAnalysis['balance'];
		$TotDue += ($AgedAnalysis['due']-$AgedAnalysis['overdue1']);
		$TotCurr += ($AgedAnalysis['balance']-$AgedAnalysis['due']);
		$TotOD1 += ($AgedAnalysis['overdue1']-$AgedAnalysis['overdue2']);
		$TotOD2 += $AgedAnalysis['overdue2'];

		$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,220-$Left_Margin,$FontSize,$AgedAnalysis['supplierid'] . ' - ' . $AgedAnalysis['suppname'],'left');
		$LeftOvers = $pdf->addTextWrap(220,$YPos,60,$FontSize,$DisplayBalance,'right');
		$LeftOvers = $pdf->addTextWrap(280,$YPos,60,$FontSize,$DisplayCurrent,'right');
		$LeftOvers = $pdf->addTextWrap(340,$YPos,60,$FontSize,$DisplayDue,'right');
		$LeftOvers = $pdf->addTextWrap(400,$YPos,60,$FontSize,$DisplayOverdue1,'right');
		$LeftOvers = $pdf->addTextWrap(460,$YPos,60,$FontSize,$DisplayOverdue2,'right');

		$YPos -=$line_height;
		if ($YPos < $Bottom_Margin + $line_height){
			  include('includes/PDFAgedSuppliersPageHeader.inc');
		}

		if ($_POST['DetailedReport']=='Yes'){

		   $FontSize=6;
		   /*draw a line under the Supplier aged analysis*/
		   $pdf->line($Page_Width-$Right_Margin, $YPos+10,$Left_Margin, $YPos+10);

		   $sql = "SELECT systypes.typename,
							supptrans.suppreference,
							supptrans.trandate,
							(supptrans.ovamount + supptrans.ovgst - supptrans.alloc) as balance,
							CASE WHEN paymentterms.daysbeforedue > 0 THEN
								CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= paymentterms.daysbeforedue  THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1', 'MONTH') . "), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= 0 THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
							END AS due,
							CASE WHEN paymentterms.daysbeforedue > 0 THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue	   AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
							ELSE
								CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1','MONTH') . "), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= " . $_SESSION['PastDueDays1'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
							END AS overdue1,
							CASE WHEN paymentterms.daysbeforedue > 0 THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
							ELSE
								CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(supptrans.trandate, " . INTERVAL('1','MONTH') . "), " . INTERVAL('(paymentterms.dayinfollowingmonth - DAYOFMONTH(supptrans.trandate))', 'DAY') . ")) >= " . $_SESSION['PastDueDays2'] . ") THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
							END AS overdue2
						FROM suppliers
						LEFT JOIN paymentterms
							ON suppliers.paymentterms = paymentterms.termsindicator
						LEFT JOIN supptrans
							ON suppliers.supplierid = supptrans.supplierno
						LEFT JOIN systypes
							ON systypes.typeid = supptrans.type
						WHERE ABS(supptrans.ovamount + supptrans.ovgst - supptrans.alloc) >0.009
							AND supptrans.settled = 0
							AND supptrans.supplierno = '" . $AgedAnalysis["supplierid"] . "'";

			$DetailResult = DB_query($sql,$db,'','',False,False); /*dont trap errors - trapped below*/
			if (DB_error_no($db) !=0) {
			$title = _('Aged Supplier Account Analysis - Problem Report');
			include('includes/header.inc');
			prnMsg(_('The details of outstanding transactions for Supplier') . ' - ' . $AgedAnalysis['supplierid'] . ' ' . _('could not be retrieved because') . ' - ' . DB_error_msg($db),'error');
			echo '<br /><a href="' . $rootpath . '/index.php">' . _('Back to the menu') . '</a>';
			if ($debug==1){
			   echo '<br />' . _('The SQL that failed was') . '<br />' . $sql;
			}
			include('includes/footer.inc');
			exit;
			}

			while ($DetailTrans = DB_fetch_array($DetailResult)){

				$LeftOvers = $pdf->addTextWrap($Left_Margin+5,$YPos,60,$FontSize,$DetailTrans['typename'],'left');
				$LeftOvers = $pdf->addTextWrap($Left_Margin+65,$YPos,50,$FontSize,$DetailTrans['suppreference'],'left');
				$DisplayTranDate = ConvertSQLDate($DetailTrans['trandate']);
				$LeftOvers = $pdf->addTextWrap($Left_Margin+105,$YPos,70,$FontSize,$DisplayTranDate,'left');

				$DisplayDue = locale_money_format($DetailTrans['due']-$DetailTrans['overdue1'],$AgedAnalysis['currabrev']);
				$DisplayCurrent = locale_money_format($DetailTrans['balance']-$DetailTrans['due'],$AgedAnalysis['currabrev']);
				$DisplayBalance = locale_money_format($DetailTrans['balance'],$AgedAnalysis['currabrev']);
				$DisplayOverdue1 = locale_money_format($DetailTrans['overdue1']-$DetailTrans['overdue2'],$AgedAnalysis['currabrev']);
				$DisplayOverdue2 = locale_money_format($DetailTrans['overdue2'],$AgedAnalysis['currabrev']);

				$LeftOvers = $pdf->addTextWrap(220,$YPos,60,$FontSize,$DisplayBalance,'right');
				$LeftOvers = $pdf->addTextWrap(280,$YPos,60,$FontSize,$DisplayCurrent,'right');
				$LeftOvers = $pdf->addTextWrap(340,$YPos,60,$FontSize,$DisplayDue,'right');
				$LeftOvers = $pdf->addTextWrap(400,$YPos,60,$FontSize,$DisplayOverdue1,'right');
				$LeftOvers = $pdf->addTextWrap(460,$YPos,60,$FontSize,$DisplayOverdue2,'right');

				$YPos -=$line_height;
				if ($YPos < $Bottom_Margin + $line_height){
				$PageNumber++;
				include('includes/PDFAgedSuppliersPageHeader.inc');
				$FontSize=6;
				}
			} /*end while there are detail transactions to show */
			/*draw a line under the detailed transactions before the next Supplier aged analysis*/
		   $pdf->line($Page_Width-$Right_Margin, $YPos+10,$Left_Margin, $YPos+10);
		   $FontSize=8;
		} /*Its a detailed report */
	} /*end Supplier aged analysis while loop */

	$YPos -=$line_height;
	if ($YPos < $Bottom_Margin + (2*$line_height)){
		$PageNumber++;
		include('includes/PDFAgedSuppliersPageHeader.inc');
	} elseif ($_POST['DetailedReport']=='Yes') {
		//dont do a line if the totals have to go on a new page
		$pdf->line($Page_Width-$Right_Margin, $YPos+10 ,220, $YPos+10);
	}

	$DisplayTotBalance = locale_money_format($TotBal,$_SESSION['CompanyRecord']['currencydefault']);
	$DisplayTotDue = locale_money_format($TotDue,$_SESSION['CompanyRecord']['currencydefault']);
	$DisplayTotCurrent = locale_money_format($TotCurr,$_SESSION['CompanyRecord']['currencydefault']);
	$DisplayTotOverdue1 = locale_money_format($TotOD1,$_SESSION['CompanyRecord']['currencydefault']);
	$DisplayTotOverdue2 = locale_money_format($TotOD2,$_SESSION['CompanyRecord']['currencydefault']);

	$LeftOvers = $pdf->addTextWrap(220,$YPos,60,$FontSize,$DisplayTotBalance,'right');
	$LeftOvers = $pdf->addTextWrap(280,$YPos,60,$FontSize,$DisplayTotCurrent,'right');
	$LeftOvers = $pdf->addTextWrap(340,$YPos,60,$FontSize,$DisplayTotDue,'right');
	$LeftOvers = $pdf->addTextWrap(400,$YPos,60,$FontSize,$DisplayTotOverdue1,'right');
	$LeftOvers = $pdf->addTextWrap(460,$YPos,60,$FontSize,$DisplayTotOverdue2,'right');

	$YPos -=$line_height;
	$pdf->line($Page_Width-$Right_Margin, $YPos ,220, $YPos);

	if ($ListCount == 0) {
		$title = _('Aged Supplier Analysis');
		include('includes/header.inc');
		prnMsg( _('There are no results so the PDF is empty'), 'info');
		include('includes/footer.inc');
	} else {
		$pdf->OutputD($_SESSION['DatabaseName'] . '_AggedSupliers_' . date('Y-m-d').'.pdf');
	}
	$pdf->__destruct();
} else { /*The option to print PDF was not hit */

	$title = _('Aged Supplier Analysis');
	include('includes/header.inc');

	echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $title.'</p><br />';

	if (!isset($_POST['FromCriteria']) or !isset($_POST['ToCriteria'])) {

	/*if $FromCriteria is not set then show a form to allow input	*/

		echo '<form sction="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<table class="selection">
			<tr>
				<td>' . _('From Supplier Code') . ':</font></td>
				<td><input tabindex="1" type="text" maxlength="6" size="7" name="FromCriteria" value="1" /></td>
			</tr>
			<tr>
				<td>' . _('To Supplier Code') . ':</td>
				<td><input tabindex="2" type="text" maxlength="6" size="7" name="ToCriteria" value="zzzzzz" /></td>
			</tr>
			<tr>
				<td>' . _('All balances or overdues only') . ':' . '</td>
				<td><select tabindex="3" name="All_Or_Overdues">
					<option selected="selected" value="All">' . _('All suppliers with balances') . '</option>
					<option value="OverduesOnly">' . _('Overdue accounts only') . '</option>
					</select></td>
			</tr>
			<tr>
				<td>' . _('For suppliers trading in') . ':' . '</td>
				<td><select tabindex="4" name="Currency">';

		$sql = "SELECT currency, currabrev FROM currencies";
		$result=DB_query($sql,$db);

		while ($myrow=DB_fetch_array($result)){
			if ($myrow['currabrev'] == $_SESSION['CompanyRecord']['currencydefault']){
				echo '<option selected="selected" value="' . $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
			} else {
				echo '<option value="' . $myrow['currabrev'] . '">' . $myrow['currency'] . '</option>';
			}
		}
		echo '</select></td>
			</tr>
			<tr>
				<td>' . _('Summary or Detailed Report') . ':' . '</td>
				<td><select tabindex="5" name="DetailedReport">
					<option selected="selected" value="No">' . _('Summary Report')  . '</option>
					<option value="Yes">' . _('Detailed Report')  . '</option>
					</select></td>
			</tr>
			</table>
			<br />
			<div class="centre">
				<button tabindex="6" type="submit" name="PrintPDF">' . _('Print PDF') . '</button>
			</div><br />';
	}
	include('includes/footer.inc');
} /*end of else not PrintPDF */

?>
