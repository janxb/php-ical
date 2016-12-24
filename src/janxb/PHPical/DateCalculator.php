<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 24.12.16
 * Time: 14:51
 */

namespace janxb\PHPical;


class DateCalculator
{
    public static function nextMonth($year, $month)
    {
        if ($month == 12)
            return 'y=' . ($year + 1) . '&m=' . (1);

        return 'y=' . ($year) . '&m=' . ($month + 1);
    }

    public static function previousMonth($year, $month)
    {
        if ($month == 1)
            return 'y=' . ($year - 1) . '&m=' . (12);

        return 'y=' . ($year) . '&m=' . ($month - 1);
    }
}