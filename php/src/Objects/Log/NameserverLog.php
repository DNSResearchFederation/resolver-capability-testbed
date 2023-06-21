<?php

namespace ResolverTest\Objects\Log;

/**
 * @table nameserver_queue
 * @generate
 */
class NameserverLog extends BaseLog {

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var integer
     */
    private $port;

    /**
     * @var string
     */
    private $request;

    /**
     * @var string
     */
    private $flags;

    /**
     * @param string $hostname
     * @param \DateTime $date
     * @param string $ipAddress
     * @param integer $port
     * @param string $request
     * @param string $flags
     */
    public function __construct($hostname, \DateTime $date, $ipAddress, $port, $request, $flags) {
        parent::__construct($hostname, $date);
        $this->ipAddress = $ipAddress;
        $this->port = $port;
        $this->request = $request;
        $this->flags = $flags;
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
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @param string $request
     */
    public function setRequest($request) {
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getFlags() {
        return $this->flags;
    }

    /**
     * @param string $flags
     */
    public function setFlags($flags) {
        $this->flags = $flags;
    }

}