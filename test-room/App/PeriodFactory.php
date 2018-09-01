<?php
/**
 * Created by PhpStorm.
 * User: rtt
 * Date: 31.07.18
 * Time: 16:41
 */

class PeriodFactory
{

    /**
     * @param $req
     * @return Period
     */
    static public function fromPostRequest($req)
    {
        $startDate = $req->get('start_date');
        $endDate = $req->get('end_date');
        $price = $req->get('price');
        $days = $req->get('days');
        $endDate .= '23:59:59';


        $Period = new Period($startDate, $endDate, $price);
        $Period->setDays($days);

        $id = $req->get('id');

        if($id)
        {
            $Period->setId($id);
        }

        return $Period;
    }


    /**
     * Построить период из строки бд
     * @param $row
     * @return Period
     */
    static public function fromRow($row)
    {
        $period = new Period($row['start_date'], $row['end_date'], $row['price']);
        $period->setId($row['id']);

        $days = [
            $row['mon'], $row['tue'], $row['wed'],
            $row['thu'], $row['fri'], $row['sat'], $row['sun']
        ];

        $period->setDays($days);

        return $period;
    }
}