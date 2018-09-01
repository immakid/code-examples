<?php
/**
 * @author Silchenko Vlad <v.silchenko@minexsystems.com>
 * @date: 15.03.18 - 18:37
 */


namespace App\Modules\Integration\Modules\CoinMarketCap\Models\Query;


use yii\db\ActiveQuery;

class CoinmarketcapStatisticQuery extends ActiveQuery
{
    
    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return $this
     * @throws \yii\base\InvalidConfigException
     */
    public function byPeriod(\DateTime $startDate, \DateTime $endDate) : ActiveQuery
    {
        $this->andWhere(['>', 'createdAt', \Yii::$app->formatter->asDatetime($startDate)]);
        $this->andWhere(['<=', 'createdAt', \Yii::$app->formatter->asDatetime($endDate)]);
        return $this;
    }
    
    /**
     * @return ActiveQuery
     */
    public function avgPrices() : ActiveQuery
    {
        $this->select('FLOOR(AVG(priceBtc)) as priceBtc, FLOOR(AVG(priceUsd)) as priceUsd, FLOOR(AVG(priceEth)) as priceEth');
        return $this;
    }
    
    /**
     * @return ActiveQuery
     */
    public function minMaxBtc(): ActiveQuery
    {
        $this->select('min(priceBtc) as minPriceBtc, max(priceBtc) as maxPriceBtc');
        return $this;
    }
    
    
}