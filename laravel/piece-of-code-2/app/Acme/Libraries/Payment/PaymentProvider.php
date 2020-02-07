<?php

namespace App\Acme\Libraries\Payment;

use App\Acme\Libraries\Payment\Exceptions\UnsupportedPaymentMethodException;
use Illuminate\Support\Arr;
use App\Acme\Interfaces\PaymentProviderInterface;

abstract class PaymentProvider implements PaymentProviderInterface {

    /**
     * @var array
     */
    private $data = [
        'total' => 0,
        'totalVat' => 0,
        'totalShipping' => 0,
        'currency' => null,
        'orderId' => null,
        'returnUrl' => null,
        'cancelUrl' => null,
        'paymentMethod' => 'creditCard',
        'description' => 'order description',
        'order' => null,
        'shipping' => null
    ];

    /**
     * @return mixed
     */
    public abstract function getPaymentUrl();

    /**
     * @param string $reference
     * @return mixed
     */
    public abstract function authorizeTransaction($reference);

    /**
     * @param string $transaction_id
     * @return mixed
     */
    public abstract function captureTransaction($transaction_id);

    /**
     * @return array
     */
    protected abstract function getSupportedPaymentMethods();

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {

        if (Arr::get($this->data, $name, false) !== false) {
            $this->data[$name] = $value;
        }
    }

    /**
     * @param string $name
     * @return bool|mixed
     */
    public function __get($name) {

        if (!$value = Arr::get($this->data, $name)) {
            return false;
        }

        return $value;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setPaymentMethod($method) {

        if(!in_array($method, $this->getSupportedPaymentMethods())) {
            throw new UnsupportedPaymentMethodException($method);
        }

        $this->paymentMethod = $method;

        return $this;
    }

    /**
     * @param float $total
     * @return $this
     */
    public function setTotal($total) {

        $this->total = $total;

        return $this;
    }

    /**
     * @param float $total
     * @return $this
     */
    public function setTotalVat($total) {

        $this->totalVat = $total;

        return $this;
    }

    public function setTotalShipping($total) {

        $this->totalShipping = $total;

        return $this;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency) {

        $this->currency = $currency;

        return $this;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setOrderId($id) {

        $this->orderId = $id;

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setReturnUrl($url) {

        $this->returnUrl = $url;

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setCancelUrl($url) {

        $this->cancelUrl = $url;

        return $this;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description) {

        $this->description = $description;

        return $this;
    }

    public function setOrder($order) {

        $this->order = $order;

        return $this;
    }

    public function setShipping($shipping) {

        $this->shipping = $shipping;

        return $this;
    }
}