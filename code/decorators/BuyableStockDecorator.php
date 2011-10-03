<?php

/**
 * BuyableStockDecorator
 * Extension for any buyable - adding stock level capabilities.
 */

class BuyableStockDecorator extends DataObjectDecorator{

	protected static $buyables = array();
		public static function set_buyables($a) {self::$buyables = $a;}
		public static function get_buyables() {return self::$buyables;}
		public static function add_buyable($s) {self::$buyables[] = $s;}
		public static function has_buyable($s) {return in_array($s, self::$buyables);}

	protected static $quantity_field_selector = "";
		public static function set_quantity_field_selector($s) {self::$quantity_field_selector = $s;}
		public static function get_quantity_field_selector() {return self::$quantity_field_selector;}

	public function extraStatics() {
		return array (
			'db' => array(
				'MinQuantity' => 'Int',
				'MaxQuantity' => 'Int',
				'UnlimitedStock' => 'Boolean'
			),
			'casting' => array(
				'ActualQuantity' => 'Int'
			),
			'defaults' => array(
				'UnlimitedStock' => 1,
				'MinQuantity' => 0,
				'MaxQuantity' => 0
			)
		);
	}
	/*
	 * Allow setting stock level in CMS
	 */
	function updateCMSFields(&$fields){
		if($this->owner instanceOf SiteTree) {
			$tabName = 'Root.Content.Stock';
		}
		else {
			$tabName = 'Root.Stock';
		}
		$fields->addFieldsToTab(
			$tabName,
			array(
				new HeaderField('MinMaxHeader','Minimum and Maximum Quantities per Order', 3),
				new NumericField('MinQuantity','Minimum Quantity'),
				new NumericField('MaxQuantity','Maximum Quantity'),
				new HeaderField('ActualQantityHeader','Stock available', 3),
				new CheckboxField('UnlimitedStock','Unlimited Stock'),
				new NumericField('ActualQantity','Actual Stock Available', $this->getActualQuantity()),
				new HeaderField('ActualQantityAdjustmentHeader','Adjust all stock', 3),
				new LiteralField('ActualQantityAdjustmentLink','This CMS also provides a <a href="/'.StockControlController::get_url_segment().'/" target="_blank">quick stock adjuster</a>.')
			)
		);
	}

	/*
	 * Getter for stock level
	 */
	function ActualQuantity(){return $this->getActualQuantity();}
	function getActualQuantity(){
		return BuyableStockCalculatedQuantity::get_quantity_by_buyable($this->owner);
	}

	/*
	 * Setter for stock level
	 */
	function setActualQuantity($value){
		if(!$this->owner->ID){
			$this->owner->write();
		}
		//only set stock level if it differs from previous
		if($this->owner->ID && $value != $this->owner->getActualQuantity() && $member = Member::currentUser()){
			$parent = BuyableStockCalculatedQuantity::get_by_buyable($this->owner);
			if($parent) {
				$obj = new BuyableStockManualUpdate();
				$obj->ParentID = $parent->ID;
				$obj->Quantity = (int)$value;
				$obj->MemberID = $member->ID;
				$obj->write();
			}
			else {
				user_error("Could not write BuyableStockCalculatedQuantity Object for ".$this->owner->Title);
			}
		}
	}


	/*
	 * Only allow purchase if stock levels allow
	 * TODO: customise this to a certian stock level, on, or off
	 */
	function canPurchase($member = null){
		if($this->owner->MinQuantity > 0) {
			if($this->owner->getActualQuantity() <= $this->owner->MinQuantity){
				if(!$this->owner->UnlimitedStock) {
					return false;
				}
			}
		}
		return null; //returning null ensures that checks can continue
	}


	function onAfterWrite(){
		BuyableStockCalculatedQuantity::get_by_buyable($this->owner);
		if(isset($_REQUEST["ActualQantity"])) {
			$actualQantity = intval($_REQUEST["ActualQantity"]);
			if($actualQantity != $this->owner->getActualQuantity() && ($actualQantity === 0 || $actualQantity) ) {
				$this->owner->setActualQuantity($actualQantity);
			}
		}
	}

}

class BuyableStockDecorator_Extension extends Extension {
	/**
	 * TO DO: review method below
	 *  - move to init???
	 *
	 *
	 **/


	function index() {
		$min = 0;
		$max = 0;
		$msg = MinMaxModifier::get_sorry_message();
		$fieldSelector = BuyableStockDecorator::get_quantity_field_selector() ;
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
