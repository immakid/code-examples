<?php

namespace App\Http\Controllers\App\Users;

use App\Jobs\RefreshNornixCache;
use Illuminate\Support\Arr;
use App\Events\Users\UserRegister;
use App\Http\Requests\SubmitAddressFormRequest;
use App\Models\Address;
use App\Models\FinancialTransactions;
use App\Models\Orders\Order;
use App\Http\Controllers\FrontendController;
use App\Http\Requests\App\CreateOrderFormRequest;
use App\Acme\Repositories\Criteria\Orders\OrderId;
use App\Acme\Libraries\Email\Services\ThirdParty\RelationBrand;
use Illuminate\Http\Request;

class OrderController extends FrontendController
{
    protected $order = null;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('app.users.orders.new-index', [
            'orders' => $this->userRepository->current()
                ->orders()
                ->orderBy('confirm_at', 'DESC')
                ->complete()
                ->get(),
        ]);
    }


    public static function showOrderInIndex(Order $order)
    {
        //->$this->orderRepository->setCriteria(new OrderId($order->internal_id))->parse()
        return (new self)->showOrderInIndex_S($order);

        // return new OrderId($order->internal_id);
    }
    private function showOrderInIndex_S(Order $order)
    {
        return  $this->orderRepository->setCriteria(new OrderId($order->internal_id))->parse();
    }

    /**
     * @param Order $order
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function show(Order $order)
    {
        if (!$this->userRepository->current()->orders->find($order->id)) {
            return response()->json(json_message("Invalid request.", "error"));
        }

        return view('app.cart.steps.new-step3', [
            'cart' => $this->orderRepository->setCriteria(new OrderId($order->internal_id))->parse(),
        ]);
    }

    /**
     * @param CreateOrderFormRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function create(CreateOrderFormRequest $request)
    {

//        $cart = $this->cartRepository->get(app('defaults')->currency);
//        $result = $this->couponRepository->validateCartCoupons($cart);
//
//        if ($result['code'] != 600) {
//            $value = isset($result['value']) ? $result['value'] : '';
//            flash()->error(__t($result['message_key'], ['value' => $value]));
//            return redirect()->back();
//        }

        $data = [];

        if (!$user = $this->userRepository->current()) {

            try {

                if ($this->userRepository->findByUsername($request->input('guest_username'))) {
                    flash()->error('Username already use. Please, try again');
                    return redirect()->route('app.cart.index');
                }
                $addressData = session()->get('guest_address');
                $data = [
                    "name" => ($addressData)? $addressData['first_name']." ".$addressData['last_name'] : $request->input('guest_username'),
                    "username" => $request->input('guest_username'),
                    "password" => gen_random_string(10),
                    "status" => 1,
                ];
                \DB::beginTransaction();
                $user = $this->userRepository->create($data);

                if ($user && $addressData) {
                    $address = new Address($addressData);
                    $address->saveRelationsFromRequest(new SubmitAddressFormRequest($addressData));
                    $address->save();

                    foreach (['shipping', 'billing'] as $type) {
                        $user->addresses()->attach($address, ['type' => $type]);
                    }

                    $data["addresses"] = [
                        "shipping" => $address->id,
                        "_same" => "1",
                        "billing" => $address->id
                    ];

                }

                \Auth::login($user);
                session()->flash('guest_address');
                \DB::commit();

            } catch (\Exception $e) {
                \Log::error($e);
                \DB::rollback();
                flash()->error('User registration fail.');
                return redirect()->back();
            }

        }

        //Check login
        if (!$user = $this->userRepository->current()) {
            return redirect()->back();
        }

        if (!$order = $this->orderRepository->createFromCart($request, app('defaults')->currency, $data)) {
            flash()->error('Your session has expired. Please, try again');
        }

        if ($request->input('newsletter')) {
            RelationBrand::subscribe($this->userRepository->current()->username);
        }

        $provider = app('payment', ['payex']);
        $provider->setTotal($order->totalDiscounted->value) //$order->total->value
            ->setTotalVat($order->totalVatDiscounted->value)
            ->setTotalShipping($order->totalShipping->value)
            ->setCurrency(app('defaults')->currency->code)
            ->setOrderId($order->internal_id)
            ->setPaymentMethod($request->input('data.payment.method'))
            ->setCancelUrl(route_region(config('cms.payment.cancel_url')))
            ->setReturnUrl(route_region(config('cms.payment.return_url')) . sprintf("?orderId=%s", $order->internal_id))
            ->setOrder($order)
            ->setShipping($request->input('shipping', []));

        return view('app.cart.redirect', [
            'value' => $order->totalDiscounted->value,
            'currency' => app('defaults')->currency->code,
            'url' => $provider->getPaymentUrl(),
        ]);
//        return redirect($provider->getPaymentUrl());
    }

    public function setInValidOrders()
    {
        $user = $this->userRepository->current();
        $lists =$user->getCurrentUserOrderList;
        if ($lists) {
            foreach ($lists as $list) {
                $list->setStatus('inValid');
            }
        }
        return;
    }
    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirm()
    {
        $id = $this->request->get('orderId');

        $reference = $this->request->get('orderRef');
        $date = date('Y-m-d H:i:s');
        $status = "";
        $items = array();
        $items['products_info'] = array();

        if (!$order = $this->orderRepository->setCriteria(new OrderId($id))->first()) {
            $status = "order_not_found";
            //flash()->error(__t('messages.error.payment_failed'));
            print_logs_app("Order not found");
        } else {

            if ($order->status == 2) { // Order is authorized
                $status = "order_authorized";
            }
            else {

                // print_logs_app('order id'.$order->id);
                //print_logs_app("Status - " . print_r($order->status, true));
                print_logs_app('order Controller....');
                $provider = app('payment', ['payex']);
                $provider->setPaymentMethod($order->data('payment.method'));
                
                if (!$response = $provider->authorizeTransaction($reference)) {
                    $status = "payment_failed";
                    flash()->error(__t('messages.error.payment_failed'));
                } else {
                    list($order_id, $transaction_id) = $response;
                    // $order_id = $id;
                    // $transaction_id = $order->transaction_id;

                    if ($this->orderRepository->confirm($order_id, $transaction_id)) {
                        $this->cartRepository->truncate();
                        session()->flash('order_id', $order_id);
                        $this->setInValidOrders();
                        $order->confirm_at = $date;
                        $order->update();
                        $status = "order_authorized";

                        $transactionIsUpdated = false;
                        $order = Order::where('internal_id', $id)->first();
                        $order_items = $order->items;

                        $total_order_amount = 0;
                        $i = 0;
                        $items['products_info'] = array();

                        foreach ($order_items as $item) {

                            if (isset(app('defaults')->currency->id)) {
                                $currency_id = app('defaults')->currency->id;
                            } else {
                                $currency_id = 96;
                            }

                            $store_id = $item->product->store->id;
                            $totalDiscounted = $item->totalDiscounted->value;
                            $totalVatDiscounted = $item->totalVatDiscounted->value;
                            $totalShipping = $order->getShippingPrice($item->product->store);

                            //Hello retail conversion data
                            $total_order_amount += $totalDiscounted;

                            $items['products_info'][$i]['product_id'] = $item->product_id;
                            $items['products_info'][$i]['quantity'] = $item->quantity;
                            $items['products_info'][$i]['product_url'] = get_product_url($item->product);
                            $i++;

                            if (isset($financial_transactions)) {

                                foreach ($financial_transactions as $key => $transaction) {

                                    if ( ($transaction['store_id'] == $store_id) && ($transaction['order_id'] == $order->internal_id) && ($transaction['type'] == 'credit') ) {

                                        $totalPaidByCustomer = $transaction['total_sales_price'] + $totalDiscounted;
                                        $wg_commission = ($totalPaidByCustomer*config('cms.defaults.wg_commission_percentage'))/100;
                                        $exclude_wg_commission = $totalPaidByCustomer - $wg_commission;

                                        $totalVatDiscounted = $transaction['vat'] + $item->totalVatDiscounted->value;

                                        $financial_transactions[$key]['total_sales_price'] = $totalPaidByCustomer;
                                        $financial_transactions[$key]['exclude_vat'] = $exclude_wg_commission-$totalVatDiscounted;
                                        $financial_transactions[$key]['vat'] = $totalVatDiscounted;
                                        $financial_transactions[$key]['wg_commission'] = $wg_commission;
                                        $financial_transactions[$key]['exclude_wg_commission'] = $exclude_wg_commission;
                                        $financial_transactions[$key]['vat_commission'] = ($wg_commission*config('cms.defaults.wg_vat_percentage'))/100;
                                        $financial_transactions[$key]['payable_to_store'] = $exclude_wg_commission-$financial_transactions[$key]['vat_commission'];

                                        $transactionIsUpdated = true;
                                    }
                                }
                            }

                            if (!$transactionIsUpdated) {

                                if ($order_data = $this->orderRepository->setCriteria(new OrderId($order->internal_id))->parse()) {

                                    if (isset($order_data['shipping'][$store_id]['vat'])) {

                                        if( ($order_data['shipping'][$store_id]['vat'] > 0) && ($totalShipping > 0) ){

                                            $totalVatDiscounted = $totalVatDiscounted + $order_data['shipping'][$store_id]['vat']; // Adding Shipping VAT

                                            print_logs_app("After adding shipping_vat the total vat is - ".$totalVatDiscounted);
                                        }
                                    }
                                }

                                $totalPaidByCustomer = $totalDiscounted + $totalShipping;
                                $wg_commission = ($totalPaidByCustomer*config('cms.defaults.wg_commission_percentage'))/100;
                                $exclude_wg_commission = $totalPaidByCustomer - $wg_commission;

                                $insert_array['type'] = "credit";
                                $insert_array['order_id'] = $id;
                                $insert_array['transaction_id'] = $transaction_id;
                                $insert_array['store_id'] = $store_id;
                                $insert_array['currency_id'] = $currency_id ;
                                $insert_array['total_sales_price'] = $totalPaidByCustomer;
                                $insert_array['exclude_vat'] = $exclude_wg_commission-$totalVatDiscounted;
                                $insert_array['vat'] = $totalVatDiscounted;
                                $insert_array['wg_commission'] = $wg_commission;
                                $insert_array['exclude_wg_commission'] = $exclude_wg_commission;
                                $insert_array['vat_commission'] = ($wg_commission*config('cms.defaults.wg_vat_percentage'))/100;
                                $insert_array['payable_to_store'] = $exclude_wg_commission-$insert_array['vat_commission'];
                                $financial_transactions[] = $insert_array;
                            }
                            $transactionIsUpdated = false;
                        }

                        $items['total_order_amount'] = $total_order_amount;

                        print_logs_app("Inserting FinancialTransactions - ".print_r($financial_transactions,true));
                        FinancialTransactions::insert($financial_transactions);

                        RefreshNornixCache::clearFinancialTransactionsCache();

                        flash()->success(__t('messages.success.order_received'));
                    } else {
                        $status = "general_error";
                        flash()->error(__t('messages.error.general'));
                    }
                }
            }
        }

        $user = $this->userRepository->current();
        $items['email'] = $user->username;
        $items['id_order'] = $id;

        switch ($status) {
            case 'payment_failed':
                $items["error_message"] = "Your payment is failed";
                break;
            case 'order_authorized':
                $items["success_message"] = "";
                break;
            default:
                $items["error_message"] = "Something went wrong, Please try again later.";
                break;
        }
        return view('app.users.orders.new-confirmation', ["items" => $items]);
    }
}
