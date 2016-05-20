<?php

namespace SyntaxError\NotificationBundle;


class Date
{
    /**
     * @var \DateTime
     */
    private $monthStart;

    /**
     * @var \DateTime
     */
    private $monthEnd;

    /**
     * @var \DateTime
     */
    private $yearStart;

    /**
     * @var \DateTime
     */
    private $yearEnd;

    /**
     * Date constructor.
     */
    public function __construct()
    {
        $this->monthStart = new \DateTime();
        $this->monthEnd = new \DateTime();
        $this->yearStart = new \DateTime();
        $this->yearEnd = new \DateTime();
        $this->_calc();
    }

    /**
     * Calculate special points in calendar.
     *
     * @void
     */
    private function _calc()
    {
        $this->monthStart->setDate($this->monthStart->format('Y'), $this->monthStart->format('m'), 1);
        $this->monthEnd->setDate($this->monthEnd->format('Y'), $this->monthEnd->format('m'), $this->monthEnd->format('t'));
        $this->yearEnd->setDate($this->yearEnd->format('Y'), 12, 31);
        $this->yearStart->setDate($this->yearStart->format('Y'), 1, 1);
    }

    /**
     * Set time for calculating.
     *
     * @param \DateTime $time
     * @return Date
     */
    public function setTime(\DateTime $time)
    {
        $this->monthStart = $time;
        $this->monthEnd = $time;
        $this->yearStart = $time;
        $this->yearEnd = $time;
        $this->_calc();
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getMonthStart()
    {
        return $this->monthStart;
    }

    /**
     * @return \DateTime
     */
    public function getMonthEnd()
    {
        return $this->monthEnd;
    }

    /**
     * @return \DateTime
     */
    public function getYearStart()
    {
        return $this->yearStart;
    }

    /**
     * @return \DateTime
     */
    public function getYearEnd()
    {
        return $this->yearEnd;
    }
}