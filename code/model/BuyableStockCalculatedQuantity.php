<?php
/**
 *@author: Nicolaas [at] Sunny Side Up . Co . Nz
 *@description:
 * works out the quantity available for each buyable
 * based on the the number of items sold, recorded in BuyableStockOrderEntry,
 * and manual corrections, recorded in BuyableStockManualUpdate.
 *
 *
 **/

class BuyableStockCalculatedQuantity extends DataObject {

	static $db = array(
		"BaseQuantity" => "Int",
		"BuyableID" => "Int",
		"BuyableClassName" => "Varchar"
	);

	static $has_many = array(
		"BuyableStockOrderEntry" => "BuyableStockOrderEntry",
		"BuyableStockManualUpdate" => "BuyableStockManualUpdate"
	);

	static $defaults = array(
		"BaseQuantity" => 0
	);

	static $casting = array(
		"Name" => "Varchar",
		"Buyable" => "DataObject",
		"UnlimitedStock" => "Boolean"
	);

	//MODEL ADMIN STUFF
	public static $searchable_fields = array(
		"BaseQuantity"
	);

	public static $field_labels = array(
		"BaseQuantity" => "Calculated Quantity On Hand",
		"BuyableID" => "Buyable ID",
		"LastEdited" => "Last Calculated"
	);

	public static $summary_fields = array(
		"Name",
		"BaseQuantity",
		"LastEdited"
	);

	static $indexes = array(
		"BuyableID" => true,
		"BuyableClassName" => true
	);

	public static $default_sort = "\"BuyableClassName\", \"BaseQuantity\" DESC";

	public static $singular_name = "Stock Calculated Quantity";

	public static $plural_name = "Stock Calculated Quantities";

	protected static $calculation_done = array();

	public function canCreate() {return false;}

	public function canEdit() {return false;}

	public function canDelete() {return false;}

	public function canView() {return $this->canDoAnything();}

	function Link($action = "update") {
		return "/".StockControlController::get_url_segment()."/".$action."/".$this->ID."/";
	}

	function HistoryLink() {
		return $this->Link("history");
	}

	function Buyable() {return $this->getBuyable();}
	function getBuyable() {
		if($this->BuyableID && class_exists($this->BuyableClassName)) {
			return DataObject::get_by_id($this->BuyableClassName, $this->BuyableID);
		}
	}

	function UnlimitedStock() {return $this->geUnlimitedStock();}
	function getUnlimitedStock() {
		if($buyable = $this->getBuyable()) {
			return $buyable->UnlimitedStock;
		}
	}

	function Name() {return $this->getName();}
	function getName() {
		if($buyable = $this->getBuyable()) {
			return $buyable->getTitle();
		}
		return "no name";
	}

	protected function canDoAnything($member = null) {
		if($buyable = $this->getBuyable()) {
			if($buyable->canEdit($member)) {
				return true;
			}
		}
		Security::permissionFailure($this, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
	}

	public static function get_quantity_by_buyable($buyable) {
		$value = 0;
		$item = self::get_by_buyable($buyable);
		if($item) {
			$value = $item->calculatedBaseQuantity();
			if($value < 0) {
				$value = 0;
			}
		}
		return $value;
	}

	public static function get_by_buyable($buyable) {
		if($obj = DataObject::get_one("BuyableStockCalculatedQuantity", "\"BuyableID\" = ".$buyable->ID." AND \"BuyableClassName\" = '".$buyable->ClassName."'")) {
			//do nothing
		}
		else {
			$obj = new BuyableStockCalculatedQuantity();
			$obj->BuyableID = $buyable->ID;
			$obj->BuyableClassName = $buyable->ClassName;
		}
		if($obj) {
			if(isset($obj->ID) && $obj->ID && $obj->UnlimitedStock == $buyable->UnlimitedStock) {
				//do nothing
			}
			else {
				$obj->UnlimitedStock = $buyable->UnlimitedStock;
				//we must write here to calculate quantities
				$obj->write();
			}
			return $obj;
		}
		user_error("Could not find / create BuyableStockCalculatedQuantity for buyable with ID / ClassName ".$buyableID."/".$buyableClassName, E_WARNING);
	}

	function calculatedBaseQuantity() {
		if(!$this->ID) {
			return 0;
		}
		$actualQuantity = $this->workoutActualQuantity();
		if($actualQuantity != $this->BaseQuantity) {
			$this->BaseQuantity = $actualQuantity;
			$this->write();
			return $actualQuantity;
		}
		else {
			return $this->getField("BaseQuantity");
		}
	}

	protected function calculatedBaseQuantities($buyables = null) {
		if($buyables) {
			foreach($buyables as $buyable) {
				$buyableStockCalculatedQuantity = BuyableStockCalculatedQuantity::get_by_buyable($buyable);
				if($buyableStockCalculatedQuantity) {
					$buyableStockCalculatedQuantity->calculatedBaseQuantity();
				}
			}
		}
	}

	/**
	 * TODO: change to submitted from CustomerCanEdit criteria
	 */


	protected function workoutActualQuantity() {
		$actualQuantity = 0;
		if($buyable = $this->getBuyable()) {
			//set name
			//add total order quantities
			$data = DB::query("
				SELECT
					\"OrderItem\".\"BuyableID\",
					Sum(\"OrderItem\".\"Quantity\")+0 \"QuantitySum\",
					\"Order\".\"ID\" \"OrderID\",
					\"OrderAttribute\".\"ClassName\",
					\"OrderItem\".\"BuyableClassName\",
					\"OrderStep\".\"CustomerCanEdit\"
				FROM
					\"Order\"
					INNER JOIN \"OrderAttribute\" ON \"OrderAttribute\".\"OrderID\" = \"Order\".\"ID\"
					INNER JOIN \"OrderItem\" ON \"OrderAttribute\".\"ID\" = \"OrderItem\".\"ID\"
					INNER JOIN \"OrderStep\" ON \"OrderStep\".\"ID\" = \"Order\".\"StatusID\"
				GROUP BY
					\"Order\".\"ID\", \"BuyableID\"
				HAVING
					\"OrderItem\".\"BuyableID\" = ".(intval($this->BuyableID) - 0)."
					AND
					\"OrderItem\".\"BuyableClassName\" = '".$this->BuyableClassName."'
					AND
					\"OrderStep\".\"CustomerCanEdit\" = 0
			");
			if($data) {
				foreach($data as $row) {
					if($row["OrderID"] && $this->ID && $row["QuantitySum"]) {
						if($buyableStockOrderEntry = DataObject::get_one("BuyableStockOrderEntry", "\"OrderID\" = ".$row["OrderID"]." AND \"ParentID\" = ".$this->ID)) {
							//do nothing
						}
						else {
							$buyableStockOrderEntry = new BuyableStockOrderEntry();
							$buyableStockOrderEntry->OrderID = $row["OrderID"];
							$buyableStockOrderEntry->ParentID = $this->ID;
							$buyableStockOrderEntry->IncludeInCurrentCalculation = 1;
							$buyableStockOrderEntry->Quantity = 0;
						}
						if($buyableStockOrderEntry->Quantity != $row["QuantitySum"]) {
							$buyableStockOrderEntry->Quantity = $row["QuantitySum"];
							$buyableStockOrderEntry->write();
						}
					}
				}
			}
			//find last adjustment
			$latestManualUpdate = DataObject::get_one("BuyableStockManualUpdate","\"ParentID\" = ".$this->ID, "\"LastEdited\" DESC");
			//nullify order quantities that were entered before last adjustment
			if($latestManualUpdate) {
				$latestManualUpdateQTY = $latestManualUpdate->Quantity;
				DB::query("
					UPDATE \"BuyableStockOrderEntry\"
					SET \"IncludeInCurrentCalculation\" = 0
					WHERE
					\"LastEdited\" < '".$latestManualUpdate->LastEdited."'
						AND
						\"ParentID\" = ".$this->ID
				);
			}
			else {
				$latestManualUpdateQTY = 0;
			}
			//work out additional purchases
			$sqlQuery = new SQLQuery(
				 "SUM(\"Quantity\")", // Select
				 "\"BuyableStockOrderEntry\"", // From
				 "\"ParentID\" = ".$this->ID." AND \"IncludeInCurrentCalculation\" = 1" // Where (optional)
			);
			$orderQuantityToDeduct = $sqlQuery->execute()->value();
			if(!$orderQuantityToDeduct) {
				$orderQuantityToDeduct = 0;
			}
			//work out base total
			$actualQuantity = $latestManualUpdateQTY - $orderQuantityToDeduct;
			if(isset($_GET["debug"])) {
				echo "<hr />";
				echo $this->Name;
				echo " | Manual SUM: ".$latestManualUpdateQTY;
				echo " | Order SUM: ".$orderQuantityToDeduct;
				echo " | Total SUM: ".$this->BaseQuantity;
				echo "<hr />";
			}
		}
		return $actualQuantity;
	}

}
