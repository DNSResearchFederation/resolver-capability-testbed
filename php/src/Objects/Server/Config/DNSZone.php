<?php

namespace ResolverTest\Objects\Server\Config;

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
     * @param string $domainName
     */
    public function setDomainName($domainName) {
        $this->domainName = $domainName;
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

    public function getIdentifier() {
        return $this->getDomainName();
    }


}