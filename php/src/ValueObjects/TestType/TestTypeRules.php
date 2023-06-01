<?php

namespace ResolverTest\ValueObjects\TestType;

class TestTypeRules {

    /**
     * @var TestTypeDNSRules
     * @requiredEither webserver
     */
    private $dns;

    /**
     * @var TestTypeWebServerRules
     */
    private $webserver;

    /**
     * @var string
     * @values HOSTNAME
     * @required
     */
    private $relationalKey;

    /**
     * @var integer
     */
    private $timeoutSeconds = 5;

    const RELATIONAL_KEY_HOSTNAME = "HOSTNAME";

    /**
     * @param TestTypeDNSRules $dns
     * @param TestTypeWebServerRules $webserver
     * @param string $relationalKey
     * @param int $timeoutSeconds
     */
    public function __construct($dns, $webserver, $relationalKey, $timeoutSeconds) {
        $this->dns = $dns;
        $this->webserver = $webserver;
        $this->relationalKey = $relationalKey;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    /**
     * @return TestTypeDNSRules
     */
    public function getDns() {
        return $this->dns;
    }

    /**
     * @param TestTypeDNSRules $dns
     */
    public function setDns($dns) {
        $this->dns = $dns;
    }

    /**
     * @return TestTypeWebServerRules
     */
    public function getWebserver() {
        return $this->webserver;
    }

    /**
     * @param TestTypeWebServerRules $webserver
     */
    public function setWebserver($webserver) {
        $this->webserver = $webserver;
    }

    /**
     * @return string
     */
    public function getRelationalKey() {
        return $this->relationalKey;
    }

    /**
     * @param string $relationalKey
     */
    public function setRelationalKey($relationalKey) {
        $this->relationalKey = $relationalKey;
    }

    /**
     * @return int
     */
    public function getTimeoutSeconds() {
        return $this->timeoutSeconds;
    }

    /**
     * @param int $timeoutSeconds
     */
    public function setTimeoutSeconds($timeoutSeconds) {
        $this->timeoutSeconds = $timeoutSeconds;
    }

}