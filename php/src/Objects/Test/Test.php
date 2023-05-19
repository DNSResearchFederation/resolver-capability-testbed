<?php

namespace ResolverTest\Objects\Test;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use ResolverTest\Exception\InvalidDateFormatException;
use ResolverTest\Exception\InvalidTestTypeException;
use ResolverTest\Exception\StartAfterExpiryException;
use ResolverTest\Services\TestManager\TestManager;

class Test {

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $domainName;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $starts;

    /**
     * @var string
     */
    private $expires;

    /**
     * @var string
     */
    private $status;

    /**
     * @var mixed
     */
    private $testData;

    // Status constants
    const STATUS_PENDING = "Pending";
    const STATUS_ACTIVE = "Active";
    const STATUS_COMPLETED = "Completed";

    /**
     * @param string $key
     * @param string $type
     * @param string $domainName
     * @param string $description
     * @param string $starts
     * @param string $expires
     * @param string $status
     * @param mixed $testData
     */
    public function __construct($key, $type, $domainName, $description = null, $starts = null, $expires = null, $status = null, $testData = []) {
        $this->key = $key;
        $this->type = $type;
        $this->domainName = $domainName;
        $this->starts = $starts ?? date("Y-m-d H:i:s");
        $this->expires = $expires;
        $this->testData = $testData;
        $this->status = $status;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
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
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getStarts() {
        return $this->starts;
    }

    /**
     * @param string $starts
     */
    public function setStarts($starts) {
        $this->starts = $starts;
    }

    /**
     * @return string
     */
    public function getExpires() {
        return $this->expires;
    }

    /**
     * @param string $expires
     */
    public function setExpires($expires) {
        $this->expires = $expires;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getTestData() {
        return $this->testData;
    }

    /**
     * @param mixed $testData
     */
    public function setTestData($testData) {
        $this->testData = $testData;
    }

    public function validate() {

        // Validate date format
        if (!date_create_from_format("Y-m-d H:i:s", $this->getStarts())) {
            throw new InvalidDateFormatException();
        }

        if ($this->getExpires()) {
            if (!date_create_from_format("Y-m-d H:i:s", $this->getExpires())) {
                throw new InvalidDateFormatException();
            }

            // Ensure expiry comes after start
            if ($this->getStarts() > $this->getExpires()) {
                throw new StartAfterExpiryException();
            }
        }

        try {
            Container::instance()->getInterfaceImplementation(TestManager::class, $this->getType());
        } catch (MissingInterfaceImplementationException $e) {
            throw new InvalidTestTypeException($this->getType());
        }
    }

}