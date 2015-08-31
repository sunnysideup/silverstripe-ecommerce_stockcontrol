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

	function init() {
		// Only administrators can run this method
		$shopAdminCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code");
		if(!Permission::check("ADMIN") && !Permission::check($shopAdminCode)) {
			Security::permissionFailure($this, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
		}
		parent::init();

		Requirements::themedCSS("StockControlPage", 'ecommerce_stockcontrol');
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
		//Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
		Requirements::javascript("ecommerce_stockcontrol/javascript/StockControlPage.js");
		$url = Director::absoluteURL($this->Link()."update/");
		Requirements::customScript("StockControlPage.set_url('".$url."');", "StockControlPage.set_url");
	}

	function Link($action = NULL){
		return "/update-stock/";
	}

	function StockProductObjects() {
		$buyableStockCalculatedQuantities = BuyableStockCalculatedQuantity::get()->limit(10);
		if($buyableStockCalculatedQuantities->count()) {
			foreach($buyableStockCalculatedQuantities as $buyableStockCalculatedQuantity) {
				$buyable = $buyableStockCalculatedQuantity->Buyable();
				if($buyable) {
					if($buyable->UnlimitedStock) {
						$buyableStockCalculatedQuantities->remove($buyableStockCalculatedQuantity);
					}
					else {
						$buyableStockCalculatedQuantity->calculatedBaseQuantity();
					}
				}
				else {
					//user_error("Buyable can not be found!", E_USER_NOTICE);
				}
			}
			return $buyableStockCalculatedQuantities;
		}
	}

	function update($request = null) {
		$id = intval($request->param("ID"));
		$newValue = intval($request->param("OtherID"));
		if($newValue || $newValue === 0) {
			$obj = BuyableStockCalculatedQuantity::get()->byID($id);
			if($obj) {
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
		$buyableStockCalculatedQuantity = BuyableStockCalculatedQuantity::get()->byID($id);
		if($buyableStockCalculatedQuantity) {
			$buyableStockCalculatedQuantity->ManualUpdates = BuyableStockManualUpdate::get()->filter(array('ParentID' => $buyableStockCalculatedQuantity->ID));
			$buyableStockCalculatedQuantity->OrderEntries = BuyableStockOrderEntry::get()->filter(array('ParentID' => $buyableStockCalculatedQuantity->ID));
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
