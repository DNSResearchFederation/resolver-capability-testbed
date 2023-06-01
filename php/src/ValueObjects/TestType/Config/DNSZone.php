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
     * @var DNSRecord[]
     */
    private $records;

    /**
     * @param string $domainName
     * @param DNSRecord[] $records
     */
    public function __construct($domainName, $records = []) {
        $this->domainName = $domainName;
        $this->records = $records;
    }

    /**
     * @return string
     */
    public function getDomainName() {
        return $this->domainName;
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