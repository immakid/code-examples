<?php

namespace App\Console\Commands\PayEx\Payment;

use ErrorException;
use Illuminate\Console\Command;
use App\Acme\Repositories\Criteria\Status;
use App\Acme\Repositories\Interfaces\OrderItemInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Credit extends Command {

    /**
     * @var string
     */
    protected $signature = 'payex:payment-credit'
    . ' {id : OrderItem Id}'
    . ' {--force}';

    /**
     * @var string
     */
    protected $description = 'Refund captured funds for order item.';

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
            print_logs_app("Came to refund money");
            $item = $this->orderItem;
            if (!$this->option('force')) {
                $item = $item->setCriteria(new Status(['captured', 'shipped']));
            }

            if ($this->orderItem->credit($item->findOrFail($this->argument('id')))) {
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
