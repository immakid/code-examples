<?php

/*
 * This file is part of the Trellis Instagram Content service.
 *
 * (c) Vinelab <dev@vinelab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Traits;

/**
 * This trait enables the using class to strip a Date string value, following the '2058-1125 14:52:09' format to '20581125145209' like string and vice versa.
 *
 * @author Kinane Domloje <kinane@vinelab.com>
 */
trait DateFormatterTrait
{
    /**
     * Format DB publishing date to Redis's SortedSet score compliance and back.
     *
     *  @param string date ('Y-m-d H:i:s')
     *  @param string format Specify the output format when translating back to date (from score)
     *
     *  @return string
     */
    public function formatDate($dateTime, $format = 'Y-m-d H:i:s')
    {
        // Check for Redis-specific times to return as is
        if ($dateTime == '-inf' || $dateTime == '+inf') {
            $date = $dateTime;
        } elseif (preg_match('/-/', $dateTime) || preg_match('/:/', $dateTime)) {
            // Used when formating to string
            $date = date('YmdHis', strtotime($dateTime));
        } else {
            // Used when formating back from string. In cases such as mapping to models
            $date = date($format, strtotime($dateTime));
        }

        return $date;
    }
}
