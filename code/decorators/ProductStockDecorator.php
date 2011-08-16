<?php

/**
 * ProductStockDecorator
 * Extension of Product for adding stock level capabilities.
 */

class ProductStockDecorator extends DataObjectDecorator{

	protected static $quantity_field_selector = '#Quantity input';
		static function set_quantity_field_selector($s) {self::$quantity_field_selector = $s;}
		static function get_quantity_field_selector() {return self::$quantity_field_selector;}
	
	protected $alwaysAllowPurchase = false;
		function alwaysAllowPurchase($b) {$this->alwaysAllowPurchase = $b;}

	public static $stock_level_indicators = array(
		0 => "none",
		10 => "limited",
		1000 => "many"
	);

	/*
	 * Allow setting stock level in CMS
	 */
	function updateCMSFields(&$fields){
		$fields->addFieldToTab('Root.Content.Main',new NumericField('Stock','Stock'),'Content');
		//FIXME: hack, because $this->Variations doesn't seem to work???
		if(DataObject::get('ProductVariation',"\"ProductID\" = ".$this->owner->ID) == null){
			$fields->addFieldToTab('Root.Content.StockLevels',new NumericField('Stock','Stock'));
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
	function canPurchase($member = null){
		if( $this->alwaysAllowPurchase ) {
			return true;
		}
		if($this->owner->Stock <= 0){
			 return false;
		}
		return null; //returning null ensures that can checks continue
	}

	function StockIndicator($level = null){
		$level = is_numeric($level) ? $level : $this->owner->Stock;
		$last = null;
		foreach(self::$stock_level_indicators as $key => $value) {
			$last = $value;
			if($level <= $key) {
				return $value;
			}
		}
		return $last;
	}
}

class ProductStockDecorator_Extension extends Extension {

	function index() {
		$min = 0;
		$max = 0;
		$msg = MinMaxModifier::get_sorry_message();
		$fieldSelector = ProductStockDecorator::get_quantity_field_selector() ;
		if($minField = MinMaxModifier::get_min_field()) {
			$min = $this->owner->$minField;
		}
		if($maxField = MinMaxModifier::get_max_field()) {
			$max = $this->owner->$maxField;
		}
		$js = 'MinMaxModifier.add_item("'.$fieldSelector.'", '.intval($min).', '.intval($max).', "'.addslashes($msg).'");';
		Requirements::javascript("ecommerce_stockcontrol/javascript/MinMaxModifier.js");
		Requirements::customScript($js,$fieldSelector);		
		return array();
	}

	

}
