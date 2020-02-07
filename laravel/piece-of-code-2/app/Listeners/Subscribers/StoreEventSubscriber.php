<?php

namespace App\Listeners\Subscribers;

use StoreFacade;
use App\Models\Stores\Store;
use Illuminate\Support\Arr;
use App\Events\Stores\Saved;
use App\Events\Stores\Created;
use App\Events\Stores\Deleted;
use Illuminate\Events\Dispatcher;
use App\Events\Users\DetachedFromStore;
use App\Acme\Interfaces\Events\StoreEventInterface;
use App\Acme\Repositories\Interfaces\StoreInterface;

class StoreEventSubscriber {

	/**
	 * @param StoreEventInterface $event
	 * @throws \Exception
	 */
	public function onDelete(StoreEventInterface $event) {

		$store = $event->getStore();
		foreach (['shippingOptions', 'addresses', 'products'] as $relation) {
			foreach ($store->$relation as $item) {
				$item->delete();
			}
		}

		if ($store->priceFile) {
			$store->priceFile->delete();
		}

		foreach ($store->users as $user) {

			$store->users()->detach($user->id);
			event(new DetachedFromStore($user, $store));
		}
	}

	/**
	 * @param StoreEventInterface $event
	 */
	public function onCreated(StoreEventInterface $event) {

		$prefixes = [];
		foreach (Store::all() as $store) {
			if (!$prefix = $store->data('payex.prefix')) {
				continue;
			}

			array_push($prefixes, $prefix);
		}

		$store = $event->getStore();
		$name = trim(preg_replace("/[^A-Za-z0-9]/", '', $store->name));

		$prefixes = array_unique(array_filter($prefixes));
		if (strlen($prefix = strtoupper(substr($name, 0, 6))) < 6) {
			$prefix .= gen_random_string(6 - strlen($prefix), [], ['lowercase']);
		}else {
            $prefix = substr($prefix, 0, 4);
            $prefix .= gen_random_string(6 - strlen(substr($prefix, 0, 4)), [], ['lowercase']);
        }


		/*while (in_array($prefix, $prefixes)) {
			$prefix = gen_random_string(6, [], ['lowercase']);
		}*/
        //If the randomly generated prefix is matched regenerate prefix with 3 randomize characters
		while (in_array($prefix, $prefixes)) {
            $prefix = gen_random_string(6 - strlen(substr($prefix, 0, 3)), [], ['lowercase']);
		}

		$store->dataUpdate([
			'payex' => [
				'prefix' => $prefix,
				'cron' => ['activate' => true]
			]
		]);
	}

	/**
	 * @param StoreEventInterface $event
	 */
	public function onSaved(StoreEventInterface $event) {

		$store = $event->getStore();
		$fields = array_keys(StoreFacade::getPayExFields());

		foreach ($fields as $field) {
			if (!$store->data($field)) {

                //WEBG-58 If Payex.prefix is not
                // update payex.prefix with the same logic
                //Unique key with 6 characters
                if($field == 'payex.prefix') {
                    $this->onCreated($event);
                }else {
                    $store->enabled = false;
                    return;
                }
			}
		}

		$newData = Arr::get($store->getDirty(), 'data', false);
		$oldData = Arr::get($store->getOriginal(), 'data', false);

		if ($oldData !== false && $newData !== false) {

			$newData = Arr::only(Arr::dot(unserialize($newData)), $fields);
			$oldData = Arr::only(Arr::dot(unserialize($oldData)), $fields);

			if (array_diff_assoc($newData, $oldData)) {

				$store->enabled = false;
				$store->dataUpdate([
					'payex' => [
						'xml' => null,
						'synced' => false,
						'cron' => ['activate' => true]
					]
				]);
			}
		}
	}

	/**
	 * @param Dispatcher $events
	 */
	public function subscribe(Dispatcher $events) {

		$events->listen(
			Created::class,
			'App\Listeners\Subscribers\StoreEventSubscriber@onCreated'
		);

		$events->listen(
			Saved::class,
			'App\Listeners\Subscribers\StoreEventSubscriber@onSaved'
		);

		$events->listen(
			Deleted::class,
			'App\Listeners\Subscribers\StoreEventSubscriber@onDelete'
		);
	}
}