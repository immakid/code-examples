<?php

namespace App\Acme\Repositories\Concrete;

use App\Acme\Repositories\Criteria\Where;
use Illuminate\Container\Container;
use App\Acme\Repositories\Criteria\Has;
use App\Models\Products\Product as Model;
use App\Acme\Repositories\Criteria\Enabled;
use App\Acme\Repositories\Criteria\WhereHas;
use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\ProductInterface;
use App\Acme\Repositories\Interfaces\CommentInterface;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Collections\RepositoryCriteriaCollection as Collection;

class Product extends EloquentRepository implements ProductInterface {

    /**
     * @var CommentInterface
     */
    protected $comment;

    public function __construct(
        Container $container,
        Collection $collection,
        CommentInterface $comment
    ) {

        $this->comment = $comment;
        parent::__construct($container, $collection);
    }

    /**
     * @return string
     */
    protected function model() {
        return \App\Models\Products\Product::class;
    }

    /**
     * @return array
     */
    public function defaultCriteria() {

        $currency = app('defaults')->currency;

        return [
            new Enabled(),
	        new Where('in_stock', true),
            new Has('categories'),
            new WhereHas('store', function (QueryBuilder $builder) {
                return $builder->enabled();
            }),
            ($currency ? new WhereHas('pricesGeneral', function (QueryBuilder $builder) use ($currency) {
                return $builder->forCurrency($currency);
            }) : new Has('pricesGeneral'))
        ];
    }

    /**
     * @param Model $product
     * @param string $text
     * @param int $rating
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function writeReview(Model $product, $text, $rating = 1) {
        return $product->reviews()->save($this->comment->makeNew($text, $rating));
    }
}