<?php
/**
 * @author Andru Cherny <acherny@minexsystems.com>
 * @date: 28.12.17 - 11:46
 */


namespace App\Modules\Integration\Modules\CoinMarketCap\Commands;

use App\Modules\Integration\Components\IntegrationFactory;
use App\Modules\Integration\Modules\CoinMarketCap\Components\CoinMarketCapApi;
use yii\base\UserException;
use yii\console\Controller;
use yii\helpers\Json;

class ParserController extends Controller
{
    
    /**
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRun()
    {
        $data = CoinMarketCapApi::getTicker();
        /** @var \App\Modules\Integration\Modules\CoinMarketCap\Models\IntegrationCoinmarketcapStatistic $cmp */
        $cmp = IntegrationFactory::getClass(IntegrationFactory::COIN_MARKET_CUP_STATISTIC);


        $res = $cmp::find()->where(['lastUpdated' => \Yii::$app->formatter->asDatetime($data->lastUpdated)])->one();
        
        if(!$res) {
            $cmp = new $cmp();
            $cmp->setAttributes($data->getAttributes());
            if(!$cmp->save()) {
                throw new UserException('Error to save data to database:' . Json::encode($cmp->getErrors()));
            }
            \Yii::info('Data saved into database');
        } else {
            \Yii::info('This row is already exist in database');
        }

    }

}