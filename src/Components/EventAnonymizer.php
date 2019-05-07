<?php


namespace App\Components;


use ICal\Event;

class EventAnonymizer
{
    private $shouldAnonymize;

    public function __construct(bool $shouldAnonymize)
    {
        $this->shouldAnonymize = $shouldAnonymize;
    }

    /**
     * @param Event[] $events
     * @return Event[]
     */
    public function anonymize(array $events)
    {
        if ($this->shouldAnonymize) {
            foreach ($events as $event) {
                $event->description = "";
                $event->summary = "not available";
                $event->location = "";
            }
        }
        return $events;
    }
}