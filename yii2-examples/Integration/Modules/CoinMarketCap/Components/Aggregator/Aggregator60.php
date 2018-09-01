<?php
/**
 * @date: 07.03.18 - 12:33
 */

declare(strict_types=1);

namespace App\Modules\Integration\Modules\CoinMarketCap\Components\Aggregator;
use App\Helpers\Date;
use App\Modules\Integration\Components\IntegrationFactory;
use App\Helpers\DateTime;

/**
 * Class Aggregator30
 * @package App\Modules\Integration\Modules\CoinMarketCap\Components\Agregator
 */
class Aggregator60 extends AbstractAggregator
{
    
    private const PERIOD = 60;
    
    private const FULL_PACKAGE_COUNT = 6;
    
    /**
     * @return bool
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function run():bool
    {
        $dateTime = $this->getStartDate();
        if(is_null($dateTime)) {
            return false;
        }
    
        return $this->startAggregation($dateTime);
    }
    
    /**
     * @param int $rowsCount
     * @return bool
     */
    protected function checkFullPackage(int $rowsCount): bool
    {
        return $rowsCount <= self::FULL_PACKAGE_COUNT;
    }
    
    /**
     * @return string
     */
    protected function getAggregatedModel(): string
    {
        return IntegrationFactory::getClass(IntegrationFactory::COIN_MCS_AGGREGATION60);
    }
    
    /**
     * 60 minutes
     * @return int
     */
    public function getPeriod(): int
    {
        return self::PERIOD;
    }
}