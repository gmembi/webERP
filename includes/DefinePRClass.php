<?php
/* Definition of the PurchOrder class to hold all the information for a purchase order and delivery
*/


Class PaymentRequest {

	var $LineItems; /*array of objects of class LineDetails using the product id as the pointer */
	var $CurrCode;
	var $ReqDate;
	var $Initiator;
    var $SupplierID;
	var $PaymentNo; /*Only used for modification of existing orders otherwise only established when order committed */
	var $LinesOnOrder;
	var $DatePaymentRequestPrinted;
	var $Total;
	var $GLLink; /*Is the GL link to stock activated only checked when order initiated or reading in for modification */
	var $Status;
	var $AllowPrintPR;
	var $PaymentTerms;
    var $PayeeName;
	var $AuthDate;
	var $Authoriser;
    var $Payer;
    var $GLItemCounter;

	function __construct(){
	/*Constructor function initialises a new purchase order object */
		$this->LineItems = array();
		$this->total=0;
		$this->LinesOnOrder=0;
	}

	function PaymentRequest() {
		self::__construct();
	}

	function add_to_order($LineNo,
	$chequeno,
	$bankact,
	$bankaccountname,
	$GLCode,
	$accountname,
	$narrative,
	$amount,
	$locgroup,
	$groupname,
	$locsection,
	$sectionname,
	$loccost,
	$costname,
	$source,
	$sourcedescription,
	$exrate,
	$functionalexrate,
	$paymenttypes,
	$completed){

		if ($amount!=0 and isset($amount)){

			$this->LineItems[$LineNo] = new LineDetails($LineNo,
			$chequeno,
			$bankact,
			$bankaccountname,
			$GLCode,
			$accountname,
			$narrative,
			$amount,
			$locgroup,
			$groupname,
			$locsection,
			$sectionname,
			$loccost,
			$costname,
			$source,
			$sourcedescription,
			$exrate,
			$functionalexrate,
			$paymenttypes,
			$completed									);
			$this->LinesOnOrder++;
			Return 1;
		}
		Return 0;
	}

	function update_order_item($LineNo,
	$bankact,
	$GLCode,
	$LGroup,
	$LSection,
	$LCost,
	$Source,
	$amount
								){

									$this->LineItems[$LineNo]->BankAct = $bankact;
									$this->LineItems[$LineNo]->GLCode = $GLCode;
									$this->LineItems[$LineNo]->LGroup = $LGroup;
									$this->LineItems[$LineNo]->LSection = $LSection;
									$this->LineItems[$LineNo]->LCost = $LCost;
									$this->LineItems[$LineNo]->Source = $Source;
									$this->LineItems[$LineNo]->Amount = $amount;
	}

	function remove_from_order(&$LineNo){
		 $this->LineItems[$LineNo]->Deleted = True;
	}


	function Any_Already_Received(){
		/* Checks if there have been deliveries or invoiced entered against any of the line items */
		if (count($this->LineItems)>0){
		   foreach ($this->LineItems as $OrderedItems) {
			if ($OrderedItems->QtyReceived !=0 OR $OrderedItems->QtyInv !=0){
				return 1;
			}
		   }
		}
		return 0;
	}

	function Any_Lines_On_A_Shipment(){
		/* Checks if any of the line items are on a shipment */
		if (count($this->LineItems)>0){
		   foreach ($this->LineItems as $OrderedItems) {
			if ($OrderedItems->ShiptRef !=''){
				return $OrderedItems->ShiptRef;
			}
		   }
		}
		return 0;
	}
	function Some_Already_Received($LineNo){
		/* Checks if there have been deliveries or amounts invoiced against a specific line item */
		if (count($this->LineItems)>0 and isset($this->LineItems[$LineNo])){
		   if ($this->LineItems[$LineNo]->QtyReceived !=0 or $this->LineItems[$LineNo]->QtyInv !=0){
			return 1;
		   }
		}
		return 0;
	}

	function Order_Value() {
		$TotalValue=0;
		foreach ($this->LineItems as $OrderedItems) {
			if ($OrderedItems->Deleted == False){
				$TotalValue += ($OrderedItems->Price)*($OrderedItems->Quantity);
			}
		}
		return $TotalValue;
	}

	function AllLinesReceived(){
		foreach ($this->LineItems as $OrderedItems) {
			if (($OrderedItems->QtyReceived + $OrderedItems->ReceiveQty) < $OrderedItems->Quantity){
				return 0;
			}
		}
		return 1; //all lines must be fully received
	}

	function SomethingReceived(){
		foreach ($this->LineItems as $OrderedItems) {
			if ($OrderedItems->ReceiveQty !=0){
				return 1;
			}
		}
		return 0; //nowt received
	}

} /* end of class defintion */

Class LineDetails {
/* PurchOrderDetails */
	Var $LineNo;
	Var $PODetailRec;
	Var $StockID;
	Var $ItemDescription;
	Var $DecimalPlaces;
	Var $GLCode;
	Var $GLActName;
	Var $Quantity;
	Var $Price;
	Var $Units;
	Var $ReqDelDate;
	Var $QtyInv;
	Var $QtyReceived;
	Var $StandardCost;
	var $ShiptRef;
	var $Completed;
	Var $JobRef;
	var $ConversionFactor;
	var $SuppliersUnit;
	Var $Suppliers_PartNo;
	Var $LeadTime;
	Var $ReceiveQty; //this receipt of stock
	Var $Deleted;
	Var $Controlled;
	Var $Serialised;
	Var $SerialItems;  /*An array holding the batch/serial numbers and quantities in each batch*/
	Var $AssetID;

	function __construct (	$LineNo,
	$chequeno,
	$bankact,
	$bankaccountname,
	$GLCode,
	$accountname,
	$narrative,
	$amount,
	$locgroup,
	$groupname,
	$locsection,
	$sectionname,
	$loccost,
	$costname,
	$source,
	$sourcedescription,
	$exrate,
	$functionalexrate,
	$paymenttypes,
	$completed
						)	{

	/* Constructor function to add a new LineDetail object with passed params */
	$this->LineNo = $LineNo;
	$this->Chequeno =$chequeno;
	$this->Bankact = $bankact;
	$this->Bankaccountname = $bankaccountname;
	$this->GLCode = $GLCode;
	$this->Accountname = $accountname;
	$this->Narrative = $narrative;
	$this->Amount = $amount;
	$this->LGroup = $locgroup;
	$this->groupname = $groupname;
	$this->LSection = $locsection;
	$this->sectionname = $sectionname;
	$this->LCost = $loccost;
	$this->costname = $costname;
	$this->source = $source;
	$this->sourcedescription = $sourcedescription;
	$this->ExRate = $exrate;
	$this->Total_Amount = $Total_Amount;
	$this->FunctionalExrate = $functionalexrate;
	$this->PaymentTypes = $paymenttypes;
	$this->Completed = $completed;

	}
	function LineDetails($LineNo,
	$chequeno,
	$bankact,
	$bankaccountname,
	$GLCode,
	$accountname,
	$narrative,
	$amount,
	$locgroup,
	$groupname,
	$locsection,
	$sectionname,
	$loccost,
	$costname,
	$source,
	$sourcedescription,
	$exrate,
	$functionalexrate,
	$paymenttypes,
	$completed
						) {
		self::__construct($LineNo,
		$chequeno,
		$bankact,
		$bankaccountname,
		$GLCode,
		$accountname,
		$narrative,
		$amount,
		$locgroup,
		$groupname,
		$locsection,
		$sectionname,
		$loccost,
		$costname,
		$source,
		$sourcedescription,
		$exrate,
		$functionalexrate,
		$paymenttypes,
		$completed
					);
	}
}
?>
