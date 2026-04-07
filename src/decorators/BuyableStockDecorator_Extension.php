<?php

class BuyableStockDecorator_Extension extends Extension
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
        $msg = Config::inst()->get("MinMaxModifier", "sorry_message");
        $fieldSelector = Config::inst()->get("BuyableStockDecorator", "quantity_field_selector");
        if ($minField = Config::inst()->get("MinMaxModifier", "min_field")) {
            $min = $this->owner->$minField;
        }
        if ($maxField = Config::inst()->get("MinMaxModifier", "max_field")) {
            $max = $this->owner->$maxField;
        }
        $js = '
            MinMaxModifierData = [];
            MinMaxModifierData.push(
                {
                    selector: "'.$fieldSelector.'",
                    min: '.intval($min).',
                    max: '.intval($max).',
                    msg: "'.addslashes($msg).'"
                }
            );';
        Requirements::javascript("ecommerce_stockcontrol/javascript/MinMaxModifier.js");
        Requirements::customScript($js, $fieldSelector);
        return array();
    }
}


