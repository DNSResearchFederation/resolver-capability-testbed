<?php

namespace ResolverTest\ValueObjects\TestType\Config;

class WebServerVirtualHost implements OperationConfig {

    /**
     * @var string
     * @requiredEither wildcard
     */
    private $domainName;

    /**
     * @var bool
     */
    private $wildcard;

    /**
     * @var string
     */
    private $content;

    /**
     * @var array
     */
    private $sslCertPrefixes;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param string $domainName
     * @param bool $wildcard
     * @param string $content
     * @param array $sslCertPrefixes
     * @param string $prefix
     */
    public function __construct($domainName = null, $wildcard = false, $content = null, $sslCertPrefixes = ["*"], $prefix = "") {
        $this->domainName = $domainName;
        $this->wildcard = $wildcard;
        $this->content = $content;
        $this->sslCertPrefixes = $sslCertPrefixes;
        $this->prefix = $prefix;
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
     * @return bool
     */
    public function isWildcard() {
        return $this->wildcard;
    }

    /**
     * @param bool $wildcard
     */
    public function setWildcard($wildcard) {
        $this->wildcard = $wildcard;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * @return array
     */
    public function getSslCertPrefixes() {
        return $this->sslCertPrefixes;
    }

    /**
     * @param array $sslCertPrefixes
     */
    public function setSslCertPrefixes($sslCertPrefixes) {
        $this->sslCertPrefixes = $sslCertPrefixes;
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

    public function getIdentifier() {
        return $this->getDomainName();
    }

    public function updateDynamicValues($test, $testParameterValues = []) {

        if ($this->prefix) {
            $this->domainName = $this->getPrefix() . $test->getDomainName();
        } else {
            $this->domainName = $test->getDomainName();
        }

        if (in_array($this->content, array_keys($testParameterValues))) {
            $this->setContent($testParameterValues[$this->content]);
        }


    }

}