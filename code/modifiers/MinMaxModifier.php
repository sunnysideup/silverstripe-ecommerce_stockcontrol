<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_stockcontrol
 * @description: makes sure that a buyable quantity in cart stays between a min and a max
 */
class MinMaxModifier extends OrderModifier {

//--------------------------------------------------------------------*** static variables

	private static $db = array(
		"Adjustments" => "HTMLText"
	);

	private static $singular_name = "Stock Adjustment";
		function i18n_singular_name() { self::$singular_name;}

	private static $plural_name = "Stock Adjustments";
		function i18n_plural_name() { return self::$plural_name;}

	private static $title = "MinMaxModifier";

	private static $default_min_quantity = 1;

	private static $default_max_quantity = 9999;

	private static $min_field = "MinQuantity";

	private static $max_field = "MaxQuantity";

	private static $adjustment_message = "Based on stock availability, quantities have been adjusted as follows: ";

	private static $sorry_message = "Sorry, your selected value not is available.";

	private static $use_stock_quantities = true;

	private static $ids_of_items_adjusted = array();

//-------------------------------------------------------------------- *** static functions

	static function show_form() {
		self::apply_min_max();
		return false;
	}

	static function get_form($controller) {
		return false;
	}

//-------------------------------------------------------------------- *** cms fuctions

	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->removeByName("Adjustments");
		$fields->addFieldToTab("Root.Debug", new ReadonlyField("AdjustmentsShown", "Adjustments", $this->Adjustments));
		return $fields;
	}
//-------------------------------------------------------------------- *** display functions
	function CanBeRemoved() {
		return false;
	}

	function ShowInTable() {
		return false;
	}


//--------------------------------------------------------------------*** table values
	function LiveCalculatedTotal() {
		self::apply_min_max();
		return 0;
	}

	function LiveTableValue() {
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
			$currentOrder = ShoppingCart::current_order();
			if($currentOrder->IsSubmitted()) {
				//too late!
				return;
			}
			$items = $currentOrder->Items();
			$i = 0;
			if($items) {
				foreach($items as $item) {
					$buyable = $item->Buyable();
					if($buyable) {
						$quantity = $item->Quantity;
						$absoluteMin = self::$default_min_quantity;
						$absoluteMax = self::$default_max_quantity;
						$parent = $buyable->Parent();
						if($minFieldName) {
							if(isset($buyable->$minFieldName) && $buyable->$minFieldName > 0) {
								$absoluteMin = $buyable->$minFieldName;
							}
							//product variations
							elseif(!isset($buyable->$minFieldName)) {
								if($parent && isset($parent->$minFieldName) && $parent->$minFieldName > 0) {
									$absoluteMin = $parent->$minFieldName;
								}
							}
						}
						if($maxFieldName) {
							if(isset($buyable->$maxFieldName) && $buyable->$maxFieldName > 0) {
								$absoluteMax = $buyable->$maxFieldName;
							}
							//product variations
							elseif(!isset($buyable->$maxFieldName)) {
								if($parent && isset($parent->$maxFieldName) && $parent->$maxFieldName > 0) {
									$absoluteMax = $parent->$maxFieldName;
								}
							}
						}
						if($buyable->UnlimitedStock) {
							//nothing more to do
						}
						elseif(self::$use_stock_quantities) {
							$maxStockQuantity = $buyable->getActualQuantity();
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
						$newValue = $quantity;
						if($quantity < $absoluteMin && $absoluteMin > 0) {
							debug::log("adjusting for MIN: $quantity < $absoluteMin");
							$newValue = $absoluteMin;
						}
						if($quantity > $absoluteMax && $absoluteMax > 0) {
							debug::log("adjusting for MAX: $quantity > $absoluteMax");
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
							//IS THIS WORKING?
							$fieldName = $item->AJAXDefinitions()->QuantityFieldName();
							$js = 'MinMaxModifier.add_item("input[name=\''.$fieldName.'\']", '.intval($absoluteMin).', '.intval($absoluteMax).', "'.addslashes(self::$sorry_message).'");';
							Requirements::javascript("ecommerce_stockcontrol/javascript/MinMaxModifier.js");
							Requirements::customScript($js,$fieldName);
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
				//$this->Adjustments = $msg;
			}
		}
	}

	function updateForAjax(array $js) {
		parent::updateForAjax($js);
		self::apply_min_max();
		if(is_array(self::$ids_of_items_adjusted) && count(self::$ids_of_items_adjusted)) {
			$items = OrderItem::get()->filter(array('ID' => self::$ids_of_items_adjusted));
			if($items->count()) {
				foreach($items as $item) {
					$item->updateForAjax($js);
				}
			}
		}
		return $js;
	}


//--------------------------------------------------------------------*** database functions

}

