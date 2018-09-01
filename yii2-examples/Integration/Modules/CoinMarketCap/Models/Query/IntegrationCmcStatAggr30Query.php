<?php
/**
 * @author Silchenko Vlad <v.silchenko@minexsystems.com>
 * @date: 15.03.18 - 12:37
 */


namespace App\Modules\Integration\Modules\CoinMarketCap\Models\Query;

use yii\db\ActiveQuery;

class IntegrationCmcStatAggr30Query extends ActiveQuery
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
}