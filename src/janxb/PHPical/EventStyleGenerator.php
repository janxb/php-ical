<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 24.12.16
 * Time: 13:49
 */

namespace janxb\PHPical;


class EventStyleGenerator
{
    public static function generate(Event $event)
    {
        $style = 'style ="';
        if ($event->isFullDayEvent()) {
            $style .= 'background:' . $event->getColor() . ';';
            $style .= 'color:white;';
            $style .= 'font-weight:bold;';
            $style .= 'opacity:0.6;';
        } else {
            $style .= 'color:' . $event->getColor() . ';';
        }

        $style .= '"';

        return $style;
    }
}