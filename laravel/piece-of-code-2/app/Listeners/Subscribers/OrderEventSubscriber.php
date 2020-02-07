<?php

namespace App\Listeners\Subscribers;

use View;
use App\Models\Price;
use App\Jobs\SendEmail;
use App\Events\Orders\Completed;
use Illuminate\Events\Dispatcher;
use App\Acme\Repositories\Criteria\Orders\OrderId;
use App\Acme\Interfaces\Events\OrderEventInterface;
use App\Acme\Repositories\Interfaces\OrderInterface;
use App\Acme\Interfaces\Events\OrderItemEventInterface;
use App\Events\Orders\Items\StatusUpdated as ItemStatusUpdated;

class OrderEventSubscriber {

	/**
	 * @param OrderEventInterface $event
	 */
	public function onCompleted(OrderEventInterface $event) {

		$order = $event->getOrder();
		$repository = app(OrderInterface::class)->setCriteria(new OrderId($order->internal_id));

		$links = [
			'stores' => [],
			'region' => route_region('admin.regions.orders.edit', [$order->region->id, $order->id]),
		];

        //captured payment on order confirmation step
		$captureArray = array();
		$stores = collect([]);
		foreach ($order->items as $key => $item) {
			$stores->push($store = $item->product->store);
			$links['stores'][$store->id] = route('admin.stores.orders.edit', [$store->id, $order->id]);
			$captureArray[$store->id][$key]["totalDiscounted"] = $item->totalDiscounted->value;
			$captureArray[$store->id][$key]["totalVatDiscounted"] = $item->totalVatDiscounted->value;
			$total = $item->totalDiscounted->value;
			$totalShipping = 0;
			$price = Price::build(
				$item->totalDiscounted->currency,
				$total + $totalShipping,
				'total-captured'
			);

			$item->prices()->save($price->deleteDuplicates(true));
			$price->deleteDuplicates(false);
			$item->setStatus("captured",true);//Change status to capture amount WEBG-45
		}

        //Capture shipping amount WEBG-45
        $processedStores = [];
        foreach ($stores as $store) {
            if(!in_array($store->id, $processedStores)) {
                array_push($processedStores, $store->id);

                $totalShipping = $order->getShippingPrice($store);
				$storeVATPercentage = ($store->getAttribute('vat') !== null) ? $store->getAttribute('vat') : 0;
				$totalDiscounted = 0;
				$totalVatDiscounted = 0;
				foreach ($captureArray[$store->id] as $value) {
					$totalDiscounted = $totalDiscounted + $value["totalDiscounted"];
					$totalVatDiscounted = $totalVatDiscounted + $value["totalVatDiscounted"];
				}
				if ($totalShipping > 0) {
					$totalVatDiscounted = $totalVatDiscounted + ( $totalShipping - ( $totalShipping / (1 + ($storeVATPercentage / 100) ) ) ); // Adding Shipping VAT to order items
				}

	            print_logs_app("BEFORE CAPRTURE");
	            print_logs_app("totalDiscounted - ".$totalDiscounted);
	            print_logs_app("totalShipping - ".$totalShipping);
	            print_logs_app("totalVatDiscounted - ".$totalVatDiscounted);
                if ($totalDiscounted > 0) {
                    app('payment', ['payex'])
                        ->setTotal($totalDiscounted)
                        ->setTotalShipping($totalShipping)
                        ->setTotalVat($totalVatDiscounted)
                        ->setOrderId(sprintf("%s-%s", $store->data('payex.prefix'), $order->internal_id))
                        ->captureTransaction($order->transaction_id);
                }
            }
        }

		$jobs = [
			new SendEmail( // Customer
				__tF('emails.order.created.subject'),
				View::make('emails.orders.created', ['order' => $repository->parse()])->render(),
				[$order->user->username, $order->user->name]
			)
		];

        /**
         * Remove the restriction due to client's request
         * With this all the environments will send emails to stores and admin
         * https://ascentic.atlassian.net/browse/WEBG-131
         */
		//if (config('app.env') === 'production') {

			/**
			 * They were big enough idiots to send mighty developer product
			 * which he ordered on provider's sandbox mode without
			 * any confirmation, so don't take any chances
			 */

			array_push($jobs, SendEmail::usingTranslationWithCustomerEmail('order.received', [
				'order_id' => $order->internal_id,
				'link' => sprintf('<a href="%s">%s</a>', $links['region'], $links['region'])
			], 'backend',[],[],View::make('emails.orders.created', ['order' => $repository->parse(),'excludeHeaderAndFooter' => true])->render(),
				[$order->user->username, $order->user->name])->setRecipients([config('cms.emails.notifications'), 'Gg']));

			foreach ($stores->unique() as $store) { // Store's admin(s)
				if (!$email = $store->data('notifications.email')) {
					continue;
				}

				array_push($jobs, SendEmail::usingTranslation('order.received', [
					'order_id' => $order->internal_id,
					'link' => sprintf('<a href="%s">%s</a>', $links['stores'][$store->id], $links['stores'][$store->id])
				], 'backend')->setRecipients([$email, $store->name])->attach([
					[
						'application/pdf',
						sprintf("%s.pdf", $order->internal_id),
						$repository->generatePdf($store, 'store')
					]
				]));
			}

		//}

		foreach ($jobs as $job) {
			dispatch($job)->onConnection('wg.emails');
		}
	}

	/**
	 * @param OrderItemEventInterface $event
	 */
	public function onItemStatusUpdated(OrderItemEventInterface $event) {

		$item = $event->getItem();

		$prices = [];
		$order = $item->order;
		$currency = $order->total->currency;
		$total = $order->totalCaptured ? $order->totalCaptured->value : 0;
		$totalVat = $order->totalVatCaptured ? $order->totalVatCaptured->value : 0;

		switch ($item->hrStatus) {
			case 'captured':
			case 'refunded':

				$priceMath = function ($value) use ($item) {
					return $item->hrStatus === 'refunded' ? $value : abs($value);
				};

				$prices = [
					'total-captured' => $total + $priceMath(-$item->totalCaptured->value),
					'total-vat-captured' => $totalVat + $priceMath(-$item->totalVatDiscounted->value)
				];

				break;
		}

		foreach ($prices as $label => $value) {
			$order->prices()->save(Price::build($currency, $value, $label)->deleteDuplicates(true));
		}

		foreach ($order->fresh('items')->items as $item) {
			if (!$item->isProcessed && $item->hrStatus !== 'shipped') {
				return;
			}
		}

		$order->setStatus('processed');
	}

	/**
	 * @param Dispatcher $dispatcher
	 */
	public function subscribe(Dispatcher $dispatcher) {

		$dispatcher->listen(
			Completed::class,
			'App\Listeners\Subscribers\OrderEventSubscriber@onCompleted'
		);

		$dispatcher->listen(
			ItemStatusUpdated::class,
			'App\Listeners\Subscribers\OrderEventSubscriber@onItemStatusUpdated'
		);
	}
}
