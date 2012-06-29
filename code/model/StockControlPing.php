<?php



class StockControlPing_OrderStep extends OrderStep {

	static $db = array(
		"URLToPing" => "Varchar(200)",
		"Username" => "Varchar(30)",
		"Password" => "Varchar(30)"
	);

	static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanPay" => 0,
		"CustomerCanCancel" => 0,
		"Name" => "StockControlPing",
		"Code" => "STOCKCONTROLPING",
		"Sort" => 23,
		"ShowAsInProcessOrder" => 1
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("HowToSaveSubmittedOrder", _t("OrderStep.STOCKCONTROLPING", "Please enter details below"), 3), "URLToPing");
		return $fields;
	}

	/**
	 * Can run this step once any items have been submitted.
	 * @param DataObject - $order Order
	 * @return Boolean
	 **/
	public function initStep($order) {
		return true;
	}

	/**
	 * Add a member to the order - in case he / she is not a shop admin.
	 * @param DataObject - $order Order
	 * @return Boolean
	 **/
	public function doStep($order) {
		if(!DataObject::get_one("StockControlPing_OrderStatusLog", "\"OrderID\" = ".$order->ID)) {
			if($this->Username && $this->Password) {
				$authentication = array(
					CURLOPT_USERPWD =>
					$this->Username.":".$this->Password
				);
			}
			else {
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
	public function nextStep($order) {
		if($order->IsSubmitted()) {
			return parent::nextStep($order);
		}
		return null;
	}

	/**
	 *
	 * @return Boolean
	 */
	protected function hasCustomerMessage(){
		return false;
	}

	/**
	 * Explains the current order step.
	 * @return String
	 */
	protected function myDescription(){
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
	protected function curlGet($url, array $get = NULL, array $options = array()) {
		$defaults = array(
			CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get),
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT => 4
		);
		$ch = curl_init();
		curl_setopt_array($ch, ($options + $defaults));
		if( ! $result = curl_exec($ch)){
			return curl_error($ch);
		}
		curl_close($ch);
		return $result;
	}


}


class StockControlPing_OrderStatusLog extends OrderStatusLog {


	public static $singular_name = "Stock Control External Ping";
		function i18n_singular_name() { return _t("OrderStatusLog.STOCKCONTROLEXTERNALPING", "Stock Control External Ping");}

	public static $plural_name = "Stock Control External Pings";
		function i18n_plural_name() { return _t("OrderStatusLog.STOCKCONTROLEXTERNALPINGS", "Stock Control External Pings");}

	static $defaults = array(
		'Title' => 'Ping External Service',
		'Note' => 'HTMLText',
		'InternalUseOnly' => 1
	);

}
