<?php

namespace App\Providers;

use App\Acme\Repositories\Concrete\PriceFiles\PriceFile;
use App\Acme\Repositories\Interfaces\PriceFiles\PriceFileInterface;
use Illuminate\Support\ServiceProvider;
use App\Acme\Repositories\Concrete\Cart;
use App\Acme\Repositories\Concrete\User;
use App\Acme\Repositories\Concrete\Page;
use App\Acme\Repositories\Concrete\Order;
use App\Acme\Repositories\Concrete\Store;
use App\Acme\Repositories\Concrete\Region;
use App\Acme\Repositories\Concrete\Coupon;
use App\Acme\Repositories\Concrete\Comment;
use App\Acme\Repositories\Concrete\Product;
use App\Acme\Repositories\Concrete\BlogPost;
use App\Acme\Repositories\Concrete\Category;
use App\Acme\Repositories\Concrete\OrderItem;
use App\Acme\Repositories\Concrete\HomepageSection;
use App\Acme\Repositories\Interfaces\PageInterface;
use App\Acme\Repositories\Interfaces\UserInterface;
use App\Acme\Repositories\Interfaces\CartInterface;
use App\Acme\Repositories\Interfaces\OrderInterface;
use App\Acme\Repositories\Interfaces\StoreInterface;
use App\Acme\Repositories\Interfaces\RegionInterface;
use App\Acme\Repositories\Interfaces\CouponInterface;
use App\Acme\Repositories\Interfaces\CommentInterface;
use App\Acme\Repositories\Interfaces\ProductInterface;
use App\Acme\Repositories\Interfaces\BlogPostInterface;
use App\Acme\Repositories\Interfaces\CategoryInterface;
use App\Acme\Repositories\Interfaces\OrderItemInterface;
use App\Acme\Repositories\Concrete\Api\Client as ApiClient;
use App\Acme\Repositories\Interfaces\HomepageSectionInterface;
use App\Acme\Repositories\Interfaces\Api\ClientInterface as ApiClientInterface;

class RepositoryServiceProvider extends ServiceProvider {

	/**
	 * @return void
	 */
	public function boot() {
		//
	}

	/**
	 * @return void
	 */
	public function register() {

		$this->app->bind(OrderInterface::class, Order::class);
		$this->app->bind(OrderItemInterface::class, OrderItem::class);
		$this->app->singleton(CartInterface::class, Cart::class);
		$this->app->singleton(CouponInterface::class, Coupon::class);

		$this->app->bind(UserInterface::class, User::class);
		$this->app->bind(StoreInterface::class, Store::class);
		$this->app->bind(RegionInterface::class, Region::class);
		$this->app->bind(ProductInterface::class, Product::class);
		$this->app->bind(CategoryInterface::class, Category::class);
		$this->app->bind(CommentInterface::class, Comment::class);

		$this->app->bind(ApiClientInterface::class, ApiClient::class);

		$this->app->bind(PriceFileInterface::class, PriceFile::class);

		$this->app->bind(PageInterface::class, Page::class);
		$this->app->bind(BlogPostInterface::class, BlogPost::class);
		$this->app->bind(HomepageSectionInterface::class, HomepageSection::class);

		$this->registerAliases();
	}

	protected function registerAliases() {

		$this->app->alias(OrderInterface::class, 'repo.order');
		$this->app->alias(OrderItemInterface::class, 'repo.order.item');
		$this->app->alias(CartInterface::class, 'repo.cart');
		$this->app->alias(CouponInterface::class, 'repo.coupon');

		$this->app->alias(UserInterface::class, 'repo.user');
		$this->app->alias(StoreInterface::class, 'repo.store');
		$this->app->alias(RegionInterface::class, 'repo.region');
		$this->app->alias(ProductInterface::class, 'repo.product');
		$this->app->alias(CategoryInterface::class, 'repo.category');
		$this->app->alias(CommentInterface::class, 'repo.comment');

		$this->app->alias(ApiClientInterface::class, 'repo.api.client');

		$this->app->alias(PriceFileInterface::class, 'repo.price-files.file');

		$this->app->alias(PageInterface::class, 'repo.page');
		$this->app->alias(HomepageSectionInterface::class, 'repo.homepage-section');
		$this->app->alias(BlogPostInterface::class, 'repo.blog-post');
	}
}
