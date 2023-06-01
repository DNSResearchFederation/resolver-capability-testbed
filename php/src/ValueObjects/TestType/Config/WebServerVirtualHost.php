<?php

namespace ResolverTest\ValueObjects\TestType\Config;

class WebServerVirtualHost implements OperationConfig {

    /**
     * @var string
     * @requiredEither wildcard
     */
    private $prefix;

    /**
     * @var bool
     */
    private $wildcard;

    /**
     * @var string
     */
    private $content;

    /**
     * @param string $prefix
     * @param bool $wildcard
     * @param string $content
     */
    public function __construct($prefix = null, $wildcard = false, $content = null) {
        $this->prefix = $prefix;
        $this->wildcard = $wildcard;
        $this->content = $content;
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

    public function getIdentifier() {
        return $this->getPrefix();
    }

    public function updateDynamicValues($test) {
        $this->prefix = $test->getDomainName();
    }

}