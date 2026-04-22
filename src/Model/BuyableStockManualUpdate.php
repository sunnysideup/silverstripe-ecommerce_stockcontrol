<?php

namespace Sunnysideup\EcommerceStockControl\Model;

use Override;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 *@author: Nicolaas [at] Sunny Side Up . Co . Nz
 *@description: manual change for a buyable
 * the buyable available quantity can be changed (manually overridden) using this class.
 *
 * All entries link to the BuyableStockCalculatedQuantity object.
 * The BuyableStockCalculatedQuantity objects calculates, for each buyable,
 * how many are available.
 *
 **/

class BuyableStockManualUpdate extends DataObject
{
    private static $table_name = 'BuyableStockManualUpdate';

    private static $db = [
        'Quantity' => 'Int',
        'ExternalUpdate' => 'Boolean',
    ];

    private static $has_one = [
        'Parent' => BuyableStockCalculatedQuantity::class,
        'Member' => Member::class,
    ];

    //MODEL ADMIN STUFF

    private static $searchable_fields = [
        'Quantity',
        'MemberID',
    ];

    private static $field_labels = [
        'Quantity',
        'ParentID' => 'Buyable',
        'MemberID' => 'Updated by ...',
    ];

    private static $summary_fields = [
        'Parent.Name' => 'Buyable',
        'Member.FirstName' => 'Updater',
        'Quantity' => 'Quantity',
    ];

    private static $api_access = true;

    private static $indexes = [
        'LastEdited' => true,
    ];


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: default_sort = [
  * NEW: default_sort = [ ...  (COMPLEX)
  * EXP: A string is preferred over an array
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    private static $default_sort = [
        'LastEdited' => 'DESC',
        'ParentID' => 'ASC',
        'ID' => 'DESC',
    ];

    private static $singular_name = 'Stock Manual Update Entry';

    #[Override]
    public function i18n_singular_name()
    {
        return _t('BuyableStockManualUpdate.STOCKUPDATEENTRY', 'Stock Manual Update Entry');
    }

    private static $plural_name = 'Stock Manual Update Entries';

    #[Override]
    public function plural_name()
    {
        return _t('BuyableStockManualUpdate.STOCKUPDATEENTRIES', 'Stock Manual Update Entries');
    }

    #[Override]
    public function canView($member = null)
    {
        return $this->canDoAnything($member);
    }

    #[Override]
    public function canCreate($member = null, $context = [])
    {
        return $this->canDoAnything($member);
    }

    #[Override]
    public function canEdit($member = null)
    {
        return false;
    }

    #[Override]
    public function canDelete($member = null)
    {
        return false;
    }

    protected function canDoAnything($member = null)
    {
        $shopAdminCode = EcommerceConfig::get(EcommerceRole::class, 'admin_permission_code');
        if (! Permission::check($shopAdminCode)) {
            Security::permissionFailure($this, _t('Security.PERMFAILURE', ' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
        }

        return true;
    }
}
