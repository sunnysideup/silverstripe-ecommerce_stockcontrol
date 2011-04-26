<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_stockcontrol
 * @description: makes sure that a product quantity in cart stays between a min and a max
 */
class MinMaxModifier extends OrderModifier {

//--------------------------------------------------------------------*** static variables

	static $db = array(
		"Adjustments" => "HTMLText"
	);

	protected static $apply_min_max_jsAjaxArray = false;

	protected static $title = "MinMaxModifier";

	protected static $default_min_quantity = 1;
		static function set_default_min_quantity($v) { self::$default_min_quantity = $v;}

	protected static $default_max_quantity = 99;
		static function set_default_max_quantity($v) { self::$default_max_quantity = $v;}

	protected static $min_field = "MinQuantity";
		static function set_min_field($v) { self::$min_field = $v;}

	protected static $max_field = "MaxQuantity";
		static function set_max_field($v) { self::$max_field = $v;}

	protected static $adjustment_message = "Based on stock availability, quantities have been adjusted as follows: ";
		static function set_adjustment_message($v) { self::$adjustment_message = $v;}

	protected static $sorry_message = "Sorry, your selected value not is available";
		static function set_sorry_message($v) { self::$sorry_message = $v;}

	protected static $use_stock_quantities = false;
		static function set_use_stock_quantities($v) { self::$use_stock_quantities = $v;}
		static function get_use_stock_quantities() {return self::$use_stock_quantities;}

	protected static $show_adjustments_in_checkout_table = true;
		static function set_show_adjustments_in_checkout_table($v) { self::$show_adjustments_in_checkout_table = $v;}
		static function get_show_adjustments_in_checkout_table() {return self::$show_adjustments_in_checkout_table;}
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
		if($this->Name() && self::get_show_adjustments_in_checkout_table()) {
			return true;
		}
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
		return $this->readAdjustments();
	}

	function Name() {
		if($this->ID) {
			return $this->Name;
		}
		else {
			return $this->LiveName();
		}
	}

	function TableTitle() {
		return $this->Name();
	}


//-------------------------------------------------------------------- *** calculations
	static function apply_min_max() {
		if(!self::$apply_min_max_jsAjaxArray) {
			$jsAjaxArray = array();
			if(self::$min_field || self::$max_field  || self::$default_min_quantity || self::$default_max_quantity ) {
				$msgArray = array();
				$minFieldName = self::$min_field;
				$maxFieldName = self::$max_field;
				$items = ShoppingCart::get_items();
				$i = 0;
				foreach($items as $itemIndex => $item) {
					if($item) {
						$product = $item->Product();

						if($quantity = $item->getQuantity()) {
							$newQuantity = -1; //can be zero, but can not be minus 1!
							$absoluteMin = 0;
							$absoluteMax = 9999999;
							if($minFieldName) {
								if($product->$minFieldName) {
									$absoluteMin = $product->$minFieldName;
									if($quantity < $product->$minFieldName) {
										$newQuantity = $product->$minFieldName;
									}
								}
							}
							elseif(self::$default_min_quantity) {
								if($absoluteMin < self::$default_min_quantity ) {
									$absoluteMin = self::$default_min_quantity;
								}
								if($quantity < self::$default_min_quantity) {
									$newQuantity = self::$default_min_quantity;
								}
							}
							if($maxFieldName) {
								if($product->$maxFieldName) {
									$absoluteMax = $product->$maxFieldName;
									if($quantity > $product->$maxFieldName) {
										$newQuantity = $product->$maxFieldName;
									}
								}
							}
							elseif(self::$default_max_quantity) {
								if($absoluteMax > self::$default_max_quantity) {
									$absoluteMax = self::$defaul_max_quantity;
								}

								if($quantity > self::$default_max_quantity) {
									$newQuantity = self::$default_max_quantity;
								}
							}
							if(self::$use_stock_quantities) {
								$maxStockQuantity = ProductStockCalculatedQuantity::get_quantity_by_product_id($product->ID);
								if($absoluteMax > $maxStockQuantity) {
									$absoluteMax = $maxStockQuantity;
								}
								if($absoluteMin > $maxStockQuantity) {
									$absoluteMax = 0;
									$maxStockQuantity = 0;
								}
								if($quantity > $maxStockQuantity) {
									$newQuantity = $maxStockQuantity;
								}
							}
							if($newQuantity != $quantity && $newQuantity > -1) {
								ShoppingCart::set_quantity_item($product->ID, $newQuantity);
								$msgArray[$i] = $product->Title." changed from ".$quantity." to ".$newQuantity;
								$i++;
								$quantity = $newQuantity;
							}
							if(!Director::is_ajax()) {
								if($absoluteMin || $absoluteMax < 99999) {
									//IS THIS WORKING
									$js = '
										jQuery(document).ready(
											function() {
												jQuery("input[name=\'Product_OrderItem_'.$product->ID.'_Quantity\']").blur(
													function() {
														var updated = 0;
														if(jQuery(this).val() > '.intval($absoluteMax).') {
															jQuery(this).val('.intval($absoluteMax).');
															updated = 1;
														}
														if(jQuery(this).val() < '.intval($absoluteMin).') {
															jQuery(this).val('.intval($absoluteMin).');
															updated = 1;
														}
														if(updated) {
															alert("'.addslashes(self::$sorry_message).'");
															jQuery("input[name=\'Product_OrderItem_'.$product->ID.'_Quantity\']").change();
														}
													}
												);
											}
										);';
										Requirements::customScript($js,'Product_OrderItem_'.$product->ID.'_Quantity');
								}
							}
							elseif($quantity) {
								$jsAjaxArray[] = array("name" => 'Product_OrderItem_'.$product->ID.'_Quantity', "value" => $quantity);
							}
						}
					}
				}
			}
			if(count($msgArray)) {
				if(self::get_show_adjustments_in_checkout_table()) {
					self::write_adjustments($msgArray);
				}
				if(self::$adjustment_message && !Director::is_ajax()) {
					$msg = self::$adjustment_message."\n".implode("\n",$msgArray);
					if($msg) {
						Requirements::customScript('alert("'.Convert::raw2js($msg).'");', "MinMaxModifierAlert");
					}
				}
			}
			self::$apply_min_max_jsAjaxArray = $jsAjaxArray;
		}
		return self::$apply_min_max_jsAjaxArray;
	}

	function updateForAjax(array &$js) {
		$jsAjaxArray = self::apply_min_max();
		if(count($jsAjaxArray)) {
			foreach($jsAjaxArray as $nameValueArray) {
				$js[] = array('name' => $nameValueArray["name"], 'parameter' => 'value', 'value' => $nameValueArray["value"]);
			}
		}
		if(self::get_show_adjustments_in_checkout_table()) {
			$js[] = array('id' => $this->CartTotalID(), 'parameter' => 'innerHTML', 'value' => 0);
			$js[] = array('id' => $this->TableTotalID(), 'parameter' => 'innerHTML', 'value' => 0);
			$js[] = array('id' => $this->TableTitleID(), 'parameter' => 'innerHTML', 'value' => $this->readAdjustments());
		}
	}


//--------------------------------------------------------------------*** database functions

	function readAdjustments() {
		$listItems = Session::get("MinMaxModifier_name");
		if($listItems) {
			return self::$adjustment_message." ".$listItems.".";
		}
		return "";
	}

	static function write_adjustments($msgArray) {
		$newMsg = implode("; ",$msgArray);
		$oldMsg = Session::get("MinMaxModifier_name");
		if($newMsg && $oldMsg) {
			$oldMsg .="; ";
		}
		Session::set("MinMaxModifier_name", $oldMsg.$newMsg);
	}

	function writeAdjustments($msg){
		self::write_adjustments($msg);
	}

	function clearAdjustments(){
		Session::clear("MinMaxModifier_name");
		Session::set("MinMaxModifier_name", "");
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->Adjustments = $this->readAdjustments();
		$this->clearAdjustments();

	}

}

