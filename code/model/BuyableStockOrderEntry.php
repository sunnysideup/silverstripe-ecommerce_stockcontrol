<?php
/**
 *@author: Nicolaas [at] Sunny Side Up . Co . Nz
 *@description:
 * keeps a record of the quantity deduction made for each sale.  That is, if we sell 10 widgets in an order then an entry is made in this dataclass for
 * a reduction of ten widgets in the available quantity
 *
 **/

class BuyableStockOrderEntry extends DataObject {

	static $db = array(
		"Quantity" => "Int",
		"IncludeInCurrentCalculation" => "Boolean"
	);

	static $has_one = array(
		"Parent" => "BuyableStockCalculatedQuantity",
		"Order" => "Order",
	);

	static $defaults = array(
		"IncludeInCurrentCalculation" => 1
	);


	//MODEL ADMIN STUFF
	public static $searchable_fields = array(
		"Quantity",
		"IncludeInCurrentCalculation",
		"ParentID",
		"OrderID",
	);

	public static $field_labels = array(
		"Quantity" => "Calculated Quantity On Hand",
		"IncludeInCurrentCalculation" => "Include in Calculation",
		"ParentID" => "Buyable Calculation",
		"OrderID" => "Order"
	);

	public static $summary_fields = array(
		"OrderID",
		"ParentID",
		"Quantity"
	);

	public static $default_sort = "\"LastEdited\" DESC, \"ParentID\" ASC";

	public static $singular_name = "Stock Sale Entry";
		function i18n_singular_name() { return _t("BuyableStockOrderEntry.STOCKSALEENTRY", "Stock Sale Entry");}

	public static $plural_name = "Stock Sale Entries";
		function i18n_plural_name() { return _t("BuyableStockOrderEntry.STOCKSALEENTRIES", "Stock Sale Entries");}

	public function canCreate() {return false;}

	public function canEdit() {return false;}

	public function canDelete() {return false;}

	public function canView() {return $this->canDoAnything();}

	protected function canDoAnything() {
		if(!Permission::check("ADMIN") && !Permission::check("SHOPADMIN")) {
			Security::permissionFailure($this, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
		}
		return true;
	}

	function onAfterWrite() {
		parent::onAfterWrite();
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		if($this->ID) {
			//basic checks
			if(!$this->ParentID) {
				$this->delete();
				user_error("Can not create record without associated buyable.", E_USER_ERROR);
			}
			if(!$this->OrderID) {
				$this->delete();
				user_error("Can not create record without order.", E_USER_ERROR);
			}
			//make sure no duplicates are created
			while($tobeDeleted = DataObject::get_one("BuyableStockOrderEntry", "{$bt}OrderID{$bt} = ".$this->OrderID." AND \"ParentID\" = ".$this->ParentID." AND {$bt}ID{$bt} <> ".$this->ID, false, "\"LastEdited\" ASC")) {
				$toBeDeleted = DataObject::get_one("BuyableStockOrderEntry", "\"OrderID\" = ".$this->OrderID, false, "\"LastEdited\" ASC");
				$toBeDeleted->delete();
				$toBeDeleted->destroy();
				user_error("deleting BuyableStockOrderEntry because there are multiples!", E_USER_ERROR);
			}
		}
	}

}
