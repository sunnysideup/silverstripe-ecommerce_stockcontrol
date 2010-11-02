<?php

class ProductVariationStockDecorator extends DataObjectDecorator{
	
	function updateCMSFields(&$fields){
		$fields->addFieldToTab('Root.Content.Main',new NumericField('Stock','Stock'),'Content');	
	}

	function setStock($value){
		//only set stock level if it differs from previous
		if($this->owner->ID && $value != $this->owner->getStock() && $member = Member::currentUser()){
			$parent = ProductStockCalculatedQuantity::get_by_product_id($this->owner->ID);
			$obj = new ProductStockManualUpdate();
			$obj->ParentID = $parent->ID;
			$obj->Quantity = (int)$value;
			$obj->MemberID = $member->ID;
			$obj->write();
		}
	}
	
	function getStock(){
		return ProductStockCalculatedQuantity::get_by_product_variation_id($this->owner->ID);
	}
	
	
}