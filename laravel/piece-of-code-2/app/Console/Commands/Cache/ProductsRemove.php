<?php

namespace App\Console\Commands\Cache;

use Cache;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use App\Models\Products\Product;
use App\Acme\Repositories\Criteria\Where;
use App\Acme\Repositories\Criteria\IncludingTrashed;
use App\Acme\Repositories\Interfaces\ProductInterface;

class ProductsRemove extends Command {

    /**
     * @var string
     */
    protected $signature = 'cache:products-remove {items*}';

    /**
     * @var string
     */
    protected $description = "Remove product's cache values";

    /**
     * @var ProductInterface
     */
    protected $product;

    public function __construct(ProductInterface $product) {
        parent::__construct();

        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function handle() {

        foreach ($this->argument('items') as $item) {

            list($id, $language_id, $keywords) = explode(':::', $item);

            $this->removeProduct(
                $this->product->ignoreDefaultCriteria()
                    ->setCriteria([
                        new IncludingTrashed(),
                        new Where('id', $id)
                    ])
                    ->first(),
                $language_id,
                explode('---', $keywords)
            );
        }
    }

    /**
     * @param Product $product
     * @param int $language_id
     * @param array $keywords
     */
    protected function removeProduct(Product $product, $language_id, array $keywords) {

        if (!$store = $product->store()->withTrashed()->first()) {
            return false;
        }

        $tag_prefix = config('cms.cache.ac.tag_prefix');
        $tags = array_merge(config('cms.cache.ac.tags', []), [
            sprintf("%s-language.%d", $tag_prefix, $language_id),
            sprintf("%s-store.%d", $tag_prefix, $product->store->id),
        ]);

        foreach ($keywords as $keyword) {

            if ($keyword !== mb_convert_encoding($keyword, 'UTF-8', 'UTF-8')) {
                $keyword = utf8_encode($keyword);
            }

            $key = iconv_substr($keyword, 0, 4);
            if (!$values = Cache::tags($tags)->get($key)) {
                continue;
            }

            Cache::tags($tags)->forever($key, Arr::except($values, $keyword));
        }
    }
}