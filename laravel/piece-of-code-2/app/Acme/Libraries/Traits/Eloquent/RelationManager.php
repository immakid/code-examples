<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait RelationManager
 * @package App\Acme\Libraries\Traits\Eloquent
 * @mixin \Eloquent
 */

trait RelationManager {

    /**
     * @param array $items
     * @return $this
     */
    public function saveRelations(array $items) {

        foreach ($this->requestRelations as $relation => $key) {

            if ($this->$relation() instanceof BelongsTo) {

                if (!$id = Arr::get($items, $key)) {
                    continue;
                }

                /**
                 * Get related ids for Eloquent relation
                 * and attach them, so
                 */

                $class = $this->$relation()->getRelated();
                $model = call_user_func([$class, 'find'], $id);

                $this->$relation()->associate($model); // ex. attach User via user_id
            } elseif ($this->$relation() instanceof BelongsToMany) {

                if (!$ids = Arr::get($items, $key)) {
                    continue;
                }

                /**
                 * We can sync only after model has been saved,
                 * so queue it for later...
                 */

                static::registerModelEvent('saved', function () use ($relation, $ids) {
                    $this->$relation()->sync($ids);
                });

            } else {
                throw new RuntimeException("Supported relations are `BelongsTo`, `BelongsToMany`");
            }
        }

        return $this;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function saveRelationsFromRequest(Request $request) {
        return $this->setBooleanRelationsFromRequest($request)->saveRelations($request->all());
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setBooleanRelationsFromRequest(Request $request) {

        if (isset($this->casts)) {

            foreach ($this->casts as $key => $type) {

                if (!in_array($type, ['bool', 'boolean'])) {
                    continue;
                }

                $this->setAttribute($key, $request->input($key, false));
            }
        }

        return $this;
    }

}