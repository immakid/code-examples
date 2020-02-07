<?php

namespace App\Acme\Repositories\Interfaces;

use App\Models\Currency;
use App\Models\Products\Product;
use App\Models\Orders\OrderItem;

/**
 * Interface CartInterface
 * @package App\Acme\Repositories\Interfaces
 */

interface CartInterface {

    /**
     * @return mixed
     */
    public function count();

    /**
     * @param Currency $currency
     * @return mixed
     */
    public function get(Currency $currency);

    /**
     * @param bool $force
     * @param bool $load_relations
     * @return \App\Models\Orders\Order|false
     */
    public function getModel($force = false, $load_relations = false);

    /**
     * @param Product $product
     * @param int $quantity
     * @param array $data
     * @return mixed
     */
    public function add(Product $product, $quantity = 1, array $data = []);

    /**
     * @param OrderItem $item
     * @return mixed
     */
    public function remove(OrderItem $item);

    /**
     * @return mixed
     */
    public function truncate();
}