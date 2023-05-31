<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name stop
 * @description Terminate an active test
 */
class TestStopCommand extends BaseTestCommand {

    /**
     * @param string $key @argument The key of the test to stop
     * @param bool $all @option Stop all tests
     *
     * @return void
     */
    public function handleCommand($key = null, $all = false) {

        if ($all) {
            foreach ($this->testService->listTests() as $test) {
                $this->testService->stopTest($test->getKey());
            }
        } else if ($key) {
            $this->testService->stopTest($key);
        }

    }

}