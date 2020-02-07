<?php

namespace App\Http\Controllers\Backend\Subsystems;

use App\Acme\Libraries\Datatables\Transformers\OrderTransformer;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Acme\Interfaces\Eloquent\HasOrders;
use App\Acme\Libraries\Datatables\Datatables;
use App\Acme\Repositories\Criteria\Orders\OrderId;
use App\Acme\Repositories\Interfaces\OrderInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Jobs\SendEmail;
use View;


class OrdersController extends SubsystemController {

	/**
	 * @var OrderInterface
	 */
	protected $order;

	public function __construct(HasOrders $model = null, OrderInterface $order = null) {

		$this->order = $order;
		$this->model = $model;
		$this->model_route_identifier = 'order';

		parent::__construct(['ajax.index']);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {

		return view('backend._subsystems.index', [
			'_model' => $this->model,
			'_routes' => $this->routes,
			'_parameters' => $this->parameters,
			'_subsystem' => 'orders',
			'_type' => Arr::first($this->request->route()->parameterNames()),
			'title' => __t('titles.subsystems.orders'),
			'subtitle' => __t('subtitles.index')
		]);
	}

	/**
	 * @param Order $order
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function edit(Order $order) {

		$type = Arr::first($this->request->route()->parameterNames());
		$data = $this->order->setCriteria(new OrderId($order->internal_id))->parse();

		return view('backend._subsystems.edit', [
			'_model' => $this->model,
			'_routes' => $this->routes,
			'_parameters' => $this->parameters,
			'_subsystem' => 'orders',
			'_type' => $type,
			'order' => $data,
			'title' => __t('titles.subsystems.orders'),
			'subtitle' => __t('subtitles.index'),
			'statuses' => [
				'list' => OrderItem::getStatuses(),
				'conditions' => OrderItem::getStatusConditions()
			],
			'address_keys' => [
				'first_name' => 'first_name',
				'last_name' => 'last_name',
				'street' => 'street',
				'street2' => 'street2',
				'city' => 'city',
				'zip' => 'zip',
				'county' => 'country.name',
                'telephone' => 'telephone'
			]
		]);
	}

	/**
	 * @param Order $order
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update(Order $order) {

		$error = false;
		$conditions = OrderItem::getStatusConditions();

		foreach ($this->request->input('items', []) as $id => $values) {

			try {

				$status = Arr::get($values, 'status');
                if($status !== null) {
                    $item = $order->items()->findOrFail($id);
                    $item->dataUpdate(Arr::get($values, 'data', []));
                    if (!in_array($status, $conditions) || $item->{$conditions[$status]}) {
                        $refunded_quantity = Arr::get($values, 'refunded_quantity');
						
						if($refunded_quantity !== null && $item->refunded_quantity+$refunded_quantity < $item->quantity && $status == "refunded") {
							if ( ($refunded_quantity > 0) && ($refunded_quantity < $item->quantity) ) {
								$item = $order->items()->findOrFail($id);
								print_logs_app("refunded_quantity - ".$refunded_quantity);
								print_logs_app("Item refunded quantity - ".$item->refunded_quantity);
								if (!$item->update(["refunded_quantity"=>($item->refunded_quantity+$refunded_quantity)])) {
									print_logs_app("------> Error in update refunded_quantity - 1");
									$error = true;
								}
							}
	                        if (!$item->setStatus("refunded")) {
	                            print_logs_app("------------> ERROR in refunded setStatus");
								$error = true;
	                        }
	                        if (!$item->setStatus("partial_refunded")) {
	                            print_logs_app("------------> ERROR in partial_refunded setStatus");
								$error = true;
	                        }
						} elseif ($refunded_quantity !== null && $item->refunded_quantity+$refunded_quantity == $item->quantity && $status == "refunded") {
							$item = $order->items()->findOrFail($id);
							if (!$item->update(["refunded_quantity"=>$item->quantity])) {
								print_logs_app("------> Error in update refunded_quantity - 2");
								$error = true;
							}
	                        if (!$item->setStatus($status)) {
								print_logs_app("------------> ERROR in CUSTOM refunded setStatus");
	                            $error = true;
	                        }
						} else {
	                        if (!$item->setStatus($status)) {
	                            print_logs_app("------------> ERROR in DEFAULT status setStatus");
								$error = true;
	                        }
						}
                    }
                    $this->setMail($status ,$order, $id);
                }
			} catch (ModelNotFoundException $e) {
				continue;
			}
		}

		if (!$error) {
			flash()->success(__t('messages.success.updated', ['object' => 'order']));
		} else {
			flash()->error(__t('messages.error.saving', ['object' => 'order']));
		}

		return redirect()->back();
	}

	function setMail($status ,$order, $id) {
	    
	    $item = $order->items()->findOrFail($id);
	    $user = $order->user->name;
	    if($status == 'refunded' || $status == 'shipped') {
            $jobs = [
                new SendEmail(__tF('emails.order.' . $status . '.subject'),
                    View::make('emails.'.$status,['user' => $user, 'item'=> $item]),
                    [$order->user->username, $order->user->name]
                )
            ];
            foreach ($jobs as $job) {
                dispatch($job)->onConnection('wg.emails');
            }
        }
    }
	/**
	 * @return mixed
	 */
	public function indexDatatables() {

		$criteria = $this->request->get('order') ? [] : [new OrderBy('created_at', 'DESC')];
		$repository = $this->order->setCriteria(array_merge($this->model->getOrdersCriteria(), $criteria))
			->with('user:id,name')
			->columns(['orders.status as order_status', 'orders.*']);

		return Datatables::of($repository->query())
			->filter(function (QueryBuilder $builder) {

				$query = fStr(Arr::get($this->request->get('search', []), 'value'));

				if ($query) {

					$builder->orWhereHas('user', function (QueryBuilder $builder) use ($query) {
						$builder->whereRaw(sprintf("%s LIKE '%s' COLLATE utf8mb4_general_ci",
							get_table_column_name($builder->getModel(), 'name'),
							"%$query%"
						));
					});
				}

			}, true)
			->setTransformer(new OrderTransformer())
			->make(true);
	}

	/**
	 * @param Order $order
	 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
	 */
	public function downloadPdf(Order $order) {

		$type = Arr::first($this->request->route()->parameterNames());
		$pdf = $this->order->setCriteria(new OrderId($order->internal_id))->generatePdf($this->model, $type);

		return response($pdf, 200, [
				'Content-Type' => 'application/pdf',
				'Content-Disposition' => 'attachment; filename="' . $order->internal_id . '.pdf"'
			]
		);
	}

	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	protected function verifyModelRelation(Request $request, Closure $next) {

		$param = $request->route()->parameter($this->model_route_identifier);
		$this->order->setCriteria(array_merge($this->model->getOrdersCriteria(), [
			new OrderId($param->internal_id)
		]));

		if (!$this->order->first()) {
			throw new ModelNotFoundException(get_class($param), $param->id);
		}

		return $next($request);
	}
}