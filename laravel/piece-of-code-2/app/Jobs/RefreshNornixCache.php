<?php

namespace App\Jobs;

use App;
use Artisan;
use Exception;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use InvalidArgumentException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Acme\Interfaces\Eloquent\Categorizable;

class RefreshNornixCache implements ShouldQueue {

	use Queueable,
		Dispatchable,
		SerializesModels,
		InteractsWithQueue;

	/**
	 * @var array
	 */
	protected $commands = [
		'n-cache:category-tree', // child/parent array                                              CreateCategoryTree::class
		'n-cache:category-listing', // parental categories (regional/stores) collection             CreateCategoryListing::class
		'n-cache:category-listing-featured', // featured collections collection                     CreateFeaturedCategoryListing::class
		'n-cache:map-categories-stores', // region categories => store ids map array                MapCategories2Stores::class
		'n-cache:map-categories-store-categories', // region => store categories map array          MapCategories2StoresCategories::class   | (needs store's tree)
		'n-cache:map-products-store-categories', // store categories => product ids                 MapProducts2StoreCategories::class      | (needs store's tree)
		'n-cache:count-products-stores', // count products within store based on it's categories    CountProductsInStores::class            | (needs cat mapping)
		'n-cache:count-products', // count all products based on regional categories                CountProducts::class                    | (needs cat mapping)
		'n-cache:store-listing', // enabled stores collection                                       CreateStoreListing::class
		'n-cache:product-listing-featured', // featured products collection                         CreateFeaturedProductListing::class
		'n-cache:product-listing-special-campaigns', // campaigns products collection                         CreateSpecialCampaignsProductListing::class
		'n-cache:product-listing-best-selling', // best selling products collection                         CreateBestSellingProductListing::class
		'n-cache:campaign-data', // collection & counters (based on regional categories)            CreateCampaignData::class               | (needs store cat mapping)
		'n-cache:page-listing', // system pages collection                                          CreatePageListing::class
		'n-cache:blog-post-data', // collection & counters (based on regional categories)           CreateBlogPostData::class               | (need regional tree)
	];

	/**
	 * @var array
	 */
	protected $arguments = [];

	/**
	 * @var null
	 */
	protected $command = null;

	/**
	 * RefreshNornixCache constructor.
	 * @param null $command
	 * @param array $arguments
	 */
	public function __construct($command = null, array $arguments = []) {

		$this->command = $command;
		$this->arguments = $arguments;
	}

	/**
	 * @return int
	 */
	public function handle() {

		try {

			if ($this->command) {

				if (!$commands = Arr::only($this->commands, array_search(sprintf("n-cache:%s", $this->command), $this->commands))) {
					throw new InvalidArgumentException("Command $this->command is not defined.");
				}
			} else {
				$commands = $this->commands;
			}

			if (App::runningInConsole()) {
				echo sprintf("Executing: \n%s\n%s\n", implode("\n", $commands), str_repeat('-', 60));
			}

			foreach ($commands as $command) {
				Artisan::call($command, $this->arguments);
			}
		} catch (Exception $e) {

			if (App::runningInConsole()) {
				echo sprintf("ERROR: %s\n", $e->getMessage());
			}

			return 1;
		}

		return 0;
	}

	/**
	 * @param Category $category
	 * @param Store $store
	 */
	public static function afterCategoryAliasUpdate(Category $category, Store $store) {

		foreach (
			[
				'map-categories-stores' => ['--category_id' => $category->id],
				'map-categories-store-categories' => ['--category_id' => $category->id, '--store_id' => $store->id],
				'category-listing' => ['--region_id' => $store->region->id],
				'count-products-stores' => ['--store_id' => $store->id],
				'count-products' => [],
                'campaign-data' => [],
                'product-listing-featured' => [],
                'product-listing-best-selling' => [],
			] as $command => $arguments) {

			self::dispatch($command, $arguments)->onConnection('wg.cache');
		}
	}

	/**
	 * @param Categorizable $model
	 */
	public static function afterCategoryCreation(Categorizable $model) {

		if ($model instanceof Store) {

			foreach (
				[
					'category-tree' => ['--store_id' => $model->id],
					'category-listing' => ['--store_id' => $model->id],
				] as $command => $arguments) {
				self::dispatch($command, $arguments)->onConnection('wg.cache');
			}
		} else {
			self::dispatch()->onConnection('wg.cache');
		}
	}

	/**
	 * @param Category $category
	 * @param Store $store
	 */
	public static function afterCategoryDelete(Category $category, Store $store) {

		foreach (
			[
				'category-tree' => ['--store_id' => $store->id],
				'category-listing' => ['--store_id' => $store->id],
				'map-categories-stores' => ['--category_id' => $category->id],
				'map-categories-store-categories' => ['--store_id' => $store->id],
				'map-products-store-categories' => ['--store_id' => $store->id],
				'count-products-stores' => ['--store_id' => $store->id],
				'count-products' => []
			] as $command => $arguments) {

			self::dispatch($command, $arguments)->onConnection('wg.cache');
		}
	}

	/**
	 * @param Categorizable $model
	 */
	public static function afterCategoryOrderUpdate(Categorizable $model) {

		foreach (
			[
				'category-tree' => [sprintf("--%s_id", get_class_short_name($model)) => $model->id],
				'category-listing' => [sprintf("--%s_id", get_class_short_name($model)) => $model->id],
			] as $command => $arguments) {

			self::dispatch($command, $arguments)->onConnection('wg.cache');
		}
	}

	/**
	 * @param Categorizable $model
	 */
	public static function afterCategoryParentUpdate(Categorizable $model) {

		switch (get_class_short_name($model)) {
			case 'store':
				$items = [
					'category-tree' => ['--store_id' => $model->id],
					'category-listing' => ['--store_id' => $model->id],
					'map-products-store-categories'  => ['--store_id' => $model->id],
					'count-products-stores'  => ['--store_id' => $model->id],
				];
				break;
			case 'region':
				$items = [
					'category-tree' => ['--region_id' => $model->id],
					'category-listing' => ['--region_id' => $model->id],
					'count-products-stores' => ['--region_id' => $model->id],
					'category-listing-featured' => [],
					'count-products' => [],
					'campaign-data' => [],
					'blog-post-data' => []
				];
				break;
			default:
				return;
		}

		foreach ($items as $command => $arguments) {
			self::dispatch($command, $arguments)->onConnection('wg.cache');
		}
	}

	/**
	 * @param Categorizable $model
	 */
	public static function afterTranslationUpdate(Categorizable $model) {
		self::dispatch('category-listing', [sprintf("--%s_id", get_class_short_name($model)) => $model->id])->onConnection('wg.cache');
	}

	public static function afterDiscountUpdate() {
        $command = 'n-cache:campaign-data';
        Artisan::call($command);
        self::dispatch('product-listing-featured')->onConnection('wg.cache');
        self::dispatch('product-listing-best-selling')->onConnection('wg.cache');

	}

    public static function afterStoreUpdate() {

        // Add queue to store cache clear
        $command = 'n-cache:store-listing';
        Artisan::call($command);
        self::dispatch('product-listing-featured')->onConnection('wg.cache');
        self::dispatch('product-listing-best-selling')->onConnection('wg.cache');
    }

    public static function clearFinancialTransactionsCache() {
    	
        Artisan::call('cache:clear-specific', ['--tag' => 'table-query-financial_transactions']);
    }

    public static function afterContentUpdate() {

	    //Direct run the page content cache clear
        $command = 'n-cache:page-listing';
        Artisan::call($command);

        // Add queue to page content cache clear
//        self::dispatch('page-listing')->onConnection('wg.cache');
    }


    /**
     * @param Store $store
     */
    public static function afterProductUpdate(Store $store) {
// Temp disable this queue process
//        foreach (
//            [
//                'category-tree' => ['--store_id' => $store->id],
//                'category-listing' => ['--store_id' => $store->id],
//                'map-products-store-categories' => ['--store_id' => $store->id],
//                'count-products-stores' => ['--store_id' => $store->id],
//                'product-listing-featured' => [],
//            ] as $command => $arguments) {
//
//            self::dispatch($command, $arguments)->onConnection('wg.cache');
//        }

        Artisan::call('n-cache:category-tree', ['--store_id' => $store->id]);
        Artisan::call('n-cache:category-listing', ['--store_id' => $store->id]);
        Artisan::call('n-cache:map-products-store-categories', ['--store_id' => $store->id]);
        Artisan::call('n-cache:count-products-stores', ['--store_id' => $store->id]);
        Artisan::call('n-cache:product-listing-featured');
        Artisan::call('n-cache:product-listing-special-campaigns');
        Artisan::call('n-cache:product-listing-best-selling');

    }



    /**
     * @param Store $store
     */
    public static function afterPricefileProcess(Store $store) {

        foreach (
            [
                'category-tree' => ['--store_id' => $store->id],
                'category-listing' => ['--store_id' => $store->id],
                'category-listing-featured' => [],
                'map-categories-stores'=> [],
                'map-categories-store-categories' => ['--store_id' => $store->id],
                'map-products-store-categories' => ['--store_id' => $store->id],
                'count-products-stores' => ['--store_id' => $store->id],
                'count-products' => [],
                'store-listing' => [],
                'product-listing-featured' => [],
                'product-listing-best-selling' => [],
                'campaign-data' => [],
                'blog-post-data' => []
            ] as $command => $arguments) {

            self::dispatch($command, $arguments)->onConnection('wg.cache');
        }
    }

    public static function clearStoreUserRelationsCache() {
    	
        Artisan::call('cache:clear-specific', ['--tag' => 'table-query-store_user_relations']);
    }

    public static function clearAdmAgreementsCache() {
    	
        Artisan::call('cache:clear-specific', ['--tag' => 'table-query-adm_agreements']);
    }

    public static function clearStoreLeadsCache() {
    	
        Artisan::call('cache:clear-specific', ['--tag' => 'table-query-store_leads']);
    }
}
