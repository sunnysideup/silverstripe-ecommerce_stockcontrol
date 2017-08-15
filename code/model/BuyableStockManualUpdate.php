<?php
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
    private static $db = array(
        "Quantity" => "Int",
        "ExternalUpdate" => "Boolean"
    );

    private static $has_one = array(
        "Parent" => "BuyableStockCalculatedQuantity",
        "Member" => "Member"
    );

    //MODEL ADMIN STUFF

    private static $searchable_fields = array(
        "Quantity",
        "MemberID"
    );

    private static $field_labels = array(
        "Quantity",
        "ParentID"  => "Buyable",
        "MemberID"  => "Updated by ..."
    );

    private static $summary_fields = array(
        "Parent.Name" => "Buyable",
        "Member.FirstName" => "Updater",
        "Quantity" => "Quantity"
    );

    private static $api_access = true;


    private static $indexes = [
        'LastEdited' =>  true
    ];

    private static $default_sort = [
        'LastEdited' =>  'DESC',
        'ParentID' => 'ASC',
        'ID' => 'DESC'
    ];

    private static $singular_name = "Stock Manual Update Entry";
    public function i18n_singular_name()
    {
        return _t("BuyableStockManualUpdate.STOCKUPDATEENTRY", "Stock Manual Update Entry");
    }

    private static $plural_name = "Stock Manual Update Entries";
    public function i18n_plural_name()
    {
        return _t("BuyableStockManualUpdate.STOCKUPDATEENTRIES", "Stock Manual Update Entries");
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
        if (!Permission::check($shopAdminCode)) {
            Security::permissionFailure($this, _t('Security.PERMFAILURE', ' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
        }
        return true;
    }
}
