<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name delete-all
 * @description Delete all tests
 */
class TestDeleteAll extends BaseTestCommand {

    /**
     * @return void
     */
    public function handleCommand() {

        foreach ($this->testService->listTests() as $test) {
            $this->testService->deleteTest($test->getKey());
        }
    }

}