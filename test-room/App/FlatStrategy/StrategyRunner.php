<?php
/**
 * Created by PhpStorm.
 * User: rtt
 * Date: 31.07.18
 * Time: 20:32
 */

namespace FlatStrategy;

/**
 * Применить стратегию "флэт" для периода
 * Class StrategyRunner
 * @package FlatStrategy
 */
class StrategyRunner
{

    public function Run($strategy)
    {
        $result = [];


        if($strategy['decline'])
        {
            $result['decline'] = $strategy['decline'];
            return $result;
        }

        // удалить периоды
        if(count($strategy['delete']))
        {
            $result['delete'] = $strategy['delete'];

            \db::where_in('id', $strategy['delete']);
            \db::delete('periods');
        }


        // внести изменения в бд
        foreach ($strategy['current'] as $item) {

            $Period = $this->applyMergeInfo($item['period'], $strategy);
            $periodData = $Period->toArray();


            if($strategy['action_type'] == 'new')
            {
                \db::insert('periods', $periodData);

                $result['insert'][] = $periodData;

            } elseif($strategy['action_type'] == 'edit') {

                \db::where('id', $periodData['id']);
                \db::update('periods', $periodData);

                $result['update'][] = $periodData;
            }
        }

        return $result;
    }

    protected function applyMergeInfo($Period, $strategy)
    {
        if(sp($strategy, 'merge_start_date'))
        {
            $Period->setStartDate($strategy['merge_start_date']['start_date']);
        }

        if(sp($strategy, 'merge_end_date'))
        {
            $Period->setEndDate($strategy['merge_end_date']['end_date']);
        }

        return $Period;
    }
}