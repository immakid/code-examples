<?php

namespace App\Http\Controllers\App\Users;

use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Repositories\Criteria\Where;
use App\Http\Controllers\FrontendController;
use App\Http\Requests\App\ModifyFavouritesFormRequest;
use App\Acme\Libraries\Traits\Controllers\RequestFilters;

class FavouritesController extends FrontendController {

    use RequestFilters;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        return view('app.users.favourites.index-guest');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexProducts() {

        $user = $this->userRepository->current();
        $filters = $this->getRequestFilters();
        $criteria = new In(array_values(Arr::pluck($user->favouriteProducts->toArray(), 'id')));

        return view('app.users.favourites.new-index', [
            'type' => 'products',
            'filters' => $filters,
            'items' => $this->applyRequestFilter($this->productRepository, [$criteria]),
            'pagination' => [
                'total' => ceil($this->productRepository->setCriteria($criteria)->count() / $filters['values']['limit'])
            ]
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexStores() {

        $user = $this->userRepository->current();
        $criteria = new In(array_values(Arr::pluck($user->favouriteStores->toArray(), 'id')));

        $filters = $this
            ->setFilterOptions('order', config('cms.ordering_options.stores'))
            ->getRequestFilters();

        return view('app.users.favourites.new-index', [
            'type' => 'stores',
            'filters' => $filters,
            'items' => $this->applyRequestFilter($this->storeRepository, [$criteria]),
            'pagination' => [
                'total' => ceil($this->storeRepository->setCriteria($criteria)->count() / $filters['values']['limit'])
            ]
        ]);
    }

    /**
     * @param ModifyFavouritesFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(ModifyFavouritesFormRequest $request) {

        $id = $request->input('id');
        $type = $request->input('type');
        $relation = sprintf('favourite%ss', ucfirst($type));

        switch ($type) {
            case 'product':
                $model = $this->productRepository->setCriteria(new Where('id', $id));
                break;
            case 'store':
                $model = $this->storeRepository->setCriteria(new Where('id', $id));
                break;
            default:
                return response()->json([
                    'messages' => [
                        'error' => __t('messages.error.invalid_request')
                    ]
                ]);
        }

        $this->userRepository->current()->$relation()->attach($model->first(), ['type' => $type]);
        return response()->json(json_message(__t('messages.success.added_to_favourites'), 'success', [
            'favourite_trigger_mark' => [$type, $id]
        ]));
    }
}
