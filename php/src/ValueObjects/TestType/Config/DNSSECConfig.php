<?php

namespace ResolverTest\ValueObjects\TestType\Config;

class DNSSECConfig {

    const ALGORITHMS = [
        2 => "DH|2048",
        3 => "DSA|1024",
        5 => "RSASHA1|2048",
        6 => "NSEC3DSA|1024",
        7 => "NSEC3RSASHA1|2048",
        8 => "RSASHA256|2048",
        10 => "RSASHA512|2048",
        12 => "ECCGOST",
        13 => "13",
        14 => "14",
        15 => "15",
        16 => "16"
    ];


    /**
     * @var string
     */
    private $algorithm;

    /**
     * @var integer
     */
    private $keyStrength;

    /**
     * @param string $algorithm
     * @param mixed $keyStrength
     */
    public function __construct($algorithm, $keyStrength = null) {
        $this->algorithm = $algorithm;
        $this->keyStrength = $keyStrength;
    }

    /**
     * @return string
     */
    public function getAlgorithm(): string {
        return self::ALGORITHMS[$this->algorithm] ?? $this->algorithm;
    }

    /**
     * @param string $algorithm
     */
    public function setAlgorithm($algorithm): void {
        $this->algorithm = $algorithm;
    }


    /**
     * @return string
     */
    public function getAlgorithmKey() {
        return explode("|", $this->getAlgorithm())[0];
    }

    /**
     * @return int
     */
    public function getKeyStrength() {
        return $this->keyStrength ?? explode("|", $this->getAlgorithm())[1] ?? "";
    }

    /**
     * @param mixed $keyStrength
     */
    public function setKeyStrength($keyStrength): void {
        $this->keyStrength = $keyStrength;
    }


}