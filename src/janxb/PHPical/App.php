<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 23.12.16
 * Time: 20:07
 */

namespace janxb\PHPical;

use DateTime;
use ICal\ICal;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Yaml\Yaml;

class App
{
    private $config = [];
    /** @var ICal[] */
    private $calendars = [];
    private $cache;

    public function __construct($configPath)
    {
        $this->parseConfig($configPath);
        if (!empty($this->config['calendar.password'])) {
            new PasswordProtector($this->config['calendar.password']);
        }

        $this->cache = new FilesystemAdapter(null, $this->config['cache.lifetime'], $this->config['cache.directory']);

        $this->parseCalendars();
    }

    /**
     * @param int $year
     * @param int $month
     * @param null|int $day
     * @return Event[]
     */
    public function getEvents($year, $month, $day = null)
    {
        $events = [];
        $daysOfThisMonth = (int)(new DateTime($year . '-' . $month . '-01'))->format('t');

        if ($day == null) {
            $minDay = 01;
            $maxDay = 99;
        } elseif ($day < 1 || $day > $daysOfThisMonth) {
            return $events;
        } else {
            $minDay = $day;
            $maxDay = $day;
        }

        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $minDay = str_pad($minDay, 2, '0', STR_PAD_LEFT);
        $maxDay = str_pad($maxDay, 2, '0', STR_PAD_LEFT);

        foreach ($this->calendars as $key => $calendar) {
            foreach ($calendar->eventsFromRange($year . $month . $minDay, $year . $month . $maxDay) as $event) {
                $color = $this->config['calendar.colors'][$key];
                $event = new Event($color, $event);
                if (!$event->isFullDayEventFromYesterday($year, $month, $day))
                    $events[] = $event;
            }
        }

        usort($events, function (Event $a, Event $b) {
            return $a->getRawTimestamp() - $b->getRawTimestamp();
        });

        return $events;
    }

    private function parseCalendars()
    {
        if (!is_array($this->config['calendar.urls']))
            return;

        foreach ($this->config['calendar.urls'] as $key => $calendarPath) {
            $cacheCalendar = $this->cache->getItem(sha1($calendarPath));
            if ($cacheCalendar->isHit())
                $calendar = $cacheCalendar->get();
            else {
                $calendar = new ICal($calendarPath);
                $cacheCalendar->set($calendar);
                $this->cache->save($cacheCalendar);
            }

            $this->calendars[$key] = $calendar;
        }
    }

    private function parseConfig($configPath)
    {
        if (!is_readable($configPath))
            throw new \RuntimeException('Config file is not readable');

        $this->config = Yaml::parse(file_get_contents($configPath))['parameters'];
    }
}