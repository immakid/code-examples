<?php
/**
 * @author Silchenko Vlad <v.silchenko@minexsystems.com>
 * @date: 20.03.18 - 18:51
 */

declare(strict_types=1);

namespace App\Modules\Integration\Modules\CoinMarketCap\Commands;

use App\Modules\Integration\Components\IntegrationFactory;
use Curl\Curl;
use Minexsystems\Satoshi\SatoshiConverter;
use yii\console\Controller;
use yii\helpers\Json;

class HistoryParserController extends Controller
{
    /**
     *
     */
    private const URL = 'https://min-api.cryptocompare.com/data/pricehistorical?fsym=MNX&tsyms=BTC,USD,ETH&ts=';
    
    /**
     *
     */
    private const START_DATE = '2017-11-02 00:00:00';
    
    
    /**
     * @throws \ErrorException
     */
    public function actionRun(): void
    {
        $loopDate = new \DateTime(self::START_DATE);
        $curDate = new \DateTime();
        
        
        while ($curDate->diff($loopDate)->days > 0 )
        {
            $data = (new Curl())->get(self::URL. $loopDate->getTimestamp(), []);
            
            
            if (isset($data->MNX)) {
                $this->saveModelStatistic($loopDate, (string)$data->MNX->ETH, (string)$data->MNX->BTC, (string)$data->MNX->USD);
                
                \Yii::info('Save data for date:'. $loopDate->format('Y-m-d H:i:s'));
                
            } else {
                
                \Yii::warning('Cant find data for date:'. $loopDate->format('Y-m-d H:i:s'));
                
            }
            
            
            $loopDate->modify('+1 day');
            usleep(500);
        }
    }
    
    /**
     * @param \DateTime $Date
     * @param string $btcPrice
     * @param string $usdPrice
     */
    protected function saveModelStatistic(\DateTime $date, string $ethPrice, string $btcPrice, string $usdPrice)
    {
        /** @var  $cmp */
        $cmp = IntegrationFactory::getClass(IntegrationFactory::COIN_MCS_AGGREGATION1D);
        $cmp = new $cmp();
        
        $cmp->priceUsd = SatoshiConverter::toSatoshi($btcPrice);
        $cmp->priceBtc = SatoshiConverter::toSatoshi($usdPrice);
        $cmp->priceEth = SatoshiConverter::toSatoshi($ethPrice);
        $cmp->createdAt = $date->format('Y-m-d H:i:s');
        
        
        if(!$cmp->save())
        {
            throw new \Exception('Cant save model:'. Json::encode($cmp->getErrors()));
        }
    }
}