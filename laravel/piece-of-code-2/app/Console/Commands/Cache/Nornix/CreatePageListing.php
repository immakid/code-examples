<?php

namespace App\Console\Commands\Cache\Nornix;

use NornixCache;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Interfaces\PageInterface;
use App\Acme\Repositories\Interfaces\RegionInterface;
use Illuminate\Support\Arr;

class CreatePageListing extends Command {

    /**
     * @var string
     */
    protected $signature = 'n-cache:page-listing';

    /**
     * @var string
     */
    protected $description = 'Load list of system pages';

    /**
     * @var PageInterface
     */
    protected $page;

    /**
     * @var RegionInterface
     */
    protected $region;

    public function __construct(PageInterface $page, RegionInterface $region) {
        parent::__construct();

        $this->page = $page;
        $this->region = $region;
    }

    /**
     * @return mixed
     */
    public function handle() {

        return $this->handleProxy(function () {

            foreach ($this->region->all() as $region) {

                if (!$pages = $region->pages()->system()->without(['translations'])->get()) {
                    continue;
                }

                NornixCache::region($region, 'pages', 'listing')->write($pages->toArray(), false);

                //get page content
                $this->getContent($region, $pages);

                return 0;
            }
        });
    }


    /**
     * @param Store $store
     * @param Category $category
     */
    protected function getContent($region) {

        $pages = $region->pages()->system()->get();
        $pageKey = Arr::pluck($pages, 'key');
        $result = [];
        foreach ($pageKey as $key => $value){
            $result[$value] = $pages->where('key', $value)->first()->toArray();
        }
        NornixCache::region($region, 'pages', 'content')->write($result, false);

    }
}
