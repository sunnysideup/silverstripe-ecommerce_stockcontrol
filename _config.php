<?php




/**
 * @author Nicolaas modules [at] sunnysideup.co.nz, + jeremy [at] burnbright.co.nz
 *
 **/


Director::addRules(50, array(
	'updatestockquantity/edit/$ProductCode/$VariationCode/$NewQuantity' => 'UpdateStockQuantity'
));




//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START ecommerc_stockcontrol MODULE ----------------===================
//MIN MAX MUST SET
//Order::set_modifiers(array('MinMaxModifier'));
// MIN MAX ONLY MAY SET
//MinMaxModifier::set_default_min_quantity(1);
//MinMaxModifier::set_default_max_quantity(99);
//MinMaxModifier::set_min_field("MinQuantity");
//MinMaxModifier::set_max_field("MaxQuantity");
//MinMaxModifier::set_adjustment_message("Based on stock availability, quantities have been adjusted as follows: ");
//MinMaxModifier::set_sorry_message("Sorry, your selected value not is available");
//MinMaxModifier::set_show_adjustments_in_checkout_table(true);

//STOCK CONTROL MUST SET
//MinMaxModifier::set_use_stock_quantities(true); //make use of the stock quantity tables to keep track of them
//DataObject::add_extension('Product', 'ProductStockDecorator');
//DataObject::add_extension('ProductVariation', 'ProductVariationStockDecorator');
//===================---------------- END ecommerc_stockcontrol MODULE ----------------===================
