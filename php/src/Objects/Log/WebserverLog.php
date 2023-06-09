<?php

namespace ResolverTest\Objects\Log;

/**
 * @table webserver_log
 * @generate
 */
class WebserverLog extends BaseLog {

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @param string $hostname
     * @param \DateTime $date
     * @param string $ipAddress
     * @param string $userAgent
     */
    public function __construct($hostname, $date, $ipAddress, $userAgent) {
        parent::__construct($hostname, $date);
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    /**
     * @return string
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }

    /**
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return string
     */
    public function getUserAgent() {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
    }

}