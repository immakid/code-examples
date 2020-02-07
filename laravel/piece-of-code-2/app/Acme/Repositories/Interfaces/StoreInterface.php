<?php

namespace App\Acme\Repositories\Interfaces;

use App\Models\Stores\Store;

/**
 * Interface StoreInterface
 * @package App\Acme\Repositories\Interfaces
 * @mixin \App\Acme\Repositories\EloquentRepositoryInterface
 */
interface StoreInterface {

    /**
     * @return array
     */
    public function getPayExFields();

    /**
     * @param Store $model
     * @param bool $hash
     * @return array
     */
    public function getPayExData(Store $model, $hash = true);

    /**
     * @param array|null $data
     * @param Store|null $model
     * @return string
     */
    public function getPayExHash(array $data = null, Store $model = null);
}