<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name start
 * @description Immediately start a scheduled test
 */
class TestStartCommand extends BaseTestCommand {

    /**
     * @param string $key @argument The key of the test to start
     * @param bool $all @option Start all pending tests
     *
     * @return void
     */
    public function handleCommand($key = null, $all = false) {

        if ($all) {
            foreach ($this->testService->listTests() as $test) {
                $this->testService->startTest($test->getKey());
            }
        } else if ($key) {
            $this->testService->startTest($key);
        }

    }

}