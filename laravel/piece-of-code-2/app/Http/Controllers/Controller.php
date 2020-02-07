<?php

namespace App\Http\Controllers;

use Closure;
use Developer;
use App\Acme\Libraries\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Acme\Repositories\Interfaces\CartInterface;
use App\Acme\Repositories\Interfaces\UserInterface;
use Illuminate\Routing\Controller as BaseController;
use App\Acme\Repositories\Interfaces\StoreInterface;
use App\Acme\Repositories\Interfaces\OrderInterface;
use App\Acme\Repositories\Interfaces\CouponInterface;
use App\Acme\Repositories\Interfaces\ProductInterface;
use App\Acme\Repositories\Interfaces\BlogPostInterface;
use App\Acme\Repositories\Interfaces\CategoryInterface;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Acme\Repositories\Interfaces\OrderItemInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Acme\Repositories\Interfaces\HomepageSectionInterface;

class Controller extends BaseController {

	use DispatchesJobs,
		ValidatesRequests,
		AuthorizesRequests;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var UserInterface
	 */
	protected $userRepository;

	/**
	 * @var CartInterface
	 */
	protected $cartRepository;

	/**
	 * @var CouponInterface
	 */
	protected $couponRepository;

	/**
	 * @var OrderInterface
	 */
	protected $orderRepository;

	/**
	 * @var OrderItemInterface
	 */
	protected $orderItemRepository;

	/**
	 * @var StoreInterface
	 */
	protected $storeRepository;

	/**
	 * @var ProductInterface
	 */
	protected $productRepository;

	/**
	 * @var CategoryInterface
	 */
	protected $categoryRepository;

	/**
	 * @var BlogPostInterface
	 */
	protected $blogPostRepository;

	/**
	 * @var HomepageSectionInterface
	 */
	protected $homepageSectionRepository;

	public function __construct(Closure $closure = null) {

		$this->userRepository = app('repo.user');
		$this->cartRepository = app('repo.cart');
		$this->couponRepository = app('repo.coupon');
		$this->orderRepository = app('repo.order');
		$this->orderItemRepository = app('repo.order.item');
		$this->storeRepository = app('repo.store');
		$this->productRepository = app('repo.product');
		$this->categoryRepository = app('repo.category');
		$this->blogPostRepository = app('repo.blog-post');
		$this->homepageSectionRepository = app('repo.homepage-section');

		$this->middleware(function (Request $request, $next) use ($closure) {

			if ($closure) {
				$closure($request);
			}

			/**
			 * Determine default language and currency
			 * and share basic data with views.
			 */

			$user = $this->userRepository
				->with(['favouriteStores', 'favouriteProducts'])
				->current();

			/**
			 * Basic, default, data
			 */

			$this->request = $request;
			app('defaults')->currency = $request->getScope()->defaultCurrency;
			app('defaults')->language = ($user ? $user->language : $request->getScope()->defaultLanguage);
			app('translator')->setLocale(app('defaults')->language->code);

			// Share it with views
			View::share([
				'user' => $user,
				'is_developer' => Developer::isPresent(),
				'defaults' => app('defaults')->toArray(),
			]);

			// Sub Controllers view data
			if (!in_array('ajax', $request->route()->gatherMiddleware()) && method_exists($this, 'shareViewsData')) {
				View::share((array)call_user_func([$this, 'shareViewsData'], $request));
			}

			$response = $next($request);

			return $response;
		});
	}
}
