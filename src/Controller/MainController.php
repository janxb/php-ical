<?php

namespace App\Controller;


use App\Components\ApiResponse;
use App\Components\CalendarJson;
use App\Components\EventAnonymizer;
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

    private function buildEventCacheUrl($calendarUrl, $year, $month, $isAnonymous)
    {
        return 'calendar.'
            . md5($calendarUrl)
            . '.' . $year
            . '.' . $month
            . '.' . ($isAnonymous ? 'anonymous' : 'authenticated');
    }

    private function buildCalendarCacheUrl($calendarUrl)
    {
        return 'calendar.' . md5($calendarUrl);
    }

    /**
     * @param Request $request
     * @param $year
     * @param $month
     * @param CacheInterface $cache
     * @return JsonResponse
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @Route("/api/events/{year}/{month}")
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

        $isPublicAvailabilityEnabled = $this->getParameter("calendar_public_availability") == "true";

        $requestPassword = $request->get('p');

        if ($isPasswordsEnabled) {
            if (in_array($requestPassword, $passwords)) {
                $isAnonymous = false;
            } else {
                if ($isPublicAvailabilityEnabled) {
                    $isAnonymous = true;
                } else {
                    return new JsonResponse(null, 403);
                }
            }
        }

        $result = new ApiResponse();
        $result->isAuthenticated = !$isAnonymous;

        $startDate = DateTime::createFromFormat('Ymd', $year . $month . '01')->sub(new DateInterval('P1M'));
        $endDate = (clone $startDate)->add(new DateInterval('P3M'));
        foreach ($calendarUrls as $index => $calendarUrl) {
            if ($cache->has($this->buildEventCacheUrl($calendarUrl, $year, $month, $isAnonymous))) {
                $result->calendars[] = $cache->get($this->buildEventCacheUrl($calendarUrl, $year, $month, $isAnonymous));
            } else {
                if ($cache->has($this->buildCalendarCacheUrl($calendarUrl))) {
                    $ical = $cache->get($this->buildCalendarCacheUrl($calendarUrl));
                } else {
                    $ical = new ICal($calendarUrl);
                    $cache->set($this->buildCalendarCacheUrl($calendarUrl), $ical, $cacheTimeout);
                }
                $calendar = new CalendarJson();
                $calendar->name = $calendarNames[$index];
                $calendar->color = $calendarColors[$index];
                $result->calendars[] = $calendar;
                $calendar->events =
                    (new EventAnonymizer($isAnonymous))->anonymize(
                        (new EventToJsonConverter())->convert($ical,
                            $ical->eventsFromRange(
                                $startDate->format('Ymd'),
                                $endDate->format('Ymd')
                            )
                        )
                    );
                $cache->set($this->buildEventCacheUrl($calendarUrl, $year, $month, $isAnonymous), $calendar, $cacheTimeout);
            }
        }
        return new JsonResponse($result);
    }
}