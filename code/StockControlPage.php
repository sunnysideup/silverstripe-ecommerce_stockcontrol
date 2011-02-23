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

	static $icon = "mysite/images/treeicons/StockControlPage";

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
		ProductStockCalculatedQuantity::add_all_products();
		$dos = new DataObjectSet();
		$products = DataObject::get("Product");
		foreach($products as $product) {
			$product->CalculatedQuantity = ProductStockCalculatedQuantity::get_quantity_by_product_id($product->ID);
			$product->VariationQuantities = $this->StockVariationObjects($product->ID);
			$product->StockControlPage = $this;
		}
		return $products;
	}

	function StockVariationObjects($ProductID) {
		$dos = new DataObjectSet();
		$variations = DataObject::get("ProductVariation", "\"ProductID\" = ".$ProductID);
		if($variations) {
			foreach($variations as $variation) {
				$variation->CalculatedQuantity = ProductStockCalculatedQuantity::get_quantity_by_product_id($variation->ID);
				$variation->StockControlPage = $this;
			}
			return $variations;
		}
		else {
			return null;
		}
	}


	function update($request = null) {
		$table = $request->param("ID");
		$id = intval($request->param("OtherID"));
		$newValue = intval($request->getVar("v"));
		if($memberID = Member::currentUserID()) {
			if(class_exists($table) && $id && ($newValue || $newValue === 0)) {
				if($page = DataObject::get_by_id($table, $id)) {
					if($page instanceOf Product) {
						$parent = ProductStockCalculatedQuantity::get_by_product_id($id);
					}
					elseif($page instanceOf ProductVariation) {
						$parent = ProductStockCalculatedQuantity::get_by_product_variation_id($id);
					}
					else {
						user_error("$table is not an instance of Product or Product Variation", E_ERROR);
					}
					if($parent) {
						$page->Stock = $newValue;
						return $parent->Name . " quantity updated to ".$newValue;
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
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$table = $request->param("ID");
		$id = intval($request->param("OtherID"));
		if($table == "product") {
			$parent = DataObject::get_one("ProductStockCalculatedQuantity", "{$bt}ProductID{$bt} = '".$id."'");
		}
		elseif($table == "variation") {
			$parent = DataObject::get_one("ProductStockCalculatedQuantity", "{$bt}ProductVariationID{$bt} = '".$id."'");
		}
		else {
			user_error("could not find class: derived from ($table) for history", E_ERROR);
		}
		if($parent) {
			$parent->ManualUpdates = DataObject::get("ProductStockManualUpdate", "\"ParentID\" = ".$parent->ID);
			$parent->OrderEntries = DataObject::get("ProductStockOrderEntry", "\"ParentID\" = ".$parent->ID);
			return $this->customise($parent)->renderWith("AjaxStockControlPageHistory");
		}
		else {
			return " could not find historical data";
		}
	}

}
