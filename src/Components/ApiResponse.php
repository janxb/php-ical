<?php


namespace App\Components;


class ApiResponse
{
    /** @var bool */
    public $isAuthenticated;

    /** @var CalendarJson[] */
    public $calendars;
}