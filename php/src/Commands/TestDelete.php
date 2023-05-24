<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name delete
 * @description Delete a test and remove all relevant files
 */
class TestDelete extends BaseTestCommand {

    /**
     * @param string $testKey @argument @required The test to be uninstalled
     *
     * @return void
     */
    public function handleCommand($testKey) {

        // Are you sure?

        $this->testService->deleteTest($testKey);

    }

}