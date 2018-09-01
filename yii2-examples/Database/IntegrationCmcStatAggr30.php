<?php

namespace App\Modules\Database;

use Yii;

/**
 * This is the model class for table "{{%integration_cmc_stat_aggr30}}".
 *
 * @property integer $id
 * @property string $priceUsd
 * @property string $priceBtc
 * @property string $createdAt
 * @property string $priceEth
 */
class IntegrationCmcStatAggr30 extends \App\Components\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%integration_cmc_stat_aggr30}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['priceUsd', 'priceBtc', 'createdAt', 'priceEth'], 'required'],
            [['priceUsd', 'priceBtc', 'priceEth'], 'integer'],
            [['createdAt'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'priceUsd' => Yii::t('app', 'Price Usd'),
            'priceBtc' => Yii::t('app', 'Price Btc'),
            'createdAt' => Yii::t('app', 'Created At'),
            'priceEth' => Yii::t('app', 'Price Eth'),
        ];
    }
}
