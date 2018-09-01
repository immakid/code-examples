<?php
/**
 * @date: 07.03.18 - 12:31
 */
declare(strict_types=1);

namespace App\Modules\Integration\Modules\CoinMarketCap\Components\Aggregator;

use App\Components\BaseModel;
use App\Modules\Integration\Components\IntegrationFactory;

use App\Modules\Integration\Modules\CoinMarketCap\Exceptions\AgregationErrorException;
use yii\base\Component;
use App\Helpers\DateTime;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * Class AbstractAggregator
 * @package App\Modules\Integration\Modules\CoinMarketCap\Components\Agregator
 */
abstract class AbstractAggregator extends Component
{
    /**
     * @var \App\Modules\Integration\Modules\CoinMarketCap\Models\IntegrationCoinmarketcapStatistic
     */
    protected $statistic;
    
    /**
     * @var DateTime
     */
    protected $currentDate;
    
    /**
     * Если в аггрегационной таблице нет данных, стартовая дата - начало исходной таблицы
     * @var bool
     */
    protected $isStartFromBeginning = false;
    
    /**
     *
     */
    public function init(): void
    {
        $this->currentDate = new DateTime();
        $this->statistic = IntegrationFactory::getClass(IntegrationFactory::COIN_MARKET_CUP_STATISTIC);
    }
    
    /**
     * @return string|\yii\db\ActiveRecord
     */
    abstract protected function getAggregatedModel(): string;
    
    abstract public function run(): bool;
    
    /**
     * Проверить "полноту" данных в исходной таблице
     *
     * @param int $rowsCount
     * @return bool
     */
    abstract protected function checkFullPackage(int $rowsCount): bool;
    
    
    /**
     * @return int
     */
    abstract public function getPeriod():int;
    
    
    /**
     * @return null|ActiveRecord
     */
    protected function getFirstStatistic(): ?ActiveRecord
    {
        return $this->statistic::find()->orderBy(['id' => SORT_ASC])->one();
    }
    
    /**
     * Получить последнюю модель из агрегационной таблицы
     *
     * @return null|ActiveRecord
     */
    protected function getLastAggregatedModel(): ?ActiveRecord
    {
        return $this->getAggregatedModel()::find()->orderBy(['id' => SORT_DESC])->one();
    }
    
    /**
     * Получить стартовую дату, с которой начинается процесс аггрегации
     * @return DateTime|null
     */
    protected function getStartDate(): ?DateTime
    {
        $startDate = null;
        
        $lastAggregatorModel = $this->getLastAggregatedModel();
        
        if (is_null($lastAggregatorModel))
        {
            // Start from the very beginning
            $firstStatistic = $this->getFirstStatistic();
            
            if (!is_null($firstStatistic)) {
    
                // запоминаем, что начали именнно с начала исходной таблицы и отнимаем 1 секунду
                $this->isStartFromBeginning = true;
                
                $startDate = new DateTime($firstStatistic->createdAt);
                $startDate->modify("-1 second");
                
                
                \Yii::info(sprintf('Start date from the very beginning %s',
                    $startDate
                ), __CLASS__);
            } else {
                \Yii::error('Can not find data to aggregate', __CLASS__);
            }
            
        } else {
            // Get last exists aggregator date
            $startDate = new DateTime($lastAggregatorModel->createdAt);
            
            \Yii::info(sprintf('Start date from last aggregate %s',
                $startDate
            ), __CLASS__);
        }
        
        return $startDate;
    }
    
    /**
     * @param DateTime $dateTime
     * @return bool
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function startAggregation(DateTime $startDate):bool
    {
        $aggregateModel = null;
        $intervals = $this->getTimeIntervalsCount($startDate);
        
        
        \Yii::info(sprintf('Found %s intervals from %s', $intervals, $startDate), __CLASS__);
        
        for($i = 0; $i < $intervals; $i++) {
            
            $endDate = $this->getNextDate($startDate);
            
            \Yii::trace(sprintf('Processing period: %s %s', $startDate, $endDate)
                , __CLASS__);
            
            $periodModelsCount = $this->getPeriodModelsCount($startDate, $endDate);
            
            $this->notifyIfNotFullPackageData($periodModelsCount, $startDate, $endDate);
            
            
            if ($periodModelsCount) {
                $aggregateModel = $this->buildAggregatedModel($startDate, $endDate);
            }
            
            
            $this->saveData($periodModelsCount, $aggregateModel);
            
            $startDate = $endDate;
        }
        
        return $intervals > 0;
    }
    
    
    /**
     * Записать след. аггрегационное значение
     *
     * @param int $periodModelsCount
     * @param BaseModel|null $aggregateModel
     * @throws AgregationErrorException
     * @throws \yii\base\InvalidConfigException
     */
    protected function saveData(int $periodModelsCount, ?BaseModel $aggregateModel): void
    {
        if ($periodModelsCount > 0) {
            
            $saved = $aggregateModel->save();
            
            if (!$saved) {
                \Yii::warning('Error to save data to database:'
                    . Json::encode($aggregateModel->getErrors()));
                
                throw new AgregationErrorException('Period '. $this->getPeriod(), Json::encode($aggregateModel->getErrors()));
            }
        
        } else {
    
            \Yii::info(sprintf('Trying repeat last aggregated model'), __CLASS__);
            
            $this->repeatLastAggregatedModel();
        }
    }
    
    /**
     * Пишем в лог варнинг если мало данных в исходной таблице за период
     *
     * @param int $periodModelsCount
     * @param DateTime $startDate
     * @param DateTime $endDate
     */
    protected function notifyIfNotFullPackageData(int $periodModelsCount, DateTime $startDate, DateTime $endDate): void
    {
        if (!$this->checkFullPackage($periodModelsCount)) {
            \Yii::warning(sprintf('Not enought data for %s - %s in period %d minutes',
                $startDate, $endDate, $this->getPeriod()), __CLASS__);
        }
    }
    
    /**
     * Получит кол-во временных интервалов для аггрегации
     *
     * @param DateTime $startDate
     * @return int
     * @throws \Exception
     */
    private function getTimeIntervalsCount(DateTime $startDate): int
    {
        $intervals = 0;
        $minutes = ($this->currentDate->getTimestamp() - $startDate->getTimestamp()) / 60;
        
        if ($minutes > 0) {
            $intervals = (int)floor($minutes / $this->getPeriod());
        }
        
        return $intervals;
    }
    
    /**
     *
     * @param DateTime $startDate
     * @return DateTime
     */
    protected function getNextDate(DateTime $startDate): DateTime
    {
        $endDate = clone $startDate;
        
        // добавляем 1 секунду, если отнимали в начале
        if ($this->isStartFromBeginning) {
            $endDate->modify("+1 second");
            $this->isStartFromBeginning = false;
        }
        
        $endDate->modify('+'.$this->getPeriod().' minutes');
        return $endDate;
    }
    
    /**
     * Получить кол-во строк из исходной таблицы с которой строиться агрегация
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return int
     * @throws \yii\base\InvalidConfigException
     */
    protected function getPeriodModelsCount(DateTime $startDate, DateTime $endDate): int
    {
        $periodModelsCount = $this->statistic::find()
            ->byPeriod($startDate, $endDate)
            ->count();
        
        return (int)$periodModelsCount;
    }
    
    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function buildAggregatedModel(DateTime $startDate, DateTime $endDate): BaseModel
    {
        $stats = $this->statistic::find()
            ->byPeriod($startDate, $endDate)
            ->avgPrices()
            ->one();
        
        $aggregatedModelClass = $this->getAggregatedModel();
        $aggregateModel = new $aggregatedModelClass();
    
        $aggregateModel->priceUsd = $stats->priceUsd;
        $aggregateModel->priceBtc = $stats->priceBtc;
        $aggregateModel->priceEth = $stats->priceEth;
        $aggregateModel->createdAt = $endDate;
        
        return $aggregateModel;
    }
    
    
    /**
     * Добавить в агрегационную таблицу пред. значение
     *
     * @return bool
     * @throws AgregationErrorException
     * @throws \yii\base\InvalidConfigException
     */
    protected function repeatLastAggregatedModel(): bool
    {
        $saved = false;
    
        $lastAggregatorModel = $this->getLastAggregatedModel();
        
        if (!is_null($lastAggregatorModel)) {
            
            $aggregatedModelClass = $this->getAggregatedModel();
            $nextStatistic = new $aggregatedModelClass();
            $nextStatistic->attributes = $lastAggregatorModel->attributes;
            
            \Yii::info(sprintf('Repeat last aggregated model: '
                . Json::encode($nextStatistic->attributes)), __CLASS__);
            
            
            $nextDate = $this->getNextDate(new DateTime($lastAggregatorModel->createdAt));
            $nextStatistic->createdAt = \Yii::$app->formatter->asDatetime($nextDate);
            $saved = $nextStatistic->save();
            
            
            if (!$saved) {
                \Yii::warning('Error to save data to database:'
                    . Json::encode($nextStatistic->getErrors()));
                
                throw new AgregationErrorException('Period '. $this->getPeriod(). ' '. Json::encode($nextStatistic->getErrors()));
            }
            
        } else {
            
            \Yii::info('Cant repeat last aggregated model');
            
        }
        
        return $saved;
    }
    
}