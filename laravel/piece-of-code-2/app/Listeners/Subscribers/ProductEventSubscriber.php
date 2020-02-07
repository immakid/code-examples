<?php

namespace App\Listeners\Subscribers;

use Artisan;
use Illuminate\Support\Arr;
use App\Models\Products\Product;
use App\Events\Products\Deleted;
use Illuminate\Events\Dispatcher;
use App\Events\Products\TranslationUpdate;
use App\Events\Products\TranslationUpdated;
use App\Models\Translations\ProductTranslation;
use App\Acme\Interfaces\Events\ProductEventInterface;

class ProductEventSubscriber {

	/**
	 * @param ProductEventInterface $event
	 */
	public function onTranslationUpdate(ProductEventInterface $event) {

		$product = $event->getProduct();
		$translation = $event->getTranslation();

		$keywords = array_diff(
			$translation->getKeywords(Arr::get($translation->getOriginal(), 'name')), // old
			$translation->getKeywords() // new
		);

		$this->deleteKeywords($product, $translation, $keywords);
	}

	/**
	 * @param ProductEventInterface $event
	 */
	public function onTranslationUpdated(ProductEventInterface $event) {

		Artisan::call('cache:products-import', [
			'ids' => [$event->getProduct()->id]
		]);
	}

	/**
	 * @param ProductEventInterface $event
	 * @throws \Exception
	 */
	public function onDelete(ProductEventInterface $event) {

		$product = $event->getProduct();
		foreach (['prices', 'reviews', 'propertyValues'] as $relation) {
			foreach ($product->{$relation} as $item) {
				$item->delete();
			}
		}

		foreach ($product->translations as $translation) {

			$this->deleteKeywords($product, $translation);
			$translation->delete();
		}

//	    Artisan::call('cache:products-count', ['--store' => $product->store->id]);
	}

	/**
	 * @param Product $product
	 * @param ProductTranslation $translation
	 * @param array|null $keywords
	 */
	protected function deleteKeywords(Product $product, ProductTranslation $translation, array $keywords = null) {

		if (!$keywords) {
			$keywords = $translation->getKeywords();
		}

		Artisan::call('cache:products-remove', [
			'items' => [
				sprintf("%d:::%d:::%s", $product->id, $translation->language->id, implode('---', $keywords))
			]
		]);
	}

	/**
	 * @param Dispatcher $events
	 */
	public function subscribe(Dispatcher $events) {

		$events->listen(
			TranslationUpdate::class,
			'App\Listeners\Subscribers\ProductEventSubscriber@onTranslationUpdate'
		);

		$events->listen(
			TranslationUpdated::class,
			'App\Listeners\Subscribers\ProductEventSubscriber@onTranslationUpdated'
		);

		$events->listen(
			Deleted::class,
			'App\Listeners\Subscribers\ProductEventSubscriber@onDelete'
		);

	}
}