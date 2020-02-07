<?php

namespace App\Http\Controllers\Backend;

use App\Acme\Repositories\Criteria\Scope;
use App\Http\Controllers\BackendController;

class DashboardController extends BackendController {

	public function index() {
		$stats = [
			'counters' => [
				'products' => [
					'total' => $this->productRepository->count(),
					'sold' => $this->countSoldProducts()
				],
				'users' => $this->userRepository->clearCriteria()->count(),
				'orders' => $this->orderRepository->setCriteria(new Scope('complete'))->count()
			]
		];

		return view('backend.dashboard', [
			'subtitle' => 'Dashboard',
			'stats' => $stats
		]);
	}

	protected function countSoldProducts() {

		$count = 0;
		foreach ($this->orderItemRepository->setCriteria(new Scope('shipped'))->all() as $item) {
			$count += $item->quantity;
		}

		return $count;
	}
}
