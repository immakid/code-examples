<?php

namespace App\Http;

use Route;

class Router {

    public static function setupBackend() {

        Route::get('/', 'DashboardController@index')->name('admin.dashboard');

        self::backendSystem();
        self::backendUsers();

        self::backendRegions();
        self::backendStores();

        self::backendTranslations();

        self::backendContent();
        self::backendStoreLeads();
    }

    public static function appPaginationHandler($route, $controller, $name) {

        $keys = config('cms.pagination.keys');
        Route::get(ltrim(sprintf("%s/%s/{page}", $route, $keys['uri']), '/'), $controller)
            ->name(sprintf("%s.%s", $name, $keys['route']))
            ->where($keys['uri'], '[0-9]+');
    }

    public static function backendSystem() {

        Route::group(['namespace' => 'System'], function () {

            Route::get('logs', 'SysLogController@index')->name('admin.system.logs');

            Route::resource('careers', 'CareersController', [
                'except' => ['create', 'show', 'destroy'],
                'names' => gen_resource_names('admin.system.careers', [], ['create', 'show', 'destroy'])
            ]);

            Route::resource('price-files', 'PriceFilesController', [
                'except' => ['show', 'destroy'],
                'names' => gen_resource_names('admin.system.price-files', [], ['show', 'destroy'])
            ]);

            Route::patch('price-files/{price_file}/on-demand-import', 'PriceFilesController@OnDemandImport')
                ->name('admin.system.price-files.on-demand-import');

            Route::patch('price-files/{price_file}/maps', 'PriceFilesController@updateMappings')
                ->name('admin.system.price-files.update-maps');

            self::backendHolocaustHandler('careers', 'CareersController', 'system.careers');
            self::backendHolocaustHandler('price-files', 'PriceFilesController', 'system.price-files');
        });
    }

    public static function backendUsers() {

        Route::group(['namespace' => 'Users'], function () {

            Route::resource('users', 'UsersController', [
                'except' => ['destroy'],
                'names' => gen_resource_names('admin.users', [], ['destroy'])
            ]);

            self::backendHolocaustHandler('users', 'UsersController');
            Route::get('ajax/users', 'UsersController@indexDatatables')->name('admin.users.ajax.index');
        });
    }

    public static function backendRegions() {

        Route::group(['namespace' => 'Regions'], function () {

            Route::resource('regions', 'RegionsController', [
                'except' => ['destroy'],
                'names' => gen_resource_names('admin.regions', [], ['destroy'])
            ]);

            self::backendHolocaustHandler('regions', 'RegionsController');
        });

        foreach (['orders', 'categories', 'coupons', 'discounts'] as $type) {
            self::backendSubsystem($type, 'regions');
        }
    }

    public static function backendStores() {

        Route::group(['namespace' => 'Stores'], function () {

            Route::resource('stores', 'StoresController', [
                'except' => ['edit', 'destroy'],
                'names' => gen_resource_names('admin.stores', [], ['edit', 'destroy']),
            ]);

            self::backendHolocaustHandler('stores', 'StoresController');

            Route::patch('stores/{store}/users/{user}', 'UsersController@update')->name('admin.stores.users.update');
            Route::delete('stores/{store}/users/detach', 'UsersController@detach')->name('admin.stores.users.detach');
            Route::post('stores/{store}/users/associate', 'UsersController@associate')->name('admin.stores.users.associate');

            Route::get('stores/ajax/index', 'StoresController@indexDatatables')->name('admin.stores.ajax.index');

            /**
             * Categories
             */
            Route::get('stores/{store}/category-aliases', 'Categories\AliasesController@index')->name('admin.stores.category-aliases.index');
            Route::patch('stores/{store}/category-aliases', 'Categories\AliasesController@update')->name('admin.stores.category-aliases.update');

            /**
             * Products
             */
            Route::resource('stores.products', 'ProductsController', [
                'except' => ['destroy'],
                'names' => gen_resource_names('admin.stores.products', [], ['destroy']),
            ]);

            self::backendHolocaustHandler('stores/{store}/products', 'ProductsController', 'stores.products');
            Route::get('stores/{store}/products/ajax/index', 'ProductsController@indexDatatables')->name('admin.stores.products.ajax.index');

            /**
             * Shipping
             */
            Route::resource('stores.shipping-options', 'ShippingController', [
                'only' => ['index', 'store'],
                'names' => gen_resource_names('admin.stores.shipping-options', ['index', 'store'])
            ]);

            /**
             * Financial Transactions
             */
            Route::resource('stores.financial-transactions', 'FinancialTransactionsController', [
                'only' => ['index'],
                'names' => gen_resource_names('admin.stores.financial-transactions', ['index'])
            ]);

            Route::patch('stores/{store}/financial-transactions', 'FinancialTransactionsController@applyFilters')
                ->name('admin.stores.financial-transactions.index');

            /**
             * PriceFile
             */

            Route::patch('stores/{store}/price-file', 'PriceFileController@update')->name('admin.stores.price-file.update');
        });

        foreach (['categories', 'orders', 'coupons', 'discounts'] as $type) {
            self::backendSubsystem($type, 'stores');
        }

        self::backendSubsystem('discounts', 'stores.products');
    }

    public static function backendTranslations() {

        Route::group(['prefix' => 'translations', 'namespace' => 'Translations'], function () {
            Route::get('strings/destroy', 'StringTranslationsController@destroy')->name('admin.translations.strings.destroy');
            Route::get('strings', 'StringTranslationsController@index')->name('admin.translations.strings.index');
            Route::post('strings', 'StringTranslationsController@store')->name('admin.translations.strings.store');
            Route::get('strings/create', 'StringTranslationsController@create')->name('admin.translations.strings.create');
            Route::post('strings/add-new', 'StringTranslationsController@addNewKey')->name('admin.translations.strings.add-new');

        });
    }

    public static function backendContent() {

        Route::group(['prefix' => 'content', 'namespace' => 'Content'], function () {

            Route::get('homepage', 'HomepageSectionsController@index')->name('admin.content.homepage.index');
            Route::patch('homepage', 'HomepageSectionsController@update')->name('admin.content.homepage.update');

            Route::resource('pages', 'PagesController', [
                'except' => ['destroy'],
                'names' => gen_resource_names('admin.content.pages', [], ['destroy'])
            ]);

            self::backendHolocaustHandler('pages', 'PagesController', 'content.pages');

            Route::group(['prefix' => 'blog', 'namespace' => 'Blog'], function () {
                Route::resource('posts', 'PostsController', [
                    'except' => ['destroy'],
                    'names' => gen_resource_names('admin.content.blog.posts', [], ['destroy'])
                ]);

                self::backendHolocaustHandler('posts', 'PostsController', 'content.blog.posts');
            });

            Route::group(['prefix' => 'banner-positions', 'namespace' => 'Banners'], function () {

                Route::get('/', 'BannersPositionsController@index')->name('admin.content.banners.positions.index');
                Route::patch('{position}', 'BannersPositionsController@update')->name('admin.content.banners.positions.update');

                Route::resource('position.items', 'BannersController', [
                    'except' => ['destroy'],
                    'names' => gen_resource_names('admin.content.banners.positions.items', [], ['destroy'])
                ]);

                self::backendHolocaustHandler('position/{position}', 'BannersController', 'content.banners.positions.items');
            });

            Route::group(['prefix' => 'comments'], function () {

                Route::get('/', 'CommentsController@index')->name('admin.content.comments.index');
                Route::patch('mapprove', 'CommentsController@approve')->name('admin.content.comments.approve-many');
                Route::get('ajax/index', 'CommentsController@indexDatatables')->name('admin.content.comments.ajax.index');

                self::backendHolocaustHandler(null, 'CommentsController', 'content.comments');
            });

            Route::group(['namespace' => 'Faq'], function () {

                Route::resource('faq', 'FaqSectionsController', [
                    'except' => ['create', 'destroy'],
                    'names' => gen_resource_names('admin.content.faq', [], ['create', 'destroy'])
                ]);

                Route::resource('faq.items', 'FaqItemsController', [
                    'only' => ['index', 'store'],
                    'names' => gen_resource_names('admin.content.faq.items', ['index', 'store'])
                ]);

                self::backendHolocaustHandler('faq', 'FaqSectionsController', 'content.faq');
            });
        });
    }

    /**
     * @param string $type
     * @param string $parent
     */
    public static function backendSubsystem($type, $parent) {

        switch ($type) {
            case 'orders':
                self::backendSubsystemOrders($parent);
                break;
            case 'categories':
                self::backendSubsystemCategories($parent);
                break;
            default:

                $name = sprintf("admin.%s.%s", $parent, $type);
                $controller = sprintf('Subsystems\%sController', ucfirst($type));

                Route::resource(sprintf("%s.%s", $parent, $type), $controller, [
                    'except' => ['create', 'show', 'destroy'],
                    'names' => gen_resource_names($name, [], ['create', 'show', 'destroy'])
                ]);

                $prefix = [];
                foreach (explode('.', $parent) as $part) {
                    array_push($prefix, sprintf("%s/{%s}", $part, substr($part, 0, strlen($part) - 1)));
                }

                self::backendHolocaustHandler(
                    implode('/', array_merge($prefix, [$type])),
                    $controller,
                    substr($name, strpos($name, '.') + 1)
                );
        }
    }

    /**
     * @param string $parent
     */
    public static function backendSubsystemOrders($parent) {

        $name = sprintf("admin.%s.orders", $parent);
        $controller = sprintf('Subsystems\OrdersController');

        Route::resource(sprintf("%s.orders", $parent), $controller, [
            'except' => ['show', 'create', 'store', 'destroy'],
            'names' => gen_resource_names($name, [], ['show', 'create', 'store', 'destroy'])
        ]);

        Route::get(
            sprintf("%s/{%s}/orders/{order}/download", $parent, substr($parent, 0, strlen($parent) - 1), $parent),
            sprintf("%s@downloadPdf", $controller))->name(sprintf("%s.download", $name));

        $path = "%s/{%s}/orders/ajax/index";
        $route = sprintf($path, $parent, substr($parent, 0, strlen($parent) - 1));

        Route::get($route, 'Subsystems\OrdersController@indexDatatables')
            ->name(sprintf("admin.%s.orders.ajax.index", $parent));
    }

    /**
     * @param string $parent
     */
    public static function backendSubsystemCategories($parent) {

        Route::resource(sprintf("%s.categories", $parent), 'Subsystems\CategoriesController', [
            'except' => ['create', 'show'],
            'names' => gen_resource_names(sprintf("admin.%s.categories", $parent), [], ['create', 'show'])
        ]);

        $path = "%s/{%s}/categories/ajax/category-tree/{category}";
        $route = sprintf($path, $parent, substr($parent, 0, strlen($parent) - 1));

        Route::get($route, 'Subsystems\CategoriesController@showCategoryiesTree')
            ->name(sprintf("admin.%s.categories.category-tree", $parent));

        $path = "%s/{%s}/categories/ajax/update";
        $route = sprintf($path, $parent, substr($parent, 0, strlen($parent) - 1));

        Route::patch($route, 'Subsystems\CategoriesController@ajaxUpdate')
            ->name(sprintf("admin.%s.categories.ajax.update", $parent));
    }

    /**
     * @param string $prefix
     * @param string $controller
     * @param string|null $name
     */
    public static function backendHolocaustHandler($prefix, $controller, $name = null) {

        Route::delete(sprintf('%s/mdestroy', $prefix), sprintf('%s@destroyMultiple', $controller))
            ->name(sprintf('admin.%s.destroy-many', $name ? $name : $prefix));
    }

    public static function backendStoreLeads() {

        Route::group(['namespace' => 'StoreLeads'], function () {
            
            Route::get('storeleads/agreement/{agmt_id}', 'StoreLeadsController@showAgreement')->name('admin.storeleads.agreement');

            Route::patch('storeleads/sign_agmt/{agmt_id}', 'StoreLeadsController@signAgreement')->name('admin.storeleads.sign_agmt');

            Route::get('storeleads/ajax/index', 'StoreLeadsController@indexDatatables')->name('admin.storeleads.ajax.index');

            Route::get('storeleads/ajax/index/{sales_rep_id}', 'StoreLeadsController@indexDatatables')->name('admin.storeleads.ajax.index');
            
            Route::get('/storeleads/ajax/pagination', 'StoreLeadsController@leadsPagination')->name('admin.storeleads.ajax.pagination');

            Route::get('/storeleads/ajax/pagination', 'StoreLeadsController@leadsPagination')->name('admin.storeleads.ajax.pagination');

            Route::post('storeleads/assign_sales_rep', 'StoreLeadsController@assignLeadToSalesRep')->name('admin.storeleads.assign_sales_rep');

            Route::resource('storeleads', 'StoreLeadsController', [
                'except' => ['destroy'],
                'names' => gen_resource_names('admin.storeleads', [], ['destroy']),
            ]);

            self::backendHolocaustHandler('storeleads', 'StoreLeadsController');

        });
    }
}