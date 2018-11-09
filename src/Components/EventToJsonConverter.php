<?php


namespace App\Components;


use DateInterval;
use DateTime;
use ICal\Event;
use ICal\ICal;

class EventToJsonConverter
{
    /**
     * @param Event[] $events
     * @return array
     * @throws \Exception
     */
    public function convert(ICal $calendar, array $events)
    {
        $result = [];
        /** @var Event $event */
        foreach ($events as $event) {
            $this->fixMissingEventProperties($event);
            $this->fixRepeatedFullDayEventsAlreadyInLocalTime($event);
            $this->fixFullDayEventsMissingTime($event);
            $this->fixTimestampsAlreadyInLocalTime($event);

            $json = new EventJson();
            $json->uid = $event->uid;
            $json->summary = $event->summary;
            $json->description = str_replace("\n", '<br>', $event->description);
            $json->location = $event->location;
            /** @noinspection PhpUndefinedFieldInspection */
            $json->dateStart = $event->dtstart_tz;
            /** @noinspection PhpUndefinedFieldInspection */
            $json->dateEnd = $event->dtend_tz;

            $this->setFullDayProperty($json);
            $this->setMultiDayProperty($json);
            $this->setEventTimeUntilMidnight($json);

            $result[] = $json;
        }
        return $result;
    }

    /**
     * @param EventJson $json
     * @throws \Exception
     */
    private function setEventTimeUntilMidnight(EventJson $json)
    {
        if (!$json->isFullDay && $json->isMultiDay && DateHelper::getTimeFromDateTimeString($json->dateEnd) == 0){
            $end = DateTime::createFromFormat('Ymd\THis', $json->dateEnd)->sub(new DateInterval('PT1S'));
            $json->dateEnd = $end->format("Ymd\THis");
        }
    }

    private function fixFullDayEventsMissingTime(Event $event)
    {
        if (strlen($event->dtstart) == 8) {
            $dateDifferenceDays = DateHelper::getDateDifference($event->dtstart, $event->dtend, 'Ymd');
            $dateEnd =
                ($dateDifferenceDays <= 1)
                    ? $event->dtstart
                    : $event->dtend - 1;
            $event->dtstart = $event->dtstart . 'T000000';
            $event->dtend = $dateEnd . 'T000000';
        }
    }

    private function fixRepeatedFullDayEventsAlreadyInLocalTime($event)
    {
        if (property_exists($event, 'rrule') &&
            DateHelper::getTimeFromDateTimeString($event->dtstart) == 0
        ) {
            $event->dtstart = substr($event->dtstart, 0, 8);
            $event->dtend = substr($event->dtend, 0, 8);
        }
    }

    private function fixTimestampsAlreadyInLocalTime($event)
    {
        if (
            !StringHelper::stringEndsWith($event->dtstart, 'Z') // date has no UTC marker
            && strlen($event->dtstart) == 15 // timestamp includes time part (if not, it's a full-time event
        ) {
            $event->dtstart_tz = $event->dtstart;
            $event->dtend_tz = $event->dtend;
        }
    }

    private function fixMissingEventProperties($event)
    {
        if (!property_exists($event, 'dtend_tz'))
            $event->dtend_tz = $event->dtstart_tz;
        if (is_null($event->dtend))
            $event->dtend = $event->dtstart;
    }

    private function setFullDayProperty(EventJson $json)
    {
        $json->isFullDay =
            DateHelper::getTimeFromDateTimeString($json->dateStart) == 0
            && DateHelper::getTimeFromDateTimeString($json->dateEnd) == 0;
    }

    private function setMultiDayProperty(EventJson $json)
    {
        $json->isMultiDay =
            DateHelper::getDateFromDateTimeString($json->dateStart) !=
            DateHelper::getDateFromDateTimeString($json->dateEnd);
    }
}