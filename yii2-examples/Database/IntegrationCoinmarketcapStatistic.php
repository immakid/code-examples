<?php

namespace App\Modules\Database;

use Yii;

/**
 * This is the model class for table "{{%integration_coinmarketcap_statistic}}".
 *
 * @property integer $id
 * @property integer $rank
 * @property string $priceUsd
 * @property string $priceBtc
 * @property string $dayVolumeUsd
 * @property string $marketCapUsd
 * @property string $availableSupply
 * @property string $totalSupply
 * @property string $maxSupply
 * @property integer $percentChangeHour
 * @property integer $percentChangeDay
 * @property integer $percentChangeWeek
 * @property string $lastUpdated
 * @property string $priceEth
 * @property string $createdAt
 */
class IntegrationCoinmarketcapStatistic extends \App\Components\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%integration_coinmarketcap_statistic}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['rank', 'priceUsd', 'priceBtc', 'dayVolumeUsd', 'marketCapUsd', 'availableSupply', 'totalSupply', 'maxSupply', 'percentChangeHour', 'percentChangeDay', 'percentChangeWeek', 'lastUpdated', 'priceEth', 'createdAt'], 'required'],
            [['rank', 'priceUsd', 'priceBtc', 'dayVolumeUsd', 'marketCapUsd', 'availableSupply', 'totalSupply', 'maxSupply', 'percentChangeHour', 'percentChangeDay', 'percentChangeWeek', 'priceEth'], 'integer'],
            [['lastUpdated', 'createdAt'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'rank' => Yii::t('app', 'Rank'),
            'priceUsd' => Yii::t('app', 'Price Usd'),
            'priceBtc' => Yii::t('app', 'Price Btc'),
            'dayVolumeUsd' => Yii::t('app', 'Day Volume Usd'),
            'marketCapUsd' => Yii::t('app', 'Market Cap Usd'),
            'availableSupply' => Yii::t('app', 'Available Supply'),
            'totalSupply' => Yii::t('app', 'Total Supply'),
            'maxSupply' => Yii::t('app', 'Max Supply'),
            'percentChangeHour' => Yii::t('app', 'Percent Change Hour'),
            'percentChangeDay' => Yii::t('app', 'Percent Change Day'),
            'percentChangeWeek' => Yii::t('app', 'Percent Change Week'),
            'lastUpdated' => Yii::t('app', 'Last Updated'),
            'priceEth' => Yii::t('app', 'Price Eth'),
            'createdAt' => Yii::t('app', 'Created At'),
        ];
    }
}
