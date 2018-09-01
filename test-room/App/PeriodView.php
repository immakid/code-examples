<?php

class PeriodView
{
    protected $Period;

    public function __construct(\Period $Period)
    {

        $this->Period = $Period;

    }

    public function getDates()
    {
        $start = $this->Period->getStartDate();
        $end = $this->Period->getEndDate();

        return [
            'start_date' => [
                'Y' => $start->format('Y'),
                'm' => $start->format('m'),
                'd' => $start->format('d'),
                'H' => $start->format('H'),
                'i' => $start->format('i'),
                's' => $start->format('s')
            ],

            'end_date' => [
                'Y' => $end->format('Y'),
                'm' => $end->format('m'),
                'd' => $end->format('d'),
                'H' => $end->format('H'),
                'i' => $start->format('i'),
                's' => $start->format('s')
            ],
        ];
    }

    public function getActiveDays()
    {
        $days = $this->Period->getDays();
        $result = [];

        foreach ($days as $index => $val) {
            if($val)
            {
                $result[] = \Period::getDayName($index);
            }
        }

        return $result;
    }
}