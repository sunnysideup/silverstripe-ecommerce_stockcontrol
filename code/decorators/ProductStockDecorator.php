<?php

/**
 * ProductStockDecorator
 * Extension of Product for adding stock level capabilities.
 */

class ProductStockDecorator extends DataObjectDecorator{

	/*
	 * Allow setting stock level in CMS
	 */
	function updateCMSFields(&$fields){
		$fields->addFieldToTab('Root.Content.Main',new NumericField('Stock','Stock'),'Content');
		//FIXME: hack, because $this->Variations doesn't seem to work???
		if(DataObject::get('ProductVariation',"\"ProductID\" = ".$this->owner->ID) == null){
			$fields->addFieldToTab('Root.Content.Main',new NumericField('Stock','Stock'),'Content');
		}


	}

	/*
	 * Getter for stock level
	 */
	function getStock(){
		return ProductStockCalculatedQuantity::get_quantity_by_product_id($this->owner->ID);
	}

	/*
	 * Setter for stock level
	 */
	function setStock($value){
		if(!$this->owner->ID){
			$this->owner->TempStockValueToBeSaved = $value; //store for saving later (see onAfterWrite below)
		}

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

	/*
	 * Catch the case where stock level has been set on a new Product DataObject.
	 */
	function onAfterWrite(){
		if($this->owner->TempStockValueToBeSaved){
			$this->owner->Stock = $this->owner->TempStockValueToBeSaved;
			$this->owner->TempStockValueToBeSaved = null;
		}
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
