<?php

namespace App\Listeners\Subscribers;

use App\Models\Region;
use App\Models\Stores\Store;
use App\Jobs\RefreshNornixCache;
use Illuminate\Events\Dispatcher;
use App\Events\Categories\Created;
use App\Events\Categories\Deleting;
use App\Events\Categories\AliasUpdate;
use App\Events\Categories\OrderUpdate;
use App\Events\Categories\ParentUpdate;
use App\Events\Categories\TranslationUpdate;
use App\Acme\Interfaces\Events\CategoryEventInterface;

class CategoryEventSubscriber {

	/**
	 * @param CategoryEventInterface $event
	 */
	public function onCreated(CategoryEventInterface $event) {

		$category = $event->getCategory();
		RefreshNornixCache::afterCategoryCreation($category->categorizable);
	}

	/**
	 * @param CategoryEventInterface $event
	 */
	public function onDeleting(CategoryEventInterface $event) {

		$category = $event->getCategory();
		foreach (['translations', 'children'] as $relation) {
			foreach ($category->{$relation} as $item) {
				$item->delete();
			}
		}

		$categorizable = $category->categorizable;
		if ($categorizable instanceof Store) {
			RefreshNornixCache::afterCategoryDelete($category, $categorizable);
		} else if ($categorizable instanceof Region) {
			RefreshNornixCache::dispatch(); // re-run whole thing
		}
	}

	/**
	 * @param CategoryEventInterface $event
	 */
	public function onOrderUpdate(CategoryEventInterface $event) {
		RefreshNornixCache::afterCategoryOrderUpdate($event->getCategory()->categorizable);
	}

	/**
	 * @param CategoryEventInterface $event
	 */
	public function onParentUpdate(CategoryEventInterface $event) {
		RefreshNornixCache::afterCategoryParentUpdate($event->getCategory()->categorizable);
	}

	/**
	 * @param CategoryEventInterface $event
	 */
	public function onTranslationUpdate(CategoryEventInterface $event) {
		RefreshNornixCache::afterTranslationUpdate($event->getCategory()->categorizable);
	}

	/**
	 * @param CategoryEventInterface $event
	 */
	public function onAliasesUpdate(CategoryEventInterface $event) {

		$store = $event->getStore();
		$category = $event->getCategory();

		RefreshNornixCache::afterCategoryAliasUpdate($category, $store);
	}

	/**
	 * @param Dispatcher $events
	 */
	public function subscribe(Dispatcher $events) {

		$events->listen(
			Created::class,
			'App\Listeners\Subscribers\CategoryEventSubscriber@onCreated'
		);

		$events->listen(
			Deleting::class,
			'App\Listeners\Subscribers\CategoryEventSubscriber@onDeleting'
		);

		$events->listen(
			OrderUpdate::class,
			'App\Listeners\Subscribers\CategoryEventSubscriber@onOrderUpdate'
		);

		$events->listen(
			ParentUpdate::class,
			'App\Listeners\Subscribers\CategoryEventSubscriber@onParentUpdate'
		);

		$events->listen(
			TranslationUpdate::class,
			'App\Listeners\Subscribers\CategoryEventSubscriber@onTranslationUpdate'
		);

		$events->listen(
			AliasUpdate::class,
			'App\Listeners\Subscribers\CategoryEventSubscriber@onAliasesUpdate'
		);
	}
}