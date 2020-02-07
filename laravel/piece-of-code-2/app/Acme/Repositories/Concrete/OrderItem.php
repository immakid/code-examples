<?php

namespace App\Acme\Repositories\Concrete;

use Illuminate\Support\Arr;
use App\Jobs\RefreshNornixCache;
use App\Models\FinancialTransactions;
use App\Models\Price;
use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\OrderItemInterface;
use App\Acme\Libraries\Payment\Exceptions\PaymentProviderResponseException;

class OrderItem extends EloquentRepository implements OrderItemInterface {

    /**
     * @return string
     */
    protected function model() {
        return \App\Models\Orders\OrderItem::class;
    }

    /**
     * @param \App\Models\Orders\OrderItem $item
     * @return Price|bool
     */
    public function credit(\App\Models\Orders\OrderItem $item) {

        $order = $item->order;
        $price = $item->totalCaptured;
        //print_logs_app("item quantity - ".$item->quantity);
        //print_logs_app("item previous_refunded_quantity - ".$item->previous_refunded_quantity);
        //print_logs_app("item refunded_quantity - ".$item->refunded_quantity);
        // if ($item->quantity == $item->refunded_quantity) {
        //     //print_logs_app("---------> Returing true from OrderItem <--------");
        //     return true;
        // }
        if ( ($item->refunded_quantity > 0) && ($item->refunded_quantity <= $item->quantity) ) {

            if ($item->previous_refunded_quantity > 0) {
                print_logs_app("Inside OrderItem CUSTOM price calculation");
                //print_logs_app("item refunded_quantity - ".$item->refunded_quantity);
                //print_logs_app("price value -------------> ".$price->value);
                //print_logs_app("item quantity - ".$item->quantity);
                //print_logs_app("price_value - ".($item->refunded_quantity * $price->value)/$item->quantity);
                $price_value = (($item->refunded_quantity - $item->previous_refunded_quantity) * $price->value)/$item->quantity;
                //print_logs_app("price_value - ".$price_value);
                $item_totalVatDiscounted_value = (($item->refunded_quantity - $item->previous_refunded_quantity) * $item->totalVatDiscounted->value)/$item->quantity;
                //print_logs_app("item_totalVatDiscounted_value - ".$item_totalVatDiscounted_value);

                //print_logs_app("Updating previous_refunded_quantity - ".($item->refunded_quantity+$item->previous_refunded_quantity));
            } else {
                $price_value = ($item->refunded_quantity * $price->value)/$item->quantity;
                $item_totalVatDiscounted_value = ($item->refunded_quantity * $item->totalVatDiscounted->value)/$item->quantity;
            }
            print_logs_app("CALLING UPDATE previous_refunded_quantity - ".$item->refunded_quantity." CURRENT STATUS - ".$item->hrStatus);
            if (!$item->update(["previous_refunded_quantity"=>$item->refunded_quantity])) {
                //print_logs_app("------> Error in update previous_refunded_quantity - 2");
            }
        } else {
            //print_logs_app("Inside OrderItem DEFAULT price calculation");
            $price_value = $price->value;
            $item_totalVatDiscounted_value = $item->totalVatDiscounted->value;
        }
        $store = $item->product->store;
        //$totalShipping = $order->getShippingPrice($store);
        $totalShipping = 0;

        $totalPaidByCustomer = $price_value - $totalShipping ;
        $totalVatDiscounted = number_format($item_totalVatDiscounted_value,2);
        $wg_commission = ($totalPaidByCustomer*config('cms.defaults.wg_commission_percentage'))/100;
        $exclude_wg_commission = $totalPaidByCustomer - $wg_commission;
        $defaults = app('defaults');

        try {
            print_logs_app("BEFORE calling issueRefund");
            if (app('payment', ['payex'])
                ->setTotal($totalPaidByCustomer)
                ->setTotalShipping($totalShipping)
                ->setTotalVat($totalVatDiscounted)
                ->setOrderId(sprintf("%s-%s", $store->data('payex.prefix'), $order->internal_id))
                ->issueRefund($order->transaction_id)) {

                print_logs_app("inside if condition OrderItem");

                $insert = [
                    'type' => "debit",
                    'order_id' => $order->internal_id,
                    'transaction_id' => $order->transaction_id,
                    'store_id' => $store->id,
                    'currency_id' => $defaults->currency->id,
                    'total_sales_price' => $totalPaidByCustomer,
                    'wg_commission' => $wg_commission,
                    'exclude_wg_commission' => $exclude_wg_commission,
                    'vat' => $totalVatDiscounted,
                    'exclude_vat' => $exclude_wg_commission-$totalVatDiscounted,
                    'vat_commission' => ($wg_commission*config('cms.defaults.wg_vat_percentage'))/100,
                    'payable_to_store' =>$exclude_wg_commission-(($wg_commission*config('cms.defaults.wg_vat_percentage'))/100),
                ];
                print_logs_app("Insert - ".print_r($insert,true));

                $inserting_account = FinancialTransactions::insert($insert);
                RefreshNornixCache::clearFinancialTransactionsCache();
                print_logs_app("AFTER calling issueRefund");
                return $price;
            } else {
                print_logs_app("--------------> ERROR in issueRefund");
            }

        } catch (PaymentProviderResponseException $e) {
            print_logs_app("----------> PaymentProviderResponseException <---------");
            return false;
        }

        //print_logs_app("Retuning false in OrderItem");

        return false;
    }
    /**
     * @param \App\Models\Orders\OrderItem $item
     * @return bool|Price
     */
    public function capture(\App\Models\Orders\OrderItem $item) {

        $order = $item->order;
        $store = $item->product->store;
        $total = $item->totalDiscounted->value;
        //$totalShipping = $order->getShippingPrice($store);
        $totalShipping = 0;

        try {
            if (app('payment', ['payex'])
                ->setTotal($total)
                ->setTotalShipping($totalShipping)
                ->setTotalVat($item->totalVatDiscounted->value)
                ->setOrderId(sprintf("%s-%s", $store->data('payex.prefix'), $order->internal_id))
                ->captureTransaction($order->transaction_id)) {

                $price = Price::build(
                    $item->totalDiscounted->currency,
                    $total + $totalShipping,
                    'total-captured'
                );

                $item->prices()->save($price->deleteDuplicates(true));
                return $price->deleteDuplicates(false);
            }
        } catch (PaymentProviderResponseException $e) {
            return false;
        }

        return false;
    }
}