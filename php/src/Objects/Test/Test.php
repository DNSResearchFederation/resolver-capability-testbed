<?php

namespace ResolverTest\Objects\Test;

use Kinikit\Persistence\ORM\ActiveRecord;
use ResolverTest\Exception\InvalidDateFormatException;
use ResolverTest\Exception\InvalidTestTypeException;
use ResolverTest\Exception\NotEnoughTestParametersException;
use ResolverTest\Exception\StartAfterExpiryException;
use ResolverTest\Services\TestType\TestTypeManager;

/**
 * @generate
 */
class Test extends ActiveRecord {

    /**
     * @var string
     * @primaryKey
     */
    private $key;

    /**
     * @var string
     * @required
     */
    private $type;

    /**
     * @var string
     * @required
     */
    private $domainName;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $starts;

    /**
     * @var \DateTime
     */
    private $expires;

    /**
     * @var string
     * @values PENDING,ACTIVE,COMPLETED,INSTALLING,UNINSTALLING
     */
    private $status;

    /**
     * @var string
     */
    private $nameserversKey;

    /**
     * @var mixed
     * @json
     */
    private $testData;


    /**
     * @var string[]
     * @json
     */
    private $additionalInformation;


    // Status constants
    const STATUS_PENDING = "PENDING";
    const STATUS_ACTIVE = "ACTIVE";
    const STATUS_COMPLETED = "COMPLETED";
    const STATUS_INSTALLING = "INSTALLING";
    const STATUS_UNINSTALLING = "UNINSTALLING";

    /**
     * @param string $key
     * @param string $type
     * @param string $domainName
     * @param string $description
     * @param mixed $starts
     * @param mixed $expires
     * @param string $status
     * @param string $nameserversKey
     * @param mixed $testData
     * @param string[] $additionalInformation
     */
    public function __construct($key, $type, $domainName, $description = null, $starts = null, $expires = null, $status = null, $nameserversKey = "default", $testData = [], $additionalInformation = []) {
        $this->key = $key;
        $this->type = $type;
        $this->domainName = $domainName;
        $this->starts = $starts ?? (new \DateTime());
        $this->expires = $expires;
        $this->testData = $testData;
        $this->status = $status;
        $this->description = $description;
        $this->nameserversKey = $nameserversKey;
        $this->additionalInformation = $additionalInformation;
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
     * @return \DateTime
     */
    public function getStarts() {
        return $this->starts;
    }

    /**
     * @param \DateTime $starts
     */
    public function setStarts($starts) {
        $this->starts = $starts;
    }

    /**
     * @return \DateTime
     */
    public function getExpires() {
        return $this->expires;
    }

    /**
     * @param \DateTime $expires
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
     * @return string
     */
    public function getNameserversKey() {
        return $this->nameserversKey;
    }

    /**
     * @param string $nameserversKey
     */
    public function setNameserversKey($nameserversKey) {
        $this->nameserversKey = $nameserversKey;
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

    /**
     * @return string[]
     */
    public function getAdditionalInformation() {
        return $this->additionalInformation;
    }

    /**
     * @param string[] $additionalInformation
     */
    public function setAdditionalInformation($additionalInformation) {
        $this->additionalInformation = $additionalInformation;
    }


    public function validate() {
        if (is_string($this->getStarts()) || is_string($this->getExpires())) {
            throw new InvalidDateFormatException();
        }

        // Validate date format
        if (!date_create_from_format("Y-m-d H:i:s", $this->getStarts()->format("Y-m-d H:i:s"))) {
            throw new InvalidDateFormatException();
        }

        if ($this->getExpires()) {

            // Ensure expiry comes after start
            if ($this->getStarts()->format("Y-m-d H:i:s") > $this->getExpires()->format("Y-m-d H:i:s")) {
                throw new StartAfterExpiryException();
            }
        }

        try {
            $testTypeManager = new TestTypeManager();
            $types = $testTypeManager->listTestTypes();
            $type = $types[$this->getType()];
        } catch (\Exception $e) {
            throw new InvalidTestTypeException($this->getType());
        }

        // Check we have the right number of parameters
        $parameterValues = $this->getTestData() ?? [];
        $missingParams = [];
        foreach ($type->getParameters() ?? [] as $index => $parameter) {
            if (!$parameter->getOptional() && !isset($parameterValues[$index]))
                $missingParams[] = $parameter->getIdentifier();
        }
        if (sizeof($missingParams))
            throw new NotEnoughTestParametersException($this->getType(), $missingParams);

    }

}