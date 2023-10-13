<?php

namespace ResolverTest\ValueObjects\TestType\Config;

use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Config\GlobalConfigService;

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
     * @param string $domainName
     * @param array $nameservers
     * @param DNSRecord[] $records
     */
    public function __construct($domainName, $nameservers = [], $records = []) {
        $this->domainName = $domainName;
        $this->nameservers = $nameservers;
        $this->records = $records;
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
     * @param GlobalConfigService $globalConfig
     * @param Test $test
     * @return void
     */
    public function updateDynamicValues($globalConfig, $test) {
        $this->domainName = $test->getDomainName();

        $nameserversKey = $test->getNameserversKey();
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
            }
        }
    }


    public function getIdentifier() {
        return $this->domainName;
    }


}