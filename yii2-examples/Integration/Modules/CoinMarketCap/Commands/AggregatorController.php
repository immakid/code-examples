<?php
/**
 * @date: 28.12.17 - 11:46
 */
declare(strict_types=1);

namespace App\Modules\Integration\Modules\CoinMarketCap\Commands;

use App\Modules\Integration\Modules\CoinMarketCap\Components\Aggregator\Aggregator1d;
use App\Modules\Integration\Modules\CoinMarketCap\Components\Aggregator\Aggregator30;
use App\Modules\Integration\Modules\CoinMarketCap\Components\Aggregator\Aggregator60;
use yii\base\UserException;
use yii\console\Controller;


class AggregatorController extends Controller
{
    
    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRun30(): bool
    {
        $aggregator = new Aggregator30();
        return $aggregator->run();
    }
    
    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRun60(): bool
    {
        $aggregator = new Aggregator60();
        return $aggregator->run();
        
    }
    
    /**
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRun1d(): bool
    {
        $aggregator = new Aggregator1d();
        return $aggregator->run();
    }
}