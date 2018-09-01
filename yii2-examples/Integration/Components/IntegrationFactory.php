<?php
/**
 * @author Andru Cherny <acherny@minexsystems.com>
 * @date: 28.12.17 - 14:28
 */
namespace App\Modules\Integration\Components;

use App\Components\BaseModelFactory;
use App\Modules\Integration\Modules\CoinMarketCap\Models\IntegrationCmcStatAggr1d;
use App\Modules\Integration\Modules\CoinMarketCap\Models\IntegrationCmcStatAggr30;
use App\Modules\Integration\Modules\CoinMarketCap\Models\IntegrationCmcStatAggr60;
use App\Modules\Integration\Modules\CoinMarketCap\Models\IntegrationCoinmarketcapStatistic;

/**
 * Class IntegrationFactory
 * @package App\Modules\Integration\Components
 */
class IntegrationFactory extends BaseModelFactory
{

    /**
     *
     */
    public const COIN_MARKET_CUP_STATISTIC = 'coinmarketcap';
    public const COIN_MCS_AGGREGATION30 = 'coinmarketcap_aggregation_30';
    public const COIN_MCS_AGGREGATION60 = 'coinmarketcap_aggregation_60';
    public const COIN_MCS_AGGREGATION1D = 'coinmarketcap_aggregation_1d';

    
    /**
     * Method which populates @var $models
     */
    protected static function populateModels(): void
    {
        static::$models = [
            static::COIN_MARKET_CUP_STATISTIC => IntegrationCoinmarketcapStatistic::class,
            static::COIN_MCS_AGGREGATION30 => IntegrationCmcStatAggr30::class,
            static::COIN_MCS_AGGREGATION60 => IntegrationCmcStatAggr60::class,
            static::COIN_MCS_AGGREGATION1D => IntegrationCmcStatAggr1d::class
        ];
    }
}