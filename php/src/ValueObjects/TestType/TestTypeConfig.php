<?php

namespace ResolverTest\ValueObjects\TestType;

use ResolverTest\ValueObjects\TestType\Config\DNSZone;
use ResolverTest\ValueObjects\TestType\Config\WebServerVirtualHost;

class TestTypeConfig {

    /**
     * @var DNSZone
     */
    private $dnsZone;

    /**
     * @var WebServerVirtualHost
     */
    private $webVirtualHost;

    /**
     * @param DNSZone $dnsZone
     * @param WebServerVirtualHost $webVirtualHost
     */
    public function __construct($dnsZone, $webVirtualHost) {
        $this->dnsZone = $dnsZone;
        $this->webVirtualHost = $webVirtualHost;
    }

    /**
     * @return DNSZone
     */
    public function getDnsZone() {
        return $this->dnsZone;
    }

    /**
     * @param DNSZone $dnsZone
     */
    public function setDnsZone($dnsZone) {
        $this->dnsZone = $dnsZone;
    }

    /**
     * @return WebServerVirtualHost
     */
    public function getWebVirtualHost() {
        return $this->webVirtualHost;
    }

    /**
     * @param WebServerVirtualHost $webVirtualHost
     */
    public function setWebVirtualHost($webVirtualHost) {
        $this->webVirtualHost = $webVirtualHost;
    }

}