<?php

namespace App\Listeners\Subscribers;

use App\Models\Content\Page;
use App\Events\Page\Deleted;
use Illuminate\Events\Dispatcher;
use App\Events\Page\TranslationUpdated;
use App\Acme\Interfaces\Events\PageEventInterface;

class PageEventSubscriber {

	/**
	 * @param PageEventInterface $event
	 */
	public function onTranslationUpdated(PageEventInterface $event) {
		$this->clearDataCache($event->getPage());
	}

	/**
	 * @param PageEventInterface $event
	 */
	public function onDelete(PageEventInterface $event) {

		foreach ($event->getPage()->translations as $translation) {
			$translation->delete();
		}

		$this->clearDataCache($event->getPage());
	}

	protected function clearDataCache(Page $page) {

//		Artisan::queue('cache:clear-specific', [
//			'--group' => 'data',
//			'--key' => ['pages.system'],
//			'--key-var' => [
//				sprintf("region_id:%d", $page->region->id)
//			]
//		]);
	}

	/**
	 * @param Dispatcher $events
	 */
	public function subscribe(Dispatcher $events) {

		$events->listen(
			Deleted::class,
			'App\Listeners\Subscribers\PageEventSubscriber@onDelete'
		);

		$events->listen(
			TranslationUpdated::class,
			'App\Listeners\Subscribers\PageEventSubscriber@onTranslationUpdated'
		);
	}

}