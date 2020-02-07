<?php

namespace App\Console\Commands\PayEx\Payment;

use ErrorException;
use Illuminate\Console\Command;
use App\Acme\Repositories\Criteria\Status;
use App\Acme\Repositories\Interfaces\OrderItemInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Capture extends Command {

    /**
     * @var string
     */
    protected $signature = 'payex:payment-capture'
    . ' {id : OrderItem Id}'
    . ' {--force}';

    /**
     * @var string
     */
    protected $description = 'Capture pre-authorized funds for order item.';

    /**
     * @var OrderItemInterface
     */
    protected $orderItem;

    public function __construct(OrderItemInterface $orderItem) {
        parent::__construct();

        $this->orderItem = $orderItem;
    }

    /**
     * @return mixed
     */
    public function handle() {

        try {

            $item = $this->orderItem;
            if (!$this->option('force')) {
                $item = $item->setCriteria(new Status('accepted'));
            }

            if ($this->orderItem->capture($item->findOrFail($this->argument('id')))) {
                return 0;
            }
        } catch (ModelNotFoundException $e) {
            return 1;
        } catch (ErrorException $e) {
            return 1;
        }

        return 1;
    }
}
