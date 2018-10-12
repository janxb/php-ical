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
        $selectedLanguage = $this->getParameter('calendar_language');
        if (!in_array($selectedLanguage, $allowedLanguages))
            $selectedLanguage = null;
        return $this->render('base.html.twig', [
            'language' => $selectedLanguage
        ]);
    }

    private function buildEventCacheUrl($calendarUrl, $year, $month)
    {
        return 'calendar.' . md5($calendarUrl) . '.' . $year . '.' . $month;
    }

    private function buildCalendarCacheUrl($calendarUrl)
    {
        return 'calendar.' . md5($calendarUrl);
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
        $cacheTimeout = intval($this->getParameter("calendar_cache_ttl"));
        $passwords = explode(',', $this->getParameter("calendar_passwords"));
        $isPasswordsEnabled = !empty($passwords[0]) || count($passwords) > 1;
        foreach ($passwords as &$password) {
            $password = sha1($password);
        }

        $calendarUrls = array_map('trim', explode(',', $this->getParameter("calendar_urls")));

        $calendarColors = array_map('trim', explode(',', $this->getParameter("calendar_colors")));

        $calendarNames = array_map('trim', explode(',', $this->getParameter("calendar_names")));

        $requestPassword = $request->get('p');

        if ($isPasswordsEnabled) {
            if (!in_array($requestPassword, $passwords))
                return new JsonResponse(null, 403);
        }

        //$cache = new FilesystemCache();
        $result = [];
        $startDate = DateTime::createFromFormat('Ymd', $year . $month . '01');
        $endDate = (clone $startDate)->add(new DateInterval('P1M'));
        foreach ($calendarUrls as $index => $calendarUrl) {
            if ($cache->has($this->buildEventCacheUrl($calendarUrl, $year, $month))) {
                $result[] = $cache->get($this->buildEventCacheUrl($calendarUrl, $year, $month));
            } else {
                if ($cache->has($this->buildCalendarCacheUrl($calendarUrl))) {
                    $ical = $cache->get($this->buildCalendarCacheUrl($calendarUrl));
                } else {
                    $ical = new ICal($calendarUrl);
                    $cache->set($this->buildCalendarCacheUrl($calendarUrl), $ical, $cacheTimeout);
                }
                $calendar = new CalendarJson();
                $calendar->name = $calendarNames[$index];
                $calendar->description = $ical->calendarDescription();
                $calendar->color = $calendarColors[$index];
                $result[] = $calendar;
                $calendar->events = (new EventToJsonConverter())->convert($ical, $ical->eventsFromRange(
                    $startDate->format('Ymd'),
                    $endDate->format('Ymd'))
                );
                $cache->set($this->buildEventCacheUrl($calendarUrl, $year, $month), $calendar, $cacheTimeout);
            }
        }
        return new JsonResponse($result);
    }
}