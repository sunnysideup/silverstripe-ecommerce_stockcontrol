<?php
/**
 *@author: Nicolaas [at] Sunny Side Up . Co . Nz
 *@description: manual top-up for a product
 * at any stage, the product available quantity can be changed (manually overridden) using this class
 *
 **/

class ProductStockManualUpdate extends DataObject {

	static $db = array(
		"Quantity" => "Int",
	);

	static $has_one = array(
		"Parent" => "ProductStockCalculatedQuantity",
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
		"ParentID"  => "Product",
		"MemberID"  => "Administrator"
	);

	public static $summary_fields = array(
		"Parent.Name" => "Product",
		"Member.FirstName" => "Administrator",
		"Quantity" => "Quantity Deducted"
	);

	public static $default_sort = "\"LastEdited\" DESC, \"ParentID\" ASC";

	public static $singular_name = "Product Stock Manual Update Entry";

	public static $plural_name = "Product Stock Manual Update Entries";

	public function canView() {return $this->canDoAnything();}

	public function canCreate() {return $this->canDoAnything();}

	public function canEdit() {return false;}

	public function canDelete() {return false;}

	protected function canDoAnything() {
		if(!Permission::check("ADMIN")) {
			Security::permissionFailure($this, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
		}
		return true;
	}

	function onAfterWrite() {
		parent::onAfterWrite();
		/*
		if(!$this->ParentID) {
			$this->delete();
			user_error("Can not create record without associated product", E_USER_ERROR);
		}
		*/
		if(!$this->MemberID) {
			$this->delete();
			user_error("Can not create record without associated administrator.", E_USER_ERROR);
		}
	}

	function onBeforeWrite() {
		if(!$this->MemberID) {
			if($m = Member::currentUser()) {
				$this->MemberID = $m->ID;
			}
		}
		parent::onBeforeWrite();
	}
}
