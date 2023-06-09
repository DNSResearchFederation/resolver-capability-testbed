<?php

namespace ResolverTest\Objects\Log;


abstract class BaseLog {

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $hostname;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @param string $hostname
     * @param \DateTime $date
     */
    public function __construct($hostname, \DateTime $date) {
        $this->hostname = $hostname;
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getHostname() {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     */
    public function setHostname($hostname) {
        $this->hostname = $hostname;
    }

    /**
     * @return \DateTime
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date) {
        $this->date = $date;
    }

}