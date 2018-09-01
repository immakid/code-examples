<?php
namespace App\Modules\Integration\Modules\CoinMarketCap\Models;


use App\Components\Models\FieldsLoaderTrait;
use Minexsystems\Satoshi\Behaviors\FloatToSatoshiBehavior;
use yii\base\Model;

/**
 * Class TickerModel
 * @package App\Modules\Integration\Modules\CoinMarketCap\Models
 * @SuppressWarnings(PHPMD)
 */
class TickerModel extends Model
{
    use FieldsLoaderTrait;

    /**
     * @var
     */
    public $id;

    /**
     * @var
     */
    public $name;

    /**
     * @var
     */
    public $symbol;

    /**
     * @var
     */
    public $rank;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $priceUsd;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $priceBtc;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $priceEth;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $dayVolumeUsd;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $marketCapUsd;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $availableSupply;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $totalSupply;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $maxSupply;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $percentChangeHour;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $percentChangeDay;

    /**
     * @var \Minexsystems\Satoshi\Satoshi
     */
    public $percentChangeWeek;

    /**
     * @var
     */
    public $lastUpdated;
    
    /**
     * @var
     */
    public $createdAt;
    
    /**
     * @return array
     */
    public function behaviors(): array 
    {
        return [
            [
                'class' => FloatToSatoshiBehavior::class,
                'attributes' => [
                    'priceUsd', 'priceBtc', 'priceEth', 'dayVolumeUsd', 'marketCapUsd', 'availableSupply', 'totalSupply', 'maxSupply',
                    'percentChangeHour','percentChangeDay','percentChangeWeek'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function rules(): array 
    {
        return [
            [
                [
                    'id', 'name', 'symbol', 'rank', 'priceUsd', 'priceBtc', 'priceEth', 'dayVolumeUsd', 'marketCapUsd',
                    'availableSupply', 'totalSupply', 'percentChangeHour', 'percentChangeDay', 'percentChangeWeek',
                    'maxSupply', 'lastUpdated', 'rank', 'createdAt'
                ],
                'required'
            ],
            [['id','name','symbol'], 'string'],
            [['rank'], 'integer', 'min' => 1, 'max' => 1000],

            [['priceUsd','priceBtc','priceEth','dayVolumeUsd','marketCapUsd','availableSupply','totalSupply'], 'double'],
            [['percentChangeHour','percentChangeDay','percentChangeWeek','maxSupply'], 'double'],

            [['lastUpdated', 'rank', 'createdAt'], 'number'],
        ];
    }

    /**
     * @return array
     */
    public function fields(): array 
    {
        return [
            'priceUsd' => 'price_usd',
            'priceBtc' => 'price_btc',
            'priceEth' => 'price_eth',
            'dayVolumeUsd' => '24h_volume_usd',
            'marketCapUsd' => 'market_cap_usd',
            'availableSupply' => 'available_supply',
            'totalSupply' => 'total_supply',
            'maxSupply' => 'max_supply',
            'percentChangeHour' => 'percent_change_1h',
            'percentChangeDay' => 'percent_change_24h',
            'percentChangeWeek' => 'percent_change_7d',
            'lastUpdated' => 'last_updated'
        ];
    }
}