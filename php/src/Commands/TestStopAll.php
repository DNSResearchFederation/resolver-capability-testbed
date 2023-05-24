<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name stop-all
 * @description Terminate all currently active tests
 */
class TestStopAll extends BaseTestCommand {

    /**
     * @return void
     */
    public function handleCommand() {

        foreach ($this->testService->listTests() as $test) {
            $this->testService->stopTest($test->getKey());
        }
    }

}