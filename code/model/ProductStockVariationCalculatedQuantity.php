<?php
/**
 *@author: Nicolaas [at] Sunny Side Up . Co . Nz
 *@description: calculates quantities availabel for product variations as opposed to products.
 *
 **/

class ProductStockVariationCalculatedQuantity extends ProductStockCalculatedQuantity {

	static $db = array(
		"ProductVariationPresent" => "Boolean"
	);

	static $has_one = array(
		"ProductVariation" => "ProductVariation"
	);

	static $defaults = array(
		"ProductVariationPresent" => 1
	);

	//MODEL ADMIN STUFF
	public static $searchable_fields = array(
		"BaseQuantity",
		"ProductVariationPresent",
		"Name"
	);

	public static $field_labels = array(
		"BaseQuantity" => "Calculated Quantity On Hand",
		"ProductVariationPresent" => "Variation Present",
		"ProductVariationID" => "Product Variation ID",
		"LastEdited" => "Last Calculated"
	);


	public static $default_sort = "\"ProductVariationPresent\" DESC, \"Name\" ASC";

	public static $singular_name = "Product Stock Variation Calculated Quantity";

	public static $plural_name = "Product Stock Variation Calculated Quantities";


	//END MODEL ADMIN STUFF

	static function add_all_product_variations() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		//add ones that have not been added yet
		$sql = "
			INSERT INTO {$bt}ProductStockVariationCalculatedQuantity{$bt} (ProductVariationID, BaseQuantity, Name)
			SELECT {$bt}Product{$bt}.{$bt}ID{$bt} AS ProductID, 0 AS BaseQuantity, {$bt}ProductVariation{$bt}.{$bt}Title{$bt} AS \"Name\"
			FROM {$bt}ProductVariation{$bt}
				LEFT JOIN {$bt}ProductStockVariationCalculatedQuantity{$bt}
					ON {$bt}ProductStockVariationCalculatedQuantity{$bt}.{$bt}ProductVariationID{$bt} = {$bt}ProductVariation{$bt}.{$bt}ID{$bt}
			WHERE {$bt}ProductStockVariationCalculatedQuantity{$bt}.{$bt}ID{$bt} IS NULL;";
		DB::query($sql);
		//delete ones that are no longer required
		$sql = "
			UPDATE {$bt}ProductStockVariationCalculatedQuantity{$bt}
				LEFT JOIN {$bt}ProductVariation{$bt}
					ON {$bt}ProductVariation{$bt}.{$bt}ID{$bt} = {$bt}ProductStockVariationCalculatedQuantity{$bt}.{$bt}ProductVariationID{$bt}
			SET {$bt}ProductStockVariationCalculatedQuantity{$bt}.{$bt}ProductVariationPresent{$bt} = 0
			WHERE {$bt}ProductVariation{$bt}.{$bt}ID{$bt} IS NULL;";
		DB::query($sql);
	}

	static function get_quantity_by_product_variation_id($productVariationID) {
		$value = 0;
		$item = self::get_by_product_variation_id($productVariationID);
		if($item) {
			$value = $item->calculatedBaseQuantity();
			if($value < 0) {
				$value = 0;
			}
		}
		return $value;
	}

	static function get_by_product_variation_id($productVariationID) {
		if($obj = DataObject::get_one("ProductStockVariationCalculatedQuantity", "\"ProductVariationID\" = ".intval($productVariationID))) {
			$obj = $obj;
		}
		else {
			$obj = new ProductStockVariationCalculatedQuantity();
			$obj->ProductVariationID = $productVariationID;
		}
		if($obj) {
			$obj->write();
			return $obj;
		}
		user_error("Could not find / create ProductStockVariationCalculatedQuantity for Product Variation with ID: ".$id, E_WARNING);
	}

	function calculatedBaseQuantity() {
		$this->write();
		if(!$this->ID) {
			return 0;
		}
		else {
			return $this->getField("BaseQuantity");
		}
	}

	function WorkOutQuantities($productVariations = null) {
		if($productVariations) {
			foreach($productVariations as $productVariation) {
				$ProductStockVariationCalculatedQuantityRecord = DataObject::get_one("ProductStockVariationCalculatedQuantity", "\"ProductVariationID\" = ".$productVariation->ID);
				if(!$ProductStockVariationCalculatedQuantityRecord && $LatestUpdate) {
					$ProductStockVariationCalculatedQuantityRecord = new ProductStockVariationCalculatedQuantity();
					$ProductStockVariationCalculatedQuantityRecord->ProductVariationID = $productVariation->ID;
				}
				if($ProductStockVariationCalculatedQuantityRecord) {
					$ProductStockVariationCalculatedQuantityRecord->write();
				}
			}
		}
	}

	function onBeforeWrite() {
		//why do we not have parent::onBeforeWrite() HERE?????
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		if($this->ProductVariationID && $this->ID) {
			if($productVariation = DataObject::get_by_id("ProductVariation", $this->ProductVariationID)) {
				//set name
				$this->Name = $productVariation->Title;

				//add total order quantities
				$data = DB::query("
					SELECT
						{$bt}ProductVariation_OrderItem{$bt}.{$bt}ProductVariationID{$bt},
						Sum({$bt}OrderItem{$bt}.{$bt}Quantity{$bt})+0 \"QuantitySum\",
						{$bt}Order{$bt}.{$bt}ID{$bt} \"OrderID\"
					FROM
						{$bt}Order{$bt}
						INNER JOIN {$bt}OrderAttribute{$bt} ON {$bt}OrderAttribute{$bt}.{$bt}OrderID{$bt} = {$bt}Order{$bt}.\"ID\"
						INNER JOIN {$bt}OrderItem{$bt} ON {$bt}OrderAttribute{$bt}.{$bt}ID{$bt} = {$bt}OrderItem{$bt}.{$bt}ID{$bt}
						INNER JOIN {$bt}Product_OrderItem{$bt} ON {$bt}Product_OrderItem{$bt}.{$bt}ID{$bt} = {$bt}OrderAttribute{$bt}.{$bt}ID{$bt}
						INNER JOIN {$bt}ProductVariation_OrderItem{$bt} ON {$bt}ProductVariation_OrderItem{$bt}.{$bt}ID{$bt} = {$bt}OrderAttribute{$bt}.{$bt}ID{$bt}
						INNER JOIN {$bt}Payment{$bt} ON {$bt}Payment{$bt}.{$bt}ID{$bt} = {$bt}Order{$bt}.{$bt}ID{$bt}
						INNER JOIN {$bt}ProductStockOrderEntry{$bt} On {$bt}ProductStockOrderEntry{$bt}.{$bt}OrderID{$bt} = {$bt}Order{$bt}.{$bt}ID{$bt}
					GROUP BY
						{$bt}Order{$bt}.{$bt}ID{$bt}, {$bt}ProductID{$bt}
					HAVING
						({$bt}ProductVariation_OrderItem{$bt}.{$bt}ProductVariationID{$bt} = ".(intval($this->productVariationID) + 0).")
				");
				foreach($data as $row) {
					$ProductStockOrderEntry = new ProductStockOrderEntry();
					$ProductStockOrderEntry->OrderID = $row["OrderID"];
					$ProductStockOrderEntry->Quantity = $row["QuantitySum"];
					$ProductStockOrderEntry->ParentID = $this->ID;
					$ProductStockOrderEntry->IncludeInCurrentCalculation = 1;
					$ProductStockOrderEntry->write();
				}
				//work out additional purchases
				$sqlQuery = new SQLQuery(
					 "SUM({$bt}Quantity{$bt})", // Select
					 "ProductStockOrderEntry", // From
					 "{$bt}ParentID{$bt} = ".$this->ID." AND {$bt}IncludeInCurrentCalculation{$bt} = 1" // Where (optional)
				);
				$OrderQuantityToDeduct = $sqlQuery->execute()->value();

				//find last adjustment
				$LatestManualUpdate = DataObject::get_one("ProductStockManualUpdate","\"ParentID\" = ".$this->ID, "\"LastEdited\" DESC");

				//nullify order quantities that were entered before last adjustment
				if($LatestManualUpdate) {
					$LatestManualUpdateQuantity = $LatestManualUpdate->Quantity;
					DB::query("
						UPDATE {$bt}ProductStockOrderEntry{$bt}
						SET {$bt}IncludeInCurrentCalculation{$bt} = 0
						WHERE {$bt}LastEdited{$bt} < '".$LatestManualUpdate->LastEdited."'
							AND {$bt}ParentID{$bt} = ".$this->ID
					);
				}
				else {
					$LatestManualUpdateQuantity = 0;
				}

				//work out base total
				$this->BaseQuantity = $LatestManualUpdateQuantity - $OrderQuantityToDeduct;
			}
		}
		parent::onBeforeWrite();
	}


}
