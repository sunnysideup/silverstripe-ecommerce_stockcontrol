<?php
/**
 *@author: Nicolaas [at] Sunny Side Up . Co . Nz
 *@description: manual change for a buyable
 * the buyable available quantity can be changed (manually overridden) using this class.
 *
 * All entries link to the BuyableStockCalculatedQuantity object.
 * The BuyableStockCalculatedQuantity objects calculates, for each buyable,
 * how many are available.
 *
 **/

class BuyableStockManualUpdate extends DataObject {

	static $db = array(
		"Quantity" => "Int",
		"ExternalUpdate" => "Boolean"
	);

	static $has_one = array(
		"Parent" => "BuyableStockCalculatedQuantity",
		"Member" => "Member"
	);

	//MODEL ADMIN STUFF

	public static $searchable_fields = array(
		"Quantity",
		"ParentID",
		"MemberID"
	);

	public static $field_labels = array(
		"Quantity",
		"ParentID"  => "Buyable",
		"MemberID"  => "Administrator"
	);

	public static $summary_fields = array(
		"Parent.Name" => "Buyable",
		"Member.FirstName" => "Administrator",
		"Quantity" => "New Quantity"
	);

	public static $default_sort = "\"LastEdited\" DESC, \"ParentID\" ASC";

	public static $singular_name = "Stock Manual Update Entry";
		function i18n_singular_name() { return _t("BuyableStockManualUpdate.STOCKUPDATEENTRY", "Stock Manual Update Entry");}

	public static $plural_name = "Stock Manual Update Entries";
		function i18n_plural_name() { return _t("BuyableStockManualUpdate.STOCKUPDATEENTRIES", "Stock Manual Update Entries");}

	public function canView($member = null) {return $this->canDoAnything();}

	public function canCreate($member = null) {return $this->canDoAnything();}

	public function canEdit($member = null) {return false;}

	public function canDelete() {return false;}

	protected function canDoAnything($member = null) {
		if(!Permission::check("ADMIN")) {
			Security::permissionFailure($this, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
		}
		return true;
	}


	/**
	 *
	 *
	 * TO DO: change Member ID checks to Validate method
	 *
	 *
	 **/


}
