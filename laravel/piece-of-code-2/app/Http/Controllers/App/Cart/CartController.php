<?php

namespace App\Http\Controllers\App\Cart;

use App\Models\Country;
use Illuminate\Http\Request;
use App\Models\Products\Product;
use App\Models\Orders\OrderItem;
use App\Http\Controllers\FrontendController;
use App\Acme\Repositories\Criteria\Orders\OrderId;
use App\Http\Requests\App\AddProductToCartFormRequest;
use Illuminate\Support\Arr;
use NornixCache;


class CartController extends FrontendController
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        /**
         * Flash intended URL, so that we can
         * redirect user back to cart after taking
         * action (login, address modifications, etc...).
         */
        force_return_to($this->request->url());

        $user = $this->userRepository->current();
        $username = null;

        if ($user) {
            $username = $user->username;
        }

        if (!$order_id = session()->get('order_id')) {

            /**
             * 2. Step
             */
            return view('app.cart.new-index', [
                'addresses' => [
                    'billing' => ($user)? $user->getAddresses('billing')->toArray(): '',
                    'shipping' => ($user)? $user->getAddresses('shipping')->toArray(): '',
                ],
                'payment_methods' => config('cms.payment_methods'),
                'cart' => $this->cartRepository->get(app('defaults')->currency),
                'cart_count' => $this->cartRepository->count(),
                'temp_address' => session()->get('guest_address'),
                'pages' => NornixCache::region(app('request')->getRegion(), 'pages', 'listing')->read(),
                'step' => 2,
                'user_email' => $username,

            ]);
        }

        /**
         * Confirmation
         */
        return view('app.cart.index', [
            'step' => 3,
            'payment_methods' => config('cms.payment_methods'),
            'cart' => $this->orderRepository->setCriteria(new OrderId($order_id))->parse(),
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexAjax()
    {
        $user = $this->userRepository->current();
        $username = null;

        if ($user) {
            $username = $user->username;
        }

        return view('app.cart.new-index-modal', [
            'cart_count' => $this->cartRepository->count(),
            'cart' => $this->cartRepository->get(app('defaults')->currency),
            'user_email' => $username,
        ]);
    }

    /**
     * @param Product $product
     * @param AddProductToCartFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Product $product, AddProductToCartFormRequest $request)
    {
        $currency = app('defaults')->currency;
        $properties = !is_null($request->input('properties')) ? $request->input('properties') : [];
        $quantity = $request->input('quantity');
        if ($quantity < 1) {
            $response = ['messages' => ['error' => __t('messages.error.cart.quantity_low')]];
            return response()->json($response);
        }

        if ($this->cartRepository->add($product, $quantity, $properties) && $this->cartRepository->count() + $quantity < 1000) {
            if (!$price = $product->discountedPrice) {
                $prices = array_pluck($product->pricesGeneral->toArray(), 'value', 'currency.id');
                $price = Arr::get($prices, $currency->id, 0);
            }

            $price = round((int)($price));

            $response = [
                'callbacks' => [
                    'cart_count_update' => [$this->cartRepository->count()],
                    'facebook_pixel' => ['AddToCart', [
                        'value' => $price * $quantity,
                        'currency' => $currency->code,
                    ]],
                ],
                'messages' => ['success' => __t('messages.success.cart.item_added')],
            ];
        } else {
            $response = ['messages' => ['error' => __t('messages.error.general')]];
        }

        return response()->json($response);
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function update(Request $request)
    {
        $model = $this->cartRepository->getModel();

        if ($model) {
            foreach ($request->input('items', []) as $id => $data) {
                $model->items->find($id)->update($data);
            }
        }

        return redirect()->back()->withInput();
    }

    /**
     * @param OrderItem $item
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove(OrderItem $item)
    {
        if ($this->cartRepository->remove($item)) {
            flash()->success(__t('messages.success.cart.item_removed'));
        }

        return redirect()->back();
    }


    /**
     * @param Product $product
     * @param AddProductToCartFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Product $product, Request $request)
    {
        $currency = app('defaults')->currency;
        $properties = !is_null($request->input('properties')) ? $request->input('properties') : [];
        $item = $request->input('items');
        $quantity = $item[$product->id]['value'];
        $fullQuantity = $item[$product->id]['quantity'];
        $itemId = $item[$product->id]['item_id'];
        $orderItem = OrderItem::find($itemId);

        if ($fullQuantity < 1) {
            if ($this->cartRepository->remove($orderItem)) {
                $this->cartRepository->get($currency);

                $response = [
                    'callbacks' => [
                        'cart_count_update' => [$this->cartRepository->count()],
                        'cart_data' => [$this->cartRepository->get($currency), $currency->key, $itemId,],
                    ],
                    'messages' => ['success' => __t('messages.success.cart.item_removed')],
                ];
            } else {
                $response = ['messages' => ['error' => __t('messages.error.general')]];
            }
        } else {
            if ($this->cartRepository->add($product, $quantity, $properties)) {
                if (!$price = $product->discountedPrice) {
                    $prices = array_pluck($product->pricesGeneral->toArray(), 'value', 'currency.id');
                    $price = Arr::get($prices, $currency->id, 0);
                }

                $this->cartRepository->get($currency);

                $price = round((int)($price));

                $response = [
                    'callbacks' => [
                        'cart_count_update' => [$this->cartRepository->count()],
                        'cart_data' => [$this->cartRepository->get($currency), $currency->key, $itemId,],
                        'item_value' => $price * $fullQuantity,
                        'facebook_pixel' => ['AddToCart', [
                            'value' => $price * $quantity,
                            'currency' => $currency->code,
                        ]],
                    ],
                    'messages' => ['success' => __t('messages.success.cart.item_added')],
                ];
            } else {
                $response = ['messages' => ['error' => __t('messages.error.general')]];
            }
        }

        return response()->json($response);
    }
}
