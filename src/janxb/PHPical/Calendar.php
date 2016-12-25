<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 25.12.16
 * Time: 12:24
 */

namespace janxb\PHPical;


class Calendar
{
    private $title;
    private $color;

    /**
     * Calendar constructor.
     * @param string $title
     * @param string $color
     */
    public function __construct($title, $color)
    {
        $this->title = $title;
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }
}