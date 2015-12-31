<?php

/**
 * connection with external stock setting systems
 * as an orderstep
 *
 *
 */


class StockControlPing_OrderStep extends OrderStep
{

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

/**
 *
 *
 *
 *
 *
 *	Example of POST:
 * 	function TestPost() {
 *
 *		$baseURL = Director::absoluteBaseURL();
 *
 *		// 1) My Personal Data
 *
 *		$className = 'StockControlPing_IncomingUpdate';
 *		$fields = array(
 *			'AllowPurchase' => 0,
 *			'InternalItemID' => "xxxx",
 * 			//below are optional (if you include ID then you leave out InternalItemID)k6
 *
 * 			//'BuyableClassName' => 'Product',
 * 			//'BuyableID' => 123,
 *		);
 *
 *		// 2) The Query
 *
 *		$url = "{$baseURL}/api/ecommerce/v1/{$className}.xml";
 *		$body = $fields;
 *		$c = curl_init($url);
 *		curl_setopt($c, CURLOPT_POST, true);
 *		curl_setopt($c, CURLOPT_POSTFIELDS, $body);
 *		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
 *		$page = curl_exec($c);
 *		curl_close($c);
 *
 *		// 3) The XML Result
 *		return $page;
 *	}
 *
 *
 */
class StockControlPing_IncomingUpdate extends DataObject
{

    private static $api_access = array(
        'create' => array('InternalItemID', 'BuyableClassName', 'BuyableID', 'AllowPurchase'),
        'add' => array('InternalItemID', 'BuyableClassName', 'BuyableID', 'AllowPurchase'),
        'view' => array('InternalItemID', 'BuyableClassName', 'BuyableID', 'AllowPurchase')
    );

    private static $db = array(
        "InternalItemID" => "Varchar(30)",
        "BuyableClassName" => "Varchar(50)",
        "BuyableID" => "Int",
        "AllowPurchase" => "Boolean",
        "Actioned" => "Boolean"
    );

    private static $default_sort = "\"LastEdited\" DESC";

    private static $singular_name = "External Update to Product Availability";
    public function i18n_singular_name()
    {
        return _t("StockControlPing.EXTERNALUPDATETOPRODUCTAVAILABILITY", "External Update to Product Availability");
    }

    private static $plural_name = "External Updates to Product Availability";
    public function i18n_plural_name()
    {
        return _t("StockControlPing.EXTERNALUPDATESTOPRODUCTAVAILABILITY", "External Updates to Product Availability");
    }

    public function canView($member = null)
    {
        return $this->canDoAnything($member);
    }

    public function canCreate($member = null)
    {
        return $this->canDoAnything($member);
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }

    protected function canDoAnything($member = null)
    {
        $shopAdminCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code");
        if (!Permission::check("ADMIN") && !Permission::check($shopAdminCode)) {
            Security::permissionFailure($this, _t('Security.PERMFAILURE', ' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
        }
        return true;
    }


    public function onAfterWrite()
    {
        parent::onAfterWrite();
        //TODO: move to findBuyable in Core Ecommerce Code!
        if (!$this->Actioned) {
            $internalItemID = Convert::raw2sql($this->InternalItemID);
            $id = intval($this->ID);
            $className = Convert::raw2sql($this->BuyableClassName);
            $allowPurchase = $this->AllowPurchase ? 1 : 0;
            if ($className) {
                if ($className && $id) {
                    $buyable = $className::get()->byID($id);
                } else {
                    $buyable = $className::get()->filter(array('InternalItemID' => $internalItemID))->First();
                }
            } else {
                $buyablesArray = EcommerceConfig::get($className = "EcommerceDBConfig", $identifier = "array_of_buyables");
                if (is_array($buyablesArray)) {
                    if (count($buyablesArray)) {
                        foreach ($buyablesArray as $className) {
                            $buyable = $className::get()->filter(array('InternalItemID' => $internalItemID))->First();
                            if ($buyable) {
                                break;
                            }
                        }
                    }
                }
            }
            if ($buyable) {
                if ($buyable->AllowPurchase =! $allowPurchase) {
                    $buyable->AllowPurchase = $allowPurchase;
                    if ($buyable instanceof SiteTree) {
                        $buyable->writeToStage('Stage');
                        $buyable->publish('Stage', 'Live');
                    } else {
                        $buyable->write();
                    }
                }
                $this->BuyableClassName = $buyable->ClassName;
                $this->BuyableID = $buyable->ID;
            }
            $this->Actioned = 1;
            $this->write();
        }
    }
}
