<?php

namespace Sunnysideup\EcommerceStockControl\Model;

use SilverStripe\Forms\HeaderField;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * connection with external stock setting systems
 * as an orderstep
 */

class StockControlPingOrderStep extends OrderStep
{
    private static $table_name = 'StockControlPingOrderStep';

    private static $db = [
        'URLToPing' => 'Varchar(200)',
        'Username' => 'Varchar(30)',
        'Password' => 'Varchar(30)',
    ];

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanPay' => 0,
        'CustomerCanCancel' => 0,
        'Name' => 'StockControlPing',
        'Code' => 'STOCKCONTROLPING',
        'Sort' => 23,
        'ShowAsInProcessOrder' => 1,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', new HeaderField('HowToSaveSubmittedOrder', _t('OrderStep.STOCKCONTROLPING', 'Please enter details below'), 3), 'URLToPing');
        return $fields;
    }

    /**
     * Can run this step once any items have been submitted.
     * @param DataObject - $order Order
     * @return boolean
     **/
    public function initStep(Order $order): bool
    {
        return true;
    }

    /**
     * Add a member to the order - in case he / she is not a shop admin.
     * @param DataObject - $order Order
     * @return boolean
     **/
    public function doStep(Order $order): bool
    {
        $stockControlPing = StockControlPingOrderStatusLog::get()
            ->filter(['OrderID' => $order->ID])->First();
        if (! $stockControlPing) {
            $authentication = $this->Username && $this->Password ? [
                CURLOPT_USERPWD =>
                $this->Username . ':' . $this->Password,
            ] : [];
            $outcome = $this->curlGet(
                $this->URLToPing,
                [
                    'id' => $order->ID,
                    'link' => urlencode($order->APILink()),
                ],
                $authentication
            );
            //create record
            $obj = new StockControlPingOrderStatusLog();
            $obj->OrderID = $order->ID;
            $obj->Note = $outcome;
            $obj->write();
        }
        return true;
    }

    /**
     * go to next step if order has been submitted.
     *@param DataObject - $order Order
     *@return DataObject | null	(next step OrderStep)
     **/
    public function nextStep(Order $order): ?OrderStep
    {
        if ($order->IsSubmitted()) {
            return parent::nextStep($order);
        }
        return null;
    }

    /**
     * @return boolean
     */
    public function hasCustomerMessage(): bool
    {
        return false;
    }

    /**
     * Explains the current order step.
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.STOCKCONTROLPING_DESCRIPTION', "Sends a 'ping' to a third-party stock control system.");
    }

    /**
     * Send a GET requst using cURL
     * @source php.net
     * @param string $url to request
     * @param array $get values to send
     * @param array $options for cURL
     * @return string
     */
    protected function curlGet($url, array $get = null, array $options = [])
    {
        $defaults = [
            CURLOPT_URL => $url . (strpos($url, '?') === false ? '?' : '') . http_build_query($get),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
        ];
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        if (! $result = curl_exec($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }
}
