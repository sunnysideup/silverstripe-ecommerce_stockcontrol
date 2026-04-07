<?php

namespace Sunnysideup\EcommerceStockControl\Model;


use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;



class StockControlPing_OrderStatusLog extends OrderStatusLog
{
    private static $singular_name = "Stock Control External Ping";
    public function i18n_singular_name()
    {
        return _t("OrderStatusLog.STOCKCONTROLEXTERNALPING", "Stock Control External Ping");
    }

    private static $plural_name = "Stock Control External Pings";
    public function i18n_plural_name()
    {
        return _t("OrderStatusLog.STOCKCONTROLEXTERNALPINGS", "Stock Control External Pings");
    }

    private static $defaults = array(
        'Title' => 'Ping External Service',
        'Note' => 'HTMLText',
        'InternalUseOnly' => 1
    );
}


