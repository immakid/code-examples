<?php

namespace App\Providers;

use App\Models\Career;
use App\Models\Category;
use App\Models\Stores\Store;
use App\Models\Content\BlogPost;
use App\Models\Products\Product;
use App\Observers\MediaObserver;
use App\Models\Content\Banners\Banner;
use Illuminate\Support\ServiceProvider;

class ObserversProvider extends ServiceProvider {

    /**
     * @var array
     */
    protected static $items = [
        MediaObserver::class => [
            Store::class,
            Career::class,
            Banner::class,
            Product::class,
            BlogPost::class,
            Category::class,
        ]
    ];

    /**
     * @return void
     */
    public function boot() {

        foreach(self::$items as $observer => $models) {
            foreach($models as $model) {
                call_user_func([$model, 'observe'], $observer);
            }
        }
    }

    /**
     * @return void
     */
    public function register() {
        //
    }
}
