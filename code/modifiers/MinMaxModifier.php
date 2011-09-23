<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_stockcontrol
 * @description: makes sure that a buyable quantity in cart stays between a min and a max
 */
class MinMaxModifier extends OrderModifier {

//--------------------------------------------------------------------*** static variables

	static $db = array(
		"Adjustments" => "HTMLText"
	);

	protected static $title = "MinMaxModifier";

	protected static $default_min_quantity = 1;
		static function set_default_min_quantity($i) { self::$default_min_quantity = $i;}

	protected static $default_max_quantity = 99;
		static function set_default_max_quantity($i) { self::$default_max_quantity = $i;}

	protected static $min_field = "MinQuantity";
		static function set_min_field($s) { self::$min_field = $s;}
		static function get_min_field() { return self::$min_field;}

	protected static $max_field = "MaxQuantity";
		static function set_max_field($s) { self::$max_field = $s;}
		static function get_max_field() { return self::$max_field;}

	protected static $adjustment_message = "Based on stock availability, quantities have been adjusted as follows: ";
		static function set_adjustment_message($s) { self::$adjustment_message = $s;}

	protected static $sorry_message = "Sorry, your selected value not is available.";
		static function set_sorry_message($s) {self::$sorry_message = $s;}
		static function get_sorry_message() {return self::$sorry_message;}

	protected static $use_stock_quantities = false;
		static function set_use_stock_quantities($b) {self::$use_stock_quantities = $b;}
		static function get_use_stock_quantities() {return self::$use_stock_quantities;}

	protected static $ids_of_items_adjusted = array();

//-------------------------------------------------------------------- *** static functions

	static function show_form() {
		self::apply_min_max();
		return false;
	}

	static function get_form($controller) {
		return false;
	}

//-------------------------------------------------------------------- *** display functions
	function CanBeRemoved() {
		return false;
	}

	function ShowInTable() {
		return false;
	}


//--------------------------------------------------------------------*** table values
	function LiveCalculationValue() {
		self::apply_min_max();
		return 0;
	}

	function TableValue() {
		return "";
	}


//--------------------------------------------------------------------*** table titles
	function LiveName() {
		return "";
	}

//-------------------------------------------------------------------- *** calculations
	static function apply_min_max() {
		if(self::$min_field || self::$max_field  || self::$default_min_quantity || self::$default_max_quantity ) {
			$msgArray = array();
			$minFieldName = self::$min_field;
			$maxFieldName = self::$max_field;
			$items = ShoppingCart::current_order()->Items();
			$i = 0;
			if($items) {
				foreach($items as $item) {
					$buyable = $item->Buyable();
					if($buyable) {
						$quantity = $item->Quantity;
						//THIS IS A BIT OF A HACK - NEED TO WORK OUT CURRENT ADJUSTMENTS>>>

						//END OF HACK
						$newValue = $quantity; //can be zero, but can not be minus 1!
						$absoluteMin = 0;
						$absoluteMax = 9999;
						if($minFieldName) {
							if($buyable->$minFieldName && $buyable->$minFieldName > 0) {
								$absoluteMin = $buyable->$minFieldName;
							}
							elseif(!isset($buyable->$minFieldName)) {
								if($buyable->Parent() && isset($buyable->Parent()->$minFieldName) && $buyable->Parent()->$minFieldName > 0) {
									$absoluteMin = $buyable->Parent()->$minFieldName;
								}
							}
						}
						elseif(self::$default_min_quantity) {
							if($absoluteMin < self::$default_min_quantity ) {
								$absoluteMin = self::$default_min_quantity;
							}
						}
						if($maxFieldName) {
							if($buyable->$maxFieldName && $buyable->$maxFieldName > 0) {
								$absoluteMax = $buyable->$maxFieldName;
							}
							elseif(!isset($buyable->$maxFieldName)) {
								if($buyable->Parent() && isset($buyable->Parent()->$maxFieldName) && $buyable->Parent()->$maxFieldName > 0) {
									$absoluteMax = $buyable->Parent()->$maxFieldName;
								}
							}
						}
						elseif(self::$default_max_quantity) {
							if($absoluteMax > self::$default_max_quantity) {
								$absoluteMax = self::$defaul_max_quantity;
							}
						}
						if(self::$use_stock_quantities) {
							$maxStockQuantity = BuyableStockCalculatedQuantity::get_quantity_by_buyable($buyable);
							if($absoluteMax > $maxStockQuantity) {
								$absoluteMax = $maxStockQuantity;
							}
							if($absoluteMin > $maxStockQuantity) {
								$absoluteMax = 0;
								$maxStockQuantity = 0;
							}
						}
						$absoluteMin = intval($absoluteMin) - 0;
						$absoluteMax = intval($absoluteMax) - 0;
						if($quantity < $absoluteMin) {
							//echo "adjusting for MIN: $quantity < $absoluteMin";
							$newValue = $absoluteMin;
						}
						if($quantity > $absoluteMax) {
							//echo "adjusting for MAX: $quantity > $absoluteMax";
							$newValue = $absoluteMax;
						}
						if($quantity != $newValue) {
							$item->Quantity = $newValue;
							ShoppingCart::singleton()->setQuantity($buyable, $newValue);
							$msgArray[$i] = $buyable->Title." changed from ".$quantity." to ".$newValue;
							$i++;
							$quantity = $newValue;
							self::$ids_of_items_adjusted[$item->ID] = $item->ID;
						}
						if(Director::is_ajax()) {
							//do nothing
						}
						else {
							//IS THIS WORKING
							$js = 'MinMaxModifier.add_item("input[name=\''.$item->QuantityFieldName().'\']", '.intval($absoluteMin).', '.intval($absoluteMax).', "'.addslashes(self::$sorry_message).'");';
							Requirements::javascript("ecommerce_stockcontrol/javascript/MinMaxModifier.js");
							Requirements::customScript($js,$item->QuantityFieldName());
						}

					}
				}
			}
		}
		if(count($msgArray)) {
			if(self::$adjustment_message) {
				$msg = self::$adjustment_message."\n".implode("\n",$msgArray);
				if($msg && !Director::is_ajax()) {
					Requirements::customScript('alert("'.Convert::raw2js($msg).'");', "MinMaxModifierAlert");
				}
				$this->Adjustments = $msg;
			}
		}
	}

	function updateForAjax(array &$js) {
		self::apply_min_max();
		if(is_array(self::$ids_of_items_adjusted) && count(self::$ids_of_items_adjusted)) {
			$items = DataObject::get("OrderItem", "OrderItem.ID IN (".implode(",", self::$ids_of_items_adjusted) .")");
			if($items) {
				foreach($items as $item) {
					$item->updateForAjax($js);
				}
			}
		}
		return $js;
	}


//--------------------------------------------------------------------*** database functions

}

