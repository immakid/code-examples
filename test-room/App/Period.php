<?php


class Period implements JsonSerializable
{

    protected $id;
    protected $start;
    protected $end;
    protected $price;
    protected $days = [];


    public function __construct($start, $end, $price)
    {

        $this->start = new DateTime($start);
        $this->end = new DateTime($end);
        $this->price = $price;

    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getDays()
    {
        return $this->days;
    }

    public function setDays($days)
    {
        $this->days = $days;
    }


    public function getStartDate()
    {
        return $this->start;
    }

    public function getEndDate()
    {
        return $this->end;
    }

    public function setStartDate(DateTime $date)
    {
        $this->start = $date;
    }

    public function setEndDate(DateTime $date)
    {
        $this->end = $date;
    }


    public function toArray()
    {
        $ret = [
            'start_date'    => $this->start->format('Y-m-d H:i:s'),
            'end_date'      => $this->end->format('Y-m-d H:i:s'),
            'price'         => $this->price
        ];

        if($this->id)
        {
            $ret['id'] = $this->id;
        }

        foreach ($this->days as $index => $val) {

            $day = Period::getDayName($index);
            $ret[$day] = (int)$val;
        }

        return $ret;
    }


    /**
     * Сравнить одинаковые активные дни
     * @param Period $period
     * @return bool
     */
    public function compareHasSameActiveDays(Period $period)
    {
        $hasSame = false;
        $days = $period->getDays();

        foreach ($days as $index => $val)
        {
            if($val && $val == $this->days[$index])
            {
                $hasSame = true;
                break;
            }
        }

        return $hasSame;
    }

    public function isPriceAndDaysEqual(Period $period)
    {
        return $period->getPrice() == $this->price
            && $this->getDays() == $period->getDays();
    }


    static function getDayName($index)
    {
        switch ($index)
        {
            case 0: return 'mon';
            case 1: return 'tue';
            case 2: return 'wed';
            case 3: return 'thu';
            case 4: return 'fri';
            case 5: return 'sat';
            case 6: return 'sun';
        }
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}