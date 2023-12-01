<?php

namespace ResolverTest\ValueObjects\TestType\Config;

use ResolverTest\Objects\Test\Test;
use ResolverTest\ValueObjects\TestType\TestType;

class WebServerVirtualHost implements OperationConfig {

    /**
     * @var string
     * @requiredEither wildcard
     */
    private $domainName;

    /**
     * @var bool
     */
    private $wildcard;

    /**
     * @var string
     */
    private $content;

    /**
     * @var array
     */
    private $sslCertPrefixes;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var DNSSECConfig
     */
    private $dnssecConfig;


    /**
     * @param string $domainName
     * @param bool $wildcard
     * @param string $content
     * @param array $sslCertPrefixes
     * @param string $prefix
     * @param DNSSECConfig $dnssecConfig
     */
    public function __construct($domainName = null, $wildcard = false, $content = null, $sslCertPrefixes = ["*"], $prefix = "", $dnssecConfig = null) {
        $this->domainName = $domainName;
        $this->wildcard = $wildcard;
        $this->content = $content;
        $this->sslCertPrefixes = $sslCertPrefixes;
        $this->prefix = $prefix;
        $this->dnssecConfig = $dnssecConfig;
    }

    /**
     * @return string
     */
    public function getDomainName() {
        return $this->domainName;
    }

    /**
     * @param string $domainName
     */
    public function setDomainName($domainName) {
        $this->domainName = $domainName;
    }

    /**
     * @return bool
     */
    public function isWildcard() {
        return $this->wildcard;
    }

    /**
     * @param bool $wildcard
     */
    public function setWildcard($wildcard) {
        $this->wildcard = $wildcard;
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

    /**
     * @return array
     */
    public function getSslCertPrefixes() {
        return $this->sslCertPrefixes;
    }

    /**
     * @param array $sslCertPrefixes
     */
    public function setSslCertPrefixes($sslCertPrefixes) {
        $this->sslCertPrefixes = $sslCertPrefixes;
    }

    /**
     * @return string
     */
    public function getPrefix() {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix) {
        $this->prefix = $prefix;
    }

    /**
     * @return bool
     */
    public function getDNSSecConfig() {
        return $this->dnssecConfig;
    }


    public function getIdentifier() {
        return $this->getDomainName();
    }


    /**
     * @param Test $test
     * @param TestType $testType
     * @param mixed[] $testParameterValues
     * @return void
     */
    public function updateDynamicValues($test, $testType, $testParameterValues = []) {

        if ($this->prefix) {
            $this->domainName = $this->getPrefix() . $test->getDomainName();
        } else {
            $this->domainName = $test->getDomainName();
        }

        if (in_array($this->content, array_keys($testParameterValues))) {
            $this->setContent($testParameterValues[$this->content]);
        }

        // Determine whether we need to set the dnssec signed zone flag
        foreach ($testType->getConfig()->getDnsZones() ?? [$testType->getConfig()->getDnsZone()] as $dnsZone) {
            // If we have a matching prefix or it's a no prefix case continue
            if (($dnsZone->getPrefix() == $this->prefix) || (!$this->prefix && !$dnsZone->getPrefix())) {
                if ($dnsZone->getDnsSecConfig() && $dnsZone->getDnsSecConfig()->isSignZone()) {
                    $this->dnssecConfig = $dnsZone->getDnsSecConfig();
                    break;
                }
            }
        }


    }

}