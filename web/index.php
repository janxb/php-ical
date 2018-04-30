<?php
require_once "../vendor/autoload.php";

$app = new \janxb\PHPical\App('../config/config.yml');

$month = (isset($_GET['m']) ? intval($_GET['m']) : date('n'));
$year = (isset($_GET['y']) ? intval($_GET['y']) : date('Y'));
$currentDay = date('d');
$currentMonth = date('m');

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Calendar</title>
        <link rel="stylesheet" href="calendar.css?_v=<?= md5_file('calendar.css') ?>"/>
    </head>
    <body>
        <h3>
            <?= date("F", mktime(0, 0, 0, $month, 1, $year)) . ' ' . $year ?><br>
            <a href="?<?= \janxb\PHPical\DateCalculator::previousMonth($year, $month) ?>">&lArr;</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="?<?= \janxb\PHPical\DateCalculator::nextMonth($year, $month) ?>">&rArr;</a>
        </h3>

        <?php if ($app->getConfigParameter('calendar.showlist') === true) { ?>
            <div class="calendarlegend">
                <span class="title">Calendars</span>
                <div class="calendars">
                    <?php foreach ($app->getCalendars() as $calendar) { ?>
                        <span style="color: <?= $calendar->getColor() ?>"><?= $calendar->getTitle() ?></span>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <div class="calendar">
            <?php for ($i = 1; $i <= 7; $i++) { ?>
                <div class="column dayname"><?= date("l", mktime(0, 0, 0, 8, $i, 2011)) ?></div>
            <?php } ?>

            <?php
            $firstDayOfThisMonth = (int)(new DateTime($year . '-' . $month . '-01'))->format('N');
            $daysOfLastMonth = (int)(new DateTime($year . '-' . ($month - 1) . '-01'))->format('t');
            $daysOfThisMonth = (int)(new DateTime($year . '-' . $month . '-01'))->format('t');
            $day = -$firstDayOfThisMonth;
            for ($i = 0;
                 $i <= 42;
                 $i++) {

                $day++;

                if ($day == 0)
                    continue;

                if ($day < 0)
                    $printDay = $daysOfLastMonth - $day * -1 + 1;
                else if ($day > $daysOfThisMonth)
                    $printDay = ($daysOfThisMonth - $day) * -1;
                else
                    $printDay = $day;

                $dayDisabled = ($printDay != $day) ? 'disabled' : '';
                $dayCurrent = ($day == $currentDay && $month == $currentMonth) ? 'current' : '';
                ?>
                <div class="column day <?= $dayDisabled ?> <?= $dayCurrent ?>">
                    <span class="date"><?= $printDay ?></span>
                    <?php
                    $events = $app->getEvents($year, $month, $day);
                    /** @var \janxb\PHPical\Event $event */
                    foreach ($events as $event) { ?>
                        <div class="event"
                            <?= \janxb\PHPical\EventStyleGenerator::generate($event) ?>
                             title="<?= $event->getTitle() ?> | <?= $event->getLocation() ?>">
                            <b><?= $event->getDuration($year, $month, $day) ?></b>
                            <?= $event->getTitle() ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </body>
</html>
