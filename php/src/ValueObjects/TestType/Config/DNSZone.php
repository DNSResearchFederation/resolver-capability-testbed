<?php

namespace ResolverTest\ValueObjects\TestType\Config;

use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\ValueObjects\TestType\TestType;

class DNSZone implements OperationConfig {

    /**
     * @var string
     */
    private $domainName;

    /**
     * @var array
     */
    private $nameservers;

    /**
     * @var DNSRecord[]
     */
    private $records;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $nameserverSet;


    /**
     * @var DNSSECConfig
     */
    private $dnsSecConfig;


    /**
     * @var boolean
     */
    private $hasWebVirtualHost;


    /**
     * @param string $domainName
     * @param array $nameservers
     * @param DNSRecord[] $records
     * @param string $prefix
     * @param string $nameserverSet
     * @param DNSSECConfig $dnsSecConfig
     */
    public function __construct($domainName, $nameservers = [], $records = [], $prefix = "", $nameserverSet = null, $dnsSecConfig = null, $hasWebVirtualHost = false) {
        $this->domainName = $domainName;
        $this->nameservers = $nameservers;
        $this->records = $records;
        $this->prefix = $prefix;
        $this->nameserverSet = $nameserverSet;
        $this->dnsSecConfig = $dnsSecConfig;
        $this->hasWebVirtualHost = $hasWebVirtualHost;
    }

    /**
     * @return string
     */
    public function getDomainName() {
        return $this->domainName;
    }

    /**
     * @return array
     */
    public function getNameservers() {
        return $this->nameservers;
    }

    /**
     * @return string
     */
    public function getFirstNameserver() {
        return $this->nameservers[0] ?? "";
    }

    /**
     * @return DNSRecord[]
     */
    public function getRecords() {
        return $this->records;
    }

    /**
     * @param DNSRecord[] $records
     */
    public function setRecords($records) {
        $this->records = $records;
    }

    /**
     * @param DNSRecord $record
     */
    public function addRecord($record) {
        $this->records[] = $record;
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
     * @return string
     */
    public function getNameserverSet() {
        return $this->nameserverSet;
    }

    /**
     * @param string $nameserverSet
     */
    public function setNameserverSet($nameserverSet) {
        $this->nameserverSet = $nameserverSet;
    }

    /**
     * @return DNSSECConfig|null
     */
    public function getDnsSecConfig(): ?DNSSECConfig {
        return $this->dnsSecConfig;
    }

    /**
     * @param DNSSECConfig|null $dnsSecConfig
     */
    public function setDnsSecConfig(?DNSSECConfig $dnsSecConfig): void {
        $this->dnsSecConfig = $dnsSecConfig;
    }

    /**
     * @return bool
     */
    public function getHasWebVirtualHost() {
        return $this->hasWebVirtualHost;
    }


    /**
     * @param GlobalConfigService $globalConfig
     * @param Test $test
     * @param TestType $testType
     * @return void
     */
    public function updateDynamicValues($globalConfig, $test, $testType, $testParameterValues = []) {

        if ($this->prefix) {
            $this->domainName = $this->getPrefix() . $test->getDomainName();
        } else {
            $this->domainName = $test->getDomainName();
        }

        // If dnssec config, attempt substitution from params
        if ($this->dnsSecConfig) {
            $algorithm = $this->dnsSecConfig->getAlgorithm();
            $keyStrength = $this->dnsSecConfig->getKeyStrength();
            if (in_array($algorithm, array_keys($testParameterValues))) {
                $this->dnsSecConfig->setAlgorithm($testParameterValues[$algorithm]);
            }
            if (in_array($keyStrength, array_keys($testParameterValues))) {
                $this->dnsSecConfig->setKeyStrength($testParameterValues[$keyStrength]);
            }
        }

        switch ($this->getNameserverSet()) {
            case "DEFAULT":
                $nameserversKey = "default";
                break;
            case "NAMESERVERS_SET":
                $nameserversKey = $test->getNameserversKey();
                break;
            default:
                $nameserversKey = $test->getNameserversKey();
        }

        $this->nameservers = $globalConfig->getNameserversByKey($nameserversKey);


        // Replace Literal IP addresses with values from global config
        foreach ($this->getRecords() as $record) {
            switch ($record->getData()) {
                case "IPV4_ADDRESS":
                    $record->setData($globalConfig->getIPv4Address());
                    break;
                case "IPV6_ADDRESS":
                    $record->setData($globalConfig->getIPv6Address());
                    break;
                default:
                    if (in_array($record->getData(), array_keys($testParameterValues))) {
                        $record->setData($testParameterValues[$record->getData()]);
                    }
            }

            // Substitute domain name literally
            $record->setData(str_replace("DOMAIN_NAME", $this->domainName, rtrim($record->getData(), ".")));
        }


        // Determine whether we need to set the has webserver virtual host flag
        foreach ($testType->getConfig()->getWebVirtualHosts() ?? [$testType->getConfig()->getWebVirtualHost()] as $webVirtualHost) {
            // If we have a matching prefix or it's a no prefix case continue
            if (($webVirtualHost->getPrefix() == $this->prefix) || (!$this->prefix && !$webVirtualHost->getPrefix())) {
                $this->hasWebVirtualHost = true;
                break;
            }
        }


    }


    public function getIdentifier() {
        return $this->domainName;
    }


}