<?php

namespace App\Acme\Interfaces;

interface PaymentProviderInterface {

    /**
     * @return mixed
     */
    public function getPaymentUrl();

    /**
     * @param string $reference
     * @return mixed
     */
    public function authorizeTransaction($reference);

    /**
     * @param string $transaction_id
     * @return mixed
     */
    public function captureTransaction($transaction_id);

    /**
     * @param string $transaction_id
     * @return mixed
     */
    public function issueRefund($transaction_id);

    /**
     * @param string $method
     * @return mixed
     */
    public function setPaymentMethod($method);

    /**
     * @param float $total
     * @return $this
     */
    public function setTotal($total);

    /**
     * @param float $total
     * @return $this
     */
    public function setTotalVat($total);

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);

    /**
     * @param string $id
     * @return $this
     */
    public function setOrderId($id);

    /**
     * @param string $url
     * @return $this
     */
    public function setCancelUrl($url);

    /**
     * @param string $url
     * @return $this
     */
    public function setReturnUrl($url);

    /**
     * @param $description
     * @return mixed
     */
    public function setDescription($description);

    /**
     * @param mixed $order
     */
    public function setOrder($order);

    /**
     * @param mixed $shipping
     */
    public function setShipping($shipping);
}