<?php

namespace App\Acme\Repositories\Concrete;

use App\Http\Middleware\VerifyRegionDomain;
use App\Models\Currency;
use Illuminate\Support\Arr;
use App\Events\Orders\Completed;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Model;
use App\Acme\Repositories\EloquentRepository;
use App\Http\Requests\App\CreateOrderFormRequest;
use App\Acme\Repositories\Criteria\Orders\OrderId;
use App\Acme\Repositories\Interfaces\CartInterface;
use App\Acme\Repositories\Interfaces\OrderInterface;
use App\Acme\Repositories\Interfaces\CouponInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Acme\Collections\RepositoryCriteriaCollection as Collection;

class Order extends EloquentRepository implements OrderInterface
{

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var CouponInterface
     */
    protected $coupon;

    public function __construct(
        Container $container,
        Collection $collection,
        CartInterface $cart,
        CouponInterface $coupon
    ) {
        parent::__construct($container, $collection);

        $this->cart = $cart;
        $this->coupon = $coupon;
    }

    /**
     * @return string
     */
    protected function model()
    {
        return \App\Models\Orders\Order::class;
    }

    /**
     * @param CreateOrderFormRequest $request
     * @param Currency $currency
     * @return bool|mixed
     */
    public function createFromCart(CreateOrderFormRequest $request, Currency $currency, $data = [])
    {

        if (!$order = $this->cart->getModel(false, true)) {
            return false;
        }

        $items = $request->input('items');
        $shipping = array_values($request->input('shipping', []));

        if(isset($data['addresses'])){
            $addresses = Arr::except($data['addresses'], '_same');
        }else{
            $addresses = Arr::except($request->input('addresses', []), '_same');
        }


        if ($request->input('addresses._same')) {
            $addresses['billing'] = $addresses['shipping'];
        }

        foreach ($items as $id => $data) {
            $order->items->find($id)->update($data);
        }

        foreach ($addresses as $type => $id) {
            if (!$order->addresses->find($id)) {
                $old = $order
                    ->addresses()
                    ->wherePivot('type', '=', $type)->first();

                if ($old) {
                    $order->addresses()->detach($old->id);
                }

                $order->addresses()->attach($id, [
                    'type' => $type,
                ]);
            }
        }

        $order->update($request->all());
        $order->shippingOptions()->sync($shipping);
        $order->coupons()->sync(Arr::pluck($this->coupon->getFromSession()->toArray(), 'id'));

        return $order
            ->saveTotals($currency)
            ->setStatus('unauthorized')
            ->load('addresses');
    }

    /**
     * @param string $id
     * @param string $transaction_id
     * @return \App\Models\Orders\Order|bool
     */
    public function confirm($id, $transaction_id)
    {
        try {
            $model = $this->setCriteria(new OrderId($id))->firstOrFail();
            $model->transaction_id = $transaction_id;
            $model->update();

            event(new Completed($model));
            return $model->setStatus('authorized');
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }

    /**
     * @return array|bool
     */
    public function parse()
    {
        try {
            $model = $this->applyCriteria()->firstOrFail();
            $model->load(['items', 'items.product', 'items.product.store']);
        } catch (ModelNotFoundException $e) {
            return false;
        }

        $currency = $model->total->currency;
        $stores = array_unique(Arr::pluck($model->items->toArray(), 'product.store.id'));
        $delimiter = VerifyRegionDomain::getRegion()->price_delimiter;

        $results = [
            'order' => $model,
            'currency' => $currency,
            'items' => array_fill_keys(array_values($stores), []),
            'stores' => array_fill_keys(array_values($stores), null),
            'prices' => [
                'items' => [],
                'delimiter' => $delimiter,
                'stores' => array_fill_keys(array_values($stores), [
                    'actual' => 0,
                    'discounted' => 0,
                ]),
                'totals' => [
                    'total' => $model->total->value,
                    'vat' => $model->totalVat->value,
                    'shipping' => $model->totalShipping->value,
                    'totalDiscounted' => $model->totalDiscounted->value,
                    'totalVatDiscounted' => $model->totalVatDiscounted->value,
                    'totalCaptured' => $model->totalCaptured ? $model->totalCaptured->value : 0,
                    'totalVatCaptured' => $model->totalVatCaptured ? $model->totalVatCaptured->value : 0,
                ],
            ],
            'coupons' => $model->coupons,
            'shipping' => array_fill_keys(array_values($stores), []),
            'notices' => [
                'missing' => false,
            ],
        ];

        $totals = [];
        foreach ($model->items as $item) {
            $store = $item->product->store;
            if (!$item->product->in_stock) {
                $results['notices']['missing'] = true;
            }

            array_push($results['items'][$store->id], $item);
            Arr::set($results, sprintf("stores.%d", $store->id), $store);

            $results['prices']['items'][$item->id] = [
                'base' => $item->totalDiscounted->value / $item->quantity,
                'total' => $item->totalDiscounted->value,
            ];

            $totals[$store->id] = ($item->total->value * $item->quantity);
            $results['prices']['stores'][$store->id]['actual'] += $item->total->value;
            $results['prices']['stores'][$store->id]['discounted'] += $item->totalDiscounted->value;
        }

        // Apply coupons
        //		foreach ($totals as $store_id => $total) {
//
        //			list(, $value) = $this->coupon->findGreatestDiscount($total, $model->coupons);
        //			if ($value > ($results['prices']['stores'][$store_id]['actual'] - $results['prices']['stores'][$store_id]['discounted'])) {
        //				$results['prices']['stores'][$store_id]['discounted'] = $results['prices']['stores'][$store_id]['actual'] - $value;
        //			}
        //		}

        foreach ($model->shippingOptions as $option) {
            $shipping_price = $model->prices()
                ->forCurrency($currency)
                ->labeled(sprintf("shipping-store-%d", $option->store->id))
                ->first();

            $shipping_vat = $model->prices()
                ->forCurrency($currency)
                ->labeled(sprintf("shipping-vat-store-%d", $option->store->id))
                ->first();

            $shippingPrice = (!isset($shipping_price->value) || is_null($shipping_price->value)) ? 0 : $shipping_price->value;
            $shippingVat = (!isset($shipping_vat->value) || is_null($shipping_vat->value)) ? 0 : $shipping_vat->value;

            Arr::set($results, sprintf("shipping.%d.option", $option->store->id), $option);
            Arr::set($results, sprintf("shipping.%d.price", $option->store->id), $shippingPrice);
            Arr::set($results, sprintf("shipping.%d.vat", $option->store->id), $shippingVat);
        }

        return $results;
    }

    /**
     * @param Model $model
     * @param string $type
     * @return mixed|string
     */
    public function generatePdf(Model $model, $type)
    {
        $snappy = app('snappy.pdf');
        $snappy->setOption('encoding', 'utf-8');

        // @NOTE: Ugly, think of something
        $env = config('environment');
        config(['environment' => 'backend']);

        $html = View::make('backend._subsystems.orders.pdf.' . $type, [
            '_model' => $model,
            'order' => $this->parse(),
            'statuses' => [
                'list' => \App\Models\Orders\OrderItem::getStatuses(),
                'conditions' => \App\Models\Orders\OrderItem::getStatusConditions(),
            ],
            'address_keys' => [
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'street' => 'street',
                'street2' => 'street2',
                'city' => 'city',
                'zip' => 'zip',
                'county' => 'country.name',
                'telephone' => 'telephone',
            ],
        ])->render();

        config(['environment' => $env]);

        return $snappy->getOutputFromHtml($html);
    }
}