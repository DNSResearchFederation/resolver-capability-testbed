<?php

namespace ResolverTest\Framework;

use ResolverTest\Services\TestService;

class BaseTestCommand {

    /**
     * @var TestService
     */
    protected $testService;

    /**
     * @param TestService $testService
     */
    public function __construct($testService) {
        $this->testService = $testService;
    }

}