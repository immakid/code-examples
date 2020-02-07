<?php

namespace App\Jobs\PriceFiles;

use Artisan;
use App\Jobs\RefreshNornixCache;
use App\Models\PriceFiles\PriceFile;

class RefreshCache extends Job
{

    /**
     * @var array
     */
    protected $ids = [
        'products' => []
    ];

    public function __construct(PriceFile $file, array $productIds = [])
    {
        parent::__construct($file);

        $this->ids['products'] = $productIds;
    }

    public function handle()
    {

        $this->handleProxy(function () {

            Artisan::call('cache:clear-specific', ['--group' => 'queries']);
            $this->file->writeLocalLog('Cleared queries cache', [], 'debug', $this->getName());
            Artisan::call('cache:products-import', ['ids' => $this->ids['products']]);
            $this->file->writeLocalLog(sprintf("Imported %d products into autocomplete cache (redis)", count($this->ids['products'])), [], 'debug', $this->getName());
            //RefreshNornixCache::dispatch()->onConnection('wg.cache');

            $store = $this->file->store;
            //update cache with store id
            RefreshNornixCache::afterPricefileProcess($store);
        });
    }
}