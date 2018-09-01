<?php
/**
 * Created by PhpStorm.
 * User: rtt
 * Date: 01.08.18
 * Time: 11:47
 */

namespace FlatStrategy;


class StartDateFlatStrategy extends AbstractFlatStrategy
{
    public function Generate(\Period $CurrentPeriod, $compare)
    {

        foreach ($compare['periods'] as $key => $item) {

            if($item['is_price_and_days_equal'])
            {
                $Period = $compare['objects'][$key];

                $this->strategy['merge_start_date'] = [
                    'start_date' => $Period->getStartDate(),
                ];

                $this->strategy['delete'][] = $Period->getId();

                continue;
            }


            if($item['is_same_active_days'])
            {
                $this->strategy['decline'] = [
                    'code'              => 'crossing_start_date_periods',
                    'message'           => 'Начало нового периода попадает в уже существующий интервал',
                    'reason_period_id'  => $item['id'],
                    'period_ids'        => $compare['period_ids']
                ];

                continue;
            }
        }


        return $this->strategy;
    }
}