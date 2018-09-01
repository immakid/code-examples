<?php
/**
 * Created by PhpStorm.
 * User: rtt
 * Date: 01.08.18
 * Time: 11:47
 */

namespace FlatStrategy;


class EndDateFlatStrategy extends AbstractFlatStrategy
{
    public function Generate(\Period $CurrentPeriod, $compare)
    {
        foreach ($compare['periods'] as $key => $item) {


            if($item['is_price_and_days_equal'])
            {
                $Period = $compare['objects'][$key];

                $this->strategy['merge_end_date'] = [
                    'end_date' => $Period->getEndDate(),
                ];

                $this->strategy['delete'][] = $Period->getId();

                continue;
            }


            if($item['is_same_active_days'])
            {
                $this->strategy['decline'] = [
                    'code'              => 'crossing_end_date_periods',
                    'message'           => 'Конец нового периода попадает в уже существующий интервал',
                    'reason_period_id'  => $item['id'],
                    'period_ids'        => $compare['period_ids']
                ];

                continue;
            }
        }


        return $this->strategy;
    }
}