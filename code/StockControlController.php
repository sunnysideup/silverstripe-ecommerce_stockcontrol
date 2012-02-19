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




class StockControlController extends ContentController {


	protected static  $url_segment ="update-stock";
	public static function get_url_segment(){return self::$url_segment;}
	public static function set_url_segment($s){self::$url_segment = $s;}
	//TODO: move all this to CMS


	function init() {
		// Only administrators can run this method
		if(!Permission::check("ADMIN") && !Permission::check("SHOPADMIN")) {
			Security::permissionFailure($this, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
		}
		parent::init();

		Requirements::themedCSS("StockControlPage");
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		Requirements::javascript("ecommerce_stockcontrol/javascript/StockControlPage.js");
		$url = Director::absoluteURL($this->Link()."update/");
		Requirements::customScript("StockControlPage.set_url('".$url."');", "StockControlPage.set_url");
	}

	function Link(){
		return "/".self::get_url_segment()."/";
	}

	function StockProductObjects() {
		$buyableStockCalculatedQuantities = DataObject::get("BuyableStockCalculatedQuantity", "", "", "", "10");
		if($buyableStockCalculatedQuantities) {
			foreach($buyableStockCalculatedQuantities as $buyableStockCalculatedQuantity) {
				if($buyableStockCalculatedQuantity->Buyable()->UnlimitedStock) {
					$buyableStockCalculatedQuantities->remove($buyableStockCalculatedQuantity);
				}
				else {
					$buyableStockCalculatedQuantity->calculatedBaseQuantity();
				}
			}
			return $buyableStockCalculatedQuantities;
		}
	}

	function update($request = null) {
		$id = intval($request->param("ID"));
		$newValue = intval($request->param("OtherID"));
		if($newValue || $newValue === 0) {
			if($obj = DataObject::get_by_id("BuyableStockCalculatedQuantity", $id)) {
				if($buyable = $obj->getBuyable()) {
					$buyable->setActualQuantity($newValue);
					$msg = "<em>".$obj->Name . "</em> quantity updated to <strong>".$newValue."</strong>";
					return $this->customise(array("Message" => $msg))->renderWith("UpdateStockQuantity");
				}
				else {
					user_error("Could not create Calculation object", E_USER_NOTICE);
				}
			}
			else {
				user_error("could not find record: $id ", E_USER_NOTICE);
			}
		}
		else {
			user_error("new quantity specified is unknown", E_USER_NOTICE);
		}
	}

 	function history($request = null) {
		$id = intval($request->param("ID"));
		$buyableStockCalculatedQuantity = DataObject::get_by_id("BuyableStockCalculatedQuantity", $id);
		if($buyableStockCalculatedQuantity) {
			$buyableStockCalculatedQuantity->ManualUpdates = DataObject::get("BuyableStockManualUpdate", "\"ParentID\" = ".$buyableStockCalculatedQuantity->ID);
			$buyableStockCalculatedQuantity->OrderEntries = DataObject::get("BuyableStockOrderEntry", "\"ParentID\" = ".$buyableStockCalculatedQuantity->ID);
			/*
			$graphArray = array();
			if($buyableStockCalculatedQuantity->ManualUpdates) {
				foreach($buyableStockCalculatedQuantity->ManualUpdates as $obj) {
				}
			}
			if($buyableStockCalculatedQuantity->OrderEntries) {
				foreach($buyableStockCalculatedQuantity->OrderEntries as $obj) {
				}
			}
			*/
			return $this->customise($buyableStockCalculatedQuantity)->renderWith("AjaxStockControlPageHistory");
		}
		else {
			return " could not find historical data";
		}
 	}


}
