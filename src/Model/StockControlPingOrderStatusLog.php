<?php

declare(strict_types=1);

namespace Sunnysideup\EcommerceStockControl\Model;

use Override;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;

class StockControlPingOrderStatusLog extends OrderStatusLog
{
    private static $table_name = 'StockControlPingOrderStatusLog';

    private static $singular_name = 'Stock Control External Ping';

    #[Override]
    public function i18n_singular_name()
    {
        return _t('OrderStatusLog.STOCKCONTROLEXTERNALPING', 'Stock Control External Ping');
    }

    private static $plural_name = 'Stock Control External Pings';

    #[Override]
    public function plural_name()
    {
        return _t('OrderStatusLog.STOCKCONTROLEXTERNALPINGS', 'Stock Control External Pings');
    }

    private static $defaults = [
        'Title' => 'Ping External Service',
        'Note' => 'HTMLText',
        'InternalUseOnly' => 1,
    ];
}
