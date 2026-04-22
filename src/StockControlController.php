<?php

namespace Sunnysideup\EcommerceStockControl;

use Override;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Director;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\EcommerceStockControl\Model\BuyableStockCalculatedQuantity;
use Sunnysideup\EcommerceStockControl\Model\BuyableStockManualUpdate;
use Sunnysideup\EcommerceStockControl\Model\BuyableStockOrderEntry;

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_stockcontrol
 * @description:
 *  This is the central management page for organising stock control
 *  You will need to "turn on" the MinMaxModifier and add MinMaxModifier::set_use_stock_quantities(true)
 *  to get this page working.
 *
 *
 **/

class StockControlController extends ContentController
{
    private static $allowed_actions = [
        'update' => 'SHOPADMIN',
        'history' => 'SHOPADMIN',
    ];

    #[Override]
    protected function init()
    {
        // Only administrators can run this method
        $shopAdminCode = EcommerceConfig::get(EcommerceRole::class, 'admin_permission_code');
        if (! Permission::check('ADMIN') && ! Permission::check($shopAdminCode)) {
            Security::permissionFailure($this, _t('Security.PERMFAILURE', ' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
        }

        parent::init();

        Requirements::themedCSS('sunnysideup/ecommerce_stockcontrol: StockControlPage', 'ecommerce_stockcontrol');
        Requirements::javascript('sunnysideup/ecommerce_stockcontrol: silverstripe/admin: thirdparty/jquery/jquery.js');
        //Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
        //Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
        Requirements::javascript('sunnysideup/ecommerce_stockcontrol: ecommerce_stockcontrol/javascript/StockControlPage.js');
        $url = Director::absoluteURL($this->Link() . 'update/');
        Requirements::customScript("StockControlPage.set_url('" . $url . "');", 'StockControlPage.set_url');
    }

    #[Override]
    public function Link($action = null)
    {
        $link = '/update-stock/';
        if ($action) {
            $link .= $action . '/';
        }

        return $link;
    }

    public function StockProductObjects()
    {
        $buyableStockCalculatedQuantities = BuyableStockCalculatedQuantity::get()->limit(1000);
        if ($buyableStockCalculatedQuantities->count()) {
            foreach ($buyableStockCalculatedQuantities as $buyableStockCalculatedQuantity) {
                $buyable = $buyableStockCalculatedQuantity->Buyable();
                if ($buyable) {
                    if ($buyable->UnlimitedStock) {
                        $buyableStockCalculatedQuantities->remove($buyableStockCalculatedQuantity);
                    } else {
                        $buyableStockCalculatedQuantity->calculatedBaseQuantity();
                    }
                } else {
                    //user_error("Buyable can not be found!", E_USER_NOTICE);
                }
            }

            return $buyableStockCalculatedQuantities;
        }
    }

    public function update($request = null)
    {
        $id = intval($request->param('ID'));
        $newValue = intval($request->param('OtherID'));
        if ($newValue || $newValue === 0) {
            $obj = BuyableStockCalculatedQuantity::get()->byID($id);
            if ($obj) {
                if ($buyable = $obj->getBuyable()) {
                    $buyable->setActualQuantity($newValue);
                    $msg = '<em>' . $obj->Name . '</em> quantity updated to <strong>' . $newValue . '</strong>';
                    return $this->customise(['Message' => $msg])->RenderWith('UpdateStockQuantity');
                } else {
                    user_error('Could not create Calculation object', E_USER_NOTICE);
                }
            } else {
                user_error(sprintf('could not find record: %d ', $id), E_USER_NOTICE);
            }
        } else {
            user_error('new quantity specified is unknown', E_USER_NOTICE);
        }
        return null;
    }

    public function history($request = null)
    {
        $id = intval($request->param('ID'));
        $buyableStockCalculatedQuantity = BuyableStockCalculatedQuantity::get()->byID($id);
        if ($buyableStockCalculatedQuantity) {
            $buyableStockCalculatedQuantity->ManualUpdates = BuyableStockManualUpdate::get()->filter(['ParentID' => $buyableStockCalculatedQuantity->ID]);
            $buyableStockCalculatedQuantity->OrderEntries = BuyableStockOrderEntry::get()->filter(['ParentID' => $buyableStockCalculatedQuantity->ID]);
            $graphArray = [];
            return $this->customise($buyableStockCalculatedQuantity)->RenderWith('AjaxStockControlPageHistory');
        } else {
            return ' could not find historical data';
        }
    }
}
