<?php

namespace ResolverTest\ValueObjects\TestType;

class TestTypeWebServerRules {

    /**
     * @var integer
     */
    private $expectedQueries = 1;

    /**
     * @param int $expectedQueries
     */
    public function __construct($expectedQueries) {
        $this->expectedQueries = $expectedQueries;
    }

    /**
     * @return int
     */
    public function getExpectedQueries() {
        return $this->expectedQueries;
    }

    /**
     * @param int $expectedQueries
     */
    public function setExpectedQueries($expectedQueries) {
        $this->expectedQueries = $expectedQueries;
    }

}