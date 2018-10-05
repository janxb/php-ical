<?php

namespace App\Controller;


use App\Components\CalendarJson;
use App\Components\EventToJsonConverter;
use DateInterval;
use DateTime;
use ICal\ICal;
use Psr\SimpleCache\CacheInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class EventsController
 * @package App\Controller
 */
class MainController extends AbstractController
{


    /**
     * @param Request $request
     * @Route("/")
     * @return Response
     */
    public function renderFrontend(Request $request)
    {
        $allowedLanguages = $this->getParameter('allowedLanguages');
        $selectedLanguage = $this->getParameter('language');
        if (!in_array($selectedLanguage, $allowedLanguages))
            $selectedLanguage = null;
        return $this->render('base.html.twig', [
            'language' => $selectedLanguage
        ]);
    }

    private function buildCacheUrl($calendarUrl, $year, $month)
    {
        return 'calendar.' . md5($calendarUrl) . '.' . $year . '.' . $month;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/api/events/{year}/{month}")
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getEvents(Request $request, $year, $month, CacheInterface $cache)
    {
        $calendarUrls = $this->getParameter("calendar_urls");
        if (!is_array($calendarUrls)) $calendarUrls = [$calendarUrls];

        $calendarColors = $this->getParameter("calendar_colors");
        if (!is_array($calendarColors)) $calendarColors = [$calendarColors];

        $calendarNames = $this->getParameter("calendar_names");
        if (!is_array($calendarNames)) $calendarNames = [$calendarNames];

        //$cache = new FilesystemCache();
        $result = [];
        $startDate = DateTime::createFromFormat('Ymd', $year . $month . '01');
        $endDate = (clone $startDate)->add(new DateInterval('P1M'));
        foreach ($calendarUrls as $index => $calendarUrl) {
            if ($cache->has($this->buildCacheUrl($calendarUrl, $year, $month))) {
                $result[] = $cache->get($this->buildCacheUrl($calendarUrl, $year, $month));
            } else {
                $ical = new ICal($calendarUrl);
                $calendar = new CalendarJson();
                $calendar->name = $calendarNames[$index];
                $calendar->description = $ical->calendarDescription();
                $calendar->color = $calendarColors[$index];
                $result[] = $calendar;
                $calendar->events = (new EventToJsonConverter())->convert($ical, $ical->eventsFromRange(
                    $startDate->format('Ymd'),
                    $endDate->format('Ymd'))
                );
                $cache->set($this->buildCacheUrl($calendarUrl, $year, $month), $calendar, 3600);
            }
        }
        return new JsonResponse($result);
    }
}