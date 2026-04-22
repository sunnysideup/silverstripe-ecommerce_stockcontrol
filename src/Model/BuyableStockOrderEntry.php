<?php

namespace Sunnysideup\EcommerceStockControl\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;
use Sunnysideup\Ecommerce\Model\Order;

/**
 *@author: Nicolaas [at] Sunny Side Up . Co . Nz
 *@description:
 * keeps a record of the quantity deduction made for each sale.  That is, if we sell 10 widgets in an order then an entry is made in this dataclass for
 * a reduction of ten widgets in the available quantity
 *
 **/

class BuyableStockOrderEntry extends DataObject
{
    private static $db = [
        'Quantity' => 'Int',
        'IncludeInCurrentCalculation' => 'Boolean',
    ];

    private static $has_one = [
        'Parent' => BuyableStockCalculatedQuantity::class,
        'Order' => Order::class,
    ];

    private static $defaults = [
        'IncludeInCurrentCalculation' => 1,
    ];

    //MODEL ADMIN STUFF
    private static $searchable_fields = [
        'Quantity',
        'IncludeInCurrentCalculation',
        'ParentID',
        'OrderID',
    ];

    private static $field_labels = [
        'Quantity' => 'Calculated Quantity On Hand',
        'IncludeInCurrentCalculation' => 'Include in Calculation',
        'ParentID' => 'Buyable Calculation',
        'OrderID' => Order::class,
    ];

    private static $summary_fields = [
        'OrderID',
        'ParentID',
        'Quantity',
    ];

    private static $default_sort = [
        'LastEdited' => 'DESC',
        'ParentID' => 'ASC',
        'ID' => 'DESC',
    ];

    private static $indexes = [
        'LastEdited' => true,
    ];

    private static $singular_name = 'Stock Sale Entry';

    public function i18n_singular_name()
    {
        return _t('BuyableStockOrderEntry.STOCKSALEENTRY', 'Stock Sale Entry');
    }

    private static $plural_name = 'Stock Sale Entries';

    public function i18n_plural_name()
    {
        return _t('BuyableStockOrderEntry.STOCKSALEENTRIES', 'Stock Sale Entries');
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function canView($member = null)
    {
        return $this->canDoAnything();
    }

    protected function canDoAnything()
    {
        EcommerceConfig::get(EcommerceRole::class, 'admin_permission_code');
        if (! Permission::check('ADMIN') && ! Permission::check($shopAdminCode)) {
            Security::permissionFailure($this, _t('Security.PERMFAILURE', ' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
        }
        return true;
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->ID) {
            //basic checks
            if (! $this->ParentID) {
                $this->delete();
                user_error('Can not create record without associated buyable.', E_USER_ERROR);
            }
            if (! $this->OrderID) {
                $this->delete();
                user_error('Can not create record without order.', E_USER_ERROR);
            }
            //make sure no duplicates are created
            $toBeDeleted = BuyableStockOrderEntry::get()
                ->filter(['OrderID' => $this->OrderID, 'ParentID' => $this->ParentID])
                ->exclude(['ID' => $this->ID])
                ->sort(['LastEdited' => 'ASC']);
            foreach ($toBeDeleted as $youAreDodo) {
                $youAreDodo->delete();
                $youAreDodo->destroy();
                user_error('deleting BuyableStockOrderEntry because there are multiples!', E_USER_ERROR);
            }
        }
    }
}
