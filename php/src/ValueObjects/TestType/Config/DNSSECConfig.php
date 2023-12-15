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
     * @var integer
     */
    private $ttl;

    /**
     * @var bool
     */
    private $signZone = true;


    /**
     * @var bool
     */
    private $generateDSRecords = true;

    /**
     * @var bool
     */
    private $nsec3 = true;


    /**
     * @param string $algorithm
     * @param string $keyStrength
     * @param bool $signZone
     * @param bool $generateDSRecords
     * @param bool $nsec3
     */
    public function __construct($algorithm, $keyStrength = null, $signZone = true, $generateDSRecords = true, $nsec3 = true, $ttl = 600) {
        $this->algorithm = $algorithm;
        $this->keyStrength = $keyStrength;
        $this->signZone = $signZone;
        $this->generateDSRecords = $generateDSRecords;
        $this->nsec3 = $nsec3;
        $this->ttl = $ttl;
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

    /**
     * @return int
     */
    public function getTtl(): int {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     */
    public function setTtl(int $ttl): void {
        $this->ttl = $ttl;
    }


    /**
     * @return bool
     */
    public function isSignZone(): bool {
        return $this->signZone;
    }

    /**
     * @param bool $signZone
     */
    public function setSignZone(bool $signZone): void {
        $this->signZone = $signZone;
    }

    /**
     * @return bool
     */
    public function isGenerateDSRecords(): bool {
        return $this->generateDSRecords;
    }

    /**
     * @param bool $generateDSRecords
     */
    public function setGenerateDSRecords(bool $generateDSRecords): void {
        $this->generateDSRecords = $generateDSRecords;
    }

    /**
     * @return bool
     */
    public function isNsec3(): bool {
        return $this->nsec3;
    }

    /**
     * @param bool $nsec3
     */
    public function setNsec3(bool $nsec3): void {
        $this->nsec3 = $nsec3;
    }


}