<?php
/**
 * Created by PhpStorm.
 * User: rtt
 * Date: 01.08.18
 * Time: 11:46
 */

namespace FlatStrategy;


/**
 * Текущий период полностью попадает в существующие интервалы
 *
 * Class OuterPeriodsFlatStrategy
 * @package FlatStrategy
 */
class OuterPeriodsFlatStrategy extends AbstractFlatStrategy
{
    public function Generate(\Period $Period, $compare)
    {
        foreach ($compare['periods'] as $item) {
            if($item['is_same_active_days'])
            {
                if($item['is_price_and_days_equal'])
                {
                    $this->strategy['decline'] = [
                        'code'              => 'fully_outer_period_exists',
                        'message'           => 'Существует такой же период, только с большими временными рамками',
                        'reason_period_id'  => $item['id'],
                        'period_ids'        => $compare['period_ids']
                    ];
                } else {

                    $this->strategy['decline'] = [
                        'code'              => 'crossing_outer_periods',
                        'message'           => 'Конфликт дней с существующим периодом, с большими временными рамками',
                        'reason_period_id'  => $item['id'],
                        'period_ids'        => $compare['period_ids']
                    ];
                }

            } else {


            }
        }

        return $this->strategy;
    }
}