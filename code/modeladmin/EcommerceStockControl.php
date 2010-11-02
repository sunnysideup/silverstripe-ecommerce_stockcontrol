<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 **/

class EcommerceStockControl extends ModelAdmin {

	public static $managed_models = array("ProductStockCalculatedQuantity","ProductStockVariationCalculatedQuantity", "ProductStockManualUpdate", "ProductStockOrderEntry");

	public static function set_managed_models(array $array) {
		self::$managed_models = $array;
	}
	public static $url_segment = 'stock';

	public static $menu_title = 'Stock';

}