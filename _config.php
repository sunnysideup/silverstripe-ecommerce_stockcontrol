<?php




/**
 * @author Nicolaas modules [at] sunnysideup.co.nz, + jeremy [at] burnbright.co.nz
 *
 **/


Director::addRules(50, array(
	StockControlController::get_url_segment().'//$Action/$ID/$OtherID/$Value' => 'StockControlController'
));


Object::add_extension('Product_Controller', 'BuyableStockDecorator_Extension');

//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START ecommerc_stockcontrol MODULE ----------------===================
/**
 * ADD TO ECOMMERCE.YAML:
Order:
	modifiers: [
		...
		MinMaxModifier
	]
*/

//SET BUYABLES
//Object::add_extension('Product', 'BuyableStockDecorator');
//BuyableStockDecorator::add_buyable("Product");
//Object::add_extension('ProductVariation', 'BuyableStockDecorator');
//BuyableStockDecorator::add_buyable("ProductVariation");

//HIGHLY RECOMMENDED
//MinMaxModifier::set_use_stock_quantities(true); //make use of the stock quantity tables to keep track of them
/**
 * ADD TO ECOMMERCE.YAML:
ProductsAndGroupsModelAdmin:
	managed_modules: [
		...
		BuyableStockManualUpdate,
		BuyableStockOrderEntry
	]
*/

//MAY SET
//MinMaxModifier::set_default_min_quantity(1);
//MinMaxModifier::set_default_max_quantity(99);
//MinMaxModifier::set_min_field("MinQuantity");
//MinMaxModifier::set_max_field("MaxQuantity");
//MinMaxModifier::set_adjustment_message("Based on stock availability, quantities have been adjusted as follows: ");
//MinMaxModifier::set_sorry_message("Sorry, your selected value not is available");
//===================---------------- END ecommerc_stockcontrol MODULE ----------------===================
