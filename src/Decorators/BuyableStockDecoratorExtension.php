<?php

namespace Sunnysideup\EcommerceStockControl\Decorators;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;
use Sunnysideup\EcommerceStockControl\Modifiers\MinMaxModifier;

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
            $min = $this->getOwner()->$minField;
        }

        if ($maxField = Config::inst()->get(MinMaxModifier::class, 'max_field')) {
            $max = $this->getOwner()->$maxField;
        }

        $js = '
            MinMaxModifierData = [];
            MinMaxModifierData.push(
                {
                    selector: "' . $fieldSelector . '",
                    min: ' . intval($min) . ',
                    max: ' . intval($max) . ',
                    msg: "' . addslashes((string) $msg) . '"
                }
            );';
        Requirements::javascript('ecommerce_stockcontrol/javascript/MinMaxModifier.js');
        Requirements::customScript($js, $fieldSelector);
        return [];
    }
}
