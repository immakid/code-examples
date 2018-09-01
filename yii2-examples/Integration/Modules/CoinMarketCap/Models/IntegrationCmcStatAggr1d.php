<?php


namespace App\Modules\Integration\Modules\CoinMarketCap\Models;

use Minexsystems\Satoshi\Behaviors\SatoshiBehavior;
use Minexsystems\Satoshi\Validators\SatoshiValidator;
use App\Modules\Integration\Modules\CoinMarketCap\Models\Query\IntegrationCmcStatAggr1dQuery;

/**
 * @property \App\Components\Satoshi\Satoshi $priceUsd
 * @property \App\Components\Satoshi\Satoshi $priceBtc
 */
class IntegrationCmcStatAggr1d extends \App\Modules\Database\IntegrationCmcStatAggr1d
{
    
    public function behaviors(): array
    {
        return [
            [
                'class' => SatoshiBehavior::class,
                'fields' => [
                    'priceUsd', 'priceBtc', 'priceEth'
                ]
            ]
        ];
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'priceUsd',
                    'priceBtc',
                    'priceEth',
                    'createdAt'
                ],
                'required'
            ],
            [
                [
                    'priceUsd',
                    'priceBtc',
                    'priceEth',
                ],
                SatoshiValidator::class
            ],
            [
                ['createdAt'], 'datetime'
            ],
        ];
    }
    
    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function beforeValidate()
    {
        $this->createdAt = \Yii::$app->formatter->asDatetime($this->createdAt);
        return parent::beforeValidate(); // TODO: Change the autogenerated stub
    }
    
    
    /**
     * @return IntegrationCmcStatAggr1dQuery
     */
    public static function find(): IntegrationCmcStatAggr1dQuery
    {
        return new IntegrationCmcStatAggr1dQuery(get_called_class());
    }
}