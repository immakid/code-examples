<?php

namespace App\Acme\Libraries\Payment\Providers;

use App\Models\Orders\Order;
use App\Models\PayexTransactions;
use SoapClient;
use SimpleXMLElement;
use Illuminate\Support\Arr;
use App\Acme\Libraries\Payment\PaymentProvider;
use App\Acme\Libraries\Payment\Exceptions\PaymentProviderResponseException;

class Payex extends PaymentProvider {

	/**
	 * - transactionStatus:
	 *      1 - Initialize
	 *      2 - Credit (refund issued)
	 *      3 - Authorize (payment successful)
	 *      4 - Cancel
	 *      5 - Failure
	 *      6 - Capture (funds collected)
	 */

	/**
	 * @var SoapClient
	 */
	protected $soap;

	/**
	 * @var int
	 */
	protected $account_id;

	/**
	 * @var string
	 */
	protected $encryption_key;

	/**
	 * @var array
	 */
	protected static $acceptedPaymentMethods = [
		'creditCard',
		'masterPass'
	];

	public function __construct($id, $key, $wsdl) {

		$this->account_id = $id;
		$this->encryption_key = $key;
		$this->soap = new SoapClient($wsdl, ['trace' => true]);
	}

	/**
	 * @return SimpleXMLElement[]
	 */
	public function getPaymentUrl() {

		$price = ($this->total + $this->totalShipping);
		$vat = $this->calculateVatPercent($price);

		$data = [
			'purchaseOperation' => 'AUTHORIZATION',
			'price' => $this->formatPrice($price * 100),
			'priceArgList' => '',
			'currency' => strtoupper($this->currency),
			'vat' => $this->formatPrice($this->formatPrice($vat) * 100),
			'orderID' => $this->orderId,
			'productNumber' => '1',
			'description' => $this->description,
			'clientIPAddress' => app('request')->getClientIp(),
			'clientIdentifier' => 'USERAGENT=' . app('request')->userAgent(),
			'additionalValues' => 'RESPONSIVE=1',
			'externalID' => '',
			'returnUrl' => $this->returnUrl,
			'view' => 'CREDITCARD',
			'agreementRef' => '',
			'cancelUrl' => $this->cancelUrl,
			'clientLanguage' => '',
		];

		if ($this->paymentMethod === 'masterPass') {
			$data['additionalValues'] = 'RESPONSIVE=1&USEMASTERPASS=1&SHIPPINGLOCATIONPROFILE=1';
		}

		$response = $this->execSoapCall('Initialize8', $data);

        //Set Single Order items
        $productCount = 0;
        foreach ($this->order->items as $item) {
            $productCount ++;
            $totalDiscount = 0;
            $totalVatDiscount = 0;
            foreach ($item->prices as $price) {
                if($price->label == 'total-discounted') {
                    $totalDiscount = $price->value;
                }elseif($price->label == 'vat-discounted') {
                    $totalVatDiscount = $price->value;
                }
            }
            $this->addOrderLine($response->orderRef, $totalDiscount, $totalVatDiscount,
                $item->product->translate('name'), $productCount, $item->quantity);
        }

        //Set Single Shipping items
        foreach($this->order->shippingOptions as $option) {
            $storeId = $option->store_id;
            $store = \App\Models\Stores\Store::find($storeId);
            $prices = $this->order->with('prices')->find($this->order->id)->prices;
            foreach ($prices as $price) {
                if ($price->label == 'shipping-store-' . $storeId && $price->value > 0) {
                    $this->addOrderLine($response->orderRef, $price->value, 0, __t('labels.shipping') . '-' . $store->name, 2);
                }
            }
        }

		return $response->redirectUrl;
	}

	/**
	 * @param string $reference
	 * @return array|bool
	 */
	public function authorizeTransaction($reference) {

		switch ($this->paymentMethod) {
			case 'masterPass':

                //Best Practise was not used in the initial implementation
                //FinalizeTransaction supports only best practise
                //http://www.payexpim.com/payment-methods/masterpass/

                /*$response = $this->execSoapCall('FinalizeTransaction', [
                    'orderRef' => $reference,
                    'amount' => 0, 'vatAmount' => 0,
                    'clientIPAddress' => app('request')->getClientIp(),
                ]);*/

                $response = $this->execSoapCall('Complete', ['orderRef' => $reference]);
                break;
			default:
				$response = $this->execSoapCall('Complete', ['orderRef' => $reference]);
		}

		if ((int)$response->transactionStatus === 3) {
			return [$response->orderId, $response->transactionNumber];
		}

		return false;
	}

	/**
	 * @param string $transaction_id
	 * @return bool
	 */
	public function captureTransaction($transaction_id) {

		$parameters = [
			'transactionNumber' => $transaction_id,
			'amount' => $this->formatPrice(($this->total + $this->totalShipping) * 100),
			'orderId' => $this->orderId,
			'vatAmount' => $this->formatPrice($this->totalVat * 100),
			'additionalValues' => ''
		];
		$response = $this->execSoapCall('Capture5', $parameters);

		if ((int)$response->transactionStatus === 6) {
			PayexTransactions::insert(array_merge(['method' => 'Capture5'],$parameters));
			return true;
		}

		return false;
	}

	/**
	 * @param string $transaction_id
	 * @return bool
	 */
	public function issueRefund($transaction_id) {

		$parameters = [
			'transactionNumber' => $transaction_id,
			'amount' => $this->formatPrice($this->total * 100),
			'orderId' => $this->orderId,
			'vatAmount' => $this->formatPrice($this->totalVat * 100),
			'additionalValues' => ''
		];
		$response = $this->execSoapCall('Credit5', $parameters);

		if ((int)$response->transactionStatus === 2) {
			PayexTransactions::insert(array_merge(['method' => 'Credit5'],$parameters));
			return true;
		}

		return false;
	}

	/**
	 * @param string $reference
	 * @param float $amount
	 * @param float $vat
	 * @param string $label
	 * @param int $num
     * @param int $qty
	 */
	private function addOrderLine($reference, $amount, $vat, $label, $num = 1, $qty = 1) {

		$this->execSoapCall('AddSingleOrderLine2', [
			'orderRef' => $reference,
			'itemNumber' => $num,
			'itemDescription1' => $label,
			'itemDescription2' => '',
			'itemDescription3' => '',
			'itemDescription4' => '',
			'itemDescription5' => '',
			'quantity' => $qty,
			'amount' => $this->formatPrice($amount * 100),
			'vatPrice' => $this->formatPrice($vat * 100),
			'vatPercent' => $this->formatPrice($this->formatPrice(100 * ($vat / $amount)) * 100),
		]);
	}

	/**
	 * @param float $price
	 * @return float|int
	 */
	private function calculateVatPercent($price) {

		$vatPercent = 100 * ($this->totalVat / (($this->total + $this->totalShipping) - $this->totalVat)); // actual vat percent (based on total order amount - shipping)

        $vatAmountPrice = $price - ($price / (1 + ($vatPercent / 100)));

		//$vatAmountPrice = ($vatPercent / 100) * ($price - $this->totalVat); // vat value based on grand total (including shipping)
		$vatAmountDiff = $vatAmountPrice - $this->totalVat; // value difference between vat on grand total and vat on total (without shipping)
		//$vat = $vatPercent - (100 * ($vatAmountDiff / ($price - $this->totalVat))); // corrected percentage value
        $vat = ($vatAmountPrice / ($price - $vatAmountPrice)) * 100;

		return $vat;
	}

	/**
	 * @param float|int $value
	 * @return string
	 */
	private function formatPrice($value) {
		return (string)((float)number_format($value, 2, '.', ''));
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	private function createHash(array $parameters) {

		$values = implode('', array_values($parameters));

		return md5($values . $this->encryption_key);
	}

	/**
	 * @return array
	 */
	protected function getSupportedPaymentMethods() {
		return self::$acceptedPaymentMethods;
	}

	/**
	 * @param string $method
	 * @param array $parameters
	 * @return SimpleXMLElement
	 */
	private function execSoapCall($method, array $parameters) {

		$parameters = Arr::prepend($parameters, $this->account_id, 'accountNumber');
		Arr::set($parameters, 'hash', $this->createHash($parameters));

		$response = call_user_func([$this->soap, $method], $parameters);
		$result = json_decode(json_encode(new SimpleXMLElement($response->{sprintf("%sResult", $method)})));

		$status = $result->status;
		if ($status->errorCode !== 'OK') {

			throw new PaymentProviderResponseException(
				$status->description,
				$status->code,
				'payex',
				$parameters,
				(array)$result
			);
		}

		return $result;
	}

	/**
	 * @param string $type
	 * @param bool $headers
	 */
//    private function debugLastSoapCall($type = 'request', $headers = false) {
//
//        $items = [
//            'request' => [
//                'headers' => nl2br($this->soap->__GetLastRequestHeaders()),
//                'body' => $this->soap->__getLastRequest()
//            ],
//            'response' => [
//                'headers' => nl2br($this->soap->__getLastResponseHeaders()),
//                'body' => $this->soap->__getLastResponse()
//            ]
//        ];
//
//        if (!$data = Arr::get($items, $type)) {
//            exit("Invalid type $type");
//        }
//
//        if ($headers) {
//            exit($data['headers']);
//        }
//
//        header("Content-Type: text/xml");
//        exit($data['body']);
//    }

}