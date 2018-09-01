<?php

use App\Models\Car;
use App\Models\HasImages;

Auth::routes();

Route::get('/', 'HomeController@index')->name('home');
Route::get('test', 'HomeController@test');

Route::group(['middleware' => 'auth'], function() {

    Route::group(['prefix' => 'cars'], function() {
//        Route::get('/', 'CarsController@index');
        Route::get('my', 'CarsController@my');
        Route::get('all', 'CarsController@all');
        Route::get('{car}/extra', 'CarsController@extra');
        Route::get('create', 'CarsController@create');
        Route::post('create', 'CarsController@postCreate');
        Route::get('{car}/update', 'CarsController@update');
        Route::post('{car}/update', 'CarsController@postUpdate');
        Route::get('{car}/expenses', 'CarsController@expenses');
    });

    Route::group(['prefix' => 'clients'], function() {
        Route::get('all', 'ClientsController@all');
        Route::get('create', 'ClientsController@create');
        Route::post('create', 'ClientsController@postCreate');
        Route::get('test', 'ClientsController@test');
        Route::get('{client}/update', 'ClientsController@update');
        Route::post('{client}/update', 'ClientsController@postUpdate');
        Route::get('{client}/cars', 'ClientsController@cars');
        Route::get('{client}/extra', 'ClientsController@extra');
    });


    Route::bind('images_for_id', function(int $id, \Illuminate\Routing\Route $route) {
        if($id < 0)
            abort(404);

        $model = null;
        $for = $route->parameter('images_for');
        $route->forgetParameter('images_for');

        switch($for) {
            case 'cars':
                $model = $id ? Car::find($id) : new Car();
                break;
            case 'people':
                $model = $id ? People::find($id) : new People();
                break;
            default:
                abort(404);
        }

        if(!$model)
            abort(404);

        if(!$model->id)
            $model->id = 0;
        if(!$model->user_id)
            $model->user_id = Auth::id();
        return $model;
    });

    Route::bind('images_image', function(int $id, \Illuminate\Routing\Route $route) {
        if($id < 1)
            abort(404);

        /** @var HasImages $model */
        $model = $route->parameter('images_for_id');
        $image = $model->images()->whereKey($id)->first();

        if(!$image)
            abort(404);

        return $image;
    });

    Route::group(['prefix' => 'images/{images_for}/{images_for_id}'], function() {
        Route::get('/', 'ImagesController@index');
        Route::post('upload', 'ImagesController@upload');
        Route::post('{images_image}/setTitle', 'ImagesController@setTitle');
        Route::post('{images_image}/setMain', 'ImagesController@setMain');
        Route::post('{images_image}/delete', 'ImagesController@delete');
    });

});

Route::get('dealership/clients/my', 'Dealership\MyClientsController')->name('dealership.clients.index.my');
Route::resource('dealership/clients', 'Dealership\ClientsController', [
    'names' => [
        'index' => 'dealership.clients.index',
        'show' => 'dealership.clients.show',
        'create' => 'dealership.clients.create',
        'edit' => 'dealership.clients.edit',
    ],
])->except('store', 'update', 'destroy');
