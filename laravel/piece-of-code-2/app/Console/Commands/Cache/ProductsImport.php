<?php

namespace App\Console\Commands\Cache;

use Cache;
use Illuminate\Console\Command;
use App\Models\Products\Product;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Repositories\Criteria\Paginate;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Acme\Repositories\Interfaces\ProductInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProductsImport extends Command
{

    /**
     * @var string
     */
    protected $signature = 'cache:products-import {ids?*} {--all}';

    /**
     * @var string
     */
    protected $description = 'Import one or more products into cache.';

    /**
     * @var ProductInterface
     */
    protected $product;

    /**
     * @var int How many products per iteration on import-all
     */
    protected static $importIterationLimit = 10000;

    public function __construct(ProductInterface $product)
    {
        parent::__construct();

        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        try {
            if ($this->option('all')) {
                $this->importEverything();
            } elseif ($this->argument('ids')) {
                $products = $this->product
                    ->setCriteria([new In($this->argument('ids'))])
                    ->without(['media'])
                    ->with([
                        'store' => function (Relation $relation) {
                            $relation->without(['translations', 'media']);
                        },
                    ])
                    ->all();

                $this->importProducts($products);

            }
        } catch (\Exception $e) {
            $this->error($e->getMessage() . "\n" . $e->getFile() . "\n" . $e->getLine() . "\n"); // tmp
        }
    }


    protected function importEverything()
    {
        $count = $this->product->count();
        $limit = self::$importIterationLimit;

        for ($i = 1; $i <= ceil($count / $limit); $i++) {
            $this->line(sprintf("[i] %d/%d (%d)", $i, ceil($count / $limit), $count));

            $time = time();
            $products = $this->product
                ->setCriteria(new Paginate($limit, $i))
                ->without(['media'])
                ->with([
                    'store' => function (Relation $relation) {
                        $relation->without(['translations', 'media']);
                    },
                ])
                ->all();

            $this->line("[i] Gathered, importing... (" . (time() - $time) . "s)");
            $this->importProducts($products);

        }
    }

    protected function importProducts(Collection $products)
    {
        $time = time();
        $tag_prefix = config('cms.cache.ac.tag_prefix');

        $items = [];

        foreach ($products as $index => $product) {
            try {
                foreach ($product->translations as $translation) {

                    $tag = sprintf("%d-%d", $translation->language->id, $product->store->id);
                    $keyword = $this->test($translation->name);
                    if (Arr::get($items, "$tag", false) === false) {
                        Arr::set($items, "$tag", [
                            $keyword => [$product->id],
                        ]);
                        continue;
                    } else {
                        if (Arr::exists($items[$tag], $keyword)) {
                            array_push($items[$tag][$keyword], $product->id);
                        } else {
                            $items[$tag][$keyword] = [$product->id];
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage() . "\n" . $e->getFile() . "\n" . $e->getLine() . "\n");
                continue;
            }
        }

        $products = null;
        foreach ($items as $tag => $keys) {
            list($language_id, $store_id) = explode('-', $tag);
            $tags = array_merge(config('cms.cache.ac.tags', []), [
                sprintf("%s-language.%d", $tag_prefix, $language_id),
                sprintf("%s-store.%d", $tag_prefix, $store_id),
            ]);

            foreach ($keys as $key => $keywords) {
                if (!$existing = Cache::tags($tags)->get($key)) {
                    Cache::tags($tags)->forever($key, $keywords);
                    continue;
                }

                Cache::tags($tags)->forever($key, $keywords);
            }
        }

        $this->line("[i] Imported... (" . (time() - $time) . "s)");
    }

    public function test($name, $strToLower = true)
    {
        $find = ['/', '.', '+'];
        $replace = [' ', ' ', ' '];

        $string = mb_strtolower($name ? $name : $this->name);
        if (!$strToLower) {
            $string = $name ? $name : $this->name;
        }

        return str_replace($find, $replace, $string);
    }

}
