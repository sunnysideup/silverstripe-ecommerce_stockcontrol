<?php

namespace Sunnysideup\EcommerceStockControl\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
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
 */
class StockControlPingIncomingUpdate extends DataObject
{
    private static $api_access = [
        'create' => ['InternalItemID', 'BuyableClassName', 'BuyableID', 'AllowPurchase'],
        'add' => ['InternalItemID', 'BuyableClassName', 'BuyableID', 'AllowPurchase'],
        'view' => ['InternalItemID', 'BuyableClassName', 'BuyableID', 'AllowPurchase'],
    ];

    private static $table_name = 'StockControlPingIncomingUpdate';

    private static $db = [
        'InternalItemID' => 'Varchar(30)',
        'BuyableClassName' => 'Varchar(50)',
        'BuyableID' => 'Int',
        'AllowPurchase' => 'Boolean',
        'Actioned' => 'Boolean',
    ];

    private static $indexes = [
        'LastEdited' => true,
    ];

    private static $default_sort = [
        'LastEdited' => 'DESC',
        'ID' => 'DESC',
    ];

    private static $singular_name = 'External Update to Product Availability';

    public function i18n_singular_name()
    {
        return _t('StockControlPing.EXTERNALUPDATETOPRODUCTAVAILABILITY', 'External Update to Product Availability');
    }

    private static $plural_name = 'External Updates to Product Availability';

    public function i18n_plural_name()
    {
        return _t('StockControlPing.EXTERNALUPDATESTOPRODUCTAVAILABILITY', 'External Updates to Product Availability');
    }

    public function canView($member = null)
    {
        return $this->canDoAnything($member);
    }

    public function canCreate($member = null, $context = [])
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
        $shopAdminCode = EcommerceConfig::get(EcommerceRole::class, 'admin_permission_code');
        if (! Permission::check('ADMIN') && ! Permission::check($shopAdminCode)) {
            Security::permissionFailure($this, _t('Security.PERMFAILURE', ' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
        }
        return true;
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        //TODO: move to findBuyable in Core Ecommerce Code!
        if (! $this->Actioned) {
            $internalItemID = Convert::raw2sql($this->InternalItemID);
            $id = intval($this->ID);

            $className = Convert::raw2sql($this->BuyableClassName);
            $allowPurchase = $this->AllowPurchase ? 1 : 0;
            if ($className) {
                if ($className && $id) {
                    $buyable = $className::get()->byID($id);
                } else {
                    $buyable = $className::get()->filter(['InternalItemID' => $internalItemID])->First();
                }
            } else {
                $buyablesArray = EcommerceConfig::get($className = EcommerceDBConfig::class, $identifier = 'array_of_buyables');
                if (is_array($buyablesArray) && count($buyablesArray)) {
                    foreach ($buyablesArray as $className) {
                        $buyable = $className::get()->filter(['InternalItemID' => $internalItemID])->First();
                        if ($buyable) {
                            break;
                        }
                    }
                }
            }
            if ($buyable) {
                if ($buyable->AllowPurchase = ! $allowPurchase) {
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
