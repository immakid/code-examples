<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

trait Categorizer {

    /**
     * @param QueryBuilder $query
     * @param int|array $ids
     * @return QueryBuilder
     */
    public function scopeWithinCategories(QueryBuilder $query, $ids) {

        return $query->whereHas('categories', function (QueryBuilder $query) use ($ids) {
            return $query->whereIn(get_table_column_name($query->getModel(), 'id'), is_array($ids) ? $ids : [$ids]);
        });
    }

    /**
     * @return mixed
     */
    public function categories() {
        return $this->morphMany(Category::class, 'categorizable');
    }
}