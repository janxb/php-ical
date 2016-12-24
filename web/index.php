<!DOCTYPE html>
<?php
require_once "../vendor/autoload.php";

$app = new \janxb\PHPical\App('../config/bak.config.yml');
$month = (isset($_GET['m']) ? intval($_GET['m']) : date('m'));
$year = (isset($_GET['y']) ? intval($_GET['y']) : date('Y'));

?>
<html>
    <head>
        <title>Calendar</title>
        <link rel="stylesheet" href="calendar.css"/>
    </head>
    <body>
        <h3>
            <?= date("F", mktime(0, 0, 0, $month, 1, $year)) . ' ' . $year ?><br>
            <a href="?<?= \janxb\PHPical\DateCalculator::previousMonth($year, $month) ?>">&lArr;</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="?<?= \janxb\PHPical\DateCalculator::nextMonth($year, $month) ?>">&rArr;</a>
        </h3>

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
                 $i <= 40;
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
                ?>
                <div class="column day <?= $dayDisabled ?>">
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