<?php

namespace ResolverTest\ValueObjects\TestType;

class TestTypeDNSRules {

    /**
     * @var TestTypeExpectedQuery
     */
    private $expectedQueries = 1;

    /**
     * @param TestTypeExpectedQuery $expectedQueries
     */
    public function __construct($expectedQueries) {
        $this->expectedQueries = $expectedQueries;
    }

    /**
     * @return TestTypeExpectedQuery
     */
    public function getExpectedQueries() {
        return $this->expectedQueries;
    }

    /**
     * @param TestTypeExpectedQuery $expectedQueries
     */
    public function setExpectedQueries($expectedQueries) {
        $this->expectedQueries = $expectedQueries;
    }

}