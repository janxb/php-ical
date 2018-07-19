<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 24.12.16
 * Time: 12:51
 */

namespace janxb\PHPical;


use DateTime;
use ICal\Event as IcalEvent;

class Event
{
    /** @var IcalEvent */
    private $event;

    private $dateStart;
    private $dateEnd;

    /** @var  string */
    private $title;
    /** @var  string */
    private $location;
    /** @var  string */
    private $color;
    /** @var  string */
    private $timezone;
    /** @var  string */
    private $uuid;

    public function __construct(string $color, IcalEvent $event, string $timezone)
    {
        $this->color = $color;
        $this->timezone = $timezone;
        $this->event = $event;
        $this->uuid = uniqid();
        /** @noinspection PhpUndefinedFieldInspection */
        $this->dateStart = new DateTime($this->event->dtstart_tz);
        /** @noinspection PhpUndefinedFieldInspection */
        if (isset($this->event->dtend_tz))
            /** @noinspection PhpUndefinedFieldInspection */
            $this->dateEnd = new DateTime($this->event->dtend_tz);
        else
            $this->dateEnd = $this->dateStart;

        $this->title = str_replace('\n', ', ', $this->event->summary);
        $this->title = stripslashes($this->title);

        $this->location = str_replace('\n', ', ', $this->event->location);
        $this->location = stripslashes($this->location);
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getRawTimestamp()
    {
        return $this->dateStart->format('YmdHi');
    }

    public function isFullDayEvent()
    {
        return ($this->getStartTime() == $this->getTimezoneOffset() &&
            $this->getStartTime() == $this->getEndTime()
        );
    }

    public function getFullDayCount()
    {
        return $this->dateEnd->diff($this->dateStart)->d;
    }

    public function isMultiDayEvent()
    {
        return ($this->getFullDayCount() >= 1 && !$this->isFullDayEvent());
    }

    public function isEventUntilEndOfDay()
    {
        return ($this->getFullDayCount() == 0 && $this->getEndTime() == '00:00');
    }

    public function isFullDayEventFromYesterday($year, $month, $day)
    {
        $dateString = $year . str_pad($month, 2, '0', STR_PAD_LEFT) . str_pad($day, 2, '0', STR_PAD_LEFT);
        return ($this->isFullDayEvent() && $this->dateEnd->format('Ymd') == $dateString);
    }

    public function getDuration($year, $month, $day)
    {
        if ($this->isFullDayEvent())
            return '';

        $dateString = $year . str_pad($month, 2, '0', STR_PAD_LEFT) . str_pad($day, 2, '0', STR_PAD_LEFT);
        $result = '';
        if ($this->dateStart->format('Ymd') == $dateString)
            $result .= $this->getStartTime();
        $result .= '-';
        if ($this->dateEnd->format('Ymd') == $dateString || $this->isEventUntilEndOfDay())
            $result .= $this->getEndTime();

        return $result;
    }

    /**
     * Check if an event is from today. This is true, if it has at least a start- or end-date.
     * This method is needed, because the native calendar library sometimes selects events from non-related days.
     * @param $year
     * @param $month
     * @param $day
     * @return bool
     */
    public function isEventFromToday($year, $month, $day) : bool
    {
        $durationStringLength = strlen($this->getDuration($year, $month, $day));
        return $durationStringLength == 0 || $durationStringLength > 5;
    }

    public function getStartTime()
    {
        return $this->dateStart->format('H:i');
    }

    public function getEndTime()
    {
        return $this->dateEnd->format('H:i');
    }

    private function getTimezoneOffset()
    {
        $offsetHours = timezone_offset_get(timezone_open($this->getTimezone()), new DateTime()) / 3600;
        return str_pad($offsetHours, 2, '0', STR_PAD_LEFT) . ':00';
    }
}
