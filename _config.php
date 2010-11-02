<?php

MinMaxModifier::set_use_stock_quantities(true); //make use of the stock quantity tables to keep track of them

DataObject::add_extension('Product', 'ProductStockDecorator');
DataObject::add_extension('ProductVariation', 'ProductVariationStockDecorator');

Order::set_modifiers(array(
	'MinMaxModifier'
));