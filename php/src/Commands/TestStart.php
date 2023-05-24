<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name start
 * @description Immediately start a scheduled test
 */
class TestStart extends BaseTestCommand {

    /**
     * @param string $key @argument @required The key of the test the start
     *
     * @return void
     */
    public function handleCommand($key) {

        $this->testService->startTest($key);

    }

}