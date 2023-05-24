<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name stop
 * @description Terminate an active test
 */
class TestStop extends BaseTestCommand {

    /**
     * @param string $key @argument @required The key of the test to stop
     *
     * @return void
     */
    public function handleCommand($key) {

        $this->testService->stopTest($key);

    }

}