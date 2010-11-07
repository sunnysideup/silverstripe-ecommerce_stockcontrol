<?php

class ProductVariationStockDecorator extends DataObjectDecorator{
	
	function extraStatics(){
		
		ProductVariation::$summary_fields['Stock'] = 'Stock';
		
		return array();
	}
	
	function updateCMSFields(&$fields){
		$fields->push(new NumericField('Stock','Stock'));
	}

	function setStock($value){
		//only set stock level if it differs from previous
		if($this->owner->ID && $value != $this->owner->getStock() && $member = Member::currentUser()){
			$parent = ProductStockVariationCalculatedQuantity::get_by_product_variation_id($this->owner->ID);
			$obj = new ProductStockManualUpdate();
			$obj->ParentID = $parent->ID;
			$obj->Quantity = (int)$value;
			$obj->MemberID = $member->ID;
			$obj->write();
		}
	}
	
	function getStock(){
		return ProductStockVariationCalculatedQuantity::get_quantity_by_product_variation_id($this->owner->ID);
	}
	
	
	/*
	 * Only allow purchase if stock levels allow
	 * TODO: customise this to a certian stock level, on, or off
	 */
	function canPurchase(){
		if($this->owner->Stock <= 0){
			 return false;
		}
		return null; //returning null ensures that can checks continue
	}
	
	
}