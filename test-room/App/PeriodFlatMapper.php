<?php


/**
 * Поддержание flat структуры периодов в бд
 * Class PeriodFlatMapper
 */
class PeriodFlatMapper
{
    protected $innerPeriodsStrategy;
    protected $outerPeriodsStrategy;
    protected $startDatePeriodsStrategy;
    protected $endDatePeriodsStrategy;

    public function __construct($InnerPeriodsStrategy, $OuterPeriodsStrategy,
                                $StartDatePeriodsStrategy, $EndDatePeriodsStrategy)
    {
        $this->innerPeriodsStrategy     = $InnerPeriodsStrategy;
        $this->outerPeriodsStrategy     = $OuterPeriodsStrategy;
        $this->startDatePeriodsStrategy = $StartDatePeriodsStrategy;
        $this->endDatePeriodsStrategy   = $EndDatePeriodsStrategy;
    }

    /**
     * @param Period $period
     * @return mixed
     */
    public function GetStrategyForPeriod(Period $period, $type)
    {
        $skipPeriods = [];

        if($type == 'edit')
        {
            $skipPeriods[] = $period->getId();
        }

        /**
         * Проверить чтобы новый период не "накрывал" полностью существующие
         */
        $innerPeriods = $this->getInnerPeriods($period, $skipPeriods);
        $compare = $this->comparePeriods($period, $innerPeriods);
        $check = $this->innerPeriodsStrategy->Generate($period, $compare);

        $skipPeriods = array_merge($skipPeriods, $compare['period_ids']);

        if($check['decline'])
        {
            return $check;
        }

        /**
         * Проверить чтобы новый период не "накрывался" существующими
         */
        $outerPeriods = $this->getOuterPeriods($period, $skipPeriods);
        $compare = $this->comparePeriods($period, $outerPeriods);
        $check2 = $this->outerPeriodsStrategy->Generate($period, $compare);
        $skipPeriods = array_merge($skipPeriods, $compare['period_ids']);

        $check = $this->mergeStrategy($check, $check2);


        if($check['decline'])
        {
            return $check;
        }

        /**
         * start date crossing another periods
         */
        $startPeriods = $this->getCrossingDatePeriods($period, 'start', $skipPeriods);
        $compare = $this->comparePeriods($period, $startPeriods);
        $check3 = $this->startDatePeriodsStrategy->Generate($period, $compare);
        $check = $this->mergeStrategy($check, $check3);

        if($check['decline'])
        {
            return $check;
        }

        /**
         * end date crossing another periods
         */
        $endPeriods = $this->getCrossingDatePeriods($period, 'end', $skipPeriods);
        $compare = $this->comparePeriods($period, $endPeriods);
        $check4 = $this->endDatePeriodsStrategy->Generate($period, $compare);
        $check = $this->mergeStrategy($check, $check4);

        $check['current'][] = [
            'period' => $period
        ];

        $check['action_type'] = $type;

        return $check;
    }

    /**
     * Слить стратегии в одну
     * @param $old
     * @param $new
     * @return array
     */
    protected function mergeStrategy($old, $new)
    {
        $current = array_merge_recursive($old, $new);
        $current['decline'] = $new['decline'];

        return $current;
    }


    /**
     * Получить
     *
     * Тек. период маленький, получить все большие что его накрывают
     *
     * @param Period $period
     * @return array|null
     */
    protected function getOuterPeriods(Period $period, array $skipPeriodIds)
    {
        $start = $period->getStartDate();
        $end = $period->getEndDate();

        if(count($skipPeriodIds))
        {
            db::where_not_in('id', $skipPeriodIds);
        }

        db::where('start_date <=', $start->format('Y-m-d H:i:s'));
        db::where('end_date >=', $end->format('Y-m-d H:i:s'));
        $rows = db::get('periods');

        $periods = [];
        foreach ($rows as $row) {
            $periods[] = PeriodFactory::fromRow($row);
        }

        return $periods;
    }

    /**
     * @param Period $period
     * @return array|null
     */
    protected function getInnerPeriods(Period $period, array $skipPeriodIds) : array
    {
        $start = $period->getStartDate();
        $end = $period->getEndDate();

        if(count($skipPeriodIds))
        {
            db::where_not_in('id', $skipPeriodIds);
        }

        db::where('start_date >=', $start->format('Y-m-d H:i:s'));
        db::where('end_date <=', $end->format('Y-m-d H:i:s'));
        $rows = db::get('periods');

        $periods = [];
        foreach ($rows as $row) {
            $periods[] = PeriodFactory::fromRow($row);
        }

        return $periods;
    }

    /**
     * @param Period $period
     * @param $dateType
     * @return array
     */
    protected function getCrossingDatePeriods(Period $period, $dateType, $skipPeriodIds)
    {
        $date = $dateType == 'start'
            ? $period->getStartDate()
            : $period->getEndDate();

        $dateFormat = $date->format('Y-m-d H:i:s');


        if(count($skipPeriodIds))
        {
            db::where_not_in('id', $skipPeriodIds);
        }

        db::where('start_date <=', $dateFormat);
        db::where('end_date >=', $dateFormat);
        $rows = db::get('periods');


        $periods = [];
        foreach ($rows as $row) {
            $periods[] = PeriodFactory::fromRow($row);
        }

        return $periods;
    }


    /**
     * Получить инфо по периодам
     * @param Period $MainPeriod
     * @param array $periods
     * @return array
     */
    protected function comparePeriods(Period $MainPeriod, array $periods)
    {
        $ids = array_map(function($p){
            return $p->getId();
        }, $periods);

        $compare = [
            'period_ids'=> $ids,
            'periods'   => [],
            'objects'   => $periods
        ];

        foreach ($periods as $period) {


            $compare['periods'][] = [
                'id'                        => $period->getId(),
                'is_same_active_days'       => $MainPeriod->compareHasSameActiveDays($period),
                'is_price_and_days_equal'   => $MainPeriod->isPriceAndDaysEqual($period),
                'is_price_equal'            => $MainPeriod->getPrice() == $period->getPrice()
            ];

        }

        return $compare;
    }
}