E-commerce Stock Control
================================================================================

This module stores stock levels seperate from the products themsleves. This means that a history of stock levels can be
produced. The module also stores the type of update for each stock update - manual (adding more stock) or order based (sales).

The module also includes a MinMaxModifier, which will make sure that a product quantity in cart stays between a min and a max.

The MinMaxModifier can also be used on its own.

This module provides a stock control page for quick updating of stock levels.

About the model classes:

ProductStockOrderEntry: keeps a record of the quantity deduction made for each sale. That is, if we sell 10 items
in an order then an entry is made in this dataclass for a reduction of ten widgets in the available quantity.

ProductStockManualUpdate: at any stage, the product available quantity can be changed (manually overridden) using this class.

ProductStockCalculatedQuantity: works out the quantity available for each product based on the the number of items
sold (recorded in ProductStockOrderEntry) and manual corrections, recorded in ProductStockManualUpdate.

ProductStockVariationCalculatedQuantity: calculates quantities available for product variations


Developers
-----------------------------------------------

- Nicolaas Francken [at] sunnysideup.co.nz
- Jeremy Shipman [at] burnbright.co.nz


Documentation
-----------------------------------------------
Please contact author for more details.

Any bug reports and/or feature requests will be
looked at

We are also very happy to provide personalised support
for this module in exchange for a small donation.


Requirements
-----------------------------------------------
see composer.json


Project Home
-----------------------------------------------
See http://code.google.com/p/silverstripe-ecommerce

Demo
-----------------------------------------------
See http://www.silverstripe-ecommerce.com


Installation Instructions
-----------------------------------------------

1. Find out how to add modules to SS and add module as per usual.

2. Review configs and add entries to mysite/_config/config.yml
(or similar) as necessary.
In the _config/ folder of this module
you can usually find some examples of config options (if any).


