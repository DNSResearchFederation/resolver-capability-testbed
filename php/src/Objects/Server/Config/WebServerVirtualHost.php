<?php

namespace ResolverTest\Objects\Server\Config;

class WebServerVirtualHost implements OperationConfig {

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var string
     */
    private $content;

    /**
     * @param string $hostname
     * @param string $content
     */
    public function __construct($hostname, $content = null) {
        $this->hostname = $hostname;
        $this->content = $content;
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
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    public function getIdentifier() {
        return $this->getHostname();
    }

}