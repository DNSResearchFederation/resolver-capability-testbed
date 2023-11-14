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
     * @var DNSZone[]
     */
    private $dnsZones;

    /**
     * @var WebServerVirtualHost
     */
    private $webVirtualHost;

    /**
     * @var WebServerVirtualHost[]
     */
    private $webVirtualHosts;

    /**
     * @param DNSZone $dnsZone
     * @param DNSZone[] $dnsZones
     * @param WebServerVirtualHost $webVirtualHost
     * @param WebServerVirtualHost[] $webVirtualHosts
     */
    public function __construct($dnsZone = null, $dnsZones = null, $webVirtualHost = null, $webVirtualHosts = null) {
        $this->dnsZone = $dnsZone;
        $this->webVirtualHost = $webVirtualHost;
        $this->dnsZones = $dnsZones;
        $this->webVirtualHosts = $webVirtualHosts;
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
     * @return DNSZone[]|null
     */
    public function getDnsZones() {
        return $this->dnsZones;
    }

    /**
     * @param DNSZone[]|null $dnsZones
     */
    public function setDnsZones($dnsZones) {
        $this->dnsZones = $dnsZones;
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

    /**
     * @return WebServerVirtualHost[]|null
     */
    public function getWebVirtualHosts() {
        return $this->webVirtualHosts;
    }

    /**
     * @param WebServerVirtualHost[]|null $webVirtualHosts
     */
    public function setWebVirtualHosts($webVirtualHosts) {
        $this->webVirtualHosts = $webVirtualHosts;
    }

}