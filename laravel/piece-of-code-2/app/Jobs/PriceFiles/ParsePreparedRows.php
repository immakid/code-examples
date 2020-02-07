<?php

namespace App\Jobs\PriceFiles;

use App\Models\Discount;
use Carbon\Carbon;
use Exception;
use RuntimeException;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Models\Products\Product;
use App\Models\PriceFiles\PriceFileImage;

class ParsePreparedRows extends Job
{

    /**
     * @var array
     */
    protected $events = [
        \App\Events\Products\Deleted::class,
        \App\Events\Products\TranslationUpdated::class,
        \App\Events\Categories\Created::class,
        \App\Events\Categories\Deleting::class,
        \App\Events\Categories\TranslationUpdate::class,
    ];

    public function handle()
    {
        $this->handleProxy(function () {
            print_logs_app("Inside ParsePreparedRows");
            
            $time = microtime(true);
            
            if (!$deleted_rows_path = $this->getSectionPath('deleted_rows')) {
                throw new RuntimeException("Missing section file for 'deleted_rows'");
            }
            $deleted_products = $this->readPath($deleted_rows_path);
            if ((sizeof($deleted_products) > 0) && (!$path = $this->getSectionPath('prepared'))) {
                //Do nothing
            } else {
                if (!$path = $this->getSectionPath('prepared')) {
                    throw new RuntimeException("Missing section file for 'prepared'");
                }

                $categories = [];
                $store = $this->file->store;
                $items = $this->readPath($path);

                $this->file->writeLocalLog(sprintf("Loaded %d items", count($items)), [
                    'memory' => convert(memory_get_peak_usage(true)),
                ], 'debug');

                $ids = [
                    'internal' => [],
                    'incremental' => [],
                ];

                $holders = [
                    'categories' => $store->categories,
                    'products' => $store->products()->select(['id', 'internal_id'])->without(['translations', 'media'])->get(),
                ];

                $existing = [
                    'products' => Arr::pluck($holders['products'], 'id', 'internal_id'),
                    'categories' => [
                        'all' => $holders['categories'],
                        'parents' => Arr::pluck($holders['categories'], 'parent_id', 'id'),
                        'names' => Arr::pluck($holders['categories'], sprintf("translations.%d.name", $store->defaultLanguage->id), 'id'),
                    ],
                ];

                $holders = null;
                $this->file->writeLocalLog(sprintf("Items processing started"), [
                    'memory' => convert(memory_get_peak_usage(true)),
                ], 'debug');

                print_logs_app("items length - ".sizeof($items));

                foreach ($items as $index => $item) {
                    if ($index > 0 && $index % 1000 === 0) {
                        print_logs_app("Imported products");
                        
                        $this->file->writeLocalLog(sprintf("Imported %d of %d", $index, count($items)), [
                            'eta' => microtime(true) - $time,
                            'memory' => convert(memory_get_peak_usage(true)),
                        ], 'debug');
                    }

                    try {
                        $id = Arr::get($item, 'id');
                        $prices = Arr::get($item, 'prices');
                        $category = $this->gatherCategoryId($item, $store->load('categories'), $existing['categories']);

                        print_logs_app("before handleProduct");
                        
                        // 1. Create/Update
                        if (!$product = $this->handleProduct($store, [
                            'internal_id' => $id,
                            // 'enabled' => $item['enabled'],
                            'in_stock' => $item['stock']['available'],
                            'vat' => Arr::get($item, 'vat', $store->vat),
                            'image' => Arr::get($item, 'image'),
                        ], $existing['products'])) {
                            print_logs_app("Failed to save product");

                            $this->file->writeLog('Failed to save product.', ['item' => $item], 'warning');
                            continue;
                        }

                        $this
                            ->handleProductTranslation($product, $store->defaultLanguage, [ // 2. Save/Update Translation
                                'name' => Arr::get($item, 'name'),
                                'excerpt' => Arr::get($item, 'excerpt'),
                                'details' => Arr::get($item, 'description'),
                            ])
                            ->handleProductPrices($product, $store->defaultCurrency, $prices); // 3. Save prices


                        //if set discounted price
                        if(isset($prices['price_discounted'])) {

                            $this->handleProductDiscountedPrices($product, $store->defaultCurrency, $prices); // 3. Save discounted price
                        }


                        // 4. Sync categories
                        if (!isset($categories[$product->id])) {
                            $categories[$product->id] = [];
                        }

                        array_push($ids['incremental'], $product->id);
                        array_push($ids['internal'], $product->internal_id);
                        $product->dataUpdate(['stock' => ['count' => $item['stock']['count']]]);

                        if ( is_array($category) ) {
                            foreach ($category as $category_name) {
                                array_push($categories[$product->id], $category_name);
                                $product->categories()->sync($categories[$product->id]);
                            }
                        } else {
                            array_push($categories[$product->id], $category);
                            $product->categories()->sync($categories[$product->id]);
                        }
                    } catch (Exception $e) {
                        $this->file->writeLocalLog($e->getMessage(), [
                            'row' => ($index + 1),
                            'item' => $item,
                        ]);

                        continue;
                    }
                }

                $message = sprintf(
                    "Parsed %d products in %.2f seconds",
                    count($ids['incremental']),
                    (microtime(true) - $time)
                );

                $existing = null;
                $this->file->writeLocalLog($message, ['categories' => Arr::collapse(array_values($categories))], 'info');

                /*dispatch(new DeleteObsolete($this->file, $ids['incremental'], array_unique(Arr::collapse(array_values($categories)))))
                    ->chain([
                        (new RefreshCache($this->file, $ids['incremental']))->onConnection($this->file->queue),
                    ])
                    ->onConnection($this->file->queue);*/

                dispatch(new RefreshCache($this->file, $ids['incremental']))->onConnection($this->file->queue);

                $this->file->touchTs('data');
            }
        }, $this->events);
    }

    /**
     * @param Product $product
     * @param Language $language
     * @param array $data
     * @return $this
     */
    protected function handleProductTranslation(Product $product, Language $language, array $data)
    {
        if (!$translation = $product->translations()->forLanguage($language)->first()) {
            $product->saveTranslation($language, $data);
        } else {
            $product->updateTranslation($translation, $data);
        }

        return $this;
    }

    /**
     * @param Product $product
     * @param Currency $currency
     * @param array $prices
     * @return $this
     */
    protected function handleProductPrices(Product $product, Currency $currency, array $prices)
    {
        $product->savePrices([$currency->id => $prices['price']]);

        // if ($prices['price_shipping']) {
            $product->savePrices([$currency->id => $prices['price_shipping']], 'shipping');
        // }
        // if ($prices['price_discounted']) {
            $product->savePrices([$currency->id => $prices['price_discounted']], 'discount');
        // }

        return $this;
    }

    /**
     * @param Store $store
     * @param array $data
     * @param array $existing
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null|static|static[]
     */
    protected function handleProduct(Store $store, array $data, array &$existing)
    {
        print_logs_app("In handleProduct");
        $id = Arr::get($existing, $data['internal_id']);
        print_logs_app("internal_id---->".$id);
        if (!$id = Arr::get($existing, $data['internal_id'])) {
            $product = $store->products()->create(Arr::except($data, 'image'));
            $existing[$data['internal_id']] = $product->id;

            print_logs_app("Inside handleProduct");
            if ($data['image']) {
                $this->queueImageDownload($product, $data['image']);
            }
        } else {
            print_logs_app("Came to already existing for product id->".$id);
            $product = Product::find($id);
            if ( ($data['image']) && !empty($data['image']) ) {
                $this->queueImageDownload($product, $data['image']);
            }
            $product->update(Arr::except($data, 'internal_id'));
            $product->touch();
        }

        return $product;
    }

    /**
     * @param Product $product
     * @param string/array $product_images
     */
    protected function queueImageDownload(Product $product, $product_images)
    {
        if (is_array($product_images)) {
            
            foreach ($product_images as $image_url) {
                
                if ( $image_url && !empty($image_url) ) {

                    $image = new PriceFileImage(['url' => $image_url]);
                    $image->product()->associate($product);
                    $this->file->pendingImages()->save($image);
                }
            }
        
        } else {
        
            $image = new PriceFileImage(['url' => $product_images]);
            $image->product()->associate($product);
            $this->file->pendingImages()->save($image);
        }

    }

    /**
     * @param array $item
     * @param Store $store
     * @param array $existing
     * @return int|mixed
     */
    protected function gatherCategoryId(array $item, Store $store, array &$existing)
    {

        $parsed_category = (array)Arr::get($item, 'categories');
        if ( isset($parsed_category[0]) ) {
            
            $output_category_ids = array();
            foreach ($parsed_category as $category) {
                $output_category_ids[] =  $this->parseCategoryTree(array_keys_all($category), $existing, $store);
            }
            return $output_category_ids;

        } else {
            return $this->parseCategoryTree(array_keys_all($parsed_category), $existing, $store);
        }
    }

    /**
     * @param array $tree
     * @param array $existing
     * @param Store $store
     * @param Category|null $parent
     * @return int|mixed
     */
    protected function parseCategoryTree(array $tree, array &$existing, Store $store, Category $parent = null)
    {
        foreach ($tree as $index => $name) {

            /**
             * Iterate trough names searching for match
             */
            foreach (array_keys($existing['names'], $name) as $id) {

                /**
                 * Be sure that parent is also a match
                 */
                if ($existing['parents'][$id] === ($parent ? $parent->id : null)) {
                    $parent = $store->categories()->find($id);
                    if (!$children = array_slice($tree, 1)) {
                        return $parent->id;
                    }

                    return $this->parseCategoryTree($children, $existing, $store, $parent);
                }
            }

            $category = $this->saveCategory($name, $store, $parent);

            $existing['names'][$category->id] = $name;
            $existing['parents'][$category->id] = $parent ? $parent->id : null;

            if (!$children = array_slice($tree, 1)) {
                return $category->id;
            }

            return $this->parseCategoryTree($children, $existing, $store->load('categories'), $category);
        }
    }

    /**
     * @param string $name
     * @param Store $store
     * @param Category|null $parent
     * @return Category|\Illuminate\Database\Eloquent\Model
     */
    protected function saveCategory($name, Store $store, Category $parent = null): Category
    {
        $category = (!$parent) ?
            (new Category()) :
            (new Category())->parent()->associate($parent);

        $store->categories()->save($category);
        $category->saveTranslation($store->defaultLanguage, ['name' => $name]);

        return $category;
    }

    /**
     * @param Product $product
     * @param $discountedPrice
     * @return $this
     */
    protected function handleProductDiscountedPrices(Product $product, Currency $currency, array $prices)
    {
        $data = [
            "type" => "fixed",
            "value" => "0",
            "valid_from" => Carbon::now()->toDateString(),
            "valid_until" => null,
            "pricefile_discount" => 1,
            "prices" => [
                $currency->id => $prices['price']-$prices['price_discounted']
            ]
        ];


        $discount = Discount::where('discountable_type', 'product')
            ->where('discountable_id', $product->id)
            ->where('pricefile_discount', 1)->first();

        if(!$prices['price_discounted'] && $discount){
            $discount->deletePrices();
            $discount->delete();

        }

        if($prices['price_discounted']) {
            if ($discount) {
                $discount->update($data);
            } else {
                $discount = $product->discounts()->create($data);
            }
            $discount->value = null;
            $discount->savePrices($data['prices'])->update();
        }



        return $this;
    }
}