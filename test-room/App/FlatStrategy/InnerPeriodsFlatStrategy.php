<?php
/**
 * Created by PhpStorm.
 * User: rtt
 * Date: 31.07.18
 * Time: 20:09
 */

namespace FlatStrategy;

/**
 * Существующие периоды полностью попадают в текущий интервал
 *
 *
 * Class InnerPeriodsFlatStrategy
 * @package FlatStrategy
 */
class InnerPeriodsFlatStrategy extends AbstractFlatStrategy
{

    public function Generate(\Period $Period, $compare)
    {

        foreach ($compare['periods'] as $item) {

            if($item['is_price_and_days_equal'])
            {
                /**
                 * полностью такой же период, только меньше по датам,
                 * новый полностью перекрывает - удаляем существующий
                 */
                $this->strategy['delete'][] = $item['id'];

                continue;
            }


            if($item['is_same_active_days'])
            {
                $this->strategy['decline'] = [
                    'code'              => 'crossing_inner_periods',
                    'message'           => 'Существует период, который полностью попадает в этот, но происходит конфдикт дней',
                    'reason_period_id'  => $item['id'],
                    'period_ids'        => $compare['period_ids']
                ];

                continue;
            }
        }


        return $this->strategy;
    }
}