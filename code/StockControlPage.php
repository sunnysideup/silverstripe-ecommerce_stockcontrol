<?php
/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_stockcontrol
 * @description:
 *  This is the central management page for organising stock control
 *  You will need to "turn on" the MinMaxModifier and add MinMaxModifier::set_use_stock_quantities(true)
 *  to get this page working.
 *
 *
 **/


class StockControlPage extends Page {

	static $icon = "ecommerce_stockcontrol/images/treeicons/StockControlPage";

	static $defaults = array(
		"ShowInMenus" => 0,
		"ShowInSearch" => 0
	);

	function getCMSFields() {
		$fields = parent::getCMSFields();
		return $fields;
	}

	function canCreate() {
		if(!DataObject::get_one("SiteTree", "\"ClassName\" = 'StockControlPage'") && MinMaxModifier::get_use_stock_quantities()) {
			return true;
		}
		return false;
	}

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		if(!DataObject::get_one("SiteTree", "\"ClassName\" = 'StockControlPage'") && MinMaxModifier::get_use_stock_quantities()) {
			$page = new StockControlPage();
			$page->URLSegment = "stock-manager";
			$page->Title = "Stock Manager";
			$page->MetaTitle = "Stock Manager";
			$page->MenuTitle = "Stock Manager";
			$page->writeToStage('Stage');
			$page->publish('Stage', 'Live');
			if(method_exists('DB', 'alteration_message')) DB::alteration_message('Stock Control Page Created', 'created');
		}
	}


}

class StockControlPage_Controller extends Page_Controller {

	//TODO: move all this to CMS


	function init() {
		// Only administrators can run this method
		if(!Permission::check("ADMIN")) {
			Security::permissionFailure($this, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
		}
		parent::init();
		Requirements::themedCSS("StockControlPage");
		Requirements::javascript("ecommerce_stockcontrol/javascript/StockControlPage.js");
		$url = Director::absoluteURL($this->Link()."update/");
		Requirements::customScript("StockControlPageURL = '".$url."'");
	}

	function StockProductObjects() {
		$buyableStockCalculatedQuantities = DataObject::get("BuyableStockCalculatedQuantity");
		if($buyableStockCalculatedQuantities) {
			foreach($buyableStockCalculatedQuantities as $buyableStockCalculatedQuantity) {
				$buyable->CalculatedQuantity = BuyableStockCalculatedQuantity::get_quantity_by_buyable($buyable);
				$buyable->StockControlPage = $this;
			}
			return $buyableStockCalculatedQuantities;
		}
	}

	function update($request = null) {
		$table = $request->param("ID");
		$id = intval($request->param("OtherID"));
		$newValue = intval($request->getVar("v"));
		if($memberID = Member::currentUserID()) {
			if(class_exists($table) && $id && ($newValue || $newValue === 0)) {
				if($buyable = DataObject::get_by_id($table, $id)) {
					if($buyable) {
						$buyable->setActualQuantity($newValue);
						return $buyable->Name . " quantity updated to ".$newValue;
					}
					else {
						user_error("Could not create Calculation object", E_ERROR);
					}
				}
				else {
					user_error("could not find record: $table, $id ", E_ERROR);
				}
			}
			else {
				user_error("data object specified: $table or id: $id or newValue: $newValue is not valid", E_ERROR);
			}
		}
		else {
			user_error("you need to be logged-in to make the changes", E_ERROR);
		}
	}

 	function history($request = null) {
		$id = intval($request->param("ID"));
		$buyableStockCalculatedQuantity = DataObject::get_by_id("BuyableStockCalculatedQuantity", $id);
		if($buyableStockCalculatedQuantity) {
			$buyableStockCalculatedQuantity->ManualUpdates = DataObject::get("BuyableStockManualUpdate", "\"ParentID\" = ".$parent->ID);
			$buyableStockCalculatedQuantity->OrderEntries = DataObject::get("BuyableStockOrderEntry", "\"ParentID\" = ".$parent->ID);
			return $this->customise($buyableStockCalculatedQuantity)->renderWith("AjaxStockControlPageHistory");
		}
		else {
			return " could not find historical data";
		}
 	}


}
