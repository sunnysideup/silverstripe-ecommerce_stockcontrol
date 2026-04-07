<?php

/**
 * connection with external stock setting systems
 * as an orderstep
 *
 *
 */


class StockControlPing_OrderStep extends OrderStep
{

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD: private static $db
  * EXP: Check that is class indeed extends DataObject and that it is not a data-extension!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    
    private static $table_name = 'StockControlPing_OrderStep';

    private static $db = array(
        "URLToPing" => "Varchar(200)",
        "Username" => "Varchar(30)",
        "Password" => "Varchar(30)"
    );

    private static $defaults = array(
        "CustomerCanEdit" => 0,
        "CustomerCanPay" => 0,
        "CustomerCanCancel" => 0,
        "Name" => "StockControlPing",
        "Code" => "STOCKCONTROLPING",
        "Sort" => 23,
        "ShowAsInProcessOrder" => 1
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Main", new HeaderField("HowToSaveSubmittedOrder", _t("OrderStep.STOCKCONTROLPING", "Please enter details below"), 3), "URLToPing");
        return $fields;
    }

    /**
     * Can run this step once any items have been submitted.
     * @param DataObject - $order Order
     * @return Boolean
     **/
    public function initStep(Order $order)
    {
        return true;
    }

    /**
     * Add a member to the order - in case he / she is not a shop admin.
     * @param DataObject - $order Order
     * @return Boolean
     **/
    public function doStep(Order $order)
    {
        $stockControlPing = StockControlPing_OrderStatusLog::get()
            ->filter(array('OrderID' => $order->ID))->First();
        if (!$stockControlPing) {
            if ($this->Username && $this->Password) {
                $authentication = array(
                    CURLOPT_USERPWD =>
                    $this->Username.":".$this->Password
                );
            } else {
                $authentication = array();
            }
            $outcome = $this->curlGet(
                $this->URLToPing,
                array(
                    "id" => $order->ID,
                    "link" => urlencode($order->APILink())
                ),
                $authentication
            );
            //create record
            $obj = new StockControlPing_OrderStatusLog();
            $obj->OrderID = $order->ID;
            $obj->Note = $outcome;
            $obj->write();
        }
        return true;
    }

    /**
     * go to next step if order has been submitted.
     *@param DataObject - $order Order
     *@return DataObject | Null	(next step OrderStep)
     **/
    public function nextStep(Order $order)
    {
        if ($order->IsSubmitted()) {
            return parent::nextStep($order);
        }
        return null;
    }

    /**
     *
     * @return Boolean
     */
    protected function hasCustomerMessage()
    {
        return false;
    }

    /**
     * Explains the current order step.
     * @return String
     */
    protected function myDescription()
    {
        return _t("OrderStep.STOCKCONTROLPING_DESCRIPTION", "Sends a 'ping' to a third-party stock control system.");
    }


    /**
     * Send a GET requst using cURL
     * @source php.net
     * @param string $url to request
     * @param array $get values to send
     * @param array $options for cURL
     * @return string
     */
    protected function curlGet($url, array $get = null, array $options = array())
    {
        $defaults = array(
            CURLOPT_URL => $url. (strpos($url, '?') === false ? '?' : ''). http_build_query($get),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4
        );
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        if (! $result = curl_exec($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }
}


