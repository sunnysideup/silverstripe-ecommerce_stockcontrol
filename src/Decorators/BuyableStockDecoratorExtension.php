<?php

namespace Sunnysideup\EcommerceStockControl\Decorators;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;
use Sunnysideup\EcommerceStockControl\Modifiers\MinMaxModifier;

/**
 * ### @@@@ START REPLACEMENT @@@@ ###
 * WHY: automated upgrade
 * OLD:  extends Extension (ignore case)
 * NEW:  extends Extension ...  (COMPLEX)
 * EXP: Check for use of $this->anyVar and replace with $this->anyVar[$this->owner->ID] or consider turning the class into a trait
 * ### @@@@ STOP REPLACEMENT @@@@ ###
 */
class BuyableStockDecoratorExtension extends Extension
{
    /**
     * TO DO: review method below
     *  - move to init???
     *  - decorate Ecommerce Quantity Field?
     *
     **/
    public function index()
    {
        $min = 0;
        $max = 0;
        $msg = Config::inst()->get(MinMaxModifier::class, 'sorry_message');
        $fieldSelector = Config::inst()->get(BuyableStockDecorator::class, 'quantity_field_selector');
        if ($minField = Config::inst()->get(MinMaxModifier::class, 'min_field')) {
            $min = $this->owner->$minField;
        }
        if ($maxField = Config::inst()->get(MinMaxModifier::class, 'max_field')) {
            $max = $this->owner->$maxField;
        }
        $js = '
            MinMaxModifierData = [];
            MinMaxModifierData.push(
                {
                    selector: "' . $fieldSelector . '",
                    min: ' . intval($min) . ',
                    max: ' . intval($max) . ',
                    msg: "' . addslashes($msg) . '"
                }
            );';
        Requirements::javascript('ecommerce_stockcontrol/javascript/MinMaxModifier.js');
        Requirements::customScript($js, $fieldSelector);
        return [];
    }
}
