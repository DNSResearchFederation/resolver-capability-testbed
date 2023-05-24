<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name start-all
 * @description Start all currently pending tests
 */
class TestStartAll extends BaseTestCommand {

    /**
     * @return void
     */
    public function handleCommand() {

        foreach ($this->testService->listTests() as $test) {
            $this->testService->startTest($test->getKey());
        }

    }

}