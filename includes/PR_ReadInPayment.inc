<?php
/* $Id$*/
/*PO_ReadInOrder.inc is used by the modify existing order code in PO_Header.php and also by GoodsReceived.php */

	if (isset($_SESSION['PR'.$identifier])){
		unset ($_SESSION['PR'.$identifier]->LineItems);
		unset ($_SESSION['PR'.$identifier]);
	}

	$_SESSION['ExistingOrder'.$identifier]=$_GET['ModifyOrderNumber'];
	$_SESSION['RequireSupplierSelection'] = 0;
	$_SESSION['PR'.$identifier] = new PaymentRequest;

	$_SESSION['PR'.$identifier]->GLLink = $_SESSION['CompanyRecord']['gllink_creditors'];

/*read in all the guff from the selected order into the PO PurchOrder Class variable  */

	$OrderHeaderSQL = "SELECT 
			payrequest.PayeeName,
                        suppliers.suppname,
                        payrequest.supplierno,
                        payrequest.initiator,
                        payrequest.allowprint,
			            payrequest.reqdate,
                        payrequest.authorisedby,
			            payrequest.dateauth,
                        payrequest.dateprinted,
			            payrequest.paidby,
			            payrequest.status,
                        payrequest.currcode
			            FROM payrequest
                                Left JOIN suppliers
							ON payrequest.supplierno = suppliers.supplierid
                                 WHERE payrequest.PaymentNo = '" . $_GET['ModifyOrderNumber'] . "'";

	   $ErrMsg =  _('The payment cannot be retrieved because');
	   $DbgMsg =  _('The SQL statement that was used and failed was');
	   $GetOrdHdrResult = DB_query($OrderHeaderSQL,$ErrMsg,$DbgMsg);

	if (DB_num_rows($GetOrdHdrResult)==1 and !isset($_SESSION['PR'.$identifier]->PaymentNo )) {

			$myrow = DB_fetch_array($GetOrdHdrResult);
			$_SESSION['PR'.$identifier]->PaymentNo = $_GET['ModifyOrderNumber'];
            $_SESSION['PR'.$identifier]->SupplierID = $myrow['supplierno'];
			$_SESSION['PR'.$identifier]->PayeeName = $myrow['PayeeName'];
            $_SESSION['PR'.$identifier]->SupplierNo = $myrow['supplierno'];
			$_SESSION['PR'.$identifier]->ReqDate = $myrow['reqdate'];
			$_SESSION['PR'.$identifier]->CurrCode = $myrow['currcode'];
			$_SESSION['PR'.$identifier]->AllowPrintPR = $myrow['allowprint'];
			$_SESSION['PR'.$identifier]->DatePaymentRequestPrinted = $myrow['dateprinted'];
			$_SESSION['PR'.$identifier]->Initiator = $myrow['initiator'];
			$_SESSION['PR'.$identifier]->Status = $myrow['status'];
			$_SESSION['ExistingOrder'.$identifier] = $_SESSION['PR'.$identifier]->PaymentNo;
            $_SESSION['PR'.$identifier]->AuthDate = $myrow['dateauth'];
            $_SESSION['PR'.$identifier]->Authoriser = $myrow['authorisedby'];
            $_SESSION['PR'.$identifier]->Payer = $myrow['paidby'];
                        
                        /*now populate the line PO array with the purchase order details records */

			  $LineItemsSQL = "SELECT paynoid,
									chequeno,
                                    bankact,
									bankaccounts.bankaccountname,
									account,
                                    chartmaster.accountname,
                                    narrative,
                                    amount,
                                    locgroup,
                                    locationgroup.groupname,
                                    locsection,
                                    locationsection.sectionname,
                                    loccost,
                                    locationcost.costname,
                                    source,
                                    source.sourcedescription,
									exrate,
									functionalexrate,
									payrequestdetails.paymenttypes
								FROM payrequestdetails
								LEFT JOIN bankaccounts
									ON payrequestdetails.bankact=bankaccounts.accountcode
								LEFT JOIN payrequest
									ON payrequest.PaymentNo=payrequestdetails.PaymentNo
								LEFT JOIN chartmaster
									ON payrequestdetails.account=chartmaster.accountcode
                                LEFT JOIN locationgroup
									ON payrequestdetails.locgroup=locationgroup.groupid
								LEFT JOIN locationsection
									ON payrequestdetails.locsection=locationsection.sectionid
								LEFT JOIN locationcost
									ON payrequestdetails.loccost=locationcost.costid
                                LEFT JOIN source
									ON payrequestdetails.source=source.sourceref
								WHERE payrequestdetails.PaymentNo ='" . $_GET['ModifyOrderNumber'] . "'
								ORDER BY paynoid";

			$ErrMsg =  _('The lines on the purchase order cannot be retrieved because');
			$DbgMsg =  _('The SQL statement that was used to retrieve the purchase order lines was');
			$LineItemsResult = DB_query($LineItemsSQL,$ErrMsg,$DbgMsg);

		  if (DB_num_rows($LineItemsResult) > 0) {

				while ($myrow=DB_fetch_array($LineItemsResult)) {

					 if (is_null($myrow['account'])){
						$GLCode = '';
					 } else {
						$GLCode = $myrow['account'];
					 }
					$_SESSION['PR'.$identifier]->add_to_order($_SESSION['PR'.$identifier]->LinesOnOrder+1,
															$myrow['chequeno'],
															$myrow['bankact'],
															$myrow['bankaccountname'],
                                                            $GLCode,
															$myrow['accountname'],
															stripslashes($myrow['narrative']),
															$myrow['amount'],
															$myrow['locgroup'],
															$myrow['groupname'],
															$myrow['locsection'],
															$myrow['sectionname'],
															$myrow['loccost'],
															$myrow['costname'],
															$myrow['source'],
															$myrow['sourcedescription'],
															$myrow['exrate'],
															$myrow['functionalexrate'],
															$myrow['paymenttypes']
															 );

					$_SESSION['PR'.$identifier]->LineItems[$_SESSION['PR'.$identifier]->LinesOnOrder]->PODetailRec = $myrow['paynoid'];
					  /*Needed for receiving goods and GL interface */
			 } /* line PO from purchase order details */
	  } //end is there were lines on the order

   } // end if there was a header for the order
?>