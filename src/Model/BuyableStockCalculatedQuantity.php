<?php

namespace Sunnysideup\EcommerceStockControl\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\OrderAttribute;
use Sunnysideup\Ecommerce\Model\OrderItem;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 *@author: Nicolaas [at] Sunny Side Up . Co . Nz
 *@description:
 * works out the quantity available for each buyable
 * based on the the number of items sold, recorded in BuyableStockOrderEntry,
 * and manual corrections, recorded in BuyableStockManualUpdate.
 *
 *
 **/

class BuyableStockCalculatedQuantity extends DataObject
{
    private static $db = [
        'BaseQuantity' => 'Int',
        'BuyableID' => 'Int',
        'BuyableClassName' => 'Varchar',
    ];

    private static $has_many = [
        'BuyableStockOrderEntry' => BuyableStockOrderEntry::class,
        'BuyableStockManualUpdate' => BuyableStockManualUpdate::class,
    ];

    private static $defaults = [
        'BaseQuantity' => 0,
    ];

    private static $casting = [
        'Name' => 'Varchar',
        'Buyable' => 'DataObject',
        'UnlimitedStock' => 'Boolean',
    ];

    //MODEL ADMIN STUFF
    private static $searchable_fields = [
        'BaseQuantity',
    ];

    private static $field_labels = [
        'BaseQuantity' => 'Calculated Quantity On Hand',
        'BuyableID' => 'Buyable ID',
        'LastEdited' => 'Last Calculated',
    ];

    private static $summary_fields = [
        'Name',
        'BaseQuantity',
        'LastEdited',
    ];

    private static $indexes = [
        'BuyableClassName' => true,
        'BuyableID' => true,
        'BaseQuantity' => true,
    ];

    private static $default_sort = [
        'BuyableClassName' => 'ASC',
        'BaseQuantity' => 'DESC',
        'ID' => 'ASC',
    ];

    private static $singular_name = 'Stock Calculated Quantity';

    private static $plural_name = 'Stock Calculated Quantities';

    private static $calculation_done = [];

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

    public function Link($action = 'update')
    {
        return '/update-stock/' . $action . '/' . $this->ID . '/';
    }

    public function HistoryLink()
    {
        return $this->Link('history');
    }

    public function Buyable()
    {
        return $this->getBuyable();
    }

    public function getBuyable()
    {
        if ($this->BuyableID && class_exists($this->BuyableClassName)) {

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD: $className
             * NEW: $className ...  (COMPLEX)
             * EXP: Check if the class name can still be used as such
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
            $className = $this->BuyableClassName;

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD: $className
             * NEW: $className ...  (COMPLEX)
             * EXP: Check if the class name can still be used as such
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
            return $className::get()->byID($this->BuyableID);
        }
    }

    public function UnlimitedStock()
    {
        return $this->geUnlimitedStock();
    }

    public function getUnlimitedStock()
    {
        if ($buyable = $this->getBuyable()) {
            return $buyable->UnlimitedStock;
        }
    }

    public function Name()
    {
        return $this->getName();
    }

    public function getName()
    {
        if ($buyable = $this->getBuyable()) {
            return $buyable->getTitle();
        }
        return 'no name';
    }

    protected function canDoAnything($member = null)
    {
        if (($buyable = $this->getBuyable()) && $buyable->canEdit($member = null)) {
            return true;
        }
        Security::permissionFailure($this, _t('Security.PERMFAILURE', ' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
        return null;
    }

    public static function get_quantity_by_buyable($buyable)
    {
        $value = 0;
        $item = self::get_by_buyable($buyable);
        if ($item) {
            $value = $item->calculatedBaseQuantity();
            if ($value < 0) {
                $value = 0;
            }
        }
        return $value;
    }

    public static function get_by_buyable($buyable)
    {
        $obj = BuyableStockCalculatedQuantity::get()
            ->filter(
                [
                    'BuyableID' => $buyable->ID,
                    'BuyableClassName' => $buyable->ClassName,
                ]
            )
            ->First();
        if ($obj) {
            //do nothing
        } else {
            $obj = new BuyableStockCalculatedQuantity();
            $obj->BuyableID = $buyable->ID;
            $obj->BuyableClassName = $buyable->ClassName;
        }
        if ($obj) {
            if (isset($obj->ID) && $obj->exists() && $obj->UnlimitedStock == $buyable->UnlimitedStock) {
                //do nothing
            } else {
                $obj->UnlimitedStock = $buyable->UnlimitedStock;
                //we must write here to calculate quantities
                $obj->write();
            }
            return $obj;
        }
        user_error('Could not find / create BuyableStockCalculatedQuantity for buyable with ID / ClassName ' . $buyableID . '/' . $buyableClassName, E_WARNING);
    }

    public function calculatedBaseQuantity()
    {
        if (! $this->ID) {
            return 0;
        }
        $actualQuantity = $this->workoutActualQuantity();
        if ($actualQuantity != $this->BaseQuantity) {
            $this->BaseQuantity = $actualQuantity;
            $this->write();
            return $actualQuantity;
        } else {
            return $this->getField('BaseQuantity');
        }
    }

    protected function calculatedBaseQuantities($buyables = null)
    {
        if ($buyables) {
            foreach ($buyables as $buyable) {
                $buyableStockCalculatedQuantity = BuyableStockCalculatedQuantity::get_by_buyable($buyable);
                if ($buyableStockCalculatedQuantity) {
                    $buyableStockCalculatedQuantity->calculatedBaseQuantity();
                }
            }
        }
    }

    /**
     * TODO: change to submitted from CustomerCanEdit criteria
     */
    protected function workoutActualQuantity()
    {
        $actualQuantity = 0;
        if ($buyable = $this->getBuyable()) {
            $query = Order::get()
                ->where('
                    "OrderItem"."BuyableID" = ' . (intval($this->BuyableID)) . '
                    AND
                    "OrderItem"."BuyableClassName" = \'' . $this->BuyableClassName . '\'
                    AND
                    "OrderStep"."CustomerCanEdit" = 0
                    AND
                    "Order"."ID" <> ' . ShoppingCart::current_order()->ID . '
                ')
                ->innerJoin(OrderAttribute::class, '"OrderAttribute"."OrderID" = "Order"."ID"')
                ->innerJoin(OrderItem::class, '"OrderAttribute"."ID" = "OrderItem"."ID"')
                ->innerJoin(OrderStep::class, '"OrderStep"."ID" = "Order"."StatusID"');
            $amountPerOrder = [];
            if ($query->count()) {
                foreach ($query as $row) {
                    if (! isset($amountPerOrder[$row->OrderID])) {
                        $amountPerOrder[$row->OrderID] = 0;
                    }
                    $amountPerOrder[$row->OrderID] += $row->Quantity;
                }
                foreach ($amountPerOrder as $orderID => $sum) {
                    if ($orderID && $sum) {
                        $buyableStockOrderEntry = BuyableStockOrderEntry::get()
                            ->filter(
                                [
                                    'OrderID' => $orderID,
                                    'ParentID' => $this->ID,
                                ]
                            )
                            ->First();
                        if ($buyableStockOrderEntry) {
                            //do nothing
                        } else {
                            $buyableStockOrderEntry = new BuyableStockOrderEntry();
                            $buyableStockOrderEntry->OrderID = $orderID;
                            $buyableStockOrderEntry->ParentID = $this->ID;
                            $buyableStockOrderEntry->IncludeInCurrentCalculation = 1;
                            $buyableStockOrderEntry->Quantity = 0;
                        }
                        if ($buyableStockOrderEntry->Quantity != $sum) {
                            $buyableStockOrderEntry->Quantity = $sum;
                            $buyableStockOrderEntry->write();
                        }
                    }
                }
            }
            //find last adjustment
            $latestManualUpdate = BuyableStockManualUpdate::get()
                ->filter(['ParentID' => $this->ID])
                ->sort(['LastEdited' => 'DESC'])
                ->First();
            //nullify order quantities that were entered before last adjustment
            if ($latestManualUpdate) {
                $latestManualUpdateQuantity = $latestManualUpdate->Quantity;
                DB::query(
                    "
                    UPDATE \"BuyableStockOrderEntry\"
                    SET \"IncludeInCurrentCalculation\" = 0
                    WHERE
                    \"LastEdited\" < '" . $latestManualUpdate->LastEdited . "'
                        AND
                        \"ParentID\" = " . $this->ID
                );
            } else {
                $latestManualUpdateQuantity = 0;
            }
            //work out additional purchases
            $orderQuantityToDeduct = BuyableStockOrderEntry::get()
                ->filter(
                    [
                        'ParentID' => $this->ID,
                        'IncludeInCurrentCalculation' => 1,
                    ]
                )->sum('Quantity');
            if (! $orderQuantityToDeduct) {
                $orderQuantityToDeduct = 0;
            }
            //work out base total
            $actualQuantity = $latestManualUpdateQuantity - $orderQuantityToDeduct;
            if (isset($_GET['debug'])) {
                echo '<hr />';
                echo $this->Name;
                echo ' | Manual SUM: ' . $latestManualUpdateQuantity;
                echo ' | Order SUM: ' . $orderQuantityToDeduct;
                echo ' | Total SUM: ' . $this->BaseQuantity;
                echo '<hr />';
            }
        }
        return $actualQuantity;
    }
}
