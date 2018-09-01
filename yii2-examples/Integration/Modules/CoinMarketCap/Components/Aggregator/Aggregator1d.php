<?php
/**
 * @author Silchenko Vlad <v.silchenko@minexsystems.com>
 * @date: 07.03.18 - 12:33
 */

declare(strict_types=1);

namespace App\Modules\Integration\Modules\CoinMarketCap\Components\Aggregator;

use App\Modules\Integration\Components\IntegrationFactory;
use App\Helpers\DateTime;

/**
 * Class Aggregator1d
 * @package App\Modules\Integration\Modules\CoinMarketCap\Components\Agregator
 */
class Aggregator1d extends AbstractAggregator
{
    
    private const PERIOD = 1440;
    
    private const FULL_PACKAGE_COUNT = 144;
    
    /**
     * @return bool
     * @throws UserException
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function run():bool
    {
        $dateTime = $this->getStartDate();
        if(is_null($dateTime)) {
            return false;
        }
        
        $this->startAggregation($dateTime);
        
        return true;
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
        return IntegrationFactory::getClass(IntegrationFactory::COIN_MCS_AGGREGATION1D);
    }
    
    /**
     * 30 minutes
     * @return int
     */
    public function getPeriod(): int
    {
        return self::PERIOD;
    }
}