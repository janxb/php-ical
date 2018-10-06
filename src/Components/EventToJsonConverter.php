<?php


namespace App\Components;


use ICal\Event;
use ICal\ICal;

class EventToJsonConverter
{
    /**
     * @param Event[] $events
     * @return array
     */
    public function convert(ICal $calendar, array $events)
    {
        $result = [];
        foreach ($events as $event) {
            $this->fixMissingEventProperties($event);

            $json = new EventJson();
            $json->uid = $event->uid;
            $json->summary = $event->summary;
            $json->description = str_replace("\n", '<br>', $event->description);
            $json->location = $event->location;
            $json->dateStart = $event->dtstart_tz;
            $json->dateEnd = $event->dtend_tz;

            $this->handleEventFullDay($event, $json);
            $this->handleEventMultiDay($json);
            $result[] = $json;
        }
        return $result;
    }

    private function fixMissingEventProperties($event)
    {
        if (!property_exists($event, 'dtend_tz'))
            $event->dtend_tz = $event->dtstart_tz;
        if (is_null($event->dtend))
            $event->dtend = $event->dtstart;
    }

    private function handleEventFullDay(Event $event, EventJson &$json)
    {
        $json->isFullDay = strlen($event->dtstart) == 8 ||
            (DateHelper::getDateDifference($event->dtstart, $event->dtend) == 1
                && DateHelper::getTimeFromDateTimeString($event->dtstart) == 0
                && DateHelper::getTimeFromDateTimeString($event->dtend) == 0
            );
        if ($json->isFullDay) {
            $json->dateStart = substr($json->dateStart, 0, -6) . '000000';
            $dateEnd = (DateHelper::getDateDifference($json->dateStart, $json->dateEnd) == 1)
                ? $json->dateStart : $json->dateEnd;
            $json->dateEnd = substr($dateEnd, 0, -6) . '000000';
        }
    }

    private function handleEventMultiDay(EventJson $json)
    {
        $json->isMultiDay =
            substr($json->dateStart, 0, 8) !=
            substr($json->dateEnd, 0, 8);
    }
}