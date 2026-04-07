<?php

/**
 * BuyableStockDecorator
 * Extension for any buyable - adding stock level capabilities.
 */


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  extends DataExtension (ignore case)
  * NEW:  extends DataExtension ...  (COMPLEX)
  * EXP: Check for use of $this->anyVar and replace with $this->anyVar[$this->owner->ID] or consider turning the class into a trait
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
class BuyableStockDecorator extends DataExtension
{

    /**
     * Array of Class Names of classes that are buyables
     * @to do, do we need this here, why not use the original buyable dod.
     * @var Array
     *
     */
    private static $buyables = [];

    /**
     * Selector for field in the HTML where quantities are set
     * @to do - move this out to template...
     * @var String
     *
     */
    private static $quantity_field_selector = "";

    /**
     * Standard SS method
     *
     * @return Array
     */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    
    private static $table_name = 'BuyableStockDecorator';

    private static $db = array(
        'MinQuantity' => 'Int',
        'MaxQuantity' => 'Int',
        'UnlimitedStock' => 'Boolean'
    );

    private static $casting = array(
        'ActualQuantity' => 'Int'
    );

    private static $defaults = array(
        'UnlimitedStock' => 1,
        'MinQuantity' => 0,
        'MaxQuantity' => 0
    );

    /*
     * Allow setting stock level in CMS
     */
    public function updateCMSFields(FieldList $fields)
    {
        $tabName = 'Root.Stock';
        $fields->addFieldsToTab(
            $tabName,
            array(
                new HeaderField('MinMaxHeader', 'Minimum and Maximum Quantities per Order', 3),

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: new NumericField
  * EXP: check the number of decimals required and add as ->setScale(2)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: NumericField::create
  * NEW: NumericField::create ...  (COMPLEX)
  * EXP: check the number of decimals required and add as ->setScale(2)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                NumericField::create('MinQuantity', 'Min. Qty per order'),

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: new NumericField
  * EXP: check the number of decimals required and add as ->setScale(2)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: NumericField::create
  * NEW: NumericField::create ...  (COMPLEX)
  * EXP: check the number of decimals required and add as ->setScale(2)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                NumericField::create('MaxQuantity', 'Max. Qty per order'),
                new HeaderField('ActualQuantityHeader', 'Stock available', 3),
                new CheckboxField('UnlimitedStock', 'Unlimited Stock'),

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: new NumericField
  * EXP: check the number of decimals required and add as ->setScale(2)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: NumericField::create
  * NEW: NumericField::create ...  (COMPLEX)
  * EXP: check the number of decimals required and add as ->setScale(2)
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                NumericField::create('ActualQuantity', 'Actual Stock Available', $this->getActualQuantity()),
                new HeaderField('ActualQuantityAdjustmentHeader', 'Adjust all stock', 3),
                new LiteralField('ActualQuantityAdjustmentLink', 'This CMS also provides a <a href="/update-stock/" target="_blank">quick stock adjuster</a>.')
            )
        );
    }

    /*
     * Getter for stock level
     */
    public function ActualQuantity()
    {
        return $this->getActualQuantity();
    }
    public function getActualQuantity()
    {
        return BuyableStockCalculatedQuantity::get_quantity_by_buyable($this->owner);
    }

    /*
     * Setter for stock level
     * @param int $value
     */
    public function setActualQuantity($value)
    {
        if (!$this->owner->ID) {
            $this->owner->write();
        }
        //only set stock level if it differs from previous
        $shopAdminCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code");
        if ($this->owner->ID) {
            if ($shopAdminCode && Permission::check($shopAdminCode)) {
                if ($value != $this->owner->getActualQuantity()) {
                    $parent = BuyableStockCalculatedQuantity::get_by_buyable($this->owner);
                    if ($parent) {
                        $member = Security::getCurrentUser();
                        $obj = new BuyableStockManualUpdate();
                        $obj->ParentID = $parent->ID;
                        $obj->Quantity = (int)$value;
                        $obj->MemberID = $member->ID;
                        $obj->write();
                    } else {
                        user_error("Could not write BuyableStockCalculatedQuantity Object because there was no parent ".$this->owner->Title);
                    }
                }
            } else {
                user_error("Could not write BuyableStockCalculatedQuantity Object because you do not have permissions ".$this->owner->Title);
            }
        }
    }


    /**
     * This is a pivotal method.
     * Only allow purchase if stock levels allow
     * TODO: customise this to a certain stock level, on, or off
     * @return null (equals TRUE!), true or false
     */
    public function canPurchase($member = null)
    {
        if ($this->owner->UnlimitedStock) {
            return null;
        }
        if ($this->owner->getActualQuantity() < $this->owner->MinQuantity) {
            return false;
        }
        if ($this->owner->getActualQuantity() <= 0) {
            return false;
        }
        return null; //returning null ensures that the value from this method is ignored.
    }

    /**
     * stanard SS metehod
     */
    public function onAfterWrite()
    {
        BuyableStockCalculatedQuantity::get_by_buyable($this->owner);
        if (isset($_REQUEST["ActualQuantity"])) {
            $actualQuantity = intval($_REQUEST["ActualQuantity"]);
            if ($actualQuantity != $this->owner->getActualQuantity() && ($actualQuantity === 0 || $actualQuantity)) {
                $this->owner->setActualQuantity($actualQuantity);
            }
        }
    }
}


