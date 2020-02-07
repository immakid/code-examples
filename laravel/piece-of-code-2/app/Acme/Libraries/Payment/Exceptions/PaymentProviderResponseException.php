<?php

namespace App\Acme\Libraries\Payment\Exceptions;

use ErrorException;
use Illuminate\Support\Arr;

class PaymentProviderResponseException extends ErrorException {

    /**
     * @var string
     */
    private $provider;

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var array
     */
    private $requestData = [];

    /**
     * @var array
     */
    private $responseData = [];

    /**
     * @return int|string
     */
    public function getProvider() {
        return $this->provider;
    }

    /**
     * @return int|string
     */
    public function getErrorCode() {
        return $this->errorCode;
    }

    /**
     * @param string|null $type
     * @return array|mixed
     */
    public function getData($type = null) {

        $data = [
            'request' => $this->requestData,
            'response' => $this->responseData
        ];

        return (!$type) ? $data : Arr::get($data, $type, $data);
    }

    public function __construct($message, $code, $provider, array $requestData = null, array $responseData = null) {
        parent::__construct($message, 0, null);

        $this->errorCode = $code;
        $this->provider = $provider;
        $this->requestData = $requestData;
        $this->responseData = $responseData;
    }
}