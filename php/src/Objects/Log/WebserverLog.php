<?php

namespace ResolverTest\Objects\Log;

/**
 * @table webserver_queue
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
     * @var integer
     */
    private $statusCode;

    /**
     * @param string $hostname
     * @param string $date
     * @param string $ipAddress
     * @param string $userAgent
     * @param int $statusCode
     */
    public function __construct($hostname, $date, $ipAddress, $userAgent, $statusCode) {
        parent::__construct($hostname, $date);
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->statusCode = $statusCode;
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

    /**
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
    }

}