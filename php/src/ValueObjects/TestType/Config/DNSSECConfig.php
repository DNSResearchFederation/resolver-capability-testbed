<?php

namespace ResolverTest\ValueObjects\TestType\Config;

class DNSSECConfig {

    /**
     * @var DNSSECAlgorithmEnum
     */
    private DNSSECAlgorithmEnum $algorithm;

    /**
     * @var integer
     */
    private $keyStrength;

    /**
     * @param DNSSECAlgorithmEnum $algorithm
     * @param int $keyStrength
     */
    public function __construct(DNSSECAlgorithmEnum $algorithm, $keyStrength = null) {
        $this->algorithm = $algorithm;
        $this->keyStrength = $keyStrength;
    }

    /**
     * @return DNSSECAlgorithmEnum
     */
    public function getAlgorithm(): DNSSECAlgorithmEnum {
        return $this->algorithm;
    }

    /**
     * @param DNSSECAlgorithmEnum $algorithm
     */
    public function setAlgorithm(DNSSECAlgorithmEnum $algorithm): void {
        $this->algorithm = $algorithm;
    }


    /**
     * @return string
     */
    public function getAlgorithmKey() {
        return explode("|", $this->algorithm->value)[0];
    }

    /**
     * @return int
     */
    public function getKeyStrength() {
        return $this->keyStrength ?? explode("|", $this->algorithm->value)[1] ?? "";
    }

    /**
     * @param int $keyStrength
     */
    public function setKeyStrength(int $keyStrength): void {
        $this->keyStrength = $keyStrength;
    }


}