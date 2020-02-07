<?php

namespace App\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Content\Banners\BannerPosition as Position;

class BannersServiceProvider extends ServiceProvider {

    /**
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * @return void
     */
    public function register() {

        $this->app->singleton('banners.rotate', function ($app, $vars) {

            try {

                $results = array_fill_keys(Arr::get($vars, 0), false);
                foreach (Position::with('activeBanners')->key(array_keys($results))->get() as $position) {
                    $results[$position->key] = $this->getDisplayData($position, resource_path('views/app/banners'));
                }

                return $results;
            } catch (ModelNotFoundException $e) {
                //
            }

            return false;
        });
    }

    /**
     * @param Position $position
     * @param string $dir
     * @return array|bool
     */
    protected function getDisplayData(Position $position, $dir) {

        if (!$banner = $position->getNextInQueue()) {
            return false;
        }

        if ($position->key == "wg_home2" || $position->key == "wg_home_parent_category_banner" || $position->key == "wg_home_sub_category_banner") {
            if (count($position->getNextInQueueAll()) > 1) {
                $banner = $position->getNextInQueueAll();
            }
        }

        $view = 'app.banners.default';
        if (file_exists(sprintf("%s/%s.blade.php", $dir, $position->key))) {
            $view = sprintf("app.banners.%s", $position->key);
        }

        return [$position, $banner, $view];
    }
}